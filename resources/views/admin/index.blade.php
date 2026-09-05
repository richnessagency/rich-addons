@extends('layouts.admin')

@section('title', 'إدارة الإضافات (RichAddons)')

@section('content')
<div class="space-y-8">
    <!-- Top Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 dash-card p-6 rounded-3xl backdrop-blur-xl">
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-[var(--brand)]/15 border border-[var(--brand)]/30 flex items-center justify-center text-[var(--brand)] text-xl font-bold">
                    <i class="fa-solid fa-puzzle-piece"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black dash-title tracking-tight">إدارة الإضافات (RichAddons)</h1>
                    <p class="text-sm dash-muted">التحكم في تفعيل وإلغاء تفعيل الإضافات المستقلة بالنظام والتراخيص</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="https://github.com/richnessagency/rich-addons" target="_blank" class="dash-btn-neutral px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2">
                <i class="fa-brands fa-github"></i>
                <span>مستودع rich-addons</span>
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm font-semibold flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-sm font-semibold flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="dash-card p-4 rounded-2xl space-y-1">
            <span class="text-xs dash-muted font-medium">إجمالي الإضافات</span>
            <div class="text-2xl font-black dash-title">{{ $stats['total'] }}</div>
        </div>
        <div class="dash-card p-4 rounded-2xl space-y-1">
            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">المفعلة</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $stats['active'] }}</div>
        </div>
        <div class="dash-card p-4 rounded-2xl space-y-1">
            <span class="text-xs dash-muted font-medium">غير المفعلة</span>
            <div class="text-2xl font-black dash-muted">{{ $stats['inactive'] }}</div>
        </div>
        <div class="dash-card p-4 rounded-2xl space-y-1">
            <span class="text-xs text-sky-600 dark:text-sky-400 font-medium">إضافات مجانية</span>
            <div class="text-2xl font-black text-sky-600 dark:text-sky-400">{{ $stats['free'] }}</div>
        </div>
        <div class="dash-card p-4 rounded-2xl space-y-1">
            <span class="text-xs text-purple-600 dark:text-purple-400 font-medium">إضافات مدفوعة</span>
            <div class="text-2xl font-black text-purple-600 dark:text-purple-400">{{ $stats['paid'] }}</div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="dash-card p-4 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Status Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0">
            <a href="{{ route('admin.addons.index') }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ !request('status') && !request('tier') ? 'bg-[var(--brand)] text-white shadow-lg' : 'dash-btn-neutral' }}">
                الكل ({{ $stats['total'] }})
            </a>
            <a href="{{ route('admin.addons.index', ['status' => 'active']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request('status') === 'active' ? 'bg-emerald-600 text-white shadow-lg' : 'dash-btn-neutral' }}">
                المفعلة ({{ $stats['active'] }})
            </a>
            <a href="{{ route('admin.addons.index', ['status' => 'inactive']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request('status') === 'inactive' ? 'bg-slate-700 text-white shadow-lg' : 'dash-btn-neutral' }}">
                غير المفعلة ({{ $stats['inactive'] }})
            </a>
            <a href="{{ route('admin.addons.index', ['tier' => 'free']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request('tier') === 'free' ? 'bg-sky-600 text-white shadow-lg' : 'dash-btn-neutral' }}">
                المجانية ({{ $stats['free'] }})
            </a>
        </div>

        <!-- Search Form -->
        <form action="{{ route('admin.addons.index') }}" method="GET" class="relative min-w-[240px]">
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="بحث بالاسم أو المعرف..." 
                   class="w-full dash-input px-4 py-2 pr-9 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[var(--brand)]">
            <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-slate-400 text-xs"></i>
        </form>
    </div>

    <!-- Addons Grid -->
    @if($addons->isEmpty())
        <div class="dash-card p-12 rounded-3xl text-center space-y-4">
            <div class="w-16 h-16 rounded-2xl dash-pill flex items-center justify-center dash-muted text-2xl mx-auto">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <div class="space-y-1 max-w-md mx-auto">
                <h3 class="text-lg font-bold dash-title">لم يتم العثور على إضافات</h3>
                <p class="text-xs dash-muted">قم بإضافة مجلد الإضافة داخل مجلد <code class="dash-pill px-1.5 py-0.5 rounded text-sky-500">addons/</code> مع ملف <code class="dash-pill px-1.5 py-0.5 rounded text-sky-500">addon.json</code> ليتم اكتشافها تلقائياً.</p>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($addons as $addon)
                <div class="dash-card p-6 rounded-3xl flex flex-col justify-between space-y-6 relative overflow-hidden group">
                    <!-- Status Indicator Stripe -->
                    <div class="absolute top-0 right-0 left-0 h-1.5 {{ $addon->isActive() ? 'bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.5)]' : 'bg-slate-300 dark:bg-slate-700' }}"></div>

                    <!-- Addon Header -->
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl {{ $addon->isActive() ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400' : 'dash-pill dash-muted' }} flex items-center justify-center text-xl font-bold flex-shrink-0">
                                    <i class="fa-solid fa-plug"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-extrabold dash-title text-base truncate" title="{{ $addon->name }}">{{ $addon->name }}</h3>
                                        <span class="text-[10px] font-mono px-2 py-0.5 rounded-full dash-pill dash-muted">v{{ $addon->version }}</span>
                                    </div>
                                    <span class="text-[11px] font-mono dash-muted block truncate">{{ $addon->addon_id }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="text-xs dash-muted leading-relaxed line-clamp-3">
                            {{ $addon->description ?: 'لا يوجد وصف متاح لهذه الإضافة.' }}
                        </p>
                    </div>

                    <!-- Metadata Badges -->
                    <div class="space-y-4 pt-2 border-t border-[var(--dash-border)]">
                        <div class="flex items-center justify-between text-xs">
                            <!-- Tier Pill -->
                            @if($addon->tier->value === 'free')
                                <span class="px-2.5 py-1 rounded-lg bg-sky-500/10 border border-sky-500/30 text-sky-600 dark:text-sky-400 text-[11px] font-bold">
                                    <i class="fa-solid fa-gift ml-1"></i> {{ $addon->tier->label() }}
                                </span>
                            @elseif($addon->tier->value === 'paid')
                                <span class="px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 text-[11px] font-bold">
                                    <i class="fa-solid fa-gem ml-1"></i> {{ $addon->tier->label() }}
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg bg-purple-500/10 border border-purple-500/30 text-purple-600 dark:text-purple-400 text-[11px] font-bold">
                                    <i class="fa-solid fa-arrows-rotate ml-1"></i> {{ $addon->tier->label() }}
                                </span>
                            @endif

                            <!-- Author & Repo link -->
                            <div class="flex items-center gap-2 dash-muted text-[11px]">
                                @if($addon->author)
                                    <span>بواسطة {{ $addon->author }}</span>
                                @endif
                                @if($addon->repository)
                                    <a href="{{ $addon->repository }}" target="_blank" class="hover:text-[var(--brand)] transition-colors" title="مستودع GitHub">
                                        <i class="fa-brands fa-github text-sm"></i>
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- License Key Form for Premium Addons -->
                        @if($addon->tier->requiresLicense())
                            <form action="{{ route('admin.addons.license', $addon->addon_id) }}" method="POST" class="space-y-2 pt-2 border-t border-[var(--dash-border)]">
                                @csrf
                                <label class="text-[11px] font-bold dash-muted block">مفتاح الترخيص (License Key):</label>
                                <div class="flex gap-2">
                                    <input type="text" name="license_key" value="{{ $addon->license_key }}" placeholder="XXXX-XXXX-XXXX" class="dash-input px-3 py-1.5 rounded-lg text-xs font-mono flex-1">
                                    <button type="submit" class="dash-btn-neutral px-3 py-1.5 rounded-lg text-xs font-bold">حفظ</button>
                                </div>
                            </form>
                        @endif

                        <!-- Action Controls Footer -->
                        <div class="flex items-center justify-between pt-2">
                            <span class="text-xs font-bold flex items-center gap-1.5 {{ $addon->isActive() ? 'text-emerald-600 dark:text-emerald-400' : 'dash-muted' }}">
                                <span class="w-2 h-2 rounded-full {{ $addon->isActive() ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                                {{ $addon->status->label() }}
                            </span>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2">
                                @if($addon->isActive())
                                    @if($addon->addon_id === 'richness/announcement-bar' || \Illuminate\Support\Facades\Route::has('admin.addons.' . str_replace(['/', '-'], ['.', '_'], $addon->addon_id) . '.settings'))
                                        <a href="{{ route('admin.addons.announcement-bar.settings') }}" class="dash-btn-neutral px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5" title="إعدادات الإضافة">
                                            <i class="fa-solid fa-gear"></i>
                                            <span>الإعدادات</span>
                                        </a>
                                    @endif
                                @endif

                                <!-- Toggle Action Button -->
                                <form action="{{ route('admin.addons.toggle', $addon->addon_id) }}" method="POST">
                                    @csrf
                                    @if($addon->isActive())
                                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 transition-all flex items-center gap-2">
                                            <i class="fa-solid fa-power-off"></i>
                                            <span>إلغاء التفعيل</span>
                                        </button>
                                    @else
                                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2">
                                            <i class="fa-solid fa-bolt"></i>
                                            <span>تفعيل الإضافة</span>
                                        </button>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
