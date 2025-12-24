<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PatientController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $patients = Patient::query()->latest()->paginate(8);

        return response()->api(
            PatientResource::collection($patients),
            200,
            [
                'pagination' => [
                    'total' => $patients->total(),
                    'per_page' => $patients->perPage(),
                    'current_page' => $patients->currentPage(),
                ],
            ]
        );
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $this->authorize('create', Patient::class);

        $patient = Patient::create($request->validated());

        return (new PatientResource($patient))
            ->response()
            ->setStatusCode(201);
    }
}
