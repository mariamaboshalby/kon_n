<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\Country;
use Modules\HR\Services\AddressService;

class CountryController extends Controller
{
    public function __construct(
        private readonly AddressService $addressService
    ) {
        $this->middleware('can:view Countries')->only(['index', 'data']);
        $this->middleware('can:create Countries')->only(['store']);
        $this->middleware('can:edit Countries')->only(['update']);
        $this->middleware('can:delete Countries')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.addresses.manage-countries');
    }

    public function data(Request $request): JsonResponse
    {
        $items = $this->addressService->listCountries($request->string('search')->toString() ?: null);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255|unique:countries,title',
        ]);

        $this->addressService->createCountry($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.country_created_successfully'),
        ]);
    }

    public function update(Request $request, Country $country): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255|unique:countries,title,'.$country->id,
        ]);

        $this->addressService->updateCountry($country, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.country_updated_successfully'),
        ]);
    }

    public function destroy(Country $country): JsonResponse
    {
        $this->addressService->deleteCountry($country);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.country_deleted_successfully'),
        ]);
    }
}
