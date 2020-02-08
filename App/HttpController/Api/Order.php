<?php

namespace App\HttpController\Api;

use App\Model\Deliver\DeliverModel;
use App\Model\DeliverRule\DeliverRuleModel;
use App\Model\Member\MemberModel;
use App\Model\Order\OrderModel;
use App\Model\OrderAddress\OrderAddressModel;
use App\Model\OrderDeliver\OrderDeliverModel;
use App\Model\OrderGoods\OrderGoodsModel;
use App\Model\Store\StoreModel;
use EasySwoole\EasySwoole\Config;
use EasySwoole\Http\Annotation\Param;
use EasySwoole\Http\Message\Status;
use EasySwoole\Jwt\Jwt;
use EasySwoole\Validate\Validate;

/**
 * Class Users
 * Create With Automatic Generator
 */
class Order extends Base
{
    /**
     * 订单列表
     * @return bool
     */
    public function getOrderList()
    {
        $param = $this->request()->getRequestParam();
        $page  = !empty($param['p'])?$param['p']:1;
        $limit = !empty($param['limit'])?$param['limit']:10;
        $model = new OrderModel();
        if (!empty($param['date'])){
            $start = strtotime($param['date'].' 00:00:00');$end = strtotime($param['date'].' 23:59:59');
            $model->where('pay_time>='.$start.' and pay_time<='.$end);
        }
        if (isset($param['order_status'])){$model->where('order_status='.$param['order_status']);}
        $model->where('dot_id',$this->dot_id);
        $model->where('is_pay',1);
        $model->where('is_cancel',0);
        $field ='o.id,o.order_type,o.order_no,o.order_status,o.user_id,o.store_id,o.remark,o.coupon_id,o.coupon_money,o.total_num,o.express_money,o.total_money,o.pay_way,o.pay_money,o.is_send,o.send_time,o.is_confirm,o.confirm_time,o.finish_time,o.is_pay,o.create_time,o.dot_id,o.is_cancel,o.platform_service_money,o.deliver_express_money,o.deliver_id'; //订单信息
        $list = $model->withTotalCount()->alias('o')->field($field)->limit($limit*($page-1), $limit)->order('o.id','desc')->all();
        $config = Config::getInstance()->getConf('www');
        foreach ($list as $k=>$v){
            $fields = 'd.goods_id,d.goods_name,d.goods_attr,d.goods_no,goods_unit,d.goods_price,d.line_price,d.total_num,d.total_money,img.imgurl';
            $goods =OrderGoodsModel::create()->alias('d')->field($fields)->where('d.order_id',$v['id'])->join('(select goods_id,imgurl from td_goods_images GROUP by goods_id order by sort,id asc )img','img.goods_id=d.goods_id','LEFT')->order('d.id','desc')->select();
            foreach ($goods as $key=>$val){
                $val['imgurl'] = $val['imgurl']?$val['imgurl']:'/public/static/images/404.png';
                $goods[$key]['imgurl'] =   $config['host_img'].$val['imgurl'];
            }
            $list[$k]['goods'] = $goods;//OrderGoodsModel::create()->where('order_id',$v['id'])->select();
            $list[$k]['member'] = MemberModel::create()->where('id='.$v['user_id'])->field('id,username,nickname')->findOne();
            $list[$k]['store'] =StoreModel::create()->where('id='.$v['store_id'])->field('id,store_name')->findOne(); ;
            $list[$k]['address'] = OrderAddressModel::create()->where('order_id='.$v['id'])->field('name,sex,mobile,address,lng,lat')->findOne();
            $list[$k]['deliver'] =$v['deliver_id']? DeliverModel::create()->where('id='.$v['deliver_id'])->field('id,name,mobile,username')->findOne():[  "name"=>'', "mobile"=>'','username'=>''];
//            if($v['is_cancel']==1){
//                $list[$k]['order_status'] = 4;  //未付款
//            }else if($v['is_confirm']){
//                $list[$k]['order_status'] = 3;   //已完成
//            }else if($v['is_send']){
//                $list[$k]['order_status'] = 2;  //配送中
//            }else if($v['is_pay']){
//                $list[$k]['order_status'] = 1;  //新订单
//            }
        }
        $total = $model->lastQueryResult()->getTotalCount();;
        $data  =['total' => $total, 'list' => $list];
        $this->writeJson(Status::CODE_OK, $data, 'success');
        return true;
    }
    /**
     * 订单详情
     * @return bool
     */
    public function getOrderGoods(){
        if($this->param['order_id']){
            $model = new OrderModel();
            $field ='o.id,o.order_type,o.order_no,o.order_status,o.user_id,o.store_id,o.remark,o.coupon_id,o.coupon_money,o.total_num,o.express_money,o.total_money,o.pay_way,o.pay_money,o.is_send,o.send_time,o.is_confirm,o.confirm_time,o.is_pay,o.create_time,o.dot_id,o.platform_service_money,o.deliver_express_money,o.deliver_id'; //订单信息
            $field.=',a.name,a.sex,a.mobile,a.address,a.lng,a.lat'; //收货地址
            $field.=',m.username as member_username,m.nickname as member_nickname,m.id as member_id'; //用户信息
            $field.=',s.store_name'; //店名
            $field.=',d.name as deliver_name,d.mobile as deliver_mobile,d.username as deliver_username'; //骑手
            $data = $model->alias('o')->where('o.id',$this->param['order_id'])
                ->join('td_store s','s.id = o.store_id','LEFT')
                ->join('td_member m','m.id = o.user_id','LEFT')
                ->join('td_order_address a','a.order_id = o.id','LEFT')
                ->join('td_deliver d','d.id = o.deliver_id','LEFT')
                ->field($field)->get();
            /****订单商品列表 开始****/
            $fields = 'd.goods_id,d.goods_name,d.goods_attr,d.goods_no,goods_unit,d.goods_price,d.line_price,d.total_num,d.total_money,img.imgurl';
            $goods =OrderGoodsModel::create()->alias('d')->field($fields)->where('d.order_id',$this->param['order_id'])->join('(select goods_id,imgurl from td_goods_images GROUP by goods_id order by sort,id asc )img','img.goods_id=d.goods_id','LEFT')->order('d.id','desc')->select();
            $config = Config::getInstance()->getConf('www');
            foreach ($goods as $key=>$val){
                $val['imgurl'] = $val['imgurl']?$val['imgurl']:'/public/static/images/404.png';
                $goods[$key]['imgurl'] =   $config['host_img'].$val['imgurl'];
            }
            $data['goods'] = $goods;// $data['goods'] = OrderGoodsModel::create()->where('order_id',$data['id'])->select();
            /****订单商品列表 结束****/

            $data['member'] = [  "id"=>$data['user_id'], "username"=>$data['member_username'], "nickname"=>$data['member_nickname']];
            $data['store'] = [  "id"=>$data['store_id'], "store_name"=>$data['store_name']];
            $data['address'] = [  "name"=>$data['name'], "sex"=>$data['sex'],'mobile'=>$data['mobile'],'address'=>$data['address'],'lat'=>$data['lat'],'lng'=>$data['lng']];
            $data['deliver'] = [  "name"=>$data['deliver_name'], "mobile"=>$data['deliver_mobile'],'username'=>$data['deliver_username']];
//            if($data['is_cancel']==1){
//                $data['order_status'] = 4;  //已取消
//            }else if($data['is_confirm']){
//                $data['order_status'] = 3;   //已完成
//            }else if($data['is_send']){
//                $data['order_status'] = 2;  //配送中
//            }else if($data['is_pay']){
//                $data['order_status'] = 1;  //新订单
//            }
            $this->writeJson(Status::CODE_OK, $data, 'success');
        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST, [], '订单ID必须');
            return false;
        }

        return true;
    }
    /**
     * 订单开始配送
     */
    public function orderIsSend(){
        if(!empty($this->param['order_id'])){
            $model = new OrderModel();
            $res  = $model->where('id',$this->param['order_id'])->update(['order_status'=>2,'is_send'=>1,'send_time'=>time()]);
            $this->writeJson(Status::CODE_OK, $res, '订单开始配送');
        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST, [], '订单ID必须');
            return false;
        }
        return true;
    }

    /**
     * 订单完成
     */
    public function finishOrder(){
        if(!empty($this->param['order_id'])){
            $model = new OrderModel();
            $order = $model->where('id',$this->param['order_id'])->findOne();
            if($order['deliver_id']>0){
                $this->writeJson(Status::CODE_BAD_REQUEST, [], '骑手配送订单必须由骑手完成');return false;
            }
            if($order['is_send']==0){
                $this->writeJson(Status::CODE_BAD_REQUEST, [], '此订单还未配送');return false;
            }
            $res  = $model->where('id',$this->param['order_id'])->update(['order_status'=>3,'is_finish'=>1,'finish_time'=>time()]);
            $this->writeJson(Status::CODE_OK, $res, '订单完成成功');
        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST, [], '订单ID必须');return false;
        }
        return true;
    }

    /**
     * 呼叫骑手
     */
    public function callDeliver(){
        if(!empty($this->param['order_id'])){
            $order  = OrderModel::create()->where('id='.$this->param['order_id'])->findOne();
            $deliver_id = 1; // 后期获取最近距离且空闲的骑手
            $service_money = $this->getServiceAmount($order['pay_money']);

            $model = new OrderDeliverModel();
            if($order['deliver_id']>0){
                $this->writeJson(Status::CODE_OK, $order, '已经呼叫过骑手了'); return false;

            }
//            if($model->where('order_id',$order['id'])->where('deliver_id>0')->get()){
//                $this->writeJson(Status::CODE_OK, '', '已经呼叫过骑手了'); return false;
//            }

            $model->order_id = $order['id'];
            $model->deliver_id = $deliver_id;
            $model->service_money = $service_money;
            $model->status = 1;
            $model->update_time = time();
            $model->create_time = time();
            $res = $model->save();
            OrderModel::create()->where('id='.$this->param['order_id'])->update(['deliver_id'=>$deliver_id,'deliver_express_money'=>$service_money]);
            $deliver = DeliverModel::create()->where('id',$deliver_id)->field('id,name,mobile,username')->findOne();
            $this->writeJson(Status::CODE_OK, $deliver, '呼叫骑手成功');
        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST, [], '订单ID必须');
            return false;
        }
        return true;
    }
    /**
     * 获取当前订单骑手服务费
     */
    protected function getServiceAmount($amount){
        $rule = DeliverRuleModel::create()->field('fixed_amount,start_amount,step_amount,step_amount_ratio,`explain`')->where('id=1')->findOne();

        if($amount>$rule['fixed_amount']){
            $money = $amount-$rule['fixed_amount'];
            return  $rule['start_amount']+ceil($money/$rule['step_amount'])*($rule['step_amount_ratio']*$rule['step_amount']);
        }else{
            return $rule['start_amount'];
        }
    }
    /**
     * 骑手服务费规则
     */
    public function getDeliverRule(){
        $rule = DeliverRuleModel::create()->field('fixed_amount,start_amount,step_amount,step_amount_ratio,`explain`')->where('id=1')->findOne();
        $this->writeJson(Status::CODE_OK, $rule, 'success');
        return true;
    }
    protected function getValidateRule(?string $action): ?Validate
    {
        // TODO: Implement getValidateRule() method.
        switch ($action) {
            case 'login':
                $valitor = new Validate();
                $valitor->addColumn('u_account')->required();
                $valitor->addColumn('u_password')->required();
                return $valitor;
                break;
        }
        return NULL;
    }
    /**
     * @return bool
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    public function login()
    {
        $user = SiamUserModel::create()->get([
            'u_account' => $this->request()->getRequestParam('u_account'),
        ]);

        if ($user === NULL) {
            $this->writeJson(Status::CODE_NOT_FOUND, new \stdClass(), '用户不存在');
            return FALSE;
        }


        // 生成token
        $config    = Config::getInstance();
        $jwtConfig = $config->getConf('JWT');

        $jwtObject = Jwt::getInstance()
            ->setSecretKey($jwtConfig['key']) // 秘钥
            ->publish();

        $jwtObject->setAlg('HMACSHA256'); // 加密方式
        $jwtObject->setAud("easy_swoole_admin"); // 用户
        $jwtObject->setExp(time()+$jwtConfig['exp']); // 过期时间
        $jwtObject->setIat(time()); // 发布时间
        $jwtObject->setIss($jwtConfig['iss']); // 发行人
        $jwtObject->setJti(md5(time())); // jwt id 用于标识该jwt
        $jwtObject->setNbf(time()); // 在此之前不可用
        $jwtObject->setSub($jwtConfig['sub']); // 主题

        // 自定义数据
        $jwtObject->setData([
            'u_id'   => $user->u_id,
            'u_name' => $user->u_name
        ]);

        // 最终生成的token
        $token = $jwtObject->__toString();

        $this->writeJson(Status::CODE_OK, [
            'token'    => $token,
            'userInfo' => $user->toArray(),
            'authList' => $user->getAuth(),
        ], '登陆成功');
    }
}

