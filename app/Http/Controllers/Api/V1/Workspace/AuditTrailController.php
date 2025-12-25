<?php

namespace App\Http\Controllers\Api\V1\Workspace;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clinicId = app('currentClinicId');

        $query = AuditTrail::where('clinic_id', $clinicId);

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $audit = $query->orderBy('occurred_at', 'desc')
            ->paginate(25);

        return response()->api(
            $audit->items(),
            200,
            [
                'pagination' => [
                    'total' => $audit->total(),
                    'per_page' => $audit->perPage(),
                    'current_page' => $audit->currentPage(),
                    'last_page' => $audit->lastPage(),
                ],
            ]
        );
    }
}
