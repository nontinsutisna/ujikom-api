<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

// Import Model
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Alat;

// Import Observer
use App\Observers\PeminjamanObserver;
use App\Observers\PengembalianObserver;
use App\Observers\AlatObserver;

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
        Paginator::useTailwind();

        Peminjaman::observe(PeminjamanObserver::class);
        Pengembalian::observe(PengembalianObserver::class);
        Alat::observe(AlatObserver::class);
    }
}
