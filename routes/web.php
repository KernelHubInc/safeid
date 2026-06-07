<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Wave\Facades\Wave;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SafeAssetsController;

Route::get('/admin/qr-assets/{asset}/qr.png', [SafeAssetsController::class, 'png'])
    ->name('qr-assets.qr.png');

Route::get('/print/asset/{asset}', [SafeAssetsController::class, 'printAsset'])->name('print.asset');
Route::get('/print/batch/{batch}', [SafeAssetsController::class, 'printBatch'])->name('print.batch');
Route::get('/print/assets', [SafeAssetsController::class, 'printAssets'])->name('print.assets');

Route::get('/health', fn () => response()->json(['ok' => true]))->name('health');
Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    return "Cache cleared";
});
// Wave routes
Wave::routes();
