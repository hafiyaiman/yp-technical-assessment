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
            ->block('item.state.normal', 'text-zinc-700 hover:bg-primary-50 dark:text-dark-100 dark:hover:bg-dark-600')
            ->block('item.icon', 'h-5 w-5 shrink-0 text-zinc-500 transition-all dark:text-dark-300');

        TallStackUi::customize()
            ->sideBar('separator')
            ->block('simple.wrapper', 'flex pt-2 pb-1 pl-2')
            ->block('simple.base', 'text-xs font-semibold leading-6 text-zinc-500 whitespace-nowrap overflow-hidden transition-all duration-150 dark:text-dark-300');

        TallStackUi::customize()
            ->floating()
            ->block('wrapper', 'dark:bg-dark-700 border-dark-200 dark:border-dark-600 absolute z-[80] rounded-lg border bg-white');

        TallStackUi::customize()
            ->select('styled')
            ->block('floating.class', 'z-[80] w-96 max-w-[calc(100vw-1rem)] overflow-auto')
            ->block('input.content.wrapper.first', 'relative inset-y-0 left-0 flex min-w-0 w-full items-center space-x-2 overflow-hidden rounded-lg pl-2')
            ->block('input.content.wrapper.second', 'flex min-w-0 flex-1 items-center gap-2 overflow-hidden')
            ->block('items.wrapper', 'flex min-w-0 flex-1 gap-1 overflow-hidden')
            ->block('items.multiple.item', 'dark:text-dark-100 dark:bg-dark-700 dark:ring-dark-600 inline-flex h-6 max-w-44 items-center space-x-1 rounded-lg bg-gray-100 px-2 text-sm font-medium text-gray-600 ring-1 ring-inset ring-gray-200')
            ->block('items.multiple.label.wrapper', 'flex min-w-0 items-center')
            ->block('items.multiple.label', 'truncate text-left');
    }
}
