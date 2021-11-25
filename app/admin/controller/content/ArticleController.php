<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-06-15
 * Time: 上午11:19:54
 * Info:
 */

namespace app\admin\controller\content;

use app\admin\annotation\ControllerAnnotation;
use app\admin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use app\admin\model\Article as ArticleModel;
use app\admin\model\Tag as TagModel;
use app\admin\model\Category as CategoryModel;
use think\Exception;
use think\App;
use lib\Random;
use lib\GetImgSrc;
use think\facade\Db;
use think\response\Json;

/**
 * @ControllerAnnotation(title="文章管理")
 * Class Node
 * @package app\admin\controller\content
 */
class ArticleController extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ArticleModel();
    }

    /**
     * @NodeAnotation(title="内容列表")
     */
    public function index(): Json|string
    {
        $CategoryModel = new CategoryModel();
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 10);
            $first = ($page - 1) * $limit;
            $key   = $this->request->param('key');

            $where = function ($query) use ($key) {
                if ( ! empty($key['title'])) {
                    $query->whereLike('title', '%'.$key['title'].'%');
                }
                if ( ! empty($key['type_id'])) {
                    $query->where('type_id', $key['type_id']);
                }
                if ( ! empty($key['status'])) {
                    if ($key['status'] == 2) {
                        $status = 0;
                    } else {
                        $status = 1;
                    }
                    $query->where('status', $status);
                }
            };
            $count = $this->model->where($where)->count();
            $list  = $this->model->field('a.*,c.cate_name,c.cate_en')->alias('a')->leftJoin('category c',
                'c.id=a.type_id')->where($where)->limit($first,
                $limit)->order('a.is_top desc,a.weight desc,a.id desc')->select();
            $data  = [
                'code'  => 0,
                'msg'   => 'ok',
                'count' => $count,
                'data'  => $list
            ];

            return json($data);
        }
        $pidMenuList = $CategoryModel->getPidMenuList(1);
        $this->assign('pidMenuList', $pidMenuList);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加内容")
     */
    public function add(): string
    {
        $CategoryModel = new CategoryModel();
        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $rule  = [
                'type_id|栏目' => 'require',
                'title|标题'   => 'require',
                'content|内容' => 'require'
            ];
            $this->validate($param, $rule);

            $param['is_top']   = ( ! empty($param['flag']) && in_array(1, $param['flag'])) ? 1 : 0;
            $param['flag']     = ! empty($param['flag']) ? implode(',', $param['flag']) : '';
            $thumbArr          = array_filter(explode(',', $param['thumbs']));
            $param['thumbs']   = json_encode($thumbArr, true);
            $param['admin_id'] = cmf_get_admin_id();
            //自动提取缩略图
            if (isset($param['auto_image']) && $param['image'] == '') {
                $param['image'] = GetImgSrc::src($param['content']);
                $param['image'] = ! empty($param['image']) ? $param['image'] : '';
            }
            $param['description'] = empty($param['description']) ? str_cut(strip_tags($param['content']),
                250) : $param['description'];

            //如果编辑器是md，转换编辑器代码，md的内容存储到字段：content_md
            if ( ! empty($param['editor']) && $param['editor'] == 2) {
                $param['content_md'] = ($param['content']);
                $param['content']    = htmlspecialchars($param['content-editor-html-code']);
                unset($param['content-editor-html-code']);
            } else {
                $param['content'] = htmlspecialchars($param['content']);
            }

            try {
                $param['create_time'] = time();
                $param['update_time'] = time();
                $id                   = Db::name('article')->strict(false)->insertGetId($param);

                $articleCount = Db::name('article')->where('status', 1)->count();
                cache('cacheArticleCount', $articleCount);

                //写入tag标签
                $huiTags    = $param['hui_tags'];
                $huiTagsArr = explode(',', $huiTags);
                if ( ! empty($huiTagsArr)) {
                    $TagModel = new TagModel();
                    $TagModel->tag_dispose($param['type_id'], $huiTagsArr, $id, 'add');
                }

                $this->success('保存成功');
            } catch (Exception $e) {
                $this->error('保存失败'.$e->getMessage());
            }
        }
        $click       = Random::numeric(2);
        $pidMenuList = $CategoryModel->getPidMenuList(1);
        $editor      = $this->request->param('editor', 1);
        $this->assign('editor', $editor);
        $this->assign('click', $click);
        $this->assign('pidMenuList', $pidMenuList);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="修改内容")
     */
    public function edit(): string
    {
        $CategoryModel = new CategoryModel();

        $id     = $this->request->param('id');
        $editor = $this->request->param('editor', 1);
        if (empty($id)) {
            $this->error('参数错误');
        }
        $data = $this->model->withTrashed()->find($id);
        if (empty($data)) {
            $this->error('获取数据失败');
        }
        $data['thumbs']       = json_decode($data['thumbs'], true);
        $data['thumbs_count'] = count($data['thumbs']);
        $data['thumbs']       = implode(',', $data['thumbs']);
        $data['flag']         = array_filter(explode(',', $data['flag']));
        if ( ! empty($editor) && $editor == 2) {
            $data['content'] = $data['content_md'];
        }
        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $rule  = [
                'type_id|栏目' => 'require',
                'title|标题'   => 'require',
                'content|内容' => 'require'
            ];
            $this->validate($param, $rule);
            $param['is_top']      = ( ! empty($param['flag']) && in_array(1, $param['flag'])) ? 1 : 0;
            $param['jump_url']    = ( ! empty($param['flag']) && in_array(7, $param['flag'])) ? $param['jump_url'] : '';
            $param['flag']        = ! empty($param['flag']) ? implode(',', $param['flag']) : '';
            $thumbArr             = array_filter(explode(',', $param['thumbs']));
            $param['thumbs']      = json_encode($thumbArr, true);
            $param['delete_time'] = 0;
            //自动提取缩略图
            if (isset($param['auto_image']) && $param['image'] == '') {
                $param['image'] = GetImgSrc::src($param['content']);
                $param['image'] = ! empty($param['image']) ? $param['image'] : '';
            }
            $param['description'] = empty($param['description']) ? str_cut(strip_tags($param['content']),
                250) : $param['description'];

            //如果编辑器是md，转换编辑器代码，md的内容存储到字段：content_md
            if ( ! empty($param['editor']) && $param['editor'] == 2) {
                $param['content_md'] = ($param['content']);
                $param['content']    = htmlspecialchars($param['content-editor-html-code']);
                unset($param['content-editor-html-code']);
            } else {
                $param['content'] = htmlspecialchars($param['content']);
            }

            try {
                $param['update_time'] = time();
                $res                  = Db::name('article')->strict(false)->data($param)->update();
                if ($res) {
                    //写入tag标签
                    $huiTags    = $param['hui_tags'];
                    $huiTagsArr = explode(',', $huiTags);
                    if ( ! empty($huiTagsArr)) {
                        $TagModel = new TagModel();
                        $TagModel->tag_dispose($param['type_id'], $huiTagsArr, $param['id'], 'edit');
                    }
                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            } catch (Exception $e) {
                $this->error('保存失败'.$e->getMessage());
            }
        }
        $pidMenuList = $CategoryModel->getPidMenuList(1);
        $tagsArr     = Db::name('tag_content')->alias('tc')->leftJoin('tag t',
            't.id=tc.tagid')->where(['tc.aid' => $data['id']])->column('t.tag');
        $tagsArr     = array_filter($tagsArr);
        $tags        = implode(',', $tagsArr);
        $this->assign('editor', $editor);
        $this->assign('tags', $tags);
        $this->assign('pidMenuList', $pidMenuList);
        $this->assign('data', $data);
        $this->assign('description_length', mb_strlen($data['description']));

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除内容")
     */
    public function delete()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id');
            if (empty($id)) {
                $this->error('参数错误');
            }
            $data = $this->model->find($id);
            if (empty($data)) {
                $this->error('获取数据失败');
            }
            $this->model->where(['id' => $id])->data(['status' => 0, 'delete_time' => time()])->update();
            $articleCount = Db::name('article')->where('status', 1)->count();
            cache('cacheArticleCount', $articleCount);

            $this->success('删除成功');
        }
    }

    /**
     * @NodeAnotation(title="推送到百度")
     */
    public function baidu_push()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id');
            if ( ! empty($id)) {
                $ids = explode(',', $id);
            } else {
                $ids = $this->model->where('is_push', 0)->column('id');
            }
            if (empty($ids)) {
                $this->error('没有数据被推送');
            }
            $urls      = [];
            $http_host = get_config('site_url');
            foreach ($ids as $v) {
                $urls[] = $http_host."/index/show/id/".$v.".html";
            }
            $baidu_push_token = get_config('baidu_push_token');
            if (empty($baidu_push_token)) {
                $this->error('token值为空，请到系统设置中配置！');
            }
            $api_url = 'http://data.zz.baidu.com/urls?site='.$http_host.'&token='.$baidu_push_token;
            $ch      = curl_init();
            $options = array(
                CURLOPT_URL            => $api_url,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => implode("\n", $urls),
                CURLOPT_HTTPHEADER     => array('Content-Type: text/plain'),
            );
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result, true);
            if (isset($result['success'])) {
                $a = $this->model->whereIn('id', $ids)->data(['is_push' => 1])->update();
                $this->success('成功推送'.$result['success'].'条URL地址！');
            } else {
                $this->error('推送失败，错误码：'.$result['error']);
            }

        }
    }

    /**
     * @NodeAnotation(title="采集git")
     */
    public function collection()
    {
        if ($this->request->isAjax()) {
            $gitUrl = $this->request->param('git_url');
            $type   = $this->request->param('type');
            //查询此giturl是否已存在数据库
            if ($type == 'add') {
                $findData = $this->model->where(['git_url' => $gitUrl])->find();
                if ( ! empty($findData)) {
                    //$this->error('该url已经存在啦');
                }
            }
            if (strstr($gitUrl, 'github.com')) {
                $vowels  = [
                    'https://github.com/',
                    'https://www.github.com/',
                    'https//www.github.com/',
                    'http://www.github.com/'
                ];
                $webhost = str_replace($vowels, 'https://cdn.jsdelivr.net/gh/', $gitUrl);
                $url     = $webhost."/README.md";
            } elseif (strstr($gitUrl, 'gitee.com')) {
                $url = $gitUrl."/raw/master/README.md";
            } else {

            }
            try {
                $html = file_get_contents($url);
            } catch (\Exception $e) {
                $this->error('获取数据失败，请检测url是否正确');
            }
            if ( ! empty($html)) {
                $this->success('ok', ['html' => $html]);
            } else {
                $this->error('error');
            }

        }
    }

}