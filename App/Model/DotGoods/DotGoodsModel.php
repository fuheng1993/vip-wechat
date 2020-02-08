<?php

namespace App\Model\DotGoods;

/**
 * Class SiamAuthModel
 * Create With Automatic Generator
 * id
goods_id
dot_id
store_id
status
create_time
update_time

 * @property $goods_id int | 商品ID
 * @property $dot_id string | 网点ID
 * @property $store_id string | 店铺ID
 * @property $status string | 是否上架
 * @property $create_time int | 创建时间
 * @property $update_time int | 更新时间
 */
class DotGoodsModel extends \App\Model\BaseModel
{
	protected $tableName = 'td_dot_goods';


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
}

