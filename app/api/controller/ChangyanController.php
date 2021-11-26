<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-26
 * Time: 上午9:50:35
 * Info: 畅言评论回推接口
 */

namespace app\api\controller;

class ChangyanController extends BaseController
{

    public function goBack()
    {
        $postData = file_get_contents('php://input');
        //$data     = json_decode($postData, true);
        file_get_contents('cy.txt', $postData, FILE_APPEND);
    }

}