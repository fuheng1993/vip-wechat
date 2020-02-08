<?php

namespace App\HttpController\Api;

use App\Model\Category\CategoryModel;
use App\Model\DotGoods\DotGoodsModel;
use App\Model\Goods\GoodsModel;
use App\Model\GoodsImages\GoodsImagesModel;
use App\Model\GoodsSpec\GoodsSpecModel;
use App\Model\GoodsSpecRel\GoodsSpecRelModel;
use App\Model\Order\OrderModel;
use App\Model\System\SiamSystemModel;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\Config;
use EasySwoole\Http\Annotation\Param;
use EasySwoole\Http\Message\Status;
use EasySwoole\Jwt\Jwt;
use EasySwoole\Validate\Validate;

/**
 * Class Users
 * Create With Automatic Generator
 */
class Goods extends Base
{
    /**
     * 商品统计
     * @return bool
     */
    public function getGoodsChart(){
        $model = new GoodsModel();
        $goods_num = $model->where('store_id',$this->store_id)->where('status',1)->count();
        $must_goods_num =$model->where('store_id',$this->store_id)->where('status',1)->where('is_must',1)->count();
        $DotGoods = new DotGoodsModel();
        $choose_goods_num = $DotGoods->where('status=1 and dot_id='.$this->dot_id)->count();
        $data = ['goods_num'=>$goods_num,'must_goods_num'=>$must_goods_num,'choose_goods_num'=>$choose_goods_num];
        $this->writeJson(Status::CODE_OK, $data, 'success'.$this->dot_id);
        return true;
    }
    /**
     * 商品列表
     * @return array
     */
    public function getGoodsList(){
        $param = $this->request()->getRequestParam();
        $store_id  = $this->store_id;
        $page  = !empty($param['p'])?$param['p']:1;
        $limit = !empty($param['limit'])?$param['limit']:10;
        $model = new GoodsModel();
        $model->where('g.store_id', $store_id);
        if(!empty($param['is_must'])){ //1只显示必须商品 2不显示必选商品
            $model->where('g.is_must',$param['is_must']==1?1:0);
        }
        if(!empty($param['is_choose'])){ //1只显示已选择的商品 2只显示未选中的商品
            $choose_ids = DotGoodsModel::create()->where('dot_id',$this->dot_id)->column('goods_id');
            $choose_ids[] = 0;
            $choose_ids = implode(',',$choose_ids);
            if($param['is_choose']==1){
                $model->where('g.id in('.$choose_ids.')');
            }else{
                $model->where('g.id not in('.$choose_ids.')');
            }

        }
        if(!empty($param['brand_id'])){$model->where('g.brand_id',$param['brand_id']);}
        if(!empty($param['name'])){
            $model->where('g.name',"%{$param['name']}%",'like');
        }
        if(!empty($param['category_id'])){
            $cids = CategoryModel::create()->where('parent_id',$param['category_id'])->field('id')->getAll();
            $cids = array_column($cids,'id');
            if(is_array($cids)){
                $cids[] =$param['category_id'];
                $model->where('g.category_id in('.implode(',',$cids).')');
            }else{
                $model->where('g.category_id',$param['category_id']);
            }
        }

        $model->where('g.status=1');  //上架商品
        $field =       'g.id,g.name,g.status,g.spec_type,category_id,g.content,(g.sales_initial+g.sales_actual) as sales_volume,c.name as category,b.name as brand,g.is_must,s.store_name,g.store_id';
        $list = $model->withTotalCount()->alias('g')->field($field)
            ->join('td_category c','c.id=g.category_id','LEFT')
            ->join('td_brand b','b.id=g.brand_id','LEFT')
            ->join('td_store s','s.id=g.store_id','LEFT')
            ->limit($limit*($page-1), $limit)->order('g.is_hot,g.is_new,g.id','desc')->all();
        $total = $model->lastQueryResult()->getTotalCount();;
        $config = Config::getInstance()->getConf('www');
        foreach ($list  as $k=>$v){
            $list[$k]['content'] =str_replace('src="/public/','src="'.$config['host_img'].'/public/',$v['content']);
            /*****  规格信息 ******/
            $goods_spec = GoodsSpecRelModel::create()->alias('g')->field('g.spec_id,g.spec_value_id,s.spec_name ,v.value as spec_value')
                ->join('td_spec s','s.id=g.spec_id','LEFT')
                ->join('td_spec_value v','v.id=g.spec_value_id','LEFT')
                ->where('g.goods_id',$v['id'])->order('g.spec_id','asc')->select();
            $speclist = [];
            foreach ($goods_spec as $k1=>$v1){
                $speclist[$v1['spec_id']]['id'] = $v1['spec_id'];
                $speclist[$v1['spec_id']]['name'] = $v1['spec_name'];
                $speclist[$v1['spec_id']]['list'][] = $v1;
            }
            $spec = GoodsSpecModel::create()->field('id,goods_no,goods_price,line_price,stock_num,goods_sales,goods_unit,goods_weight,spec_sku_id')->where('goods_id',$v['id'])->select();
            $list[$k]['speclist'] = array_values($speclist);
            $list[$k]['spec'] = array_values($spec);
            /*****  图片信息 ******/
            $imgs  = GoodsImagesModel::create()->where('goods_id',$v['id'])->field('concat("'.$config['host_img'].'",imgurl) as imgurl')->order('sort,id','asc')->select();
            $list[$k]['imgs'] = $imgs;

            /*****  是否已上架 ******/
            $choose = DotGoodsModel::create()->where('goods_id',$v['id'])->where('dot_id',$this->dot_id)->findOne();
            $list[$k]['is_choose'] = !empty($choose['status'])?1:0;

        }
        $data  =['total' => $total, 'list' => $list,'sql'=>$model->lastQuery()->getLastQuery(),'page'=>$page,'limit'=>$limit];
        $this->writeJson(Status::CODE_OK, $data, 'success');
        return true;

    }
    /**
     * 添加所有必选商品
     */
    public function updateMustGoods(){
        $model = new GoodsModel();
        $goods = $model->where('store_id=1 and is_must=1 and status=1')->field('id')->all();
        $dot_goods_model = new DotGoodsModel();
        foreach ($goods as $k=>$v){
            $data['goods_id'] = $v['id'];
            $data['dot_id'] = $this->dot_id;
            $data['store_id'] = $this->store_id;
            $data['status'] = 1;
            $data['create_time'] = time();
            $data['update_time'] = time();
            if(!DotGoodsModel::create()->where('dot_id',$this->dot_id)->where('goods_id',$v['id'])->findOne()){
                DotGoodsModel::create()->data($data)->save();
            }
        }
        $this->writeJson(Status::CODE_OK, $goods, 'success');
        return true;
    }
    /**
     * 上架商品
     * @return bool
     */
    public function goodsUp(){
        $param = $this->request()->getRequestParam();
        if($param['goods_id']){
            $model = new DotGoodsModel();
            if($model->where('goods_id',$param['goods_id'])->where('dot_id',$this->dot_id)->where('store_id',$this->store_id)->select()){
                $model->where('goods_id',$param['goods_id']) ;
                $model->where('dot_id',$this->dot_id) ;
                $model->where('store_id',$this->store_id) ;
                $data['status'] = 1;
                $data['update_time'] = time();
                $where['goods_id'] = $param['goods_id'];
                $where['dot_id'] = $this->dot_id;
                $where['store_id'] = $this->store_id;
                $res = DotGoodsModel::create()->update($data,$where);
            }else{
                $model = DotGoodsModel::create([
                    'dot_id' => $this->dot_id,
                    'store_id' => $this->store_id,
                    'goods_id'  => $param['goods_id'],
                ]);
                $model->dot_id = $this->dot_id;
                $model->store_id = $this->store_id;
                $model->goods_id = $param['goods_id'];
                $model->create_time=time();
                $model->update_time=time();
                $res = $model->save();
            }
            if(!$res){
                $this->writeJson(Status::CODE_BAD_REQUEST, [], '商品上架失败'); return false;
            }
            $this->writeJson(Status::CODE_OK, [], '商品上架成功');
        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST, $param, '商品ID必须');
        }
        return true;
    }
    /**
     * 下架商品
     * @return bool
     */
    public function goodsDown(){
        $param = $this->request()->getRequestParam();
        if($param['goods_id']){
            $goods = GoodsModel::create()->where('id',$param['goods_id'])->findOne();
            if($goods['is_must']==1){ $this->writeJson(Status::CODE_OK, [], '必须商品不可下架下'); return false;}
            $model = new DotGoodsModel();
            $data['status'] = 0;
            $data['update_time'] = time();
            $where['goods_id'] = $param['goods_id'];
            $where['dot_id'] = $this->dot_id;
            $where['store_id'] = $this->store_id;
            $res =$model->update($data,$where);

            if($res){
                $this->writeJson(Status::CODE_OK, [], '商品下架成功');
            }else{
                $this->writeJson(Status::CODE_BAD_REQUEST, [], '商品下架失败');
            }

        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST, [], '商品ID必须');
        }
        return true;
    }
    /**
     * 商品详情
     */
    public function getGoodsDetails(){
        $param = $this->request()->getRequestParam();
        $goods_id  = $param['goods_id'];
        $model = new GoodsModel();
        $model->where('g.id', $goods_id);
        $field =       'g.id,g.name,g.status,g.spec_type,category_id,g.content,(g.sales_initial+g.sales_actual) as sales_volume,c.name as category,b.name as brand,g.is_must,s.store_name,g.store_id';
        $list = $model->withTotalCount()->alias('g')->field($field)
            ->join('td_category c','c.id=g.category_id','LEFT')
            ->join('td_brand b','b.id=g.brand_id','LEFT')
            ->join('td_store s','s.id=g.store_id','LEFT')
           ->findOne();

        $config = Config::getInstance()->getConf('www');
        $list['content'] =str_replace('src="/public/','src="'.$config['host_img'].'/public/',$list['content']);
        /*****  规格信息 ******/
        $goods_spec = GoodsSpecRelModel::create()->alias('g')->field('g.spec_id,g.spec_value_id,s.spec_name ,v.value as spec_value')
            ->join('td_spec s','s.id=g.spec_id','LEFT')
            ->join('td_spec_value v','v.id=g.spec_value_id','LEFT')
            ->where('g.goods_id',$list['id'])->order('g.spec_id','asc')->select();
        $speclist = [];
        foreach ($goods_spec as $k1=>$v1){
            $speclist[$v1['spec_id']]['id'] = $v1['spec_id'];
            $speclist[$v1['spec_id']]['name'] = $v1['spec_name'];
            $speclist[$v1['spec_id']]['list'][] = $v1;
        }
        $spec = GoodsSpecModel::create()->field('id,goods_no,goods_price,line_price,stock_num,goods_sales,goods_unit,goods_weight,spec_sku_id')->where('goods_id',$list['id'])->select();
        $list['speclist'] = array_values($speclist);
        $list['spec'] = array_values($spec);
        /*****  图片信息 ******/
        $imgs  = GoodsImagesModel::create()->where('goods_id',$list['id'])->field('concat("'.$config['host_img'].'",imgurl) as imgurl')->order('sort,id','asc')->select();
        $list['imgs'] = $imgs;

        /*****  是否已上架 ******/
        $choose = DotGoodsModel::create()->where('goods_id',$list['id'])->where('dot_id',$this->dot_id)->findOne();
        $list['is_choose'] = !empty($choose['status'])?1:0;
        $data  =$list;
        $this->writeJson(Status::CODE_OK, $data, 'success');
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

