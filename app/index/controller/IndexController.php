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
use lib\Page;

const CACHE = 3600;

class IndexController extends BaseController
{

    protected $seo_title;

    protected $keywords;

    protected $description;

    protected $site;

    protected function initialize()
    {
        $siteUrl = __url('/', [], true, false);
        $this->getSeoInfo();
        $this->assign('site', $this->site);
        $this->assign("siteurl", $siteUrl);
    }

    /**
     * 首页
     * @return string
     */
    public function index()
    {
        $this->assign('seo_title', $this->seo_title);
        $this->assign('keywords', $this->keywords);
        $this->assign('description', $this->description);
        $this->assign('catid', 0);

        return $this->fetch();
    }

    /**
     * 列表页
     */
    public function lists()
    {
        //SEO相关设置
        $getCate = getCateName();
        if (empty($getCate)) {
            abort(404, '栏目不存在');
        }

        //如果默认list页面，则显示第一条栏目所属
        if ( ! empty($getCate[0]['id'])) {
            $getCate = $getCate[0];
        }
        $catid = $getCate['id'];

        $seo_title = empty($getCate['cate_name']) ? $this->seo_title : str_replace(" ", "",
            $getCate['cate_name'].'_'.$this->seo_title);

        $keywords    = empty($getCate['keywords']) ? $this->keywords : str_replace(" ", "", $getCate['keywords']);
        $description = empty($getCate['cat_desc']) ? $this->description : $getCate['cat_desc'];

        //栏目扩展配置信息
        $setting = unserialize($getCate['setting']);
        $content = '';
        if ($getCate['type'] == 1) {
            //栏目首页模板
            $template = $setting['category_template'] ? $setting['category_template'] : 'category_list';
            //栏目列表页模板
            $template_list = $setting['list_template'] ? $setting['list_template'] : 'list_list';
            //判断使用模板类型，如果有子栏目使用频道页模板
            $template = $getCate['child'] ? $template : $template_list;
        } else {
            $content = $getCate['content'];
            //单页面模板
            $template = $setting['page_template'] ? $setting['page_template'] : 'page';
        }
        //获取模板
        $tpar     = explode(".", $template, 2);
        $template = $tpar[0];
        unset($tpar);

        $this->assign('seo_title', $seo_title);
        $this->assign('keywords', $keywords);
        $this->assign('description', $description);
        $this->assign('content', $content);
        $this->assign('catid', $catid);

        return $this->fetch($template);
    }

    /**
     * 内容页
     */
    public function show()
    {
        $id = input('id');
        if (empty($id)) {
            abort(404, '获取id失败');
        }
        //更新点击量
        Db::name('article')->where('id', $id)->inc('click')->update();

        $data = Db::name('article')->find($id);
        //dump($data);
        if (empty($data)) {
            abort(404, '获取内容失败');
        }
        $data['content'] = htmlspecialchars_decode($data['content']);
        $catId           = $data['type_id'];

        $getCate = get_category($catId);
        if (empty($getCate)) {
            abort(404, '获取对应栏目失败');
        }
        //栏目扩展配置信息
        $setting = unserialize($getCate['setting']);
        //内容页模板
        $template = ! empty($setting['show_template']) ? $setting['show_template'] : 'show_article';
        //获取模板
        $tpar     = explode(".", $template, 2);
        $template = $tpar[0];
        unset($tpar);

        $seo_title   = $data['title'].'_'.$this->seo_title;
        $keywords    = $data['keywords'];
        $description = $data['description'];

        //获取相同分类的上一篇/下一篇内容
        $pre  = Db::name('article')->field('id,title')->where([
            ['id', '<', $id],
            ['type_id', '=', $catId],
            ['status', '=', 1]
        ])->order('id DESC')->find();
        $next = Db::name('article')->field('id,title')->where([
            ['id', '>', $id],
            ['type_id', '=', $catId],
            ['status', '=', 1]
        ])->order('id ASC')->find();
        $pre  = ! empty($pre) ? '<a href="'.buildContentUrl($pre['id']).'">'.$pre['title'].'</a>' : '已经是第一篇啦';
        $next = ! empty($next) ? '<a href="'.buildContentUrl($next['id']).'">'.$next['title'].'</a>' : '已经是最后一篇啦';

        $watchs = "";
        $forks  = "";
        $stars  = "";
        //获取github|gitee的fork、watch、star等信息
        if ( ! empty($data['git_url'])) {
            if (strstr($data['git_url'], 'github')) {
                $vowels  = [
                    'https://github.com/',
                    'https://www.github.com/',
                    'https//www.github.com/',
                    'http://www.github.com/'
                ];
                $githubN = str_replace($vowels, '', $data['git_url']);
                $watchs  = '<img alt="GitHub watchers badge" src="https://img.shields.io/github/watchers/'.$githubN.'?logo=github">';
                $forks   = '<img alt="GitHub watchers badge" src="https://img.shields.io/github/forks/'.$githubN.'?logo=github">';
                $stars   = '<img alt="GitHub watchers badge" src="https://img.shields.io/github/stars/'.$githubN.'?logo=github">';
            } else {
                $vowels  = [
                    'https://gitee.com/',
                    'https://www.gitee.com/',
                    'https//www.gitee.com/',
                    'http://www.gitee.com/'
                ];
                $githubN = str_replace($vowels, '', $data['git_url']);
                $forks   = '<img alt="GitHub watchers badge" src="https://gitee.com/'.$githubN.'/badge/fork.svg?theme=dark">';
                $stars   = '<img alt="GitHub watchers badge" src="https://gitee.com/'.$githubN.'/badge/star.svg?theme=dark">';
                $watchs  = '';
            }
        }
        $pageUrl = get_config('site_url').__url('index/index/show', ['id' => $id]);
        $this->assign('id', $id);
        $this->assign('catid', $catId);
        $this->assign('seo_title', $seo_title);
        $this->assign('keywords', $keywords);
        $this->assign('description', $description);
        $this->assign('info', $data);
        $this->assign('pre', $pre);
        $this->assign('next', $next);
        $this->assign('watchs', $watchs);
        $this->assign('forks', $forks);
        $this->assign('stars', $stars);
        $this->assign('page_url', $pageUrl);

        return $this->fetch($template);
    }

    private function getSeoInfo()
    {
        if (cache('indexSiteConfig')) {
            $site = cache('indexSiteConfig');
        } else {
            $site = get_config();
            cache('indexSiteConfig', $site, CACHE);
        }
        $this->seo_title   = $site['site_name'];
        $this->keywords    = $site['site_keyword'];
        $this->description = $site['site_description'];
        $this->site        = $site;
    }

    /**
     * tag标签
     */
    public function tags()
    {
        $tag = $this->request->param('tag');
        dump($tag);
    }

    /**
     * 搜索
     */
    public function search()
    {
        $param   = $this->request->param();
        $keyword = ! empty($param['keyword']) ? $param['keyword'] : '';
        $where   = "status=1";
        $list    = [];
        $pages   = "";
        $limit   = 10;
        $total   = 0;
        if ( ! empty($keyword)) {
            $where    .= " and title like '%".$keyword."%'";
            $total    = Db::name('article')->where($where)->count();
            $Page     = new Page($total, $limit, 0);
            $limitStr = $Page->limit();
            $first    = explode(",", $limitStr)[0];
            $limit    = explode(",", $limitStr)[1];

            $list = Db::name('article')->field('a.id,a.title,a.type_id,a.image,a.description,a.click,a.update_time,c.cate_name,c.cate_en')->alias("a")->leftJoin("category c",
                "c.id = a.type_id")->where($where)->limit($first, $limit)->select()->toArray();
            for ($i = 0; $i < count($list); $i++) {
                $list[$i]['url'] = buildContentUrl($list[$i]['id']);
            }
            if ($total > $limit) {
                $pages = $Page->pages($total);
            }
        }

        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('seo_title', $this->seo_title);
        $this->assign('keywords', $this->keywords);
        $this->assign('description', $this->description);
        $this->assign('catid', 0);
        $this->assign('total', $total);
        $this->assign('keyword', $keyword);

        return $this->fetch();
    }

    public function p()
    {
        $id = 3;
        if (cache("contentTags")):$tags = cache("contentTags");
        else: $tags = \think\facade\Db::name("tag")->field("t.tag")->alias("t")->leftJoin("tag_content c",
            "c.tagid=t.id")->where("1=1 and aid=$id")->limit(10)->select()->toArray();endif;
        dump($tags);
    }

    public function test()
    {

        return $this->fetch();
    }
}