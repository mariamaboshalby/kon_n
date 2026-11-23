<?php

declare(strict_types=1);

namespace Modules\Estate\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Estate\Models\PropertyType;

class PropertyTypeController extends Controller
{
    // All available attribute keys with their labels
    public const AVAILABLE_ATTRIBUTES = [
        'rooms_count' => ['ar' => 'عدد الغرف',              'en' => 'Rooms Count'],
        'bathrooms_count' => ['ar' => 'عدد دورات المياه',       'en' => 'Bathrooms Count'],
        'floor_number' => ['ar' => 'رقم الطابق',             'en' => 'Floor Number'],
        'finishing_level' => ['ar' => 'مستوى التشطيب',          'en' => 'Finishing Level'],
        'build_year' => ['ar' => 'سنة البناء',             'en' => 'Build Year'],
        'is_furnished' => ['ar' => 'مفروش',                  'en' => 'Furnished'],
        'furnishing_percentage' => ['ar' => 'نسبة الأثاث',            'en' => 'Furnishing %'],
        'neighbor_notes' => ['ar' => 'ملاحظات الجيران',        'en' => 'Neighbor Notes'],
        'reputation_notes' => ['ar' => 'ملاحظات السمعة',         'en' => 'Reputation Notes'],
        'roof_area' => ['ar' => 'مساحة الروف',            'en' => 'Roof Area'],
        'garden_area' => ['ar' => 'مساحة الحديقة',          'en' => 'Garden Area'],
        'facade_width' => ['ar' => 'عرض الواجهة',            'en' => 'Facade Width'],
        'is_corner' => ['ar' => 'ركنية',                  'en' => 'Corner Unit'],
        'land_license_type' => ['ar' => 'نوع الترخيص',            'en' => 'License Type'],
        'street_width' => ['ar' => 'عرض الشارع',             'en' => 'Street Width'],
        'land_shape' => ['ar' => 'شكل الأرض',              'en' => 'Land Shape'],
    ];

    public function __construct()
    {
        $this->middleware('can:view PropertyTypes')->only(['index']);
        $this->middleware('can:create PropertyTypes')->only(['create', 'store']);
        $this->middleware('can:edit PropertyTypes')->only(['edit', 'update']);
        $this->middleware('can:delete PropertyTypes')->only(['destroy']);
    }

    public function index(): View
    {
        $roots = PropertyType::with('activeChildren')
            ->roots()
            ->orderBy('sort_order')
            ->get();

        return view('estate::property-types.index', compact('roots'));
    }

    public function create(Request $request): View
    {
        $parents = PropertyType::roots()->active()->orderBy('sort_order')->get();
        $selectedParent = $request->query('parent_id');
        $attributes = self::AVAILABLE_ATTRIBUTES;

        return view('estate::property-types.create', compact('parents', 'selectedParent', 'attributes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:property_types,id'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:property_types,slug', 'regex:/^[a-z0-9_]+$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        PropertyType::create($validated);

        return redirect()->route('property-types.index')
            ->with('message', __('estate::estate.property_type_created'));
    }

    public function edit(PropertyType $propertyType): View
    {
        $parents = PropertyType::roots()->active()
            ->where('id', '!=', $propertyType->id)
            ->orderBy('sort_order')
            ->get();

        $attributes = self::AVAILABLE_ATTRIBUTES;

        return view('estate::property-types.edit', compact('propertyType', 'parents', 'attributes'));
    }

    public function update(Request $request, PropertyType $propertyType): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:property_types,id'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:property_types,slug,'.$propertyType->id, 'regex:/^[a-z0-9_]+$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $propertyType->update($validated);

        return redirect()->route('property-types.index')
            ->with('message', __('estate::estate.property_type_updated'));
    }

    public function destroy(PropertyType $propertyType): RedirectResponse
    {
        if ($propertyType->children()->exists()) {
            return redirect()->back()
                ->with('error', __('estate::estate.property_type_has_children'));
        }

        if ($propertyType->properties()->exists()) {
            return redirect()->back()
                ->with('error', __('estate::estate.property_type_has_properties'));
        }

        $propertyType->delete();

        return redirect()->route('property-types.index')
            ->with('message', __('estate::estate.property_type_deleted'));
    }
}
