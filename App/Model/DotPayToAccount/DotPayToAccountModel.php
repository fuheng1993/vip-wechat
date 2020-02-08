<?php

namespace App\Model\DotPayToAccount;

/**
 * Class SiamAuthModel
 * Create With Automatic Generator
 * id
 * @property $goods_id int | 商品ID
 * @property $dot_id string | 网点ID
 * @property $store_id string | 店铺ID
 * @property $status string | 是否上架
 * @property $create_time int | 创建时间
 * @property $update_time int | 更新时间
 */
class DotPayToAccountModel extends \App\Model\BaseModel
{
	protected $tableName = 'td_dot_pay_to_account';


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
    public function getUpdateTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
    public function getCreateTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
    public function getPayTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
}

