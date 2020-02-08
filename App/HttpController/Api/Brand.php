<?php

namespace App\HttpController\Api;

use App\Model\Brand\BrandModel;
use App\Model\Goods\GoodsModel;
use EasySwoole\EasySwoole\Config;
use EasySwoole\Http\Annotation\Param;
use EasySwoole\Http\Message\Status;
use EasySwoole\Validate\Validate;

/**
 * Class Users
 * Create With Automatic Generator
 */
class Brand extends Base
{
    /**
     *
     *
     */
    //获取当前分类下的品牌
    public function getBrandList(){
        $param = $this->request()->getRequestParam();
        $store_id = $this->store_id;
        $category_id = $param['category_id'];
        if($category_id){
            $brand_ids = GoodsModel::create()->where('store_id',$store_id)->where('category_id',$param['category_id'])->column('brand_id');
            $list = [];
            if(is_array($brand_ids)){
                $list = BrandModel::create()->where('id in('.implode(',',$brand_ids).')')->field('id,name')->select();
            }
            $this->writeJson(Status::CODE_OK, $list, 'success');
            return true;
        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST, [], '分类ID必须');
            return true;
        }
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

