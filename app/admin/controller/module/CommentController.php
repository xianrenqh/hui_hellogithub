<?php

/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-16
 * Time: 下午2:26:09
 * Info:
 */

namespace app\admin\controller\module;

use app\admin\annotation\ControllerAnnotation;
use app\admin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use think\Exception;
use think\App;

/**
 * @ControllerAnnotation(title="评论管理")
 * Class Node
 * @package app\admin\controller\content
 */
class CommentController extends AdminController
{

    /**
     * @NodeAnotation(title="评论列表")
     */
    public function index()
    {

    }
}