<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::group([
    'middleware' => ['auth:sanctum', 'verified']
], function () {
    Route::group([
        'middleware' => ['can:admin']
    ], function () {
        Route::get('users', [UserController::class, 'users']);
        Route::get('admins', [UserController::class, 'admins']);
        Route::post('admin', [UserController::class, 'store'])->name('admin.create');
        Route::delete('user/{id}', [UserController::class, 'destroy']);
        Route::put('user/{id}', [UserController::class, 'update']);
        
    });
    
    
    Route::post('user/address', [UserController::class, 'setAddress']);
    Route::put('user', [UserController::class, 'update']);
    Route::put('update/user/password/{id?}', [UserController::class, 'updatePassword']);
    Route::get('user/{id?}', [UserController::class, 'show']);
    Route::get('account/check', function() {
        return response(["status" => "completed"]);
    })->middleware('account.completed');
        

});


Route::post('register', [UserController::class, 'store'])->name('register');
Route::post('login', [UserController::class, 'login'])->name('login');
Route::get('social/{provider}', [UserController::class, 'socialAuth'])->name('social.auth');

Route::get('login/{provider}', [UserController::class, 'redirectToProvider'])->name('login.provider.redirect');
Route::get('login/{provider}/callback', [UserController::class, 'handleProviderCallback'])->name('login.provider.callback');

Route::get('verify', [UserController::class, 'verify'])->middleware(['throttle:6,1', 'signed'])->name('verification.verify');
Route::get('verify/resend', [UserController::class, 'resendVerification'])->middleware('auth:sanctum', 'throttle:6,1')->name('verification.resend');

Route::get('logout', [UserController::class, 'logout'])->name('logout');
Route::get('logout/all', [UserController::class, 'logoutAll'])->name('logout.all');

