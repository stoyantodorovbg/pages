<?php

namespace Oxygencms\Pages;

use Oxygencms\Pages\Models\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'Oxygencms\Pages\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $Page = config('pages.model');

        // Explicitly bind the page_slug because of the translations (json)
        Route::bind('page_slug', function ($slug) use ($Page) {

            $locale = session('app_locale') ?: app()->getLocale();

            $page = $Page::bySlug($slug, $locale)->first();

            if ($page) {
                return $page;
            }

            $locales = config('oxygen.locales');

            unset($locales[$locale]);

            // search for this slug in the reset of the locales
            foreach ($locales as $key => $name) {

                $page = $Page::bySlug($slug, $key)->first();

                if ($page) {
                    session()->put('app_locale', $key);

                    return $page;
                }
            }

            return abort(404);
        });


    }

    /**
     * Define the routes for the pages.
     *
     * @return void
     */
    public function map()
    {
        $this->mapAdminRoutes();

        $this->mapWebRoutes();
    }

    /**
     * Define the route for the showing a page.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapAdminRoutes()
    {
        Route::middleware([
            'web',
            'admin'
        ])
            ->namespace($this->namespace . '\\Admin')
            ->prefix('admin')
            ->name('admin.')
            ->group(function () {
                Route::resource('page', 'PageController', ['except' => 'show']);
            });
    }

    /**
     * Define the "web" routes for the package.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')->group(function () {
            Route::get('/', config('pages.home_controller') . '@show')->name('home');
        });

        Route::middleware('web')->group(function () {
            Route::get('{page_slug}', config('pages.page_controller') . '@show')->name('page.show');
        });
    }
}
