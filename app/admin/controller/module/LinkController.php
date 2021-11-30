<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-16
 * Time: 下午2:26:00
 * Info:
 */

namespace app\admin\controller\module;

use app\admin\annotation\ControllerAnnotation;
use app\admin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use app\admin\model\Link as LinkModel;
use think\Exception;
use think\App;

/**
 * @ControllerAnnotation(title="友情链接管理")
 * Class Node
 * @package app\admin\controller\content
 */
class LinkController extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new LinkModel();
    }

    /**
     * @NodeAnotation(title="链接列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 10);
            $first = ($page - 1) * $limit;
            $key   = $this->request->param('key');
            $where = function ($query) use ($key) {
                if ( ! empty($key['name'])) {
                    $query->whereLike('name', '%'.$key['name'].'%');
                }
                if ( ! empty($key['url'])) {
                    $query->whereLike('url', '%'.$key['url'].'%');
                }
                if ( ! empty($key['status'])) {
                    if ($key['status'] == 10) {
                        $key['status'] = 0;
                    }
                    $query->where('status', $key['status']);
                }
            };
            $count = $this->model->where($where)->count();
            $list  = $this->model->where($where)->order([
                'status'    => 'asc',
                'listorder' => 'asc',
                'id'        => 'asc'
            ])->limit($first, $limit)->select();
            $data  = [
                'code'  => 0,
                'msg'   => 'ok',
                'count' => $count,
                'data'  => $list
            ];

            return json($data);
        }

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加链接")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $rule  = [
                'name|链接名称' => 'require',
                'url|链接地址'  => 'require'
            ];
            $this->validate($param, $rule);
            $row = $this->model->where(['url' => $param['url']])->find();
            if ($row) {
                $this->error('该链接地址已存在');
            }
            $param['ip'] = get_client_ip();
            $this->model->create($param);
            $linkCount = $this->model->where('status', 1)->count();
            cache('cacheLinkCount', $linkCount, 3600);

            $this->success('添加成功');
        }

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑链接")
     */
    public function edit()
    {
        $id   = $this->request->param('id');
        $data = $this->model->find($id);

        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $rule  = [
                'name|链接名称' => 'require',
                'url|链接地址'  => 'require'
            ];
            $this->validate($param, $rule);
            $data->save($param);
            $this->success('保存成功');
        }
        $this->assign('data', $data);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除链接")
     */
    public function delete()
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('id不能为空');
        }
        $find = $this->model->where('id', $id)->find();
        if (empty($find)) {
            $this->error('此链接不存在');
        }
        $find->delete(true);
        $linkCount = $this->model->where('status', 1)->count();
        cache('cacheLinkCount', $linkCount, 3600);

        $this->success('删除成功');
    }

    /**
     * @NodeAnotation(title="检测链接")
     */
    public function check($list_id = '')
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('id不能为空');
        }
        $res = $this->model->find($id);
        if (empty($res)) {
            $this->error('获取数据失败');
        }
        $url      = $res['url'];
        $site_url = parse_url(get_config('site_url'));
        $site_url = $site_url['host'];
        $html     = get_url($url);

        $res         = [];
        $res['code'] = 1;
        $res['msg']  = '';
        $msg         = '';
        $code        = 1;

        $ok  = ' 友链正常';
        $err = ' 友链异常';

        $msg .= '['.$site_url.']';
        if (strpos($html, $site_url) !== false) {
            $msg         .= $ok;
            $link_status = '<span class="layui-badge layui-bg-green"> 正常 </span>';
        } else {
            $msg         .= $err;
            $link_status = '<span class="layui-badge layui-bg-red"> 异常 </span>';
        }
        if ($list_id != '') {
            return $link_status;
        } else {
            $res['msg'] = $msg;

            return json($res);
        }

    }

}