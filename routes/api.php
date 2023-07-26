<?php

use App\Http\Controllers\Api\V1\CustomerController;
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

Route::group(['prefix'=>'v1', 'namespace'=>'App\Http\Controllers\Api\V1', 'middleware'=>'auth:sanctum'], function(){   
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);

    // 呼叫 InvoiceController 控制器的 bulkStore 函式
    // 陣列也可直接替換為 'InvoiceController@bulkStore'
    Route::post('invoices/bulk', ['uses' => 'InvoiceController@bulkStore']); 
});

//http://127.0.0.1:8000/api/v1/customers
//http://127.0.0.1:8000/api/v1/invoices