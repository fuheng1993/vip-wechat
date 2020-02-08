<?php

namespace App\HttpController\Api;

use App\Model\Category\CategoryModel;
use EasySwoole\EasySwoole\Config;
use EasySwoole\Http\Annotation\Param;
use EasySwoole\Http\Message\Status;
use EasySwoole\Jwt\Jwt;
use EasySwoole\Validate\Validate;

/**
 * Class Users
 * Create With Automatic Generator
 */
class Category extends Base
{
    /**
     * 获取商品分类 二级
     * @return bool
     */
     public function getCategory(){
        $store_id  = $this->store_id;
        $model = new CategoryModel();
        $model->where('store_id', $store_id);
        $list = $model->getTree($this->dot_id);
        $this->writeJson(Status::CODE_OK, $list, 'success');
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

