<?php

namespace addons\test;

use think\Addons;

class Plugin extends Addons
{

    // 该插件的基础信息
    public $info = [
        'name'        => 'test',    // 插件标识
        'title'       => '插件测试',    // 插件名称
        'description' => 'thinkph6插件测试',    // 插件简介
        'status'      => 1,    // 状态
        'author'      => 'byron sampson',
        'version'     => '0.1'
    ];

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    /**
     * 实现的testhook钩子方法
     * @return mixed
     */
    public function testhook($param)
    {
        // 调用钩子时候的参数信息
        dump($param);
        // 当前插件的配置信息，配置信息存在当前目录的config.php文件中，见下方
        dump($this->getConfig());

        // 可以返回模板，模板文件默认读取的为插件目录中的文件。模板名不能为空！
        return $this->fetch('info');
    }
}