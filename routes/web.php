<?php

use App\Http\Controllers\BioPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BioPageController::class, 'show'])->name('bio.home');
Route::get('/go/{slug}/{blockId}', [BioPageController::class, 'redirect'])->name('bio.redirect');
Route::post('/track-event/{slug}', [BioPageController::class, 'trackEvent'])->name('bio.track-event');
Route::post('/track-click/{slug}', [BioPageController::class, 'trackEvent'])->name('bio.track-click');
Route::get('/{slug}', [BioPageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('bio.show');
