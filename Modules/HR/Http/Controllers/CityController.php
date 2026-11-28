<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\City;
use Modules\HR\Models\State;
use Modules\HR\Services\AddressService;

class CityController extends Controller
{
    public function __construct(
        private readonly AddressService $addressService
    ) {
        $this->middleware('can:view Cities')->only(['index', 'data']);
        $this->middleware('can:create Cities')->only(['store']);
        $this->middleware('can:edit Cities')->only(['update']);
        $this->middleware('can:delete Cities')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.addresses.manage-cities', [
            'states' => State::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->addressService->listCities($request->string('search')->toString() ?: null),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255|unique:cities,title',
            'state_id' => 'required|exists:states,id',
        ]);

        $this->addressService->createCity($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.city_created_successfully'),
        ]);
    }

    public function update(Request $request, City $city): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255|unique:cities,title,'.$city->id,
            'state_id' => 'required|exists:states,id',
        ]);

        $this->addressService->updateCity($city, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.city_updated_successfully'),
        ]);
    }

    public function destroy(City $city): JsonResponse
    {
        $this->addressService->deleteCity($city);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.city_deleted_successfully'),
        ]);
    }
}
