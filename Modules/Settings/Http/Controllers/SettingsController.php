<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Controllers;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\Settings\Models\Category;
use Modules\Settings\Models\PublicSetting;
use Nwidart\Modules\Facades\Module;
use RealRashid\SweetAlert\Facades\Alert;

class SettingsController extends Controller
{
    private const MODULE_SETTINGS = [
        'Invoices' => [
            'keys' => ['multi_currency_enabled', 'show_print_mode_switch'],
            'category_anchors' => ['prevent_negative_invoice', 'expiry_mode_disabled'],
        ],
        'Accounts' => [
            'keys' => ['allow_sub_accounts'],
            'category_anchors' => ['allowed_discount_account', 'show_inventory_difference_account'],
        ],
    ];

    /**
     * Apply permissions middleware
     */
    // public function __construct()
    // {
    //     $this->middleware('permission:view General Settings')->only(['index']);
    //     $this->middleware('permission:edit General Settings')->only(['update']);
    // }

    /**
     * Display general settings page
     */
    public function index()
    {
        $visibility = $this->settingsVisibility();
        $categoriesQuery = Category::query()
            ->with(['publicSettings' => function ($query) use ($visibility) {
                $this->applySettingsVisibility($query, $visibility);
            }]);

        if ($visibility['category_ids'] !== []) {
            $categoriesQuery->whereNotIn('id', $visibility['category_ids']);
        }

        $categories = $categoriesQuery->get()
            ->filter(fn (Category $category) => $category->publicSettings->isNotEmpty())
            ->values();
        $enabledModules = $visibility['enabled_modules'];

        return view('settings::settings.index', compact('categories', 'enabledModules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('settings::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('settings::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('settings::edit');
    }


    public function update(Request $request)
    {
        try {
            $visibility = $this->settingsVisibility();
            $allowedSettingKeys = $this->visiblePublicSettingsQuery($visibility)->pluck('key')->all();

            if (in_array('Notifications', $visibility['enabled_modules'], true)) {
                $allowedSettingKeys = array_merge($allowedSettingKeys, ['telegram_bot_token', 'telegram_chat_id']);
            }

            $allowedSettingKeys = array_fill_keys($allowedSettingKeys, true);
            $settings = $request->input('settings', []);
            $removeFiles = $request->input('remove_files', []);
            $uploadedFiles = $request->file('files', []);
            $submittedKeys = array_merge(
                array_keys($settings),
                array_keys($removeFiles),
                array_keys($uploadedFiles)
            );

            if (array_diff($submittedKeys, array_keys($allowedSettingKeys)) !== []) {
                Alert::toast(__('settings::settings.error_updating_settings'), 'error');
                return redirect()->back();
            }

            // Handle file removals
            foreach ($removeFiles as $key => $shouldRemove) {
                if ($shouldRemove) {
                    $setting = PublicSetting::where('key', $key)->first();
                    if ($setting && $setting->value) {
                        Storage::disk('public')->delete($setting->value);
                        $setting->update(['value' => '']);
                    }
                }
            }

            // Handle file uploads
            foreach ($uploadedFiles as $key => $file) {
                $request->validate([
                    "files.{$key}" => 'image|mimes:jpg,jpeg,png,gif,svg,webp|max:2048',
                ]);

                $setting = PublicSetting::where('key', $key)->first();
                if (!$setting) {
                    continue;
                }

                // Delete old file if exists
                if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                    Storage::disk('public')->delete($setting->value);
                }

                // Store new file
                $path = $file->store('company', 'public');
                $setting->update(['value' => $path]);

                // Remove from text settings so it doesn't get overwritten below
                unset($settings[$key]);
            }

            // Handle text/boolean settings
            if (!empty($settings)) {
                // Keys stored in the main `settings` table (not public_settings)
                $mainTableKeys = ['telegram_bot_token', 'telegram_chat_id'];
                $mainTableData = array_intersect_key($settings, array_flip($mainTableKeys));
                $publicData    = array_diff_key($settings, array_flip($mainTableKeys));

                if (!empty($mainTableData)) {
                    $setting = Setting::first();
                    if ($setting) {
                        $setting->update(array_map(fn ($v) => trim((string) ($v ?? '')), $mainTableData));
                    }
                }

                foreach ($publicData as $key => $value) {
                    PublicSetting::where('key', $key)->update([
                        'value' => is_array($value) ? json_encode($value) : trim((string) ($value ?? ''))
                    ]);
                }
            }

            // Clear settings cache
            Cache::forget('public_settings');

            Alert::toast(__('settings::settings.settings_updated_successfully'), 'success');
            return redirect()->back();
        } catch (\Exception) {
            Alert::toast(__('settings::settings.error_updating_settings'), 'error');
            return redirect()->back();
        }
    }

    private function settingsVisibility(): array
    {
        $enabledModules = collect(Module::allEnabled())
            ->map(fn ($module) => $module->getName())
            ->all();
        $hiddenKeys = [];
        $categoryAnchorKeys = [];

        foreach (self::MODULE_SETTINGS as $module => $settings) {
            if (in_array($module, $enabledModules, true)) {
                continue;
            }

            $hiddenKeys = array_merge($hiddenKeys, $settings['keys']);
            $categoryAnchorKeys = array_merge($categoryAnchorKeys, $settings['category_anchors']);
        }

        $categoryIds = $categoryAnchorKeys === []
            ? []
            : PublicSetting::whereIn('key', $categoryAnchorKeys)->pluck('category_id')->unique()->all();

        return [
            'enabled_modules' => $enabledModules,
            'hidden_keys' => $hiddenKeys,
            'category_ids' => $categoryIds,
        ];
    }

    private function visiblePublicSettingsQuery(array $visibility): Builder
    {
        $query = PublicSetting::query();
        $this->applySettingsVisibility($query, $visibility);

        return $query;
    }

    private function applySettingsVisibility(Builder|HasMany $query, array $visibility): void
    {
        if ($visibility['hidden_keys'] !== []) {
            $query->whereNotIn('key', $visibility['hidden_keys']);
        }

        if ($visibility['category_ids'] !== []) {
            $query->whereNotIn('category_id', $visibility['category_ids']);
        }
    }
}
