<?php

namespace App\Http\Controllers;

use App\Services\CoreDatabaseService;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    protected CoreDatabaseService $coreDb;

    public function __construct(CoreDatabaseService $coreDb)
    {
        $this->coreDb = $coreDb;
    }

    public function modules(Request $request)
    {
        $payload = auth('api')->payload();
        $tenantId = $payload->get('tenant')['id'] ?? null;

        if (!$tenantId) {
            return response()->json(['error' => 'Tenant não encontrado no token'], 400);
        }

        $modules = $this->coreDb->getTenantModules($tenantId);
        return response()->json($modules);
    }
}