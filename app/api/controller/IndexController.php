<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-06-30
 * Time: 14:35:23
 * Info:
 */

namespace app\api\controller;

/**
 * @title      首页接口
 * @controller api\controller\Index
 * @group      base
 */
class IndexController extends BaseController
{

    public function index()
    {
        $this->success('ok');
    }

    public function test()
    {

    }

}