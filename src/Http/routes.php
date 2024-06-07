<?php

use ManoCode\Corp\Http\Controllers;
use Illuminate\Support\Facades\Route;
use ManoCode\Corp\Http\Controllers\DepartmentController;
use ManoCode\Corp\Http\Controllers\EmployeeController;


Route::resource('departments', DepartmentController::class);
Route::post('departments/sync', [DepartmentController::class, 'sync']);
// 员工信息管理
Route::resource('employees', EmployeeController::class);

Route::post('corp/notify', [Controllers\CorpNotifyController::class, 'notify']);
Route::get('corp/notify', [Controllers\CorpNotifyController::class, 'notify']);
