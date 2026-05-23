<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('register',[UserController::class,'register']);
Route::post('login',[UserController::class,'login']);

Route::middleware('auth:sanctum')->group(function(){

//Customer Api
Route::middleware('role:customer')->group(function () {
Route::get('allProducts',[ProductController::class,'index']);
Route::get('product/category/{id}',[ProductController::class,'getProductByCategory']);
});

//Supplier Api
Route::middleware('role:supplier')->group(function () {
Route::get('supplier/products',[ProductController::class,'showSupplierProduct']);
Route::post('storeProduct',[ProductController::class,'addProduct']);
Route::put('updateProduct/{id}',[ProductController::class,'updateProduct']);
Route::delete('deleteProduct/{id}',[ProductController::class,'deleteProduct']);
});

});
