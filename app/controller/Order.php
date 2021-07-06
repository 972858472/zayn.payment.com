<?php

namespace app\controller;

use app\BaseController;
use EasyWeChat\Factory;
use think\facade\Request;

class Order extends BaseController
{
    const WX_MCH_ID = '1518899471';
    const WX_APPID = 'wxe28eb90645d3b9ca';
    const WX_KEY = 'ruVGlUhOpWXTdGW1WH0RwtTT9qU28jTq';
    const WX_SECRET = 'a1a2f9578d9e67f6051e10559c0692ca';

    public function index()
    {
        $order_id = Request::get('order_id');
        $amount = Request::get('amount');
        $type = Request::get('type');
        return view('index');
    }

    public function wx_pay($order_id, $amount)
    {
        $app = Factory::payment([
            // 必要配置
            'app_id'     => self::WX_APPID,
            'mch_id'     => self::WX_MCH_ID,
            'key'        => self::WX_KEY,   // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            //'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
            //'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！
            'notify_url' => 'https://notify.com',     // 你也可以在下单时单独设置来想覆盖它
        ]);
        $result = $app->order->unify([
            'body'         => '商超-乐果',
            'out_trade_no' => $order_id,
            'total_fee'    => $amount,
            //'spbill_create_ip' => '123.12.12.123', // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
            'notify_url'   => 'https://pay.weixin.qq.com/wxpay/pay.action', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type'   => 'MWEB', // 请对应换成你的支付方式对应的值类型
        ]);
        if ($result['return_code'] == 'SUCCESS') {
            if ($result['result_code'] == 'SUCCESS') {
                return redirect($result['mweb_url']);
            }
            return fail($result['err_code_des']);
        } else {
            return fail($result['return_msg']);
        }
    }
}