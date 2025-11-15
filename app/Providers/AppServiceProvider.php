<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\ShopifyProductInterface;
use App\Repositories\ShopifyProductRepository;

class AppServiceProvider extends ServiceProvider
{


    public function register(): void
    {
        $this->app->bind(ShopifyProductInterface::class, ShopifyProductRepository::class);
    }


    public function boot(): void
    {
        //
    }
}
