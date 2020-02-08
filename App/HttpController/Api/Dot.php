<?php

namespace App\HttpController\Api;

use App\Model\Dot\DotModel;
use App\Model\DotAccount\DotAccountModel;
use App\Model\System\SystemModel;
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
class Dot extends Base
{
    /**
     * 获取店铺信息
     * @return array
     */
    public function getDotInfo(){
        $dot_id  = $this->dot_id;
        $model = new DotModel();
        $field = 'id,shopname,username,contacts,address,lat,lng,create_time,month_sale,month_num,total_sale,total_num,card_no,card_name,card_face,card_back,business_license,imgurl,store_id,banking_hours,scope';
        $config = Config::getInstance()->getConf('www');
        $info = $model->field($field)->where('id',$dot_id)->findOne();
        $info['imgurl'] =$config['host_img'].$info['imgurl'];
        $this->writeJson(Status::CODE_OK, $info, 'success');

        return true;
    }
    /**
     * @return bool
     */
    public function updateField(){
        $field_name = $this->param['field_name'];
        $field_value = $this->param['field_value'];
        $field =explode(',',$field_name);
        $value =explode(',',$field_value);
        foreach ($field as $k=>$v){
            $data[$v] = $value[$k];
        }
        $model = new DotModel();
        $res = $model->where('id',$this->dot_id)->update($data);
        if($res===true){
            $this->writeJson(Status::CODE_OK, $data, 'success');
        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST,$data, '资料更新失败，请重试！');
        }

        return true;
    }

    /**
     * 上传图片
     * @return bool
     */
    public function uploadFile(){
        $request=  $this->request();
        $img_file = $request->getUploadedFile('img');//获取一个上传文件,返回的是一个\EasySwoole\Http\Message\UploadFile的对象
        $fileSize = $img_file->getSize();
        //上传图片不能大于5M (1048576*5)
        if($fileSize>1048576*5){
            $this->writeJson(Status::CODE_BAD_REQUEST,['size'=>$fileSize], '图片最大不能超过5MB'); return false;
        }
        $clientFileName = $img_file->getClientFilename();
        $fileName = $this->dot_id.'_'.MD5(time()).'.'.pathinfo($clientFileName, PATHINFO_EXTENSION);;
        $res = $img_file->moveTo(EASYSWOOLE_ROOT.'/../store.19diandian.com/public/uploads/dot/'.$fileName);
        if($res===true){
            $config = Config::getInstance()->getConf('www');
            $data['imgurl'] = '/public/uploads/dot/'.$fileName;
            $data['show_imgurl'] = $config['host_img'].'/public/uploads/dot/'.$fileName;
            $this->writeJson(Status::CODE_OK, $data, 'success');
        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST,[], '文件上传失败');
        }
        return true;
    }

    /**
     * 发送短信验证码
     * @return bool
     */
    public function getPasswordCode(){
        $dot = DotModel::create()->where('id',$this->dot_id)->get();
        $mobile =$dot['username'];
        $code = rand(0000,9999);
        $sms = new Sms();
        $res = $sms->sendCode($mobile,$code);
        if(!$res){ $this->writeJson(Status::CODE_BAD_REQUEST, $res, '验证码发送失败');return true; }
        Cache::getInstance()->unset('psw_'.$this->dot_id);
        Cache::getInstance()->set('psw_'.$this->dot_id,$code,5*60);
        $this->writeJson(Status::CODE_OK, $res, '验证码发送成功');
        return true;
    }
    /**
     * 更新密码
     */
    public function updatePassword(){
        DotModel::create()->where('id',$this->dot_id)->get();
        $password = $this->param['password'];
        $re_passowrd = $this->param['re_password'];
        $code = $this->param['code'];
        $psw_code = Cache::getInstance()->get('psw_1');
        if(empty($password)){ $this->writeJson(Status::CODE_NOT_FOUND, [], '请输入密码');return false;}
        if($code!=$psw_code){$this->writeJson(Status::CODE_NOT_FOUND, [], '验证码不正确'.$psw_code.'psw_'.$this->dot_id);return false;}
        if($password!=$re_passowrd){ $this->writeJson(Status::CODE_NOT_FOUND, [], '两次输入的密码不一致');return false;}
        DotModel::create()->where('id',$this->dot_id)->update(['password'=>md5($password.'pswstr'),'update_time'=>time()]);
        Cache::getInstance()->unset('psw_'.$this->dot_id);
        $this->writeJson(Status::CODE_OK, [], '密码修改成功');
        return true;
    }

    /**
     * 收款账号
     */
    public function account(){

        $code  =Cache::getInstance()->get('login_13662829560');
        $account = DotAccountModel::create()->where('dot_id',$this->dot_id)->get();
        $this->writeJson(Status::CODE_OK, $account, 'success'.$code);
        return true;
    }

    /**
     * @param null|string $action
     */
    public function getServiceTel(){
            $system = SystemModel::create()->where('id',1)->findOne();
            $this->writeJson(Status::CODE_OK, ['tel'=>$system['tel']], 'success');
    }
    protected function getValidateRule(?string $action): ?Validate
    {
        // TODO: Implement getValidateRule() method.
        switch ($action) {
            case 'saveDotInfo':
                $valitor = new Validate();
                $valitor->addColumn('u_account')->required();
                $valitor->addColumn('u_password')->required();
                return $valitor;
                break;
        }
        return NULL;
    }

    function onException(\Throwable $throwable): void
    {
        var_dump($throwable->getMessage());
    }

}

