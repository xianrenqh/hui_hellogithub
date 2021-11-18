<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-17
 * Time: 下午6:19:54
 * Info:
 */

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
            $categoryinfo[$i]['url'] = buildCatUrl($categoryinfo[$i]['cate_en']);
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

/**
 * 生成栏目URL
 */
function buildCatUrl($cat, $url = '', $suffix = true, $domain = false)
{
    $field = is_numeric($cat) ? 'catid' : 'catdir';
    if (empty($url)) {
        $data = __url('index/index/lists', [$field => $cat], $suffix, $domain);
    } else {
        $data = (strpos($url, '://') !== false) ? $url : __url($url);
    }

    return $data;
}

//创建内容链接
function buildContentUrl($cat, $id, $url = '', $suffix = true, $domain = false)
{
    $field = is_numeric($cat) ? 'catid' : 'catdir';

    if (empty($url)) {
        $data = __url('index/index/shows', [$field => $cat, 'id' => $id], $suffix, $domain);
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

