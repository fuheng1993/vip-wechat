<?php

namespace App\Model\DeliverRule;
use App\Model\DotGoods\DotGoodsModel;
use EasySwoole\EasySwoole\Config;
use \EasySwoole\Http\Request;

/**
 * Class CategoryModel
 * Create With Automatic Generator
 * @property $id int | 分类ID
 * @property $name string | 分类名称
 * @property $store_id int | 所属店铺
 * @property $sort  int| 排序值
 * @property $imgurl string | 图标
 * @property $parent_id int | 父级ID 0父级 >0下级
 * @property $create_time int | 创建时间
 * @property $update_time int | 更新时间
 */
class DeliverRuleModel extends \App\Model\BaseModel
{
	protected $tableName = 'td_deliver_rule';

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

    public function getCreateTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
    public function getUpdateTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
}

