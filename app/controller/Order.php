<?php

namespace app\controller;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Util\ResponseChecker;
use app\BaseController;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\Payment\Application;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use think\facade\Log;
use think\facade\Request;
use think\response\Json;
use think\response\Redirect;

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
        $data['amount'] = Request::get('amount',100);
        $data['pay_type'] = Request::get('pay_type',1);
        return view('', $data);
    }

    /**
     * 获取微信APP
     * @return Application
     * @author zayn
     * @date 2021-07-08
     */
    public function getWxApp(): Application
    {
        return Factory::payment([
            // 必要配置
            'app_id' => self::WX_APPID,
            'mch_id' => self::WX_MCH_ID,
            'key'    => self::WX_KEY,   // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            //'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
            //'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！
        ]);
    }

    /**
     * 微信支付
     * @return Json|Redirect
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws GuzzleException
     * @author zayn
     * @date 2021-07-08
     */
    public function wx_pay()
    {
        $order_id = 'WX' . date('YmdHis') . rand(1000, 9999);
        $amount = Request::get('amount');
        $user_id = Request::get('user_id');
        $app = $this->getWxApp();
        $result = $app->order->unify([
            'body'         => '商超-乐果',
            'out_trade_no' => $order_id,
            'total_fee'    => $amount * 100,
            //'spbill_create_ip' => '123.12.12.123', // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
            'notify_url'   => $this->request->domain() . '/wx_notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
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

    /**
     * 支付宝支付
     * @author zayn
     * @date 2021-07-08
     */
    public function zfb_pay()
    {
        $order_id = 'ALI' . date('YmdHis') . rand(1000, 9999);
        $amount = Request::get('amount');
        $user_id = Request::get('user_id');
        \Alipay\EasySDK\Kernel\Factory::setOptions($this->getAliOptions());
        try {
            $result = \Alipay\EasySDK\Kernel\Factory::payment()
                ->wap()
                ->asyncNotify('')
                ->optional('passback_params', $user_id)
                ->pay('商超-乐果', $order_id, $amount * 100, $this->request->domain() . '/zfb_notify', '');
            $responseChecker = new ResponseChecker();
            //3. 处理响应或异常
            if ($responseChecker->success($result)) {
                var_dump($result);
            } else {
                echo "调用失败，原因：" . $result->msg . "，" . $result->subMsg . PHP_EOL;
            }
        } catch (Exception $e) {
            echo "调用失败，" . $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * 获取zfb 配置
     * @return Config
     * @author zayn
     * @date 2021-07-08
     */
    public function getAliOptions(): Config
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';

        $options->appId = self::ALI_APP_ID;

        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = 'MIIEpAIBAAKCAQEAwbaJXIM85uAh7TvtlLwogov8RcfWXEVBmdygxk19q0uErU6jHOkvUBSi2yAJzYzGrFMbcvnLDU+CoREDp+G3M2c3t+H2a2vD7DhY2E4dYuy6nfUDf2uVbzcj/iCp1LObwhdkyTEoN1jCjX1cxOAb12s3F5a7AbR22T3gFRuA/6eJ9jnFdYjG8cBMCz7H4kNa4M/v3VDAXJ+DFIZ62KB+V1K5tutskiPMNln0r5T2EyCLQhcsB/WxBlEAafbtUYfWXX274z4nO8ntVWxMIsJL+x4AVfCDKp+6g4FbytcWrJdWtWpSrEhl/mX6a47Nuk4Vf6d6GWYA88oKrg1Bsnh8IwIDAQABAoIBAFTdy8AkHwJnH3X301ZeOME44wUPT/KMxPjLmARI3s21ACONWBjKcFf9MnwdxS2whznoDxaIKVVjiC9YbOmYEdMLXXXKIVNemy9aYFIjpuw4GmopdabVU1quJa6oUL9HEO4voZAjYSMeV931FjeKl6gA6NoEx1kv3wG+AfY9Xn6h/CkYACPNP2vpmyHp8wFkvFMtzCXHk9qcBMxzsr3kLQJtrTT93ksaQOAe0dS+tGbS0yDhxSValyPpT4Rae/NkxoXGLbSXXPDYAnNnr3nkEGwVBS8XJRn1sbri/0ZkJTwhx4/JRS7Y6WYckoIyRjLYTyFK1rJ6aoQjhdFiaveoXEECgYEA8SHDZ5WOXTkq8JdilQKyuzMAsghDKHJIJtlyb8Og90TdII/tWLJpiDz0ZEMFiNnvGNx8tijWfQwP4UgKMEiVCTJZH8NIFXGeENxcuh/gC+z3ftYkT6Dwr31CKjaEBRebt5PtWf3OlisMSHm+kCeqKYTzbql8jfyLaKIqpkWgWO0CgYEAzahFx9YD8Txj70lNfypkNMHnyORbCs6YauudAJUdY33JILmeHFp6480s6VbxWepPYz342KuNptSo8jdOPhaQ2XxYSts548fYO/O6TnLWvuCFykoens6QDaiMtYaZS6QvTV+uAGJQOYtBOURMa7+XR2is1y1H2B+QJQi+nlTG108CgYAe7pzZcdb0YHwApvrPcKwq1W0WaXbr/lUBHs3ORoMklSHkpnHk+eYNwvv6zJouJv7D6qzY1T5GhkCXPp2H+hecOWgzaeKaVZvYP9xpR+N+xCQvkhrQWC3n5SKStbGT0aZ5EzHUZHmWy+jkdzGZ3my2rMZpgLZopGfhwUPFVpMuNQKBgQDIF/Ts3dmwGOXSpytzkrc0bYUq/KNn/GJnhR6YtnyFlJjf8jlXtODkS3hq/2CL72GWWXGIvkFwFHDcWdsSpboBIO52xp2odYR5sEWQlkNCLAmALGVmdevKnjdpVrBH3FL5oSIW1ZDgrBClu0Hvg9WYcMvaAABq0yrYHY35VtqwGQKBgQDOYUTvdYcnLYtFEC7jPNtY2eJ9y/jHL49YzZGUnYuJQ66r5pFjaeqU6E7jmKntc7Z0FZLhC5W/oeCN/FqJWjW2zdzxMFxREz/6dZTtsGfO3M0Xov+pVS40rIXf6H99hQnvIZ+RR1EV/IJDhINQY63F27N4mUgy6jMqGre6aLbgRg==';

        //请填写您的支付宝公钥证书文件路径
        //$options->alipayCertPath = root_path().'cert/ali/rsa_public_key.pem';
        //请填写您的支付宝根证书文件路径
        //$options->alipayRootCertPath = '';
        //请填写您的应用公钥证书文件路径
        //$options->merchantCertPath = '';

        //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        $options->alipayPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjGUG+oZi9C6eN4fdHqxEHRVVBFZnnhIWeHSAu1A7wh2Xvlybbm2vXiA4NcrV9/Y9U7YJc2sJT4556h5Uo6XfHJqVB/oPq1c8Fz0NQHZEX3LLhWNhJ0ghGA34ibinQVyArp0L0ZY2dh5jq5AuQk8J+hq3ocD+aWVz13OvzPA14k7Z6j3MvlUF348yDqxivI6cbflzBXP5oW7l3khaxeCwOTBxCWGUZkxYB+DMzOksO+XxQXmyQPe8Bgp4+U+58oxNZjeNXwt9Pve6aKOrvXQhKvGbebNwanCMaiUILus3ccpRKhwgQ+HsQqwi53GJnzM0+iOrMi8SvEWpOM0rONjImwIDAQAB';

        //可设置异步通知接收服务地址（可选）
        $options->notifyUrl = "";

        //可设置AES密钥，调用AES加解密相关接口时需要（可选）
        //$options->encryptKey = "<-- 请填写您的AES密钥，例如：aa4BtZ4tspm2wnXLb1ThQA== -->";


        return $options;
    }

    /**
     * 微信回调
     * @author zayn
     * @date 2021-07-08
     */
    public function wx_notify()
    {
        $app = $this->getWxApp();
        try {
            $response = $app->handlePaidNotify(function ($message, $fail) {
                Log::info('微信回调开始：');
                Log::info($message);
                $fail('Order not exists.');
                return true;
            });
            $response->send();
        } catch (Exception $e) {
            Log::info('微信回调错误：' . $e->getMessage() . ':' . $e->getFile() . ':' . $e->getCode());
            $response->send();
        }
    }

    /**
     * 支付宝回调
     * @return string
     * @author zayn
     * @date 2021-07-08
     */
    public function zfb_notify(): string
    {
        try {
            Log::info('支付宝回调开始：');
            $post = \request()->post();
            Log::info($post);
            \Alipay\EasySDK\Kernel\Factory::setOptions($this->getAliOptions());
            \Alipay\EasySDK\Kernel\Factory::payment()->common()->verifyNotify($post);
            return 'success';
        } catch (Exception $e) {
            Log::info('支付宝回调错误：' . $e->getMessage() . ':' . $e->getFile() . ':' . $e->getCode());
            return 'fail';
        }
    }
}