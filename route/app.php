<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('/', function () {
    return 'bad request';
});

//Route::get('hello/:name', 'index/hello');

Route::get('order', 'order/index');

//Route::get('wx_pay', 'order/wx_pay');
//Route::get('zfb_pay', 'order/zfb_pay');
//Route::post('wx_notify', 'order/wx_notify');
//Route::post('zfb_notify', 'order/zfb_notify');

Route::post('wzlm_notify', 'order/wzlm_notify');
Route::post('wzlm_pay', 'order/wzlm_pay');
