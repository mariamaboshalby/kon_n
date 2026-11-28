<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\Country;
use Modules\HR\Models\State;
use Modules\HR\Services\AddressService;

class StateController extends Controller
{
    public function __construct(
        private readonly AddressService $addressService
    ) {
        $this->middleware('can:view States')->only(['index', 'data']);
        $this->middleware('can:create States')->only(['store']);
        $this->middleware('can:edit States')->only(['update']);
        $this->middleware('can:delete States')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.addresses.manage-states', [
            'countries' => Country::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->addressService->listStates($request->string('search')->toString() ?: null),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255|unique:states,title',
            'country_id' => 'required|exists:countries,id',
        ]);

        $this->addressService->createState($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.state_created_successfully'),
        ]);
    }

    public function update(Request $request, State $state): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255|unique:states,title,'.$state->id,
            'country_id' => 'required|exists:countries,id',
        ]);

        $this->addressService->updateState($state, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.state_updated_successfully'),
        ]);
    }

    public function destroy(State $state): JsonResponse
    {
        $this->addressService->deleteState($state);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.state_deleted_successfully'),
        ]);
    }
}
