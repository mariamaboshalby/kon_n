<?php

declare(strict_types=1);

namespace Modules\Invoices\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BarcodeLabelController extends Controller
{
    private string $configPath;

    public function __construct()
    {
        $this->configPath = storage_path('app/barcode-label-config.json');
    }

    /**
     * عرض صفحة مصمم نموذج الباركود
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $section = $request->query('section', 'sales');

        return view('invoices::barcode-label.index', compact('section'));
    }

    /**
     * جلب الإعدادات المحفوظة
     */
    public function getConfig(): JsonResponse
    {
        if (! file_exists($this->configPath)) {
            return response()->json($this->defaultConfig());
        }

        $config = json_decode(file_get_contents($this->configPath), true);

        return response()->json($config ?? $this->defaultConfig());
    }

    /**
     * حفظ الإعدادات في ملف JSON
     */
    public function saveConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'paper.width'  => ['required', 'numeric', 'min:10', 'max:500'],
            'paper.height' => ['required', 'numeric', 'min:10', 'max:500'],
            'elements'     => ['required', 'array'],
        ]);

        $config = [
            'paper'    => $validated['paper'],
            'elements' => $validated['elements'],
        ];

        file_put_contents($this->configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['success' => true, 'message' => __('invoices::barcode_label.saved_successfully')]);
    }

    /**
     * رفع صورة PNG
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:png', 'max:2048'],
        ]);

        $path = $request->file('image')->store('barcode-label-images', 'public');

        return response()->json([
            'success' => true,
            'url'     => Storage::url($path),
            'path'    => $path,
        ]);
    }

    /**
     * الإعدادات الافتراضية
     *
     * @return array<string, mixed>
     */
    private function defaultConfig(): array
    {
        return [
            'paper'    => ['width' => 60, 'height' => 40],
            'elements' => [],
        ];
    }
}
