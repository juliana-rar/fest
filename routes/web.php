<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FiestaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/festa/{fiesta}', [FiestaController::class, 'show'])->name('fiestas.show');
Route::get('/festa/{fiesta}/calendari.ics', [FiestaController::class, 'ics'])->name('fiestas.ics');
Route::get('/story/{fiesta}', StoryController::class)->name('story');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response(
    "User-agent: *\nDisallow: /admin\nDisallow: /api/\n\nSitemap: ".route('sitemap')."\n",
    200,
    ['Content-Type' => 'text/plain; charset=utf-8'],
))->name('robots');
Route::get('/idioma/{locale}', LocaleController::class)->whereIn('locale', ['ca', 'es'])->name('locale');
Route::view('/offline', 'offline')->name('offline');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [LoginController::class, 'create'])->name('login');
    Route::post('/admin/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/admin/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('fiestas/export', [Admin\FiestaController::class, 'export'])->name('fiestas.export');
    Route::post('fiestas/import', [Admin\FiestaController::class, 'import'])->name('fiestas.import');
    Route::post('fiestas/{fiesta}/duplicate', [Admin\FiestaController::class, 'duplicate'])->name('fiestas.duplicate');
    Route::resource('fiestas', Admin\FiestaController::class)->except('show');

    Route::resource('municipios', Admin\MunicipioController::class)->except('show');
    Route::resource('comarcas', Admin\ComarcaController::class)->except('show');
    Route::resource('tipos', Admin\TipoController::class)->except('show');

    Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
});
