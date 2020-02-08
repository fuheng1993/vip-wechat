<?php

namespace App\HttpController\Api;

use EasySwoole\EasySwoole\Config;
use EasySwoole\FastCache\Cache;
use EasySwoole\Jwt\Jwt;
use EasySwoole\Policy\Policy;
use EasySwoole\Policy\PolicyNode;
use EasySwoole\Validate\Validate;

/**
 * BaseController
 * Class Base
 * Create With Automatic Generator
 */
abstract class Base extends \EasySwoole\Http\AbstractInterface\Controller
{
    private $basicAction = [
        '/api/users/login',
        '/api/login/login',
        '/api/login/getLoginCode',
        '/api/login/register',
        '/api/login/getRegisterCode'

    ];
    protected $token;
    protected $store_id;
    protected $dot_id;
    protected $param;
    public function index(){
		$this->actionNotFound('index');
	}
	public function onRequest(?string $action): ?bool
	{
        $this->param = $this->request()->getRequestParam();
		if (!parent::onRequest($action)) { return false; };
        $path = $this->request()->getUri()->getPath();
        echo $path.PHP_EOL;
        // basic列表里的不需要验证
        if (!in_array($path, $this->basicAction)){
            // 必须有token
            if (empty( $this->request()->getHeader('token')[0] )){
                $this->writeJson(\EasySwoole\Http\Message\Status::CODE_UNAUTHORIZED, new \stdClass(), "Token不可为空");
                return false;
            }
            try{
                $config    = Config::getInstance();
                $jwtConfig = $config->getConf('JWT');
                $jwtObject = Jwt::getInstance()->setSecretKey($jwtConfig['key'])->decode($this->request()->getHeader('token')[0]);
                $status = $jwtObject->getStatus();
            }catch (\EasySwoole\Jwt\Exception $e) {
                $this->writeJson(\EasySwoole\Http\Message\Status::CODE_UNAUTHORIZED, new \stdClass(), "JWT异常：".$e->getMessage());
                return false;
            }


            // 如果encode设置了秘钥,decode 的时候要指定
            switch ($status)
            {
                case  1:
                    $this->token = $jwtObject->getData();
                    $data = $jwtObject->getData();
                    $this->store_id = $data['store_id'];
                    $this->dot_id = $data['id'];

                    break;
                case  -1:
                    $this->writeJson(\EasySwoole\Http\Message\Status::CODE_UNAUTHORIZED, new \stdClass(), "token无效");
                    return false;
                    break;
                case  -2:
                    $this->writeJson(\EasySwoole\Http\Message\Status::CODE_UNAUTHORIZED, new \stdClass(), "token过期");
                    return false;
                    break;
            }
            //单用户单点登录判断
            if($this->request()->getHeader('token')[0]!=Cache::getInstance()->get('dot_token_'.$data['id'])){
                $this->writeJson(\EasySwoole\Http\Message\Status::CODE_UNAUTHORIZED, ['request_token'=>$this->request()->getHeader('token')[0],'login_token'=>Cache::getInstance()->get('dot_token_'.$data['id'])], '此账号登录已过期或已在其他地方登录！');
                return false;
            }
            if (!is_array($this->token) || empty($this->token)){
                $this->writeJson(\EasySwoole\Http\Message\Status::CODE_UNAUTHORIZED, new \stdClass(), "token解析失败:".$this->token);
                return false;
            }
//            // 权限策略判断
//            if ( !$this->vifPolicy($this->token['u_id'], $path) ){
//                $this->writeJson(\EasySwoole\Http\Message\Status::CODE_BAD_REQUEST, new \stdClass(), "无权限访问该接口");
//                return false;
//            }
        }
		// 各个action的参数校验
		$v = $this->getValidateRule($action);
		if ($v && !$this->validate($v)) {
		    $this->writeJson(\EasySwoole\Http\Message\Status::CODE_BAD_REQUEST, ['errorCode' => 1, 'data' => []], $v->getError()->__toString());
		    return false;
		}

		return true;
	}

	public function writeJson($statusCode = 200, $result = NULL, $msg = NULL)
    {
        if (!$this->response()->isEndResponse()) {
            $data = Array(
                "code" => $statusCode,
                "result" => $result,
                "msg" => $msg
            );
            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus(200);
            return true;
        } else {
            return false;
        }
    }

    public function onException(\Throwable $throwable): void
    {
        $path = $this->request()->getUri()->getPath();
        $this->writeJson(\EasySwoole\Http\Message\Status::CODE_INTERNAL_SERVER_ERROR, new \stdClass(), "API异常：{$path}".$throwable->getMessage());
        var_dump($throwable->getMessage());
    }

    /**
     * 验证权限策略
     * @param $u_id
     * @param string $path
     * @return bool
     * @throws
     */
    private function vifPolicy($u_id, string $path)
    {
        if (empty($u_id)) return false;
        // 该路径接口不需要验证 直接通过
        if ($this->shouldVifPath($path) == false){
            return true;
        }

        // 从缓存拿 没有就从数据库读取 初始化
        $policy = Cache::getInstance()->get('policy_'.$u_id);

        if($policy === null){
            $policy = new Policy();
            // 用户权限
            $userModel = SiamUserModel::create()->get($u_id);
            $userAuth  = $userModel->getAuth();
            foreach ($userAuth as $key => $value) {
                $policy->addPath($value['auth_rules'],PolicyNode::EFFECT_ALLOW);
            }
            Cache::getInstance()->set('policy_'.$u_id, serialize($policy), 10 * 60);
        }else{
            $policy = unserialize($policy);
        }

        if($policy->check($path) === 'allow'){
            return true;
        }
        return false;
    }

    /**
     * 该路径是否建立了权限管理  没建立就是不用管
     * @param string $path
     * @return bool
     * @throws
     */
    private function shouldVifPath(string $path): bool
    {
        $cache = Cache::getInstance();
        $authRes = $cache->get('shouldvif_api_'.md5($path));
        if ($authRes === null){
            $auth = SiamAuthModel::create()->get(['auth_rules' => $path]);
            // 没有设置该api规则 所以不需要验证
            if ($auth===null){
                $cache->set('shouldvif_api_'.md5($path),  false, 3*60);
                return false;
            }else{
                $cache->set('shouldvif_api_'.md5($path),  true, 3*60);
                return true;
            }
        }
        if ($authRes === true){
            return true;
        }
        return false;
    }

    abstract protected function getValidateRule(?string $action): ?Validate;


}

