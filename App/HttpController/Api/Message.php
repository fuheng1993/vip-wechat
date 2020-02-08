<?php

namespace App\HttpController\Api;

use App\Model\Dot\DotModel;
use App\Model\DotMessage\DotMessageModel;
use EasySwoole\EasySwoole\Config;
use EasySwoole\Http\Annotation\Param;
use EasySwoole\Http\Message\Status;
use EasySwoole\Jwt\Jwt;
use EasySwoole\Validate\Validate;

/**
 * Class Users
 * Create With Automatic Generator
 */
class Message extends Base
{
    /**
     * 获取店铺信息
     * @return array
     */
    public function getMessageList(){
        $param = $this->request()->getRequestParam();
        $page  = !empty($param['p'])?$param['p']:1;
        $limit = !empty($param['limit'])?$param['limit']:10;
        $model = new DotMessageModel();
        $model->where('(m.dot_id='.$this->dot_id.' or m.dot_id=0 )');
        $field='m.title,m.content,m.order_id,m.type,m.create_time,IFNULL(r.id,0) as is_read'; //店名
        $list = $model->withTotalCount()->alias('m')
            ->join('(select * from td_dot_message_read where dot_id = '.$this->dot_id.') r','r.message_id=m.id','LEFT')
            ->field($field)->limit($limit*($page-1), $limit)->order('m.id','desc')->select();
        $total = $model->lastQueryResult()->getTotalCount();;
        $data  =['total' => $total, 'list' => $list];
        $this->writeJson(Status::CODE_OK, $data, 'success');
        return true;
    }
    /**
     * 未读消息
     * @return bool
     */
    public function getMessageNum(){
        $model = new DotMessageModel();
        $model->where('(m.dot_id='.$this->dot_id.' or m.dot_id=0 )');
        $model->where('isnull(r.id)');
        $list = $model->withTotalCount()->alias('m')
            ->join('(select * from td_dot_message_read where dot_id = '.$this->dot_id.') r','r.message_id=m.id','LEFT')
            ->select();
        $total = $model->lastQueryResult()->getTotalCount();;
        $data  =['total' => $total, 'list' => $list];
        $this->writeJson(Status::CODE_OK, $data, 'success');
        return true;
    }
    /**
     * @return bool
     */
    public function joinTest(){
        $num = DotMessageModel::create()->alias('m')->join('td_dot_message_read r','r.message_id=m.id','LEFT')->count('m.id');
        $this->writeJson(Status::CODE_OK, $num, 'success');

        return true;
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
}

