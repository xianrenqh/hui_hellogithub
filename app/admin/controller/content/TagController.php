<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-05
 * Time: 下午3:28:31
 * Info:
 */

namespace app\admin\controller\content;

use app\admin\annotation\ControllerAnnotation;
use app\admin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use app\admin\model\Tag as TagModel;
use app\admin\model\TagContent as TagContentModel;
use think\Exception;
use think\App;
use function foo\func;

/**
 * @ControllerAnnotation(title="Tag标签管理")
 * Class Node
 * @package app\admin\controller\content
 */
class TagController extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new TagModel();
    }

    /**
     * @NodeAnotation(title="Tag标签列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page  = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 10);
            $first = ($page - 1) * $limit;
            $key   = $this->request->param('key');
            $where = function ($query) use ($key) {
                if ( ! empty($key['tag_name'])) {
                    $query->whereLike('tag', '%'.$key['tag_name'].'%');
                }
            };
            $count = $this->model->where($where)->count();
            $list  = $this->model->where($where)->limit($first, $limit)->select();
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
     * @NodeAnotation(title="添加Tag")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $rule  = [
                'tag|标签名称' => 'require',
            ];
            $this->validate($param, $rule);
            $row = $this->model->where(['tag' => $param['tag']])->find();
            if ($row) {
                $this->error('标签已存在，请重新输入');
            }
            $this->model->create(['tag' => $param['tag']]);
            $this->success('添加成功');
        }

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="修改Tag")
     */
    public function edit()
    {
        $id   = $this->request->param('id');
        $data = $this->model->find($id);

        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $rule  = [
                'tag|标签名称' => 'require',
            ];
            $this->validate($param, $rule);
            $data->save(['tag' => $param['tag']]);
            $this->success('保存成功');
        }
        $this->assign('data', $data);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除Tag")
     */
    public function delete()
    {
        $TagContentModel = new TagContentModel();

        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('id不能为空');
        }

        $find = $this->model->where('id', $id)->find();
        if (empty($find)) {
            $this->error('标签不存在');
        }
        $TagContentModel->where(['catid' => $id])->delete();
        $find->delete(true);
        $this->success('删除成功');
    }

    /**
     * @NodeAnotation(title="选择Tag")
     */
    public function select()
    {
        if ($this->request->isAjax()) {

            $limit = 50;
            if (input('post.dosearch')) {
                $res = $this->model->where('tag', 'like', '%'.input('post.key').'%')->limit($limit)->select();
            } else {
                $res = $this->model->limit($limit)->select();
            }

            $tags = '';
            foreach ($res as $v) {
                $tags .= "<a onclick='set_val(\"".$v['tag']."\")'>#".$v['tag']."</a>";
            }
            $this->success('ok', $tags);
        }

        return $this->fetch('select');
    }
}