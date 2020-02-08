<?php

namespace App\HttpController\Api;

use App\Model\Deliver\DeliverModel;
use App\Model\DotPayToAccount\DotPayToAccountModel;
use App\Model\Member\MemberModel;
use App\Model\Order\OrderModel;
use App\Model\OrderAddress\OrderAddressModel;
use App\Model\Store\StoreModel;
use App\Model\OrderDeliver\OrderDeliverModel;
use App\Model\OrderGoods\OrderGoodsModel;
use EasySwoole\EasySwoole\Config;
use EasySwoole\Http\Annotation\Param;
use EasySwoole\Http\Message\Status;
use EasySwoole\Jwt\Jwt;
use EasySwoole\Validate\Validate;

/**
 * Class Users
 * Create With Automatic Generator
 */
class Chart extends Base
{
    /**
     * 订单列表
     * @return bool
     */
    public function getPaymentList()
    {
        $page  = !empty($this->param['p'])?$this->param['p']:1;
        $limit = !empty($this->param['limit'])?$this->param['limit']:10;
        $model = new DotPayToAccountModel();
        $where ='dot_id='.$this->dot_id;
        if (!empty($this->param['status'])){

            if($this->param['status']==2){
                $where.=' and  status=2';
            }else{
                $where.=' and  (status=1 or status=3)';
            }
        }
       if (!empty($this->param['start_date'])&&!empty($this->param['end_date'])){
            $start = strtotime($this->param['start_date'].' 00:00:00');
            $end = strtotime($this->param['end_date'].' 23:59:59');
           $where.=' and create_time>='.$start.' and create_time<='.$end;
        }else{
            if (!empty($this->param['start_date'])){  $start = strtotime($this->param['start_date'].' 00:00:00'); $where.=' and create_time>='.$start;}
            if (!empty($this->param['end_date'])){  $end = strtotime($this->param['end_date'].' 23:59:59'); $where.=' and create_time<='.$end;}
        }
        $field ='*';
        $list =  $model->withTotalCount()->where($where)->field($field)->limit($limit*($page-1), $limit)->order('id','desc')->all();
        //$sql = $model->lastQuery()->getLastPrepareQuery();;

        $total = $model->lastQueryResult()->getTotalCount();;
        $sum = $model->where($where)->field('sum(deliver_express_money) as deliver_express_money,sum(platform_service_money) as platform_service_money,sum(pay_money) as pay_money,sum(amount) as amount')->findOne();
        $chart['deliver_express_money'] = $sum['deliver_express_money']+0;
        $chart['platform_service_money'] = $sum['platform_service_money']+0;
        $chart['service_money'] = $sum['deliver_express_money']+$sum['platform_service_money']+0;
        $chart['amount'] = $sum['amount']+0;
        $chart['pay_money'] = $sum['pay_money']+0;
        $data  =['total' => $total, 'list' => $list,'chart'=>$chart];
        $this->writeJson(Status::CODE_OK, $data, 'success');
        return true;
    }
    /**
     * 每日结账详情
     * @return bool
     */
    public function getPaymentDetails(){
        if(!empty($this->param['payment_id'])){
            $pay_to_account = DotPayToAccountModel::create()->where('id='.$this->param['payment_id'])->findOne();
            $page  = !empty($this->param['p'])?$this->param['p']:1;
            $limit = !empty($this->param['limit'])?$this->param['limit']:10;
            $model = new OrderModel();
            $model->where('payment_id='.$this->param['payment_id'].' and o.already_pay=1 and o.is_pay=1 and o.dot_id='.$this->dot_id);
            $field ='o.id,o.order_type,o.order_no,o.order_status,o.user_id,o.store_id,o.remark,o.coupon_id,o.coupon_money,o.total_num,o.express_money,o.total_money,o.pay_way,o.pay_money,o.is_send,o.send_time,o.is_confirm,o.confirm_time,o.is_pay,o.create_time,o.dot_id,o.is_cancel,o.platform_service_money,o.deliver_express_money,o.deliver_id,already_pay_time,finish_time'; //订单信息
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
//                if($v['is_cancel']==1){
//                    $list[$k]['order_status'] = 4;  //未付款
//                }else if($v['is_confirm']){
//                    $list[$k]['order_status'] = 3;   //已完成
//                }else if($v['is_send']){
//                    $list[$k]['order_status'] = 2;  //配送中
//                }else if($v['is_pay']){
//                    $list[$k]['order_status'] = 1;  //新订单
//                }
            }
            $total = $model->lastQueryResult()->getTotalCount();;
            $data  =['total' => $total, 'list' => $list,'details'=>$pay_to_account];
            $this->writeJson(Status::CODE_OK, $data, 'success');
            return true;
        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST, [], '订单ID必须');
            return false;
        }

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

}

