<?php
namespace App\Utility;
use Qcloud\Sms\SmsSingleSender;
class Sms
{
    protected $appid;
    protected $appkey;
    protected $templateId;
    protected $smsSign;
    function __construct()
    {
        // 短信应用 SDK AppID
        $this->appid = 1400306417;// 短信应用 SDK AppKey
        $this->appkey = "849c1c5f573c6700bb02a4cefe1abdc0";// 需要发送短信的手机号码
        $this->templateId = 520956;  // 短信模板 ID，需要在短信控制台中申请
        $this->smsSign = "易久网络"; // NOTE: 签名参数使用的是`签名内容`，而不是`签名ID`。这里的签名"腾讯云"只是示例，真实的签名需要在短信控制台申请

    }
    public function sendCode($mobile,$code): ?bool
    {
        $codestr = (string)$code;
        $params = [$codestr];
        try {
            $ssender = new SmsSingleSender($this->appid, $this->appkey);
            $result = $ssender->sendWithParam("86", $mobile, $this->templateId, $params, $this->smsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            $rsp = json_decode($result,true);
            if($rsp['result']===0&&$rsp['errmsg']==='OK'){
                return true;
            }else{
                return false;
            }
        } catch(\Exception $e) {
            return false;
        }
    }

}
