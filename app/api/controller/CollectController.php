<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-12-17
 * Time: 下午5:30:19
 * Info: 采集
 */

namespace app\api\controller;
use app\admin\service\PhpspiderService;

class CollectController
{

    public function get_html()
    {
        $phpSpider = new PhpspiderService();
        $url = "https://gitee.com/explore/program-develop?lang=PHP&order=starred";
        $getContent = $phpSpider->index();
        halt($getContent);
    }


}