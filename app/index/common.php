<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-17
 * Time: 下午6:19:54
 * Info:
 */

/***
 * 获取当前栏目ID
 * @return mixed
 */
function getCateId()
{
    if (\think\facade\Request::has('catid')) {
        $result = (int)\think\facade\Request::param('catid');
    } else {
        $catDir = \think\facade\Request::param('catdir');
        $result = \think\facade\Db::name('category')->where('cate_en', $catDir)->value('id');
    }

    return $result;
}

function getCateName()
{
    $getNameArr = get_category(getCateId());

    return $getNameArr;
}

/**
 * 获取栏目信息
 *
 * @param int    $catid
 * @param string $parameter
 *
 * @return array or string
 */
function get_category($catid = '', $parameter = '')
{
    $categoryinfo = \think\facade\Db::name('category')->order('sort_order ASC, id ASC')->select()->toArray();
    if ( ! empty($categoryinfo)) {
        for ($i = 0; $i < count($categoryinfo); $i++) {
            $categoryinfo[$i]['url']   = buildCatUrl($categoryinfo[$i]['cate_en']);
            $categoryinfo[$i]['child'] = ! empty(getChild($categoryinfo, $categoryinfo[$i]['id'])) ? 1 : 0;
        }
    }
    if ($catid) {
        $catid_arr = hui_array_column($categoryinfo, 'id');
        $catid     = array_search($catid, $catid_arr);
        if ($catid === false) {
            return array();
        }

        return $parameter ? (isset($categoryinfo[$catid][$parameter]) ? $categoryinfo[$catid][$parameter] : '') : $categoryinfo[$catid];

    } else {
        return $categoryinfo;
    }

}

//获取子集
function getChild($array, $myid, $parent_str = 'parent_id')
{
    $newarr = [];
    foreach ($array as $value) {
        if ( ! isset($value['id'])) {
            continue;
        }
        if ($value[$parent_str] == $myid) {
            $newarr[$value['id']] = $value;
        }
    }

    return $newarr;
}

/**
 * 生成栏目URL
 */
function buildCatUrl($cat, $url = '', $suffix = true, $domain = false)
{
    $field = is_numeric($cat) ? 'catid' : 'catdir';
    if (empty($url)) {
        $data = __url('index/index/lists', [$field => $cat], $suffix, $domain);
        $data = str_replace("/index.php", "", $data);
    } else {
        $data = (strpos($url, '://') !== false) ? $url : __url($url);
    }

    return $data;
}

//创建内容链接
function buildContentUrl($id, $url = '', $suffix = true, $domain = false)
{
    if (empty($url)) {
        $data = __url('index/index/show', ['id' => $id], $suffix, $domain);
    } else {
        $data = (strpos($url, '://') !== false) ? $url : __url($url);
    }

    return $data;
}

/**
 * 根据栏目ID获取子栏目信息
 *
 * @param int  $catid
 * @param bool $is_show 前端不显示栏目是否显示
 * @param int  $limit   限制数量
 *
 * @return array
 */
function get_childcat($catid, $is_show = false, $limit = 0)
{
    $catid = intval($catid);
    $data  = get_category();
    $r     = array();
    foreach ($data as $v) {
        if ( ! $v['show_in_nav'] && ! $is_show) {
            continue;
        }
        if ($v['parent_id'] == $catid) {
            $r[] = $v;
        }
    }

    return $limit ? array_slice($r, 0, $limit) : $r;
}

/**
 * 当前路径
 * 返回指定栏目路径层级
 *
 * @param $catid  栏目id
 * @param $symbol 栏目间隔符
 */
function catpos($catid, $symbol = ' &gt; ')
{
    if (get_category($catid) == false) {
        return '';
    }
    //获取当前栏目的 父栏目列表
    $arrparentid = array_filter(explode(',', get_category($catid, 'parent_id').','.$catid));
    foreach ($arrparentid as $cid) {
        $parsestr[] = '<a href="'.get_category($cid, 'url').'" >'.get_category($cid, 'cate_name').'</a>';
    }
    $parsestr = implode($symbol, $parsestr);

    return $parsestr;
}