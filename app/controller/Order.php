<?php

namespace app\controller;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Util\ResponseChecker;
use app\BaseController;
use EasyWeChat\Factory;
use think\facade\Request;

class Order extends BaseController
{
    const WX_MCH_ID = '1518899471';
    const WX_APPID = 'wxe28eb90645d3b9ca';
    const WX_KEY = 'ruVGlUhOpWXTdGW1WH0RwtTT9qU28jTq';
    const WX_SECRET = 'a1a2f9578d9e67f6051e10559c0692ca';

    const ALI_APP_ID = '2019032563707127';
    const ALI_PID = '2088921324464100';

    public function index()
    {
        if (!$data['user_id'] = Request::get('user_id')) {
            return '用户ID不能为空';
        }
        $data['amount'] = Request::get('amount');
        $data['pay_type'] = Request::get('pay_type');
        return view('', $data);
    }

    public function wx_pay()
    {
        $order_id = 'WX' . date('YmdHis') . rand(1000, 9999);
        $amount = Request::get('amount');
        $user_id = Request::get('user_id');
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
            'attach'       => $user_id
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

    public function zfb_pay()
    {
        $order_id = 'ALI' . date('YmdHis') . rand(1000, 9999);
        $amount = Request::get('amount');
        $user_id = Request::get('user_id');
        \Alipay\EasySDK\Kernel\Factory::setOptions($this->getOptions());
        $result = \Alipay\EasySDK\Kernel\Factory::payment()->common()->create('房卡', $order_id, $amount, $user_id);
        $responseChecker = new ResponseChecker();
        //3. 处理响应或异常
        if ($responseChecker->success($result)) {
            echo "调用成功". PHP_EOL;
        } else {
            echo "调用失败，原因：". $result->msg."，".$result->subMsg.PHP_EOL;
        }
    }

    public function getOptions()
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';

        $options->appId = self::ALI_APP_ID;

        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = '<-- 请填写您的应用私钥，例如：MIIEvQIBADANB ... ... -->';

        //$options->alipayCertPath = '<-- 请填写您的支付宝公钥证书文件路径，例如：/foo/alipayCertPublicKey_RSA2.crt -->';
        //$options->alipayRootCertPath = '<-- 请填写您的支付宝根证书文件路径，例如：/foo/alipayRootCert.crt" -->';
        //$options->merchantCertPath = '<-- 请填写您的应用公钥证书文件路径，例如：/foo/appCertPublicKey_2019051064521003.crt -->';

        //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        // $options->alipayPublicKey = '<-- 请填写您的支付宝公钥，例如：MIIBIjANBg... -->';

        //可设置异步通知接收服务地址（可选）
        $options->notifyUrl = "";

        //可设置AES密钥，调用AES加解密相关接口时需要（可选）
        //$options->encryptKey = "<-- 请填写您的AES密钥，例如：aa4BtZ4tspm2wnXLb1ThQA== -->";


        return $options;
    }
}