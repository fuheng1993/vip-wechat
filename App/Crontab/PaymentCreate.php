<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/14
 * Time: 19:09
 */
namespace App\Crontab;

use App\Model\DotPayToAccount\DotPayToAccountModel;
use App\Model\Order\OrderModel;
use App\Model\OrderAddress\OrderAddressModel;
use App\Model\OrderGoods\OrderGoodsModel;
use App\Task\Task;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Task\TaskManager;
use EasySwoole\ORM\DbManager;
use \Swoole\Coroutine as co;

class PaymentCreate extends AbstractCronTask
{

    public static function getRule(): string
    {
        return '*/1 * * * *';
    }

    public static function getTaskName(): string
    {
        return  'PaymentCreate';
    }

    function run(int $taskId, int $workerIndex)
    {
        echo date('Y-m-d H:i:s',time()).':'.$taskId.'-'.$workerIndex.PHP_EOL;
        $orderModel = new OrderModel();
        $field = "store_id,dot_id,date_format(from_unixtime(`finish_time`),'%Y-%m-%d') as date,
        sum(coupon_money) as coupon_money,
        sum(total_money) as total_money,
        sum(pay_money) as pay_money,
        sum(deliver_express_money) as deliver_express_money,
        sum(platform_service_money) as platform_service_money";
        $list = $orderModel->field($field)->where('(is_finish=1 and dot_id>0 and store_id=1 and is_cancel=0 and already_pay=0)')->group('dot_id,date')->order('finish_time','asc')->limit(1,10)->select();
        $num = count($list);
       // echo $orderModel->lastQuery()->getLastPrepareQuery(). $num;
        if($num>0){
            $chanel = new co\Channel($num);
            $wait = new \EasySwoole\Component\WaitGroup($num); //协程等待
            foreach ($list as $k=>$v){
                $wait->add(); //等待增量1
                $i = $k+1;
                go(function ()use ($chanel,$wait,$i,$v){
                    DbManager::getInstance()->startTransaction();
                    $OrderModel = new OrderModel();
                    $data['out_biz_no'] = '';
                    $data['pay_date'] = '';
                    $data['order_id']='';
                    $data['payee_type']='';
                    $data['payee_account']='';
                    $data['payer_show_name']='';
                    $data['payee_real_name']='';
                    $data['coupon_money']=$v['coupon_money'];
                    $data['total_money']=$v['total_money'];
                    $data['pay_money']=$v['pay_money'];
                    $data['platform_service_money']=$v['platform_service_money'];
                    $data['deliver_express_money']= $v['deliver_express_money'];
                    $data['amount']=$v['pay_money']-$v['platform_service_money']-$v['deliver_express_money'];
                    $data['remark']=$v['date'].'营业额结算';
                    $data['status']=1;
                    $data['result']=$v['date'].'营业额结算';
                    $data['pay_time']=0;
                    $data['dot_id']=$v['dot_id'];
                    $data['store_id']=1;
                    $data['create_date']=$v['date'];
                    $data['create_time']=time();
                    $DotPayToAccountModel= new DotPayToAccountModel();
                    if($DotPayToAccountModel->data($data)->save()){
                        $payment_id = $DotPayToAccountModel->lastQueryResult()->getLastInsertId();
                        $start = strtotime($v['date'].' 00:00:00');
                        $end = strtotime($v['date'].' 23:59:59');
                        $OrderModel->where('finish_time>='.$start.' and finish_time<='.$end)->where('dot_id='.$v['dot_id'])->where('store_id='.$v['store_id'])
                            ->update(['already_pay'=>1,'already_pay_time'=>time(),'payment_id'=>$payment_id]);
                        echo '事务执行操作成功'.PHP_EOL;
                        DbManager::getInstance()->commit();
                    }else{
                        echo '事务回滚操作成功'.PHP_EOL;
                        DbManager::getInstance()->rollback();
                    }
//                    rand();
//                    $date = ['2019-08-01','2019-08-02','2019-08-03','2019-08-04','2019-08-05','2019-08-06','2019-08-07','2019-08-08','2019-08-09','2019-08-10','2019-08-11','2019-08-12','2019-08-13','2019-08-14','2019-08-15','2019-08-16','2019-08-17','2019-08-18','2019-08-19','2019-08-20','2019-08-21','2019-08-22','2019-08-23','2019-08-24','2019-08-25','2019-08-26','2019-08-27','2019-08-28','2019-08-29','2019-08-30','2019-08-31'];
//                    $id=72;
//                    $orderModel = new OrderModel();
//                    $orderGoodsModel = new OrderGoodsModel();
//
//                    $order = $orderModel->field('*')->where('id',$id)->findOne();
//                    $orderGoods =$orderGoodsModel->where('order_id',$order['id'])->select();
//                    $time = strtotime($date[array_rand($date)].' '.date('H:i:s',time()));
//                    $order['order_no'] = date('YmdHis',$time).rand(100000,9999999).$id;
//                    $order['dot_time'] = $order['create_time'] = $order['update_time'] = $order['finish_time'] = $order['send_time']= $order['confirm_time'] = $order['pay_time'] = $time;
//                    unset($order['id']);
//                    $orderModel->id=0;
//                    $orderModel->data($order)->save();
//                    $orderModel->lastQueryResult()->getLastInsertId();
//                    $order_id = $orderModel->lastQueryResult()->getLastInsertId();
//                    foreach ($orderGoods as $k=>$v){
//                        $v['order_id'] = $order_id;
//                        unset($v['id']);
//                        $orderGoodsModel->data($v)->save();
//                    }
//                    $order_address =OrderAddressModel::create()->where('order_id',$id)->findOne();
//                    $order_address['order_id'] = $order_id;unset($order_address['id']);
//                    OrderAddressModel::create()->data($order_address)->save();
//                    echo '新增订单成功:'.$order_id.PHP_EOL;
                    $chanel->push(['index'=>$i,'date'=>$v['date']]);//内容
                    $wait->done(); //等待减少1
                });
            }
            $wait->wait(); //等待为0时执行以下代码
            echo '---------协程-开始---------'.PHP_EOL;
            while (true){
                if($chanel->isEmpty()){break;}
                $res = $chanel->pop();
                echo $res['index'].'-'.$res['date'].PHP_EOL;
            }
            echo '---------协程-结束---------'.PHP_EOL;
        }else{
            echo '---------无协程---------'.PHP_EOL;
        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        echo $throwable->getMessage();
    }
}