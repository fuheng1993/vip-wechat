<?php

namespace App\Model\Order;

/**
 * Class SiamAuthModel
 * Create With Automatic Generator
 * @property $auth_id int | 权限id
 * @property $auth_name string | 权限名
 * @property $auth_rules string | 路由地址
 * @property $auth_icon string | 图标
 * @property $auth_type int | 权限类型 0菜单1按钮
 * @property $create_time int | 创建时间
 * @property $update_time int | 更新时间
 */
class OrderModel extends \App\Model\BaseModel
{
	protected $tableName = 'td_order';


	/**
	 * @getAll
	 * @param  int  $page  1
	 * @param  int  $pageSize  10
	 * @param  string  $field  *
	 * @return array[total,list]
	 */
	public function getAll(int $page = 1, int $pageSize = 10, string $field = '*'): array
	{
		$list = $this
		    ->withTotalCount()
			->order($this->schemaInfo()->getPkFiledName(), 'DESC')
		    ->field($field)
		    ->limit($pageSize * ($page - 1), $pageSize)
		    ->all();
		$total = $this->lastQueryResult()->getTotalCount();;
		return ['total' => $total, 'list' => $list];
	}

    /***
     * 获取器
     * @param $value
     * @param $data
     * @return string
     */

    public function getCancelTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
    public function getConfirmTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
    public function getSendTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
    public function getPayTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
    public function getFinishTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
    public function getCreateTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
    public function getUpdateTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
}

