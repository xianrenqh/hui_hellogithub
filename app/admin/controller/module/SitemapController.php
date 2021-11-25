<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-25
 * Time: 下午2:15:34
 * Info:
 */

namespace app\admin\controller\module;

use app\admin\annotation\ControllerAnnotation;
use app\admin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use think\Exception;
use think\App;
use think\facade\Db;
use app\admin\library\Sitemap as Sitemap_Class;

/**
 * @ControllerAnnotation(title="友情链接管理")
 * Class Node
 * @package app\admin\controller\content
 */
class SitemapController extends AdminController
{

    protected $filename = 'sitemap.xml';

    protected $data = [];

    protected $directory;

    protected $url_mode = 1;

    protected function initialize()
    {
        parent::initialize();
        $this->url_mode  = 1;
        $this->directory = ROOT_PATH.'public/';
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $param        = $this->request->param();
            $Sitemap      = new Sitemap_Class();
            $rootUrl      = get_config('site_url');
            $param['num'] = intval($param['num']);
            $type         = isset($param['type']) ? intval($param['type']) : 1;

            $item = $this->_sitemap_item($rootUrl, intval($param['index']['priority']), $param['index']['changefreq'],
                time());
            $this->_add_data($item);

            //栏目
            $List = Db::name('category')->where('show_in_nav', 1)->order('id desc')->field('id,cate_en')->select();
            if ( ! empty($List)) {
                foreach ($List as $vo) {
                    $cat  = $vo['cate_en'];
                    $item = $this->_sitemap_item($rootUrl."/index/list/catdir/".$cat.'.html',
                        intval($param['category']['priority']), $param['category']['changefreq'], time());
                    $this->_add_data($item);
                }
            }

            //列表
            $num    = 1;
            $volist = [];
            $volist = Db::name('article')->where('status',
                1)->order('update_time desc')->field('id,type_id,update_time')->select()->toArray();
            if ( ! empty($volist)) {
                foreach ($volist as $v) {
                    $item = $this->_sitemap_item($rootUrl."/index/show/id/".$v['id'].".html",
                        intval($param['content']['priority']), $param['content']['changefreq'], $v['update_time']);
                    $this->_add_data($item);
                    $num++;
                    if ($num >= $param['num']) {
                        break;
                    }
                }
            }

            //标签
            $tags = Db::name('tag')->order('create_time desc')->field('tag,create_time')->select();
            if ( ! empty($tags)) {
                foreach ($tags as $vo) {
                    $item = $this->_sitemap_item($rootUrl."/index/tag/".$vo['tag'].".html",
                        intval($param['tag']['priority']), $param['tag']['changefreq'], time());
                    $this->_add_data($item);
                }
            }

            if ( ! $type) {
                try {
                    foreach ($this->data as $val) {
                        $Sitemap->AddItem($val['loc'], $val['priority'], $val['changefreq'], $val['lastmod']);
                    }
                    $Sitemap->SaveToFile($this->directory.$this->filename);
                } catch (\Exception $ex) {
                    $this->error($ex->getMessage());
                }
            } else {
                $str            = $this->_txt_format();
                $this->filename = 'sitemap.txt';
                @file_put_contents($this->directory.$this->filename, $str);
            }
            $this->success($this->filename."文件已生成到运行根目录");

        }
        if (is_file($this->directory.'sitemap.xml')) {
            $make_xml_time = date('Y-m-d H:i:s', filemtime($this->directory.'sitemap.xml'));
            $this->assign('make_xml_time', $make_xml_time);
        }
        if (is_file($this->directory.'sitemap.txt')) {
            $make_txt_time = date('Y-m-d H:i:s', filemtime($this->directory.'sitemap.txt'));
            $this->assign('make_txt_time', $make_txt_time);
        }

        return $this->fetch();
    }

    /**
     * 添加数据
     */
    private function _add_data($new_item)
    {
        $this->data[] = $new_item;
    }

    /**
     * 生成txt格式
     */
    private function _txt_format()
    {
        $str = '';
        foreach ($this->data as $val) {
            $str .= $val['loc'].PHP_EOL;
        }

        return $str;
    }

    /**
     * 创建地图格式
     */
    private function _sitemap_item($loc, $priority = '', $changefreq = '', $lastmod = '')
    {
        $data               = array();
        $data['loc']        = $loc;
        $data['priority']   = $priority;
        $data['changefreq'] = $changefreq;
        $data['lastmod']    = $lastmod;

        return $data;
    }

}