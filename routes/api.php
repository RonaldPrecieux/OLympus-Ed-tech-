<?php

use App\Http\Controllers\AuthControlleur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


 
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/register', [AuthControlleur::class, 'register']);
    Route::post('/login', [AuthControlleur::class, 'login']);
    Route::post('/logout', [AuthControlleur::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh', [AuthControlleur::class, 'refresh'])->middleware('auth:api');
    Route::post('/user', [AuthControlleur::class, 'profile'])->middleware('auth:api');
});

Route::post('/resources/courses', [ResourceController::class, 'createCourse']);
Route::get('/resources/courses', [ResourceController::class, 'getCourses']);
Route::post('/resources/upload', [ResourceController::class, 'uploadResource']);
Route::get('/resources/{course_id}', [ResourceController::class, 'getResources']);
Route::delete('/resources/{resource_id}', [ResourceController::class, 'deleteResource']);
