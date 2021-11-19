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
        $total    = \think\facade\Db::name("article")->where("`status`=1 and type_id in (6,11)")->count();
        $Page     = new \lib\Page($total, 10, 0);
        $limitStr = $Page->limit();
        $first    = explode(",", $limitStr)[0];
        $limit    = explode(",", $limitStr)[1];
        $data     = \think\facade\Db::name("article")->field("a.*,c.cate_name,c.cate_en")->alias("a")->leftJoin("category c",
            "c.id = a.type_id")->where("`status`=1 and type_id in (6,11)")->limit($first, $limit)->select()->toArray();
        $pages    = $Page->pages($total);
        dump($data);
    }

    public function test()
    {

        return $this->fetch();
    }
}