<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-02-19
 * Time: 18:04:37
 * Info:
 */

namespace app\index\controller;

use app\index\BaseController;
use think\facade\Db;
use think\Container;

class IndexController extends BaseController
{

    public function index()
    {
        return $this->fetch();
    }

    public function p()
    {
        $data = \think\facade\Db::name("category")->field("id,cate_name,parent_id,cate_en,type")->where("parent_id=0 AND `show_in_nav`=1")->order("sort_order ASC")->limit(20)->select()->toArray();
        if ( ! empty($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $data[$i]['url'] = buildCatUrl($data[$i]['cate_en']);
            }
        }

        halt($data);

    }

    public function test()
    {
        return $this->fetch();
    }
}