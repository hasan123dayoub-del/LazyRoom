<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('balance', [OrderController::class, 'showBalance']);

    Route::post('/logout', [UserController::class, 'logout']);
    Route::delete('/delete-account', [UserController::class, 'deleteAccount']);

    //Customer Api
    Route::middleware('role:customer')->group(function () {
        Route::get('allProducts', [ProductController::class, 'index']);
        Route::get('product/category/{id}', [ProductController::class, 'getProductByCategory']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/my-orders', [OrderController::class, 'myOrders']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
        Route::post('/orders/{id}/complete', [OrderController::class, 'complete']);
    });

    //Supplier Api
    Route::middleware('role:supplier')->group(function () {
        Route::get('supplier/products', [ProductController::class, 'showSupplierProduct']);
        Route::post('storeProduct', [ProductController::class, 'addProduct']);
        Route::put('updateProduct/{id}', [ProductController::class, 'updateProduct']);
        Route::delete('deleteProduct/{id}', [ProductController::class, 'deleteProduct']);
        Route::get('supplier/orders', [OrderController::class, 'supplierIndex']);
        Route::post('orders/{id}/accept', [OrderController::class, 'accept']);
        Route::post('orders/{id}/reject', [OrderController::class, 'reject']);
    });

    //Admin Api

    Route::middleware('role:admin')->group(function () {
        Route::get('accounts', [AdminController::class, 'listAccounts']);
        Route::post('accounts', [AdminController::class, 'storeUser']);
        Route::delete('accounts/{id}', [AdminController::class, 'destroyUser']);
        Route::post('ban/{id}', [AdminController::class, 'banUser']);
        Route::post('unban/{id}', [AdminController::class, 'unbanUser']);
        Route::post('withdraw', [AdminController::class, 'withdrawFromSupplier']);
        Route::post('deposit', [AdminController::class, 'depositToCustomer']);
    });
});
