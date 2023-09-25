<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//Retrieve the most 5 categories that contain products.
Route::get('/categories/top', [CategoryController::class, 'getCategoriesWithProducts']);
// List all categories
Route::get('/categories', [CategoryController::class, 'index']);

// Create a new category
Route::post('/categories', [CategoryController::class, 'create']);

// Update an existing category
Route::post('/categories/{id}', [CategoryController::class, 'update']);

// Show details of a specific category
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Delete a specific category
Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

// delete Expired Products
Route::get('/products/deleteExpired', [ProductController::class, 'deleteExpiredProducts']);


// Route to get the top 5 products by category
Route::get('/categories/{id}/top-products', [ProductController::class, 'getTop5ProductsByCategory']);
// Create a new Product
Route::post('/products', [ProductController::class, 'create']);
// Update a new Product
Route::post('/products/{id}', [ProductController::class, 'update']);
// Show details of a specific Product
Route::get('/products/{id}', [ProductController::class, 'show']);

