<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $payments = Payment::query()
            ->with(['patient', 'invoice'])
            ->latest()
            ->paginate(8);

        return response()->api(
            PaymentResource::collection($payments),
            200,
            [
                'pagination' => [
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                ],
            ]
        );
    }

    public function store(StorePaymentRequest $request, PaymentService $service): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $payment = $service->create($request->validated());

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }
}
