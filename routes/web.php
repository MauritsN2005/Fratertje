<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BreedingNumberController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberTypeController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/aanmelden', [PublicController::class, 'create'])->name('registration.create');
Route::post('/aanmelden', [PublicController::class, 'store'])->middleware('throttle:5,1')->name('registration.store');
Route::get('/afmelden', [PublicController::class, 'cancellation'])->name('cancellation.create');
Route::post('/afmelden', [PublicController::class, 'sendCancellation'])->middleware('throttle:3,1')->name('cancellation.send');
Route::get('/afmelden/{member}/bevestigen', [PublicController::class, 'confirm'])->middleware('signed')->name('cancellation.confirm');
Route::post('/afmelden/{member}/bevestigen', [PublicController::class, 'cancel'])->middleware(['signed', 'throttle:5,1']);
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1');
});
Route::middleware('auth')->prefix('administratie')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('members', MemberController::class);
    Route::post('members/{member}/restore', [MemberController::class, 'restore'])->name('members.restore');
    Route::resource('member-types', MemberTypeController::class)->except('show');
    Route::post('member-types/{memberType}/restore', [MemberTypeController::class, 'restore'])->name('member-types.restore');
    Route::resource('breeding-numbers', BreedingNumberController::class)->except('store');
    Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
    Route::post('requests/{membershipRequest}/approve', [RequestController::class, 'approve'])->name('requests.approve');
    Route::post('requests/{membershipRequest}/reject', [RequestController::class, 'reject'])->name('requests.reject');
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('members/{member}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
});
