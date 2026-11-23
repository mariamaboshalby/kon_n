<?php

declare(strict_types=1);

namespace Modules\Estate\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Modules\Estate\Models\Property;
use Modules\Estate\Enums\ListingType;
use Modules\Estate\Enums\PropertyStatus;

class PropertyList extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $city        = '';
    public string $listingType = '';
    public string $status      = '';
    public string $minPrice    = '';
    public string $maxPrice    = '';
    public string $rooms       = '';
    public string $apiUrl      = '';

    protected $queryString = [
        'search'      => ['except' => ''],
        'city'        => ['except' => ''],
        'listingType' => ['except' => ''],
        'status'      => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->apiUrl = url('/api/estate/properties');
    }

    public function updatedSearch(): void      { $this->resetPage(); }
    public function updatedCity(): void        { $this->resetPage(); }
    public function updatedListingType(): void { $this->resetPage(); }
    public function updatedStatus(): void      { $this->resetPage(); }
    public function updatedMinPrice(): void    { $this->resetPage(); }
    public function updatedMaxPrice(): void    { $this->resetPage(); }
    public function updatedRooms(): void       { $this->resetPage(); }

    #[Computed]
    public function properties()
    {
        $query = Property::query()
            ->with(['propertyType', 'media'])
            ->withCount('stakeholders');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%");
            });
        }

        if ($this->city)        { $query->where('city', 'like', "%{$this->city}%"); }
        if ($this->listingType) { $query->where('listing_type', $this->listingType); }
        if ($this->status)      { $query->where('status', $this->status); }
        if ($this->minPrice)    { $query->where('price', '>=', (float) $this->minPrice); }
        if ($this->maxPrice)    { $query->where('price', '<=', (float) $this->maxPrice); }
        if ($this->rooms)       { $query->where('rooms_count', (int) $this->rooms); }

        return $query->latest()->paginate(12);
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'city', 'listingType', 'status', 'minPrice', 'maxPrice', 'rooms']);
        $this->resetPage();
    }

    public function delete(int $propertyId): void
    {
        abort_unless(auth()->user()->can('delete Properties'), 403);
        Property::findOrFail($propertyId)->delete();
        session()->flash('message', __('estate::estate.property_deleted_successfully'));
        unset($this->properties);
    }

    public function render()
    {
        return view('estate::livewire.property-list');
    }
}
