<?php

namespace App\HttpController\Api;

use App\Model\Dot\DotModel;
use App\Model\Order\OrderModel;
use App\Model\System\SiamSystemModel;
use App\Model\Users\SiamUserModel;
use App\Utility\Sms;
use EasySwoole\EasySwoole\Config;
use EasySwoole\FastCache\Cache;
use EasySwoole\Http\Annotation\Param;
use EasySwoole\Http\Message\Status;
use EasySwoole\Jwt\Jwt;
use EasySwoole\Validate\Validate;

/**
 * Class Users
 * Create With Automatic Generator
 */
class Login extends Base
{
    /**
     * @return bool
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    public function login()
    {
        $username = $this->request()->getRequestParam('username');
        $type = empty($this->param['login_type'])?1:$this->param['login_type'];
        $password = $this->request()->getRequestParam('password');
        $code = $this->request()->getRequestParam('code');
        $user = DotModel::create()->get([
            'username' => $username,
        ]);

        if ($user === NULL) {
            $this->writeJson(Status::CODE_NOT_FOUND,[], '用户不存在');
            return false;
        }
        if($type==1){
            if($user->password!=md5($password.'pswstr')){
                $this->writeJson(Status::CODE_NOT_FOUND, [], '密码不正确');return false;
            }
        }else{
            $login_code = Cache::getInstance()->get('login_'.$this->param['username']);
            if(!$login_code){  $this->writeJson(Status::CODE_NOT_FOUND, [], '验证码已过期');return false;}
            if($login_code!=$code){  $this->writeJson(Status::CODE_NOT_FOUND, [], $login_code.'验证码不正确'.'login_'.$this->param['username'].$login_code);return false;}
            Cache::getInstance()->unset('login_'.$this->param['username']);
        }


        // 生成token
        $config    = Config::getInstance();
        $jwtConfig = $config->getConf('JWT');

        $jwtObject = Jwt::getInstance()
            ->setSecretKey($jwtConfig['key']) // 秘钥
            ->publish();
        $jwtObject->setAlg('HMACSHA256'); // 加密方式
        $jwtObject->setAud($user->username); // 用户
        $jwtObject->setExp(time()+$jwtConfig['exp']); // 过期时间
        $jwtObject->setIat(time()); // 发布时间
        $jwtObject->setIss($jwtConfig['iss']); // 发行人
        $jwtObject->setJti(md5(time())); // jwt id 用于标识该jwt
        $jwtObject->setNbf(time()); // 在此之前不可用
        $jwtObject->setSub($jwtConfig['sub']); // 主题

        // 自定义数据
        $jwtObject->setData($user);
        // 最终生成的token
        $token = $jwtObject->__toString();
        $data = $user->toArray();
        $config = Config::getInstance()->getConf('www');
        $data['imgurl'] =$config['host_img'].$data['imgurl'];
        $data['card_face'] =$data['card_face']?$config['host_img'].$data['card_face']:'';
        $data['card_back'] =$data['card_back']?$config['host_img'].$data['card_back']:'';
        $data['business_license'] =$data['business_license']?$config['host_img'].$data['business_license']:'';
        Cache::getInstance()->set('dot_token_'.$data['id'],$token); //缓存令牌
        $this->writeJson(Status::CODE_OK, [
            'token'    => $token,
            'userInfo' => $data,
        ], '登陆成功');
    }
    /**
     * 发送短信验证码
     * @return bool
     */
    public function getLoginCode(){
        if(empty($this->param['username'])){ $this->writeJson(Status::CODE_BAD_REQUEST, [], '请输入手机号'); return true;}
        $dot = DotModel::create()->where('username',$this->param['username'])->get();
        if(!$dot){  $this->writeJson(Status::CODE_BAD_REQUEST, [], '此手机号暂无注册'); return true;}
        $mobile = $this->param['username'];
        $code = rand(0000,9999);
        Cache::getInstance()->unset('login_'.$mobile,$code);
        Cache::getInstance()->set('login_'.$mobile,$code,5*60);
        $sms = new Sms();
        $res = $sms->sendCode($mobile,$code);
        if(!$res){$this->writeJson(Status::CODE_BAD_REQUEST, [], '验证码发送失败'); return true;}
        $this->writeJson(Status::CODE_OK, $res, 'success' );
        return true;
    }
    protected function getValidateRule(?string $action): ?Validate
    {
        // TODO: Implement getValidateRule() method.
        switch ($action) {
            case 'login':
                $valitor = new Validate();
                $valitor->addColumn('username')->required();
                return $valitor;
                break;
        }
        return NULL;
    }


}

