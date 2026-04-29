<?php

namespace App\Http\Controllers;

use App\Actions\Tenant\ActivateTenant;
use App\Actions\Tenant\CreateFkkgWithAdmin;
use App\Actions\Tenant\CreateSchoolWithAdmin;
use App\Actions\Tenant\ExtendTenantTrial;
use App\Actions\Tenant\ForceExpireTenant;
use App\Actions\Tenant\ImpersonateSchoolAdmin;
use App\Actions\Tenant\StopImpersonation;
use App\Http\Requests\Tenant\ExtendTrialRequest;
use App\Http\Requests\Tenant\StoreFkkgRequest;
use App\Http\Requests\Tenant\StoreSchoolRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\ActionResolver;
use App\Services\Billing\SubscriptionService;
use App\Services\MetricsService;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    public function __construct(
        protected TenantService       $tenantService,
        protected SubscriptionService $subscriptionService,
        protected MetricsService      $metricsService,
        protected ActionResolver      $actionResolver,
        protected CreateSchoolWithAdmin  $createSchoolAction,
        protected CreateFkkgWithAdmin    $createFkkgAction,
        protected ImpersonateSchoolAdmin $impersonateAction,
        protected StopImpersonation      $stopImpersonateAction,
        protected ActivateTenant         $activateAction,
        protected ExtendTenantTrial      $extendTrialAction,
        protected ForceExpireTenant      $forceExpireAction,
    ) {}

    // ─── SCHOOLS ─────────────────────────────────────────────────────────────

    public function schools()
    {
        $tenants = Tenant::schools()
            ->withCount('users')
            ->orderByDesc('created_at')
            ->get();

        return view('pages.tenant.sekolah', [
            'tenants' => $tenants,
            'metrics' => $this->metricsService->getSchoolMetrics(),
        ]);
    }

    public function storeSchool(StoreSchoolRequest $request): RedirectResponse
    {
        try {
            $result = $this->createSchoolAction->execute($request->validated());

            $tenant   = $result['tenant'];
            $admin    = $result['admin'];
            $password = $result['plain_password'];

            return back()->with('success',
                "✅ Sekolah [{$tenant->name}] berhasil ditambahkan! " .
                "Akun admin: {$admin->email} | Password sementara: <strong>{$password}</strong> " .
                "(simpan sekarang, tidak akan ditampilkan lagi)"
            );
        } catch (\Throwable $e) {
            Log::error('[storeSchool] ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Gagal menambahkan sekolah: ' . $e->getMessage())
                ->withInput();
        }
    }

    // ─── IMPERSONATE ─────────────────────────────────────────────────────────

    public function impersonate(Tenant $tenant): RedirectResponse
    {
        // Hanya Super Admin yang boleh impersonate
        if (! auth()->user()?->hasRole('Super Admin')) {
            abort(403, 'Unauthorized.');
        }

        // Block jika sudah sedang impersonating
        if (session('impersonating')) {
            return back()->with('error', 'Anda sudah dalam sesi impersonation. Hentikan dulu sebelum memulai yang baru.');
        }

        try {
            $this->impersonateAction->execute($tenant);
            return redirect()->route('admin.dashboard')
                ->with('success', "Anda kini login sebagai admin sekolah [{$tenant->name}].");
        } catch (\Throwable $e) {
            Log::error('[impersonate] ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function stopImpersonate(Request $request): RedirectResponse
    {
        try {
            $this->stopImpersonateAction->execute();
            return redirect()->route('tenants.schools')
                ->with('success', 'Sesi impersonation telah dihentikan. Anda kembali ke akun Super Admin.');
        } catch (\Throwable $e) {
            Log::error('[stopImpersonate] ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('tenants.schools')
                ->with('error', 'Terjadi kesalahan saat menghentikan sesi impersonation.');
        }
    }

    // ─── FKGG ────────────────────────────────────────────────────────────────

    public function fkkg()
    {
        $tenants = Tenant::fkgg()
            ->withCount('users')
            ->orderByDesc('created_at')
            ->get();

        return view('pages.tenant.fkgg', [
            'tenants' => $tenants,
            'metrics' => $this->metricsService->getFkkgMetrics(),
        ]);
    }

    public function storeFkkg(StoreFkkgRequest $request): RedirectResponse
    {
        try {
            $result = $this->createFkkgAction->execute($request->validated());

            $tenant   = $result['tenant'];
            $admin    = $result['admin'];
            $password = $result['plain_password'];

            return back()->with('success',
                "✅ FKGG [{$tenant->name}] berhasil ditambahkan! " .
                "Akun admin: {$admin->email} | Password sementara: <strong>{$password}</strong> " .
                "(simpan sekarang, tidak akan ditampilkan lagi)"
            );
        } catch (\Throwable $e) {
            Log::error('[storeFkkg] ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Gagal menambahkan FKGG: ' . $e->getMessage())
                ->withInput();
        }
    }

    // ─── ACTIVATION ──────────────────────────────────────────────────────────

    public function activation()
    {
        $tenants = Tenant::withCount('users')
            ->orderByDesc('created_at')
            ->get();

        $metrics = $this->metricsService->getActivationMetrics();

        $actions = $tenants->mapWithKeys(
            fn ($t) => [$t->id => $this->actionResolver->getActions($t)]
        );

        return view('pages.tenant.aktivasi', compact('tenants', 'metrics', 'actions'));
    }

    // ─── COMMON UPDATE ────────────────────────────────────────────────────────

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        try {
            $this->tenantService->updateTenant($tenant, $request->validated());
            return back()->with('success', "Data [{$tenant->name}] berhasil diperbarui.");
        } catch (\Throwable $e) {
            Log::error('[update] ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage())
                ->withInput();
        }
    }

    // ─── STATE ACTIONS ────────────────────────────────────────────────────────

    public function activate(Request $request, Tenant $tenant): RedirectResponse
    {
        try {
            $force = $request->boolean('force', false);
            $this->activateAction->execute($tenant, $force);
            return back()->with('success', "✅ Tenant [{$tenant->name}] berhasil diaktivasi.");
        } catch (\Throwable $e) {
            Log::error('[activate] ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function startTrial(Tenant $tenant): RedirectResponse
    {
        try {
            $this->subscriptionService->startTrial($tenant);
            return back()->with('success', "Masa trial [{$tenant->name}] berhasil dimulai (14 hari).");
        } catch (\Throwable $e) {
            Log::error('[startTrial] ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function extendTrial(ExtendTrialRequest $request, Tenant $tenant): RedirectResponse
    {
        try {
            $days = $request->integer('days', 7);
            $this->extendTrialAction->execute($tenant, $days);
            return back()->with('success', "✅ Trial [{$tenant->name}] diperpanjang {$days} hari.");
        } catch (\Throwable $e) {
            Log::error('[extendTrial] ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        try {
            $this->subscriptionService->suspend($tenant);
            return back()->with('success', "Tenant [{$tenant->name}] berhasil disuspend.");
        } catch (\Throwable $e) {
            Log::error('[suspend] ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function expire(Tenant $tenant): RedirectResponse
    {
        try {
            $this->forceExpireAction->execute($tenant);
            return back()->with('success', "Tenant [{$tenant->name}] telah di-expire.");
        } catch (\Throwable $e) {
            Log::error('[expire] ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function sendReminder(Tenant $tenant): RedirectResponse
    {
        // Placeholder — akan diimplementasi via Notification queue
        Log::info("[REMINDER] Kirim reminder untuk {$tenant->name}");
        return back()->with('success', "Reminder untuk [{$tenant->name}] telah dijadwalkan. (Coming soon)");
    }
}
