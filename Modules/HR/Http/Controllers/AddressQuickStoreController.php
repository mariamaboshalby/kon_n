<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\HR\Models\City;
use Modules\HR\Models\Country;
use Modules\HR\Models\State;
use Modules\HR\Models\Town;

/**
 * Quick AJAX create for address lookups (used from Accounts create/edit).
 * Outside module.access:hr so Accounts users can call these endpoints.
 */
class AddressQuickStoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:create Countries')->only(['storeCountry']);
        $this->middleware('can:create States')->only(['storeState']);
        $this->middleware('can:create Cities')->only(['storeCity']);
        $this->middleware('can:create Towns')->only(['storeTown']);
    }

    public function storeCountry(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $country = Country::create([
            'title' => trim((string) $request->input('title')),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.country_created_successfully'),
            'data' => [
                'id' => $country->id,
                'title' => $country->title,
            ],
        ], 201);
    }

    public function storeState(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $state = State::create([
            'title' => trim((string) $request->input('title')),
            'country_id' => (int) $request->input('country_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.state_created_successfully'),
            'data' => [
                'id' => $state->id,
                'title' => $state->title,
                'country_id' => $state->country_id,
            ],
        ], 201);
    }

    public function storeCity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $city = City::create([
            'title' => trim((string) $request->input('title')),
            'state_id' => (int) $request->input('state_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.city_created_successfully'),
            'data' => [
                'id' => $city->id,
                'title' => $city->title,
                'state_id' => $city->state_id,
            ],
        ], 201);
    }

    public function storeTown(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:200'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $town = Town::create([
            'title' => trim((string) $request->input('title')),
            'city_id' => (int) $request->input('city_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.town_created_successfully'),
            'data' => [
                'id' => $town->id,
                'title' => $town->title,
                'city_id' => $town->city_id,
            ],
        ], 201);
    }
}
