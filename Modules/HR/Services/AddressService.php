<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Models\City;
use Modules\HR\Models\Country;
use Modules\HR\Models\State;
use Modules\HR\Models\Town;

class AddressService
{
    /**
     * @return Collection<int, Country>
     */
    public function listCountries(?string $search = null): Collection
    {
        return Country::query()
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->get();
    }

    public function createCountry(array $data): Country
    {
        return Country::create($data);
    }

    public function updateCountry(Country $country, array $data): Country
    {
        $country->update($data);

        return $country->fresh();
    }

    public function deleteCountry(Country $country): void
    {
        $country->delete();
    }

    /**
     * @return Collection<int, State>
     */
    public function listStates(?string $search = null): Collection
    {
        return State::query()
            ->with('country:id,title')
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->get();
    }

    public function createState(array $data): State
    {
        return State::create($data);
    }

    public function updateState(State $state, array $data): State
    {
        $state->update($data);

        return $state->fresh(['country']);
    }

    public function deleteState(State $state): void
    {
        $state->delete();
    }

    /**
     * @return Collection<int, City>
     */
    public function listCities(?string $search = null): Collection
    {
        return City::query()
            ->with('state:id,title')
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->get();
    }

    public function createCity(array $data): City
    {
        return City::create($data);
    }

    public function updateCity(City $city, array $data): City
    {
        $city->update($data);

        return $city->fresh(['state']);
    }

    public function deleteCity(City $city): void
    {
        $city->delete();
    }

    /**
     * @return Collection<int, Town>
     */
    public function listTowns(?string $search = null): Collection
    {
        return Town::query()
            ->with('city:id,title')
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->get();
    }

    public function createTown(array $data): Town
    {
        return Town::create($data);
    }

    public function updateTown(Town $town, array $data): Town
    {
        $town->update($data);

        return $town->fresh(['city']);
    }

    public function deleteTown(Town $town): void
    {
        $town->delete();
    }
}
