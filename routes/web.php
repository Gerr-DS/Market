<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — ReCircle Marketplace (Blade Frontend)
|--------------------------------------------------------------------------
| Seluruh frontend telah dimigrasikan ke Laravel Blade dengan arsitektur
| modern, terintegrasi langsung dengan API V1 Sanctum.
*/

// Halaman Utama / Beranda
Route::get('/', function () {
    return view('pages.home');
})->name('home');

// Katalog & Pencarian Produk
Route::get('/products', function () {
    return view('pages.products');
})->name('products.index');

// Detail Produk & Checkout
Route::get('/products/{id}', function ($id) {
    return view('pages.product-detail', ['id' => $id]);
})->name('products.show');

// AI Studio Jual Barang / Makanan Surplus
Route::get('/sell', function () {
    return view('pages.sell');
})->name('sell');

// Masuk & Daftar
Route::get('/login', function () {
    return view('pages.auth');
})->name('login');

Route::get('/register', function () {
    return view('pages.auth');
})->name('register');

// Ruang Akun & Dashboard
Route::prefix('dashboard')->group(function () {
    Route::get('/', function () {
        return view('pages.dashboard');
    })->name('dashboard');

    Route::get('/products', function () {
        return view('pages.my-products');
    })->name('dashboard.products');

    Route::get('/sales', function () {
        return view('pages.seller-sales');
    })->name('dashboard.sales');

    Route::get('/orders', function () {
        return view('pages.buyer-orders');
    })->name('dashboard.orders');

    Route::get('/wallet', function () {
        return view('pages.wallet');
    })->name('dashboard.wallet');

    Route::get('/chat', function () {
        return view('pages.chat');
    })->name('dashboard.chat');
});

// Panel Moderasi Admin
Route::get('/admin', function () {
    return view('pages.admin');
})->name('admin');
