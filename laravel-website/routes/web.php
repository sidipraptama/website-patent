<?php

use App\Http\Controllers\AutoUpdateController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DraftPatentController;
use App\Http\Controllers\PatentSearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RabbitController;
use App\Http\Controllers\SimilaritySearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

// Menambahkan rute-rute yang ada ke dalam grup 'auth'
Route::middleware('auth')->group(function () {


    // Route::get('/similarity-search', function () {
    //     return view('similaritySearch');
    // })->name('similarity-search');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::prefix('dashboard')->group(function () {
        Route::get('/statistics', [DashboardController::class, 'fetchPatentStatistics'])->name('dashboard.statistics');
        Route::get('/statistics/yearly', [DashboardController::class, 'fetchPatentStatisticsYearly'])->name('dashboard.statistics.yearly');
    });

    Route::get('/patent-search', [PatentSearchController::class, 'index'])->name('patent-search');
    Route::prefix('patent')->group(function () {
        Route::get('/search', [PatentSearchController::class, 'search'])->name('patent.search');
        Route::post('/bookmark', [PatentSearchController::class, 'bookmark'])->name('patent.bookmark');
        // Route::get('/similarity', [PatentSearchController::class, 'similaritySearch'])->name('search.similarity');
    });

    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks');
    Route::prefix('bookmarks')->group(function () {
        Route::get('/list', [BookmarkController::class, 'fetchBookmarks'])->name('bookmarks.list');
    });

    Route::get('/similarity-search', [SimilaritySearchController::class, 'index'])->name('similarity-search');
    Route::prefix('similarity')->group(function () {
        Route::post('/search', [SimilaritySearchController::class, 'search'])->name('similarity.search');
        Route::get('/history', [SimilaritySearchController::class, 'listChecks'])->name('similarity.history');
        Route::get('/results/{id}', [SimilaritySearchController::class, 'results'])->name('similarity.result');
        Route::get('/check-results/{id}', [SimilaritySearchController::class, 'checkResultsOnly'])->name('similarity.check-result');
    });

    Route::get('/draft-patent', [DraftPatentController::class, 'index'])->name('draft-patent');
    Route::prefix('draft-patent')->group(function () {
        Route::get('/getData', [DraftPatentController::class, 'getData'])->name('draft-patent.getData');
        Route::post('/create', [DraftPatentController::class, 'store'])->name('draft-patent.create');
        Route::delete('/delete/{id}', [DraftPatentController::class, 'delete'])->name('draft-patent.delete');
        Route::post('/{id}/update', [DraftPatentController::class, 'update'])->name('draft-patent.update');
        Route::post('/{id}/save', [DraftPatentController::class, 'save'])->name('draft-patent.save');
        Route::post('/{id}/duplicate', [DraftPatentController::class, 'duplicate'])->name('draft-patent.duplicate');
        Route::get('/{id}', [DraftPatentController::class, 'show'])->name('draft-patent.detail');
        Route::post('/store-image', [DraftPatentController::class, 'storeImage'])->name('draft-patent.store-image');
        Route::delete('/delete-image/{id}', [DraftPatentController::class, 'destroyImage'])->name('draft-patent.delete-image');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/auto-update-log', [AutoUpdateController::class, 'index'])->name('auto-update-log');
        Route::get('/fetch-update-history', [AutoUpdateController::class, 'fetchUpdateHistory'])->name('fetch-update-history');
        Route::get('/update-history/{id}', [AutoUpdateController::class, 'getDetailHistory'])->name('update.history.detail');
        Route::post('/update/cancel', [AutoUpdateController::class, 'cancel'])->name('update.cancel');

    });

    Route::get('/send-rabbit', [RabbitController::class, 'send'])->name('send.rabbit');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
