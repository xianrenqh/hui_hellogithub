<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-17
 * Time: 下午3:53:38
 * Info:
 */

namespace app\index;

use think\App;
use think\facade\Request;
use think\facade\Config;
use think\facade\Env;
use lib\HuiTpl;

class BaseController
{

    use \app\common\traits\JumpTrait;

    /**
     * 构造方法
     * @access public
     *
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');
    }

    /**
     * 模板变量赋值
     *
     * @param      $name
     * @param null $value
     *
     * @return \think\View
     */
    public function assign($name, $value = null)
    {
        return $this->app->view->assign($name, $value);
    }

    protected function fetch($template = '', $vars = [], $config = [], $renderContent = false)
    {
        $template     = ! empty($template) ? $template : $this->request->action();
        $controller   = strtolower($this->request->controller());
        $Theme        = empty(get_config('site_theme')) ? 'default' : get_config('site_theme');
        $viewPath     = TEMPLATE_PATH.$Theme.DS;
        $templateFile = $viewPath.trim($template, '/').'.'.Config::get('view.view_suffix');
        if ('default' !== $Theme && ! is_file($templateFile)) {
            $viewPath = TEMPLATE_PATH.'default'.DS;
        }

        $this->app->view->config(['view_path' => $viewPath]);

        return $this->app->view->fetch($template, $vars);
    }

    /**
     * 模板调用
     * 有bug，没有使用
     *
     * @param $module
     * @param $template
     *
     * @return unknown_type
     */
    function template($module = '', $template = 'index')
    {
        $controller = strtolower($this->request->controller());
        $action     = strtolower($this->request->action());
        $module     = ! empty($module) ? $module : $action;
        $getTheme   = get_config('site_theme');

        $Theme         = empty(get_config('site_theme')) ? 'default' : get_config('site_theme');
        $template_path = ! defined('MODULE_THEME') ? Env::get('app_path').$module.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.get_config('site_theme').DIRECTORY_SEPARATOR : Env::get('app_path').$module.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.MODULE_THEME.DIRECTORY_SEPARATOR;;
        $template_path = TEMPLATE_PATH.$Theme.DS.$module.DS;
        $template_c    = runtime_path().$module.DIRECTORY_SEPARATOR;

        $filename = $template.'.html';
        $tplfile  = $template_path.$filename;
        if ( ! is_file($tplfile)) {
            //showmsg(str_replace(Env::get('root_path'), "", $tplfile).' 模板不存在！', 'stop');
            die('模板不存在');
        }

        if ( ! is_dir(runtime_path().$module.DIRECTORY_SEPARATOR)) {
            @mkdir(runtime_path().$module.DIRECTORY_SEPARATOR, 0777, true);
        }
        $template   = md5($template_path.$template);
        $template_c = $template_c.$template.'.tpl.php';
        if ( ! is_file($template_c) || filemtime($template_c) < filemtime($tplfile)) {
            $HuiTPL  = new HuiTpl();
            $compile = $HuiTPL->tpl_replace(@file_get_contents($tplfile));
            file_put_contents($template_c, $compile);
        }

        return $template_c;

    }
}