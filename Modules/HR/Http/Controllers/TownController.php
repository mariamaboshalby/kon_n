<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\City;
use Modules\HR\Models\Town;
use Modules\HR\Services\AddressService;

class TownController extends Controller
{
    public function __construct(
        private readonly AddressService $addressService
    ) {
        $this->middleware('can:view Towns')->only(['index', 'data']);
        $this->middleware('can:create Towns')->only(['store']);
        $this->middleware('can:edit Towns')->only(['update']);
        $this->middleware('can:delete Towns')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.addresses.manage-towns', [
            'cities' => City::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->addressService->listTowns($request->string('search')->toString() ?: null),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:200|unique:towns,title',
            'city_id' => 'required|exists:cities,id',
            'distance' => 'nullable|numeric|min:0',
        ]);

        $this->addressService->createTown($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.town_created_successfully'),
        ]);
    }

    public function update(Request $request, Town $town): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:200|unique:towns,title,'.$town->id,
            'city_id' => 'required|exists:cities,id',
            'distance' => 'nullable|numeric|min:0',
        ]);

        $this->addressService->updateTown($town, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.town_updated_successfully'),
        ]);
    }

    public function destroy(Town $town): JsonResponse
    {
        $this->addressService->deleteTown($town);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.town_deleted_successfully'),
        ]);
    }
}
