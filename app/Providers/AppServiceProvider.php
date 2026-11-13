<?php

namespace App\Providers;

use App\Observers\UlasanObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\PermintaanService;
use App\Models\PenawaranMitra;
use App\Models\PesananAktif;
use App\Models\Ulasan;
use App\Models\Notifikasi;
use App\Policies\PermintaanPolicy;
use App\Policies\PenawaranPolicy;
use App\Policies\PesananPolicy;
use App\Policies\UlasanPolicy;
use App\Policies\NotifikasiPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Policies
        Gate::policy(PermintaanService::class, PermintaanPolicy::class);
        Gate::policy(PenawaranMitra::class, PenawaranPolicy::class);
        Gate::policy(PesananAktif::class, PesananPolicy::class);
        Gate::policy(Ulasan::class, UlasanPolicy::class);
        Gate::policy(Notifikasi::class, NotifikasiPolicy::class);

        // Observers
        Ulasan::observe(UlasanObserver::class);
    }
}
