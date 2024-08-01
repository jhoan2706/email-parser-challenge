<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailParserController;

Route::post('/parse-email', [EmailParserController::class, 'parseEmail']);
