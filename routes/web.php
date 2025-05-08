<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Barcodecontroller;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/bar-code',[Barcodecontroller::class,"index"]);