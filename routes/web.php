<?php

use Illuminate\Support\Facades\Route;
// 新導入
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// 網址：http://localhost:8000/setup
// 透過網址呼叫建立並取得一組admin的token，並在DB的'users'新增此資料，無法重複呼叫
Route::get('/setup', function(){
    $credentials = [
        'email' => 'admin@admin.com',
        'password' => 'password'
    ];

    if (!Auth::attempt($credentials)) {
        $user = new \App\Models\User();

        $user->name = 'Admin';
        $user->email = $credentials['email'];
        $user->password = Hash::make($credentials['password']);

        $user->save();
        

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            //*************************************
            //* 新建token，規定各個 Token 賦予的編輯權限 
            //* createToken( '權限名', [允許的權限＿新增/刪除/修改] )
            //* @return class NewAccessToken
            //* path: vendor\laravel\sanctum\src\NewAccessToken
            //*************************************
            $adminToken = $user->createToken('admin-token', ['create', 'update', 'delete']);
            $updateToken = $user->createToken('update-token', ['create', 'update']);
            $basicToken = $user->createToken('basic-token');

            //回傳關聯陣列，DB也會在 'personal_access_tokens' 新增此三筆
            return [
                'admin' => $adminToken->plainTextToken,
                'update' => $updateToken->plainTextToken,
                'basic' => $basicToken->plainTextToken,
            ];
        }
    }
});
