<?php

declare(strict_types=1);

namespace Richness\RichAddons\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Richness\RichAddons\Kernel\AddonKernel;
use Richness\RichAddons\Marketplace\InstallMarketplaceAddon;
use Richness\RichAddons\Marketplace\RefreshMarketplaceCatalog;
use Richness\RichAddons\Models\AddonModel;

class AddonController extends Controller
{
    public function __construct(protected AddonKernel $kernel) {}

    public function index(Request $request): View
    {
        // Automatically sync & discover add-ons from local directory / packages
        $this->kernel->discover();

        $query = AddonModel::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('tier')) {
            $query->where('tier', $request->input('tier'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('addon_id', 'like', $search);
            });
        }

        $addons = $query->orderBy('name')->get();

        $stats = [
            'total' => AddonModel::count(),
            'active' => AddonModel::where('status', 'active')->count(),
            'inactive' => AddonModel::where('status', 'inactive')->count(),
            'free' => AddonModel::where('tier', 'free')->count(),
            'paid' => AddonModel::whereIn('tier', ['paid', 'subscription'])->count(),
        ];

        return view('rich-addons::admin.index', compact('addons', 'stats'));
    }

    public function toggle(string $addonId): RedirectResponse
    {
        try {
            $addonId = rawurldecode($addonId);
            $record = AddonModel::where('addon_id', $addonId)
                ->orWhere('id', $addonId)
                ->first();

            if ($record) {
                $addonId = $record->addon_id;
            }

            $isNowActive = $this->kernel->toggle($addonId);
            $message = $isNowActive
                ? 'تم تفعيل الإضافة بنجاح.'
                : 'تم إلغاء تفعيل الإضافة بنجاح.';

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', 'خطأ أثناء تغيير حالة الإضافة: ' . $e->getMessage());
        }
    }

    public function refreshMarketplace(RefreshMarketplaceCatalog $refreshMarketplace): RedirectResponse
    {
        try {
            $count = $refreshMarketplace->handle()->count();

            return back()->with('success', "تم تحديث سوق الإضافات بنجاح. تم مزامنة {$count} إضافة.");
        } catch (\Throwable $e) {
            return back()->with('error', 'تعذر تحديث سوق الإضافات: ' . $e->getMessage());
        }
    }

    public function install(string $addonId, InstallMarketplaceAddon $installMarketplaceAddon): RedirectResponse
    {
        try {
            $addon = AddonModel::query()
                ->where('addon_id', rawurldecode($addonId))
                ->orWhere('id', $addonId)
                ->firstOrFail();

            $installMarketplaceAddon->handle($addon);

            return back()->with('success', 'تم تثبيت الإضافة بنجاح. يمكنك تفعيلها الآن.');
        } catch (\Throwable $e) {
            return back()->with('error', 'تعذر تثبيت الإضافة: ' . $e->getMessage());
        }
    }

    public function updateLicense(Request $request, string $addonId): RedirectResponse
    {
        $request->validate([
            'license_key' => 'required|string|max:255',
        ]);

        $addonId = rawurldecode($addonId);
        $addon = AddonModel::where('addon_id', $addonId)
            ->orWhere('id', $addonId)
            ->firstOrFail();

        $addon->license_key = $request->input('license_key');
        $addon->save();

        return back()->with('success', 'تم تحديث ترخيص الإضافة بنجاح.');
    }
}
