<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-18
 * Time: 上午9:55:06
 * Info:
 */

namespace app\common\taglib;

use app\portal\controller\DumpTestController;
use think\template\TagLib;

class HuiCmf extends TagLib
{

    public $page, $total;

    protected $tags = [
        'nav'          => ['attr' => 'field,order,limit,where', 'close' => 0],
        'link'         => ['attr' => 'field,typeid,limit,return', 'close' => 0],
        'banner'       => ['attr' => 'field,typeid,limit,return', 'close' => 0],
        'get'          => ['attr' => 'sql,table,where,limit,typeid,return,page', 'close' => 0],
        'lists'        => ['attr' => 'field,limit,return,where,page,typeid', 'close' => 0],
        'tag'          => ['attr', 'limit,return', 'close' => 0],
        'centent_tag'  => ['attr', 'limit,return', 'close' => 0],
        'page_content' => ['attr', 'return', 'close' => 0],
    ];

    public function get_cache_time()
    {
        $cacheTime = get_config('site_cache_time');
        $cacheTime = ! empty($cacheTime) ? $cacheTime : 3600;

        return $cacheTime;
    }

    public function tagNav($tag, $content)
    {
        $field = isset($tag['field']) ? $tag['field'] : '*';
        $order = isset($tag['order']) ? $tag['order'] : 'sort_order ASC';
        $limit = isset($tag['limit']) ? $tag['limit'] : '20';
        $where = isset($tag['where']) ? $tag['where'].' AND ' : '';
        //数据返回变量
        $return = isset($tag['return']) && trim($tag['return']) ? trim($tag['return']) : 'data';

        $where .= '`show_in_nav`=1';

        $parseStr = '<?php ';
        $parseStr .= 'if(cache("indexCategory")):';
        $parseStr .= '$'.$return.' = cache("indexCategory");';
        $parseStr .= 'else: ';
        $parseStr .= '$'.$return.'=\think\facade\Db::name("category")->field("'.$field.'")->where("'.$where.'")->order("'.$order.'")->limit('.$limit.')->select()->toArray();';
        $parseStr .= 'if ( ! empty($'.$return.')) {';
        $parseStr .= 'for ($i = 0; $i < count($'.$return.'); $i++) {';
        $parseStr .= ' $'.$return.'[$i][\'url\'] = buildCatUrl($'.$return.'[$i][\'cate_en\']);';
        $parseStr .= ' $'.$return.'[$i][\'url\'] = str_replace("/index.php","",$'.$return.'[$i][\'url\']);';
        $parseStr .= '  }';
        $parseStr .= ' }';
        $parseStr .= 'cache("indexCategory", $'.$return.', '.$this->get_cache_time().');';
        $parseStr .= 'endif;';
        $parseStr .= ' ?>';
        $parseStr .= $content;

        if ( ! empty($parseStr)) {
            return $parseStr;
        }

        return false;
    }

    /**
     * 友情链接标签
     */
    public function tagLink($tag, $content)
    {
        $field  = isset($tag['field']) ? $tag['field'] : '*';
        $limit  = isset($tag['limit']) ? $tag['limit'] : '20';
        $typeid = isset($tag['typeid']) ? $tag['typeid'] : '';
        //数据返回变量
        $return = isset($tag['return']) && trim($tag['return']) ? trim($tag['return']) : 'data';

        $where = '`status`=1';
        if ( ! empty($typeid)) {
            $where .= " and typeid=".$typeid;
        }
        //拼接php代码
        $parseStr = '<?php ';
        $parseStr .= 'if(cache("indexLink_'.$typeid.'")):';
        $parseStr .= '$'.$return.' = cache("indexLink_'.$typeid.'");';
        $parseStr .= 'else: ';
        $parseStr .= '$'.$return.'=\think\facade\Db::name("link")->field("'.$field.'")->where("'.$where.'")->order("listorder asc")->limit('.$limit.')->select()->toArray();';
        $parseStr .= 'cache("indexLink_'.$typeid.'", $'.$return.', '.$this->get_cache_time().');';
        $parseStr .= 'endif;';
        $parseStr .= ' ?>';
        $parseStr .= $content;

        if ( ! empty($parseStr)) {
            return $parseStr;
        }

        return false;
    }

    /**
     * 全站tag标签云
     *
     * @param $tag
     * @param $content
     *
     * @return false|string
     */
    public function tagTag($tag, $content)
    {
        $limit = isset($tag['limit']) ? $tag['limit'] : '20';
        $type  = isset($tag['type']) ? $tag['type'] : '';
        //数据返回变量
        $return = isset($tag['return']) && trim($tag['return']) ? trim($tag['return']) : 'data';

        //拼接php代码
        $parseStr = '<?php ';
        $parseStr .= 'if(cache("indexTagsAll_'.$type.'")):';
        $parseStr .= '$'.$return.' = cache("indexTagsAll_'.$type.'");';
        $parseStr .= 'else: ';
        $parseStr .= '$'.$return.'=\think\facade\Db::name("tag")->field("tag,total")->orderRaw("rand(),id desc")->limit('.$limit.')->select()->toArray();';
        $parseStr .= 'for ($i = 0; $i < count($'.$return.'); $i++) {';
        $parseStr .= ' $'.$return.'[$i][\'url\'] = __url("index/index/tags",[\'tag\'=>$'.$return.'[$i][\'tag\']]);';
        $parseStr .= ' }';
        $parseStr .= 'cache("indexTagsAll_'.$type.'", $'.$return.', '.$this->get_cache_time().');';
        $parseStr .= 'endif;';
        $parseStr .= ' ?>';
        $parseStr .= $content;

        if ( ! empty($parseStr)) {
            return $parseStr;
        }

        return false;
    }

    /**
     * 单页面的内容
     *
     * @param $tag
     * @param $content
     */
    public function tagPage_content($tag, $content)
    {
        $catid    = getCateId();
        $content  = \think\facade\Db::name('category')->where('id', $catid)->value('content');
        $parseStr = $content;
        if ( ! empty($parseStr)) {
            return $parseStr;
        }

        return false;
    }

    /*
     * 内容页Tag标签
     */
    public function tagCentent_tag($tag, $content)
    {
        $limit = isset($tag['limit']) ? $tag['limit'] : '20';
        $id    = isset($tag['id']) ? $tag['id'] : '';
        //数据返回变量
        $return = isset($tag['return']) && trim($tag['return']) ? trim($tag['return']) : 'data';
        $where  = "1=1";
        if ( ! empty($id)) {
            $where .= " and aid=".$id;
        }
        //拼接php代码
        $parseStr = '<?php ';
        $parseStr .= 'if(cache("contentTags_$id")):';
        $parseStr .= '$'.$return.' = cache("contentTags_$id");';
        $parseStr .= 'else: ';
        $parseStr .= '$'.$return.'=\think\facade\Db::name("tag")->field("t.tag")->alias("t")->leftJoin("tag_content c","c.tagid=t.id")->where("'.$where.'")->limit('.$limit.')->select()->toArray();';
        $parseStr .= 'cache("contentTags_$id", $'.$return.', '.$this->get_cache_time().');';
        $parseStr .= 'endif;';
        $parseStr .= ' ?>';
        $parseStr .= $content;
        if ( ! empty($parseStr)) {
            return $parseStr;
        }

        return false;
    }

    /**
     * 轮播图标签
     *
     * @param $tag
     * @param $content
     *
     * @return false|string
     */
    public function tagBanner($tag, $content)
    {
        $field  = isset($tag['field']) ? $tag['field'] : '*';
        $limit  = isset($tag['limit']) ? $tag['limit'] : '5';
        $typeid = isset($tag['typeid']) ? $tag['typeid'] : '';
        //数据返回变量
        $return = isset($tag['return']) && trim($tag['return']) ? trim($tag['return']) : 'data';

        $where = '`status`=1';
        if ( ! empty($typeid)) {
            $where .= " and typeid=".$typeid;
        }
        //拼接php代码
        $parseStr = '<?php ';
        $parseStr .= 'if(cache("indexBanner")):';
        $parseStr .= '$'.$return.' = cache("indexBanner");';
        $parseStr .= 'else: ';
        $parseStr .= '$'.$return.'=\think\facade\Db::name("banner")->field("'.$field.'")->where("'.$where.'")->order("listorder asc")->limit('.$limit.')->select()->toArray();';
        $parseStr .= 'cache("indexBanner", $'.$return.', '.$this->get_cache_time().');';
        $parseStr .= 'endif;';
        $parseStr .= ' ?>';
        $parseStr .= $content;

        if ( ! empty($parseStr)) {
            return $parseStr;
        }

        return false;
    }

    /**
     * 列表标签
     *
     * @param $tag
     * @param $content
     */
    public function tagLists($tag, $content)
    {
        $field    = isset($tag['field']) ? $tag['field'] : '*';
        $limit    = isset($tag['limit']) ? $tag['limit'] : '10';
        $typeid   = isset($tag['typeid']) ? $tag['typeid'] : '';
        $flag     = isset($tag['flag']) ? $tag['flag'] : '';
        $thumb    = isset($tag['thumb']) ? $tag['thumb'] : 0;
        $whereStr = isset($tag['where']) ? $tag['where'] : '';
        $strPage  = $tag['page'] = (isset($tag['page'])) ? ((substr($tag['page'], 0,
                1) == '$') ? $tag['page'] : (int)$tag['page']) : 0;

        //数据返回变量
        $return = isset($tag['return']) && trim($tag['return']) ? trim($tag['return']) : 'data';
        $order  = isset($tag['order']) ? $tag['order'] : 'is_top DESC,update_time DESC,id DESC';
        if ( ! empty($order) && $order == 'rand()') {
            $orderStr = 'orderRaw("rand(),id desc")';
        } elseif ( ! empty($order) && $order == 'hits') {
            $orderStr = 'order("click desc")';
        } else {
            $orderStr = 'order("'.$order.'")';
        }

        $where = '`status`=1';
        if ( ! empty($whereStr)) {
            $where .= " and ".$whereStr;
        }
        if ( ! empty($typeid)) {
            if (strpos($typeid, '$') === 0) {
                $where .= " and `type_id`=$typeid";
            } else {
                $where .= " and `type_id` in (".$typeid.")";
            }
        }
        if ( ! empty($field)) {
            if (strstr($field, '*')) {
                $fieldStr = "a.*";
            } else {
                $v1 = [];
                foreach (explode(',', $field) as $v) {
                    $v1[] = "a.".$v;
                }
                $fieldStr = implode(',', $v1);
            }
        }
        if ( ! empty($flag)) {
            $where .= " and flag in (".$flag.")";
        }
        if ( ! empty($thumb)) {
            $where .= " and image<>''";
        }
        //拼接php代码
        $parseStr = '<?php ';
        $parseStr .= '';
        if ($strPage) {
            $parseStr .= '$total=\think\facade\Db::name("article")->where("'.$where.'")->count();';
            $parseStr .= '$Page = new \lib\Page($total,'.$limit.',0);';
            $parseStr .= '$limitStr = $Page->limit();';
            $parseStr .= '$first  = explode(",", $limitStr)[0];';
            $parseStr .= '$limit  = explode(",", $limitStr)[1];';
            $parseStr .= '$'.$return.'=\think\facade\Db::name("article")->field("'.$fieldStr.',c.cate_name,c.cate_en")->alias("a")->leftJoin("category c","c.id = a.type_id")->where("'.$where.'")->limit($first,$limit)->'.$orderStr.'->select()->toArray();';
            $parseStr .= 'if($total>$limit):';
            $parseStr .= '$pages=$Page->pages($total);';
            $parseStr .= 'else: ';
            $parseStr .= '$pages="";';
            $parseStr .= 'endif;';
        } else {
            $parseStr .= '$'.$return.'=\think\facade\Db::name("article")->field("'.$fieldStr.',c.cate_name,c.cate_en")->alias("a")->leftJoin("category c","c.id = a.type_id")->where("'.$where.'")->limit("'.$limit.'")->'.$orderStr.'->select()->toArray();';
            $parseStr .= '$pages="";';
        }
        $parseStr .= 'if ( ! empty($'.$return.')) {';
        $parseStr .= 'for ($i = 0; $i < count($'.$return.'); $i++) {';
        $parseStr .= ' $'.$return.'[$i][\'url\'] = buildContentUrl($'.$return.'[$i][\'id\']);';
        $parseStr .= ' }';
        $parseStr .= '}';
        $parseStr .= ' ?>';
        $parseStr .= $content;
        if ( ! empty($parseStr)) {
            return $parseStr;
        }

        return false;
    }

    /**
     * 万能标签
     *
     * @param $content
     *
     * @return bool|string|void
     */
    public function tagGet($tag, $content)
    {
        $sqlPrefix = config('database.connections.mysql.prefix');
        //每页显示总数
        $limit = isset($tag['limit']) && intval($tag['limit']) > 0 ? intval($tag['limit']) : 10;
        //数据返回变量
        $return = isset($tag['return']) && trim($tag['return']) ? trim($tag['return']) : 'data';
        //order
        $order = (isset($tag["order"]) ? " ORDER BY ".$tag["order"] : "");
        //当前分页参数
        $page    = intval(input('page', 0));
        $strPage = $tag['page'] = (isset($tag['page'])) ? ((substr($tag['page'], 0,
                1) == '$') ? $tag['page'] : (int)$tag['page']) : 0;

        //SQL语句
        $sql = "";
        if (isset($tag['sql'])) {
            $tag['sql'] = $sql = str_replace(['think_', 'cmf_'], $sqlPrefix, strtolower($tag['sql']));
        }
        //表名
        $table = "";
        if (isset($tag['table'])) {
            $table = str_replace($sqlPrefix, '', $tag['table']);
        }
        if ( ! isset($sql) && ! isset($table)) {
            return false;
        }

        //删除，插入不执行！这样处理感觉有点鲁莽了，，，-__,-!
        if (isset($sql) && (stripos($sql, "delete")) !== false || isset($sql) && (stripos($sql,
                "insert")) !== false || isset($sql) && (stripos($sql, "update")) !== false) {
            return false;
        }
        //如果使用table参数方式，使用类似tp的查询语言效果
        if ($table) {
            $table = strtolower($table);
            if (isset($tag['where'])) {
                $tableWhere = $tag['where'];
            } else {
                $tableWhere = "1=1";
            }
        }
        //拼接php代码
        $parseStr = '<?php ';
        if ($table) {
            $parseStr .= '$get_db = \think\facade\Db::name(ucwords("'.$table.'"));';
            if (isset($tag['order'])) {
                $parseStr .= ' $get_db->order("'.$tag['order'].'"); ';
            }
            if ($strPage) {
                $parseStr .= '$total=\think\facade\Db::name(ucwords("'.$table.'"))->where("'.($tableWhere).'")->count();';
                $parseStr .= '$Page = new \lib\Page($total,'.$limit.',0);';
                $parseStr .= '$limitStr = $Page->limit();';
                $parseStr .= '$first  = explode(",", $limitStr)[0];';
                $parseStr .= '$limit  = explode(",", $limitStr)[1];';
                $parseStr .= '$'.$return.'=$get_db->where("'.($tableWhere).'")->limit($first,$limit)->select()->toArray();';
                $parseStr .= '$pages=$Page->pages($total);';
            } else {
                $parseStr .= '$'.$return.'=$get_db->where("'.($tableWhere).'")->limit($limit)->select()->toArray();';
                $parseStr .= '$pages="";';
            }
        } else {
            //判断是否变量传递
            if (substr(trim($sql), 0, 1) == '$') {
                $parseStr .= ' $_sql = str_replace(array("think_", "cmf_"), config("database.prefix"),'.$sql.');';
            } else {
                $parseStr .= ' $_sql = "'.str_replace('"', '\"', $sql).'";';
            }
            //判断分页
            if ($strPage) {
                $parseStr .= '$'.$return.'=\think\facade\Db::query($_sql."'.$order.' ");';
                $parseStr .= '$total = count($'.$return.');';
                $parseStr .= '$Page = new \lib\Page($total,'.$limit.',0);';
                $parseStr .= '$limit = $Page->limit();';
                $parseStr .= '$'.$return.'=\think\facade\Db::query($_sql."'.$order.' LIMIT ".$limit);';
                $parseStr .= '$pages=$Page->pages($total);';
            } else {
                $parseStr .= '$'.$return.'=\think\facade\Db::query($_sql."'.$order.' LIMIT '.$limit.'");';
                $parseStr .= '$pages="";';
            }
        }

        $parseStr .= ' ?>';
        $parseStr .= $content;
        if ( ! empty($parseStr)) {
            return $parseStr;
        }

        return false;
    }

}