<?php

namespace App\Model\Category;
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
class CategoryModel extends \App\Model\BaseModel
{
	protected $tableName = 'td_category';

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

    /**
     *
     */
	public function getTree($dot_id){
	    $DotGoodsModel = new DotGoodsModel();
        $category_goods =$DotGoodsModel->field('g.category_id,count(*) as num')
            ->where('dg.status=1 and dg.dot_id='.$dot_id)->alias('dg')
            ->join('td_goods g','g.id=dg.goods_id','LEFT')
            ->group('g.category_id')
            ->select();

        $choose_num = array_column($category_goods,'num','category_id');

        $field='c.id,c.name,c.imgurl,c.parent_id,g.goods_num,c.create_time';
	    $list = $this->order('c.sort,c.id','asc')->alias('c')
            ->join('(select category_id,count(*) as goods_num from td_goods GROUP BY category_id)  g','c.id = g.category_id','LEFT')
            ->field($field)->all();
	    $parent_list = [];$children = [];
	    foreach ($list as $k=>$v){
	        $v['choose_goods_num'] = empty($choose_num[$v['id']])?0:$choose_num[$v['id']];
	        if($v['parent_id']==0){
	            $parent_list[] =$v;
            }else{
                $children[$v['parent_id']][] = $v;
            }
        }
        foreach ($parent_list as $k=>$v){
	        $parent_list[$k]['children'] = empty($children[$v['id']])?[]:$children[$v['id']];
        }
	    return $parent_list?$parent_list:[];
    }
    /***
     * 获取器
     * @param $value
     * @param $data
     * @return string
     */
    protected function getImgurlAttr($value, $data)
    {
        $config = Config::getInstance()->getConf('www');
        return $value?$config['host_img'].$value:'';
    }
    public function getCreateTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
    public function getUpdateTimeAttr($value, $data){return $value?date('Y.m.d H:i:s', $value):'';}
}

