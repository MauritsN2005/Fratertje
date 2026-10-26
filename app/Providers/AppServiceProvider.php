<?php

namespace App\Providers;

use App\Events\InvoiceRequested;
use App\Listeners\CalculateInvoice;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(InvoiceRequested::class, CalculateInvoice::class);
        Paginator::useBootstrapFive();
    }
}
