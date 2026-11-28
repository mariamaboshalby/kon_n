<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\ContractType;

class ContractTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Contract Types')->only(['index', 'data']);
        $this->middleware('can:create Contract Types')->only(['store']);
        $this->middleware('can:edit Contract Types')->only(['update']);
        $this->middleware('can:delete Contract Types')->only(['destroy']);
    }

    public function index(): View
    {
        return view('recruitment::contract-types.manage');
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();

        $rows = ContractType::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id')
            ->get(['id', 'name', 'description']);

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        ContractType::create($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.contract_type_created_successfully'),
        ]);
    }

    public function update(Request $request, ContractType $contract_type): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $contract_type->update($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.contract_type_updated_successfully'),
        ]);
    }

    public function destroy(ContractType $contract_type): JsonResponse
    {
        $contract_type->delete();

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.contract_type_deleted_successfully'),
        ]);
    }
}
