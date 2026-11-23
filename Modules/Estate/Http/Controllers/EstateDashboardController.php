<?php

declare(strict_types=1);

namespace Modules\Estate\Http\Controllers;

use App\Models\Client;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Estate\Models\Property;
use Modules\Estate\Models\PropertyStakeholder;
use Modules\Estate\Models\PropertyType;

class EstateDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Properties');
    }

    public function index(): View
    {
        // ── Status counts ──────────────────────────────────────────────────
        $stats = [
            'total'     => Property::count(),
            'available' => Property::where('status', 'available')->count(),
            'reserved'  => Property::where('status', 'reserved')->count(),
            'sold'      => Property::where('status', 'sold')->count(),
        ];

        // ── Listing type counts ────────────────────────────────────────────
        $byListing = [
            'sale'          => Property::where('listing_type', 'sale')->count(),
            'rent'          => Property::where('listing_type', 'rent')->count(),
            'furnished_rent' => Property::where('listing_type', 'furnished_rent')->count(),
        ];

        // ── Financial ─────────────────────────────────────────────────────
        $totalValue   = (float) Property::sum('price');
        $avgPrice     = (float) Property::avg('price');
        $totalCommission = (float) Property::sum('commission');

        // ── By city (top 5) ───────────────────────────────────────────────
        $byCity = Property::selectRaw('city, count(*) as count')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'city');

        // ── By property type ──────────────────────────────────────────────
        $byType = PropertyType::withCount('properties')
            ->whereNull('parent_id')
            ->orderByDesc('properties_count')
            ->get();

        // ── Recent properties ─────────────────────────────────────────────
        $recent = Property::with(['propertyType', 'media'])
            ->latest()
            ->limit(6)
            ->get();

        // ── Clients count ─────────────────────────────────────────────────
        $clientsCount = PropertyStakeholder::where('stakeholder_type', Client::class)
            ->distinct('stakeholder_id')
            ->count();

        // ── Map markers ───────────────────────────────────────────────────
        $mapProperties = Property::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select(['id', 'title', 'city', 'district', 'price', 'status', 'listing_type', 'latitude', 'longitude'])
            ->get()
            ->map(fn ($p) => [
                'id'           => $p->id,
                'title'        => $p->title,
                'city'         => $p->city,
                'district'     => $p->district,
                'price'        => number_format((float) $p->price, 0),
                'status'       => $p->status->value,
                'status_label' => $p->status->label(),
                'listing_type' => $p->listing_type->label(),
                'lat'          => (float) $p->latitude,
                'lng'          => (float) $p->longitude,
                'url'          => route('properties.show', $p->id),
            ]);

        return view('estate::dashboard', compact(
            'stats', 'byListing', 'totalValue', 'avgPrice',
            'totalCommission', 'byCity', 'byType', 'recent', 'clientsCount',
            'mapProperties'
        ));
    }
}
