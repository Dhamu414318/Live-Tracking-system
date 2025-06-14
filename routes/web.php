<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Models\User;
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

// Route::get('/envtest', function () {
//     return [
//         'DB_HOST' => env('DB_HOST'),
//         'MYSQL_ATTR_SSL_CA' => env('MYSQL_ATTR_SSL_CA'),
//     ];
// });

Route::get('/envtest', function () {
    return response()->json([
        'env'            => env('APP_ENV'),
        'app_url'        => env('APP_URL'),
        'db_exists'      => DB::connection()->getDatabaseName(),
        'ssl_ca_exists'  => file_exists(env('MYSQL_ATTR_SSL_CA')) ? 'yes' : 'no',
        'ssl_path'       => env('MYSQL_ATTR_SSL_CA'),
    ]);
});



Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('hi');


// Protected Page (Web only)
Route::get('/home', function () {
    return 'Welcome, you are logged in!';
});
