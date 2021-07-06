<?php
// 应用公共文件

use Symfony\Component\HttpFoundation\JsonResponse;
use think\response\Json;

if (!function_exists('success')) {
    /**
     * 接口成功返回
     * @param string $data
     * @param string $msg
     * @return Json
     */
    function success(string $data = "", string $msg = "success"): Json
    {
        return json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }
}

if (!function_exists('fail')) {
    /**
     * 接口错误返回
     * @param string $msg
     * @param string $data
     * @param int $code
     * @return Json
     */
    function fail(string $msg = "fail", string $data = "", int $code = 1): Json
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }
}

