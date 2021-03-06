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
use think\captcha\facade\Captcha;

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
        if ( ! get_config('web_site_status')) {
            $this->error("站点已经关闭，请稍后访问~", '', '', 3600);
        }
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
            $site              = get_config();
            $site['site_code'] = htmlspecialchars_decode($site['site_code']);
            cache('indexSiteConfig', $site, CACHE);
        }
        $this->seo_title   = $site['site_name'];
        $this->keywords    = $site['site_keyword'];
        $this->description = $site['site_description'];
        $this->site        = $site;
    }

    /**
     * tag标签查询文章列表
     */
    public function tags()
    {
        $tag       = $this->request->param('tag');
        $contentId = Db::name('tag_content')->alias('c')->leftJoin('tag t', 't.id=c.tagid')->where('t.tag',
            $tag)->column('aid');

        $pages    = "";
        $total    = Db::name('article')->where('status', 1)->whereIn('id', $contentId)->count();
        $limit    = 15;
        $Page     = new Page($total, $limit, 0);
        $limitStr = $Page->limit();
        $first    = explode(",", $limitStr)[0];
        $limit    = explode(",", $limitStr)[1];

        $list = Db::name('article')->field('a.id,a.title,a.type_id,a.image,a.description,a.click,a.update_time,c.cate_name,c.cate_en')->alias("a")->leftJoin("category c",
            "c.id = a.type_id")->where('status', 1)->whereIn('a.id',
            $contentId)->order('update_time desc')->limit($first, $limit)->select()->toArray();
        if ($total > $limit) {
            $pages = $Page->pages($total);
        }
        for ($i = 0; $i < count($list); $i++) {
            $list[$i]['url'] = buildContentUrl($list[$i]['id']);
        }

        $this->assign('list', $list);
        $this->assign('pages', $pages);
        $this->assign('seo_title', $this->seo_title);
        $this->assign('keywords', $this->keywords);
        $this->assign('description', $this->description);
        $this->assign('catid', 0);
        $this->assign('total', $total);
        $this->assign('keyword', $tag);

        return $this->fetch();
    }

    public function tag_list()
    {
        $this->assign('seo_title', $this->seo_title);
        $this->assign('keywords', $this->keywords);
        $this->assign('description', $this->description);
        $this->assign('catid', 0);

        return $this->fetch();
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
        $limit   = 15;
        $total   = 0;
        if ( ! empty($keyword)) {
            $where    .= " and title like '%".$keyword."%'";
            $total    = Db::name('article')->where($where)->count();
            $Page     = new Page($total, $limit, 0);
            $limitStr = $Page->limit();
            $first    = explode(",", $limitStr)[0];
            $limit    = explode(",", $limitStr)[1];

            $list = Db::name('article')->field('a.id,a.title,a.type_id,a.image,a.description,a.click,a.update_time,c.cate_name,c.cate_en')->alias("a")->leftJoin("category c",
                "c.id = a.type_id")->where($where)->order('update_time desc')->limit($first,
                $limit)->select()->toArray();
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

    /**
     * 友情链接申请页面
     */
    public function link_apply()
    {
        if ($this->request->isPost()) {
            $ip    = get_client_ip();
            $param = $this->request->param();
            if ( ! captcha_check($param['captcha'])) {
                $this->error('验证码不正确');
            }
            if (empty($param['name'])) {
                $this->error('网站名称不能为空');
            }
            if (empty($param['url'])) {
                $this->error('网站链接不能为空');
            }
            if (empty($param['username'])) {
                $this->error('站长昵称不能为空');
            }
            if (empty($param['email'])) {
                $this->error('站长Email不能为空');
            }
            $findRow = Db::name('link')->where('url', $param['url'])->find();
            if ( ! empty($findRow)) {
                $this->error('该链接已经存在或者已经申请过了，请耐心等待');
            }
            $param['create_time'] = time();
            $param['update_time'] = time();
            $param['ip']          = $ip;

            // 调整提交频率[30秒内同一ip最多提交3条]
            $temp_time          = time() - 30;
            $comment_time_count = Db::name('link')->where('ip', $ip)->whereBetweenTime('create_time', $temp_time,
                time())->count();
            if ($comment_time_count && $comment_time_count > 3) {
                $this->error('休息一下吧~');
            }
            Db::name('link')->strict(false)->insert($param);
            $this->send_wx_api('提交友情链接申请');
            $this->success('提交成功，请耐心等待审核');
        }
        $this->assign('seo_title', '友情链接申请-'.$this->seo_title);
        $this->assign('keywords', $this->keywords);
        $this->assign('description', $this->description);
        $this->assign('catid', 0);

        return $this->fetch();
    }

    /**
     * 项目提交
     */
    public function project_add()
    {
        if ($this->request->isPost()) {

        }
        $this->assign('seo_title', '项目提交-'.$this->seo_title);
        $this->assign('keywords', $this->keywords);
        $this->assign('description', $this->description);
        $this->assign('catid', 0);

        return $this->fetch();
    }

    /**
     * 验证码
     * @return \think\Response
     */
    public function captcha()
    {
        return Captcha::create();
    }

    /**
     * 微信机器人推送
     *
     * @param $title
     *
     * @return bool|mixed|string
     */
    private function send_wx_api($title)
    {
        $web_name = get_config('site_name');
        $dataTime = date('Y-m-d H:i:s');
        //你的推送url地址【这里是企业微信群机器人api地址】
        $post_url  = "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=".get_config('weixin_webhook_key');
        $post_data = '{"msgtype": "markdown","markdown": {"content": "## <font color=\"red\" >【'.$web_name.'】\n 您有【'.$title.'】，赶快去处理吧～～～<\/font>\n ><font color=\"warning\">时间：</font>\n'.$dataTime.'\n > "}}';

        return $res = curl_post($post_url, $post_data);
    }

}