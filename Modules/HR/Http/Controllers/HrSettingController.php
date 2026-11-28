<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\HRSetting;

class HrSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view HR Settings')->only(['index']);
        $this->middleware('can:edit HR Settings')->only(['edit', 'update']);
    }

    public function index(): View
    {
        $setting = HRSetting::getCompanyDefault();
        if (! $setting) {
            $setting = HRSetting::create([
                'company_max_leave_percentage' => 7.00,
            ]);
        }

        return view('hr::hr-management.hr-settings.index', compact('setting'));
    }

    public function edit(): View
    {
        $setting = HRSetting::getCompanyDefault();
        if (! $setting) {
            $setting = HRSetting::create([
                'company_max_leave_percentage' => 7.00,
            ]);
        }

        return view('hr::hr-management.hr-settings.create-edit', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_max_leave_percentage' => 'required|numeric|min:0|max:100',
            'company_latitude'             => 'nullable|numeric|between:-90,90',
            'company_longitude'            => 'nullable|numeric|between:-180,180',
            'company_location_radius'      => 'nullable|integer|min:50|max:5000',
        ]);

        // تحويل الحقول الفارغة إلى null
        $validated['company_latitude']        = isset($validated['company_latitude']) && $validated['company_latitude'] !== '' ? $validated['company_latitude'] : null;
        $validated['company_longitude']       = isset($validated['company_longitude']) && $validated['company_longitude'] !== '' ? $validated['company_longitude'] : null;
        $validated['company_location_radius'] = $validated['company_location_radius'] ?? 200;

        $setting = HRSetting::getCompanyDefault();
        if (! $setting) {
            return back()->withErrors(['general' => __('hr::hr.hr_setting_not_found')]);
        }

        $setting->update($validated);

        return redirect()
            ->route('hr.settings.index')
            ->with('message', __('hr::hr.hr_setting_updated_successfully'));
    }
}
