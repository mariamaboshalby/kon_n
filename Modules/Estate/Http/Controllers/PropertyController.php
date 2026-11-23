<?php

declare(strict_types=1);

namespace Modules\Estate\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Estate\Http\Requests\StorePropertyRequest;
use Modules\Estate\Models\Property;
use Modules\Estate\Services\PropertyFilterService;

/**
 * PropertyController - متحكم العقارات
 * 
 * Handles CRUD operations and filtering for real estate properties
 */
class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyFilterService $filterService
    ) {
        // Authorization middleware - التحقق من الصلاحيات
        $this->middleware('can:view Properties')->only(['index', 'show']);
        $this->middleware('can:create Properties')->only(['create', 'store']);
        $this->middleware('can:edit Properties')->only(['edit', 'update']);
        $this->middleware('can:delete Properties')->only(['destroy']);
    }

    /**
     * Display a listing of properties with optional filters
     * عرض قائمة العقارات مع إمكانية التصفية
     */
    public function index(Request $request): View
    {
        // Apply filters using the service - تطبيق الفلاتر باستخدام الخدمة
        $properties = $this->filterService->filter($request->all());

        return view('estate::properties.index', compact('properties'));
    }

    /**
     * Show the form for creating a new property
     * عرض نموذج إنشاء عقار جديد
     */
    public function create(): View
    {
        return view('estate::properties.create');
    }

    public function deleteMedia(Property $property, int $media): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()->can('edit Properties'), 403);

        $mediaItem = $property->media()->findOrFail($media);
        $mediaItem->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Store a newly created property
     * حفظ عقار جديد
     */
    public function store(StorePropertyRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            
            $property = Property::create($validated);

            $this->attachStakeholders($property, $request);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $property->addMedia($image)->toMediaCollection('property_images');
                }
            }

            return redirect()
                ->route('properties.show', $property)
                ->with('message', __('estate::estate.property_created_successfully'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Property store error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());

            return redirect()
                ->back()
                ->withInput()
                ->with('store_error', $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
        }
    }

    /**
     * Display the specified property
     * عرض تفاصيل عقار محدد
     */
    public function show(Property $property): View
    {
        // Eager load relationships to prevent N+1 queries
        $property->load([
            'propertyType',
            'stakeholders.stakeholder',
            'clients',
            'employees',
            'media'
        ]);

        return view('estate::properties.show', compact('property'));
    }

    /**
     * Show the form for editing the specified property
     * عرض نموذج تعديل عقار محدد
     */
    public function edit(Property $property): View
    {
        // Eager load relationships
        $property->load([
            'stakeholders.stakeholder',
            'media'
        ]);

        return view('estate::properties.edit', compact('property'));
    }

    /**
     * Update the specified property
     * تحديث عقار محدد
     */
    public function update(StorePropertyRequest $request, Property $property): RedirectResponse
    {
        try {
            $property->update($request->validated());

            $this->attachStakeholders($property, $request, true);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $property->addMedia($image)->toMediaCollection('property_images');
                }
            }

            return redirect()
                ->route('properties.show', $property)
                ->with('message', __('estate::estate.property_updated_successfully'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Property update error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());

            return redirect()
                ->back()
                ->withInput()
                ->with('store_error', $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
        }
    }

    /**
     * Attach or sync stakeholders to property
     * ربط أو مزامنة أطراف العقار
     */
    private function attachStakeholders(Property $property, StorePropertyRequest $request, bool $sync = false): void
    {
        // Prepare stakeholders data
        $stakeholdersData = [];

        // Owners - المالكين
        if ($request->has('owners')) {
            foreach ($request->owners as $index => $ownerId) {
                $stakeholdersData[] = [
                    'stakeholder_type' => \App\Models\Client::class,
                    'stakeholder_id' => $ownerId,
                    'role' => 'owner',
                    'is_primary' => $index === 0, // First owner is primary
                ];
            }
        }

        // Tenants - المستأجرين
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

        // Buyers - المشترين
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

        // Agents - الوكلاء
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
            // Delete existing and create new
            $property->stakeholders()->delete();
        }

        // Create stakeholders
        foreach ($stakeholdersData as $data) {
            $property->stakeholders()->create($data);
        }
    }

    /**
     * Remove the specified property
     * حذف عقار محدد
     */
    public function destroy(Property $property): RedirectResponse
    {
        try {
            $property->delete();

            return redirect()
                ->route('properties.index')
                ->with('message', __('estate::estate.property_deleted_successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('estate::estate.property_deletion_failed'));
        }
    }

    /**
     * Search properties via AJAX
     * البحث عن العقارات عبر AJAX
     */
    public function search(Request $request): JsonResponse
    {
        $properties = $this->filterService->filter($request->all());

        return response()->json([
            'success' => true,
            'data' => $properties->items(),
            'pagination' => [
                'total' => $properties->total(),
                'per_page' => $properties->perPage(),
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
            ],
        ]);
    }
}
