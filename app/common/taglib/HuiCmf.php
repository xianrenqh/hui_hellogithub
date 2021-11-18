<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-11-18
 * Time: 上午9:55:06
 * Info:
 */

namespace app\common\taglib;

use think\template\TagLib;

class HuiCmf extends TagLib
{

    public $page, $total;

    protected $tags = [
        'nav'  => ['attr' => 'field,order,limit,where', 'close' => 0],
        'get'  => ['attr' => 'sql,table,where,limit,typeid,return', 'close' => 0],
        'link' => ['attr' => 'field,typeid,limit,return', 'close' => 0],
    ];

    public function tagNav($tag, $content)
    {
        $field = isset($tag['field']) ? $tag['field'] : '*';
        $order = isset($tag['order']) ? $tag['order'] : 'sort_order ASC';
        $limit = isset($tag['limit']) ? $tag['limit'] : '20';
        $where = isset($tag['where']) ? $tag['where'].' AND ' : '';
        //数据返回变量
        $return = isset($tag['return']) && trim($tag['return']) ? trim($tag['return']) : 'data';

        $where .= '`show_in_nav`=1';
        //拼接php代码
        $parseStr = '<?php ';
        $parseStr .= '$'.$return.'=\think\facade\Db::name("category")->field("'.$field.'")->where("'.$where.'")->order("'.$order.'")->limit('.$limit.')->select()->toArray();';
        $parseStr .= 'if ( ! empty($'.$return.')) {';
        $parseStr .= 'for ($i = 0; $i < count($'.$return.'); $i++) {';
        $parseStr .= ' $'.$return.'[$i][\'url\'] = buildCatUrl($'.$return.'[$i][\'cate_en\']);';
        $parseStr .= ' }';
        $parseStr .= '}';
        $parseStr .= ' ?>';
        $parseStr .= $content;

        if ( ! empty($parseStr)) {
            return $parseStr;
        }

        return;
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
        $parseStr .= '$'.$return.'=\think\facade\Db::name("link")->field("'.$field.'")->where("'.$where.'")->order("listorder asc")->limit('.$limit.')->select()->toArray();';
        $parseStr .= ' ?>';
        $parseStr .= $content;

        if ( ! empty($parseStr)) {
            return $parseStr;
        }
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

        return;
    }

}