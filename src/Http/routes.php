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

// 钉钉登录
Route::get('corp/dingLogin', [Controllers\LoginController::class, 'dingLogin'])->withoutMiddleware('admin.auth')->withoutMiddleware('admin.permission');
Route::get('corp/loginByCode', [Controllers\LoginController::class, 'loginByCode'])->withoutMiddleware('admin.auth')->withoutMiddleware('admin.permission');
Route::get('corp/getDingUrl', [Controllers\LoginController::class, 'getDingUrl'])->withoutMiddleware('admin.auth')->withoutMiddleware('admin.permission');


//setting form 配置加密
Route::any('corp/settingEncrypt', [Controllers\SettingEncryptController::class, 'settingEncrypt'])->withoutMiddleware('admin.auth')->withoutMiddleware('admin.permission');
Route::any('corp/settingDecrypt', [Controllers\SettingEncryptController::class, 'settingDecrypt'])->withoutMiddleware('admin.auth')->withoutMiddleware('admin.permission');
