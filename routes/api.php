<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\KitController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
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

        //categories routes
        Route::get('categories/all', [CategoryController::class, 'index']);
        Route::post('category', [CategoryController::class, 'store']);
        Route::get('category/{id}', [CategoryController::class, 'show']);
        Route::put('category/{id}', [CategoryController::class, 'update']);
        Route::delete('category/{id}/translation/{language}', [CategoryController::class, 'deleteTranslation']);
        Route::delete('category/{id}', [CategoryController::class, 'destroy']);


        //product
        Route::post('product', [ProductController::class, 'store']);
        Route::put('product', [ProductController::class, 'update']);
        Route::delete('product/{id}', [ProductController::class, 'destroy']);
        Route::get('products/all', [ProductController::class, 'index']);
        //product usage
        Route::post('product/usage', [ProductController::class, 'addProductUsage']);
        Route::put('product/usage', [ProductController::class, 'updateProductUsage']);
        Route::delete('product/usage/{id}', [ProductController::class, 'destroyProductUsage']);

        //kits
        Route::post('kit', [KitController::class, 'store']);
        Route::put('kit', [KitController::class, 'update']);
        Route::delete('kit/{id}', [KitController::class, 'destroy']);
        Route::get('kits/all', [KitController::class, 'index']);


        //reviews & replies
        Route::get('reviews/all', [ReviewController::class, 'index']);
        Route::get('review/{id}/approve', [ReviewController::class, 'approve']);
    });
    
    //reviews & replies
    Route::post('review', [ReviewController::class, 'storeOrUpdate']);
    Route::delete('review/{id}', [ReviewController::class, 'destroy']);
    Route::post('review/reply', [ReviewController::class, 'addReply']);
    Route::put('review/reply', [ReviewController::class, 'updateReply']);
    Route::delete('review/reply/{id}', [ReviewController::class, 'destroyReply']);
    

    Route::post('user/address', [UserController::class, 'setAddress']);
    Route::put('user', [UserController::class, 'update']);
    Route::put('update/user/password/{id?}', [UserController::class, 'updatePassword']);
    Route::get('user/{id?}', [UserController::class, 'show']);
    Route::get('account/check', function () {
        return response(["status" => "completed"]);
    })->middleware('account.completed');
});

Route::get('categories', [CategoryController::class, 'active']);

Route::post('register', [UserController::class, 'store'])->name('register');
Route::post('login', [UserController::class, 'login'])->name('login');
Route::get('social/{provider}', [UserController::class, 'socialAuth'])->name('social.auth');

Route::get('login/{provider}', [UserController::class, 'redirectToProvider'])->name('login.provider.redirect');
Route::get('login/{provider}/callback', [UserController::class, 'handleProviderCallback'])->name('login.provider.callback');

Route::get('verify', [UserController::class, 'verify'])->middleware(['throttle:6,1', 'signed'])->name('verification.verify');
Route::get('verify/resend', [UserController::class, 'resendVerification'])->middleware('auth:sanctum', 'throttle:6,1')->name('verification.resend');

Route::get('logout', [UserController::class, 'logout'])->name('logout');
Route::get('logout/all', [UserController::class, 'logoutAll'])->name('logout.all');

Route::get('product/{id}', [ProductController::class, 'show']);
Route::get('products', [ProductController::class, 'activeWithFilters']);


Route::get('kit/{id}', [KitController::class, 'show']);
Route::get('kits', [KitController::class, 'activeWithFilters']);

Route::get('reviews/{id}', [ReviewController::class, 'activated']);