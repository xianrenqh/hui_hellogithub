<?php

/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-29
 * Time: 下午3:25:06
 * Info:
 */

namespace addons\loginbg;

use think\Addons;

class Plugin extends Addons
{

    //安装
    public function install()
    {
        return true;
    }

    //卸载
    public function uninstall()
    {
        return true;
    }

    public function admin_login_style()
    {
        $config = $this->getConfig();
        if ($config['mode'] == 'random' || $config['mode'] == 'daily') {
            $gettime     = $config['mode'] == 'random' ? mt_rand(-1, 7) : 0;
            $json_string = file_get_contents('https://www.bing.com/HPImageArchive.aspx?format=js&idx='.$gettime.'&n=1');
            $data        = json_decode($json_string);
            $background  = "https://www.bing.com".$data->{"images"}[0]->{"urlbase"}."_1920x1080.jpg";
        } else {
            $background = $config['pic'];
        }
        $this->assign('background', $background);

        return $this->fetch('loginbg');
    }

}