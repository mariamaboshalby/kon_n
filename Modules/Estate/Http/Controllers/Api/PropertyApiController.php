<?php

declare(strict_types=1);

namespace Modules\Estate\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Estate\Http\Requests\StorePropertyRequest;
use Modules\Estate\Models\Property;
use Modules\Estate\Services\PropertyFilterService;

/**
 * PropertyApiController - API متحكم العقارات
 * 
 * RESTful API endpoints for property management
 */
class PropertyApiController extends Controller
{
    public function __construct(
        private readonly PropertyFilterService $filterService
    ) {
        // API authentication handled by Sanctum middleware
    }

    /**
     * Get all properties with filters
     * GET /api/estate/properties
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $properties = $this->filterService->filter($request->all());

            return response()->json([
                'success' => true,
                'data' => $properties->items(),
                'pagination' => [
                    'total' => $properties->total(),
                    'per_page' => $properties->perPage(),
                    'current_page' => $properties->currentPage(),
                    'last_page' => $properties->lastPage(),
                    'from' => $properties->firstItem(),
                    'to' => $properties->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('estate::estate.error_fetching_properties'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single property
     * GET /api/estate/properties/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $property = Property::with([
                'stakeholders.stakeholder',
                'clients',
                'employees',
                'media'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $property,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('estate::estate.property_not_found'),
            ], 404);
        }
    }

    /**
     * Create new property
     * POST /api/estate/properties
     */
    public function store(StorePropertyRequest $request): JsonResponse
    {
        try {
            $property = Property::create($request->validated());

            // Attach stakeholders
            $this->attachStakeholders($property, $request);

            // Handle media uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $property->addMedia($image)->toMediaCollection('property_images');
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('estate::estate.property_created_successfully'),
                'data' => $property->load(['stakeholders.stakeholder', 'media']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('estate::estate.property_creation_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update property
     * PUT /api/estate/properties/{id}
     */
    public function update(StorePropertyRequest $request, int $id): JsonResponse
    {
        try {
            $property = Property::findOrFail($id);
            $property->update($request->validated());

            // Sync stakeholders
            $this->attachStakeholders($property, $request, true);

            // Handle media uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $property->addMedia($image)->toMediaCollection('property_images');
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('estate::estate.property_updated_successfully'),
                'data' => $property->load(['stakeholders.stakeholder', 'media']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('estate::estate.property_update_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete property
     * DELETE /api/estate/properties/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $property = Property::findOrFail($id);
            $property->delete();

            return response()->json([
                'success' => true,
                'message' => __('estate::estate.property_deleted_successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('estate::estate.property_deletion_failed'),
            ], 500);
        }
    }

    /**
     * Get property statistics
     * GET /api/estate/properties/statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total' => Property::count(),
                'available' => Property::where('status', 'available')->count(),
                'reserved' => Property::where('status', 'reserved')->count(),
                'sold' => Property::where('status', 'sold')->count(),
                'for_sale' => Property::where('listing_type', 'sale')->count(),
                'for_rent' => Property::where('listing_type', 'rent')->count(),
                'furnished_rent' => Property::where('listing_type', 'furnished_rent')->count(),
                'average_price' => Property::avg('price'),
                'total_value' => Property::sum('price'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('estate::estate.error_fetching_statistics'),
            ], 500);
        }
    }

    /**
     * Attach stakeholders helper
     */
    private function attachStakeholders(Property $property, Request $request, bool $sync = false): void
    {
        $stakeholdersData = [];

        if ($request->has('owners')) {
            foreach ($request->owners as $index => $ownerId) {
                $stakeholdersData[] = [
                    'stakeholder_type' => \App\Models\Client::class,
                    'stakeholder_id' => $ownerId,
                    'role' => 'owner',
                    'is_primary' => $index === 0,
                ];
            }
        }

        if ($request->has('tenants')) {
            foreach ($request->tenants as $tenantId) {
                $stakeholdersData[] = [
                    'stakeholder_type' => \App\Models\Client::class,
                    'stakeholder_id' => $tenantId,
                    'role' => 'tenant',
                    'is_primary' => false,
                ];
            }
        }

        if ($request->has('buyers')) {
            foreach ($request->buyers as $buyerId) {
                $stakeholdersData[] = [
                    'stakeholder_type' => \App\Models\Client::class,
                    'stakeholder_id' => $buyerId,
                    'role' => 'buyer',
                    'is_primary' => false,
                ];
            }
        }

        if ($request->has('agents')) {
            foreach ($request->agents as $agentId) {
                $stakeholdersData[] = [
                    'stakeholder_type' => \Modules\HR\Models\Employee::class,
                    'stakeholder_id' => $agentId,
                    'role' => 'agent',
                    'is_primary' => false,
                ];
            }
        }

        if ($sync) {
            $property->stakeholders()->delete();
        }

        foreach ($stakeholdersData as $data) {
            $property->stakeholders()->create($data);
        }
    }
}
