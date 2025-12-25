<?php

namespace App\Http\Middleware;

use App\Events\Platform\IdempotencyConflict;
use App\Events\Platform\IdempotencyReplayed;
use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotency
{
    private const HEADER_NAME = 'Idempotency-Key';
    private const TTL_HOURS = 24;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header(self::HEADER_NAME);

        if (!$key) {
            return response()->apiError(
                'IDEMPOTENCY_KEY_REQUIRED',
                'The Idempotency-Key header is required for this endpoint',
                400
            );
        }

        $requestHash = $this->calculateRequestHash($request);
        $endpoint = $request->path();
        $method = $request->method();
        $userId = $request->user()?->id;
        $clinicId = $request->user()?->clinic_id;

        $existing = IdempotencyKey::query()
            ->where('key', $key)
            ->forContext($userId, $clinicId, $endpoint, $method)
            ->notExpired()
            ->first();

        if ($existing) {
            if ($existing->request_hash !== $requestHash) {
                // Emit platform.idempotency.conflict event
                event(new IdempotencyConflict(
                    endpoint: $endpoint,
                    method: $method
                ));

                return response()->apiError(
                    'IDEMPOTENCY_KEY_MISMATCH',
                    'This Idempotency-Key was used with a different request body',
                    409,
                    [
                        'key' => $key,
                        'endpoint' => $endpoint,
                    ]
                );
            }

            // Emit platform.idempotency.replayed event
            event(new IdempotencyReplayed(
                endpoint: $endpoint,
                method: $method
            ));

            return $this->restoreResponse($existing);
        }

        $response = $next($request);

        if ($this->shouldStoreResponse($response)) {
            // Remove any expired records with the same key before creating a new one
            IdempotencyKey::where('key', $key)
                ->where('expires_at', '<=', now())
                ->delete();

            $this->storeResponse($key, $userId, $clinicId, $endpoint, $method, $requestHash, $response);
        }

        return $response;
    }

    private function calculateRequestHash(Request $request): string
    {
        $content = $request->getContent();
        $normalized = json_encode(json_decode($content, true));
        return hash('sha256', $normalized ?: $content);
    }

    private function shouldStoreResponse(Response $response): bool
    {
        $status = $response->getStatusCode();
        return $status >= 200 && $status < 300 || $status === 422;
    }

    private function storeResponse(
        string $key,
        ?int $userId,
        ?int $clinicId,
        string $endpoint,
        string $method,
        string $requestHash,
        Response $response
    ): void {
        IdempotencyKey::create([
            'key' => $key,
            'user_id' => $userId,
            'clinic_id' => $clinicId,
            'endpoint' => $endpoint,
            'method' => $method,
            'request_hash' => $requestHash,
            'response_status' => $response->getStatusCode(),
            'response_body' => json_decode($response->getContent(), true),
            'expires_at' => now()->addHours(self::TTL_HOURS),
        ]);
    }

    private function restoreResponse(IdempotencyKey $record): Response
    {
        return response()->json(
            $record->response_body,
            $record->response_status
        );
    }
}
