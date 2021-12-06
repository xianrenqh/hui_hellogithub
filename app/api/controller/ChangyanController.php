<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-26
 * Time: 上午9:50:35
 * Info: 畅言评论回推接口
 */

namespace app\api\controller;

use app\admin\library\Sitemap as Sitemap_Class;
use think\facade\Db;

class ChangyanController extends BaseController
{

    /**
     * 畅言评论回掉方法
     * @return bool|mixed|string
     */
    public function goBack()
    {
        $postData = file_get_contents('php://input');
        $data     = json_decode(substr(urldecode($postData), 5), true);
        //file_put_contents('cy.txt', json_encode($postData), FILE_APPEND);

        /*评论使用企业微信插件推送一下*/
        $url      = $data['url'];
        $web_name = get_config('site_name');
        $title    = $data['title'];
        $dataTime = date('Y-m-d H:i:s', intval(microtime($data['ttime'])));
        $author   = $data['comments'][0]['user']['nickname'];
        $desp     = $data['comments'][0]['content'];
        //你的推送url地址【这里是企业微信群机器人api地址】
        $post_url  = "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=".get_config('weixin_webhook_key');
        $post_data = '{"msgtype": "markdown","markdown": {"content": "## <font color=\"red\" >【'.$web_name.'】有新评论啦<\/font>\n ><font color=\"warning\">标题：</font>'.$title.'\n ><font color=\"warning\">时间：</font>'.$dataTime.'\n ><font color=\"warning\">昵称：</font>'.$author.'\n ><font color=\"warning\">网址：</font>['.$url.']('.$url.')\n ><font color=\"warning\">评论内容：</font>\n <font color=\"info\" >'.$desp.'</font> "}}';

        return $res = curl_post($post_url, $post_data);
    }

    /**
     * 生成sitemap.xml文件
     */
    public function set_sitemap()
    {
        $filename        = 'sitemap.xml';
        $this->directory = ROOT_PATH.'public/';
        $Sitemap         = new Sitemap_Class();
        $rootUrl         = get_config('site_url');
        $param['num']    = 500;
        $type            = 1;

        $item = $this->_sitemap_item($rootUrl, intval(1), 'always', time());
        $this->_add_data_sitemap($item);

        //栏目
        $List = Db::name('category')->where('show_in_nav', 1)->order('id desc')->field('id,cate_en')->select();
        if ( ! empty($List)) {
            foreach ($List as $vo) {
                $cat  = $vo['cate_en'];
                $item = $this->_sitemap_item($rootUrl."/index/list/catdir/".$cat.'.html', intval(1), 'always', time());
                $this->_add_data_sitemap($item);
            }
        }

        //列表
        $num    = 1;
        $volist = [];
        $volist = Db::name('article')->where('status',
            1)->order('update_time desc')->field('id,type_id,update_time')->select()->toArray();
        if ( ! empty($volist)) {
            foreach ($volist as $v) {
                $item = $this->_sitemap_item($rootUrl."/index/show/id/".$v['id'].".html", 2, 'daily',
                    $v['update_time']);
                $this->_add_data_sitemap($item);
                $num++;
                if ($num >= $param['num']) {
                    break;
                }
            }
        }

        //标签
        $tags = Db::name('tag')->field('tag,create_time')->limit(50)->order('create_time desc')->select();
        if ( ! empty($tags)) {
            foreach ($tags as $vo) {
                $item = $this->_sitemap_item($rootUrl."/index/tag/".$vo['tag'].".html", 2, 'weekly', time());
                $this->_add_data_sitemap($item);
            }
        }
        try {
            foreach ($this->data as $val) {
                $Sitemap->AddItem($val['loc'], $val['priority'], $val['changefreq'], $val['lastmod']);
            }
            $Sitemap->SaveToFile($this->directory.$filename);
        } catch (\Exception $ex) {
            halt($ex->getMessage());
        }

        return "sitemap.xml已生成到运行根目录";
    }

    public function test()
    {
        $url  = "data=%7B%22comments%22%3A%5B%7B%22apptype%22%3A0%2C%22attachment%22%3A%5B%5D%2C%22channelid%22%3A1166522%2C%22channeltype%22%3A1%2C%22cmtid%22%3A%221756302997%22%2C%22content%22%3A%22%E5%A4%A9%E7%A7%80+%E5%B0%B1%E6%98%AF%E4%BD%A0+%22%2C%22ctime%22%3A1637892333000%2C%22from%22%3A0%2C%22ip%22%3A%221.193.57.67%22%2C%22opcount%22%3A0%2C%22referid%22%3A%221756302997%22%2C%22replyid%22%3A%220%22%2C%22score%22%3A0%2C%22spcount%22%3A0%2C%22status%22%3A0%2C%22user%22%3A%7B%22nickname%22%3A%22%E8%BE%89+%E2%9C%85%E2%80%8D%E5%BE%AE%E4%BF%A1VIP%E8%B6%85%E7%B4%9A%E6%9C%83%E5%93%A1%22%2C%22sohuPlusId%22%3A100002919067%2C%22usericon%22%3A%22http%3A%2F%2Fthirdwx.qlogo.cn%2Fmmopen%2FZqDaDiccbgkj9Ie7cITgsf9dQBdR45u0TzsCEZz55JBgtNuMbor5wYzQHkPxT8hCt2YJueAJiaRbK71pzqsruBVf58XudPns6c%2F132%22%7D%2C%22useragent%22%3A%22Mozilla%2F5.0+%28X11%3B+Linux+x86_64%29+AppleWebKit%2F537.36+%28KHTML%2C+like+Gecko%29+Chrome%2F96.0.4664.45+Safari%2F537.36+Edg%2F96.0.1054.26%22%7D%5D%2C%22metadata%22%3A%22%7B%5C%22local_ip%5C%22%3A%5C%22172.22.33.165%5C%22%7D%22%2C%22sourceid%22%3A%228%22%2C%22title%22%3A%22mattermost-%E5%9B%A2%E9%98%9F%E9%80%9A%E8%AE%AF%E6%9C%8D%E5%8A%A1%E9%A1%B9%E7%9B%AE_HelloGitHub%E5%88%86%E4%BA%AB%E7%BD%91%E7%AB%99%22%2C%22ttime%22%3A1637891761000%2C%22url%22%3A%22https%3A%2F%2Fxiaohuihui.club%2Findex%2Fshow%2Fid%2F8.html%22%7D";
        $data = json_decode(substr(urldecode($url), 5), true);
        dump($data);
        $author = $data['comments'][0]['user']['nickname'];
        dump($author);
        dump(date('Y-m-d H:i:s', intval(microtime($data['ttime']))));
    }

    /**
     * 添加数据到sitemap
     */
    private function _add_data_sitemap($new_item)
    {
        $this->data[] = $new_item;
    }

    /**
     * 生成txt格式
     */
    private function _txt_format_sitemap()
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