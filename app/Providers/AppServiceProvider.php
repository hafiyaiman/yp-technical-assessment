<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use TallStackUi\Facades\TallStackUi;

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
        TallStackUi::customize()
            ->sideBar()
            ->block('desktop.footer', 'shrink-0 overflow-hidden border-t border-gray-200 dark:border-dark-600 px-2 pt-4');

        TallStackUi::customize()
            ->sideBar('item')
            ->block('item.state.normal', 'text-foreground hover:bg-primary-50 dark:hover:bg-dark-600 dark:text-white')
            ->block('item.icon', 'text-foreground h-5 w-5 shrink-0 transition-all dark:text-white');

        TallStackUi::customize()
            ->sideBar('separator')
            ->block('simple.wrapper', 'flex pt-2 pb-1 pl-2')
            ->block('simple.base', 'text-foreground dark:text-dark-100 text-xs font-semibold leading-6 whitespace-nowrap overflow-hidden transition-all duration-150');
    }
}
