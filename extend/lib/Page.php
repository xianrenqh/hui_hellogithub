<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-01-11
 * Time: 17:10:54
 * Info:
 */

namespace lib;

class Page
{

    private $url;                //当前URL

    private $total_rows;        //一共多少条数据

    private $list_rows;        //每页显示记录数

    private $total_page;        //总的分页数

    private $now_page;            //当前页

    private $parameter;        //分页跳转的参数

    private $url_rule;            //URL规则

    private $page_prefix;        //URL分页前缀,默认为list

    /**
     * 构造函数
     *
     * @param int   $total_rows 一共多少条数据
     * @param int   $list_rows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     */
    public function __construct($total_rows, $list_rows = 10, $parameter = array())
    {
        $this->total_rows  = $total_rows;
        $this->list_rows   = $list_rows;
        $this->total_page  = ceil($this->total_rows / $this->list_rows);
        $this->now_page    = ! empty(input('page')) ? intval(input('page')) : 1;
        $this->now_page    = $this->now_page > 0 ? $this->now_page : 1;
        $this->parameter   = empty($parameter) ? input() : $parameter;
        $this->url_rule    = defined('LIST_URL') && LIST_URL ? true : false;
        $this->page_prefix = defined('PAGE_PREFIX') ? PAGE_PREFIX : 'list';
        $this->url         = $this->geturl();
    }

    /**
     * 获得当前地址
     */
    protected function geturl()
    {
        unset($this->parameter['m'], $this->parameter['c'], $this->parameter['a']);
        $this->parameter['page'] = 'PAGE';

        $appName    = (app('http')->getName());
        $controller = request()->controller(true);
        $action     = request()->action(true);

        return url($appName."/".$controller."/".$action, $this->parameter);
    }

    /**
     * 生成链接URL
     */
    private function make_url($page)
    {
        return str_replace('PAGE', $page, $this->url);
    }

    /**
     * 总页数
     */
    public function total()
    {
        return $this->total_page;
    }

    /**
     * 获得当前页
     */
    public function getpage()
    {
        return $this->now_page;
    }

    /**
     * 获得首页
     */
    public function gethome()
    {
        return '<a href="'.$this->make_url(1).'" class="homepage">首页</a>';
    }

    /**
     * 获得尾页
     */
    public function getend()
    {
        return '<a href="'.$this->make_url($this->total_page).'" class="endpage">末页</a>';
    }

    /**
     * 获得上页
     */
    public function getpre()
    {
        if ($this->now_page <= 1) {
            return '<a href="'.$this->make_url(1).'" class="nopage">上一页</a>';
        }

        return '<a href="'.$this->make_url($this->now_page - 1).'" class="prepage">上一页</a>';
    }

    /**
     * 获得下页
     */
    public function getnext()
    {
        if ($this->now_page >= $this->total_page) {
            return '<a href="'.$this->make_url($this->now_page).'" class="nopage">下一页</a>';
        }

        return '<a href="'.$this->make_url($this->now_page + 1).'" class="nextpage">下一页</a>';
    }

    /**
     * 获取开始数列
     */
    public function start_rows()
    {
        if ($this->total_page && $this->now_page > $this->total_page) {
            $this->now_page = $this->total_page;
        }

        return ($this->now_page - 1) * ($this->list_rows);
    }

    /**
     * 每页显示的条数
     */
    public function list_rows()
    {
        return $this->list_rows;
    }

    /**
     * 供外部分页使用
     */
    public function limit()
    {
        return $this->start_rows().','.$this->list_rows();
    }

    /**
     * 数字数字列表页---[1][2][3][4][5]
     */
    public function getlist()
    {
        $str = '';
        if ($this->total_page <= 5) {
            for ($i = 1; $i <= $this->total_page; $i++) {
                $class = $this->now_page == $i ? ' curpage' : '';
                $str   .= '<a href="'.$this->make_url($i).'" class="listpage'.$class.'">'.$i.'</a>';
            }
        } else {
            if ($this->now_page <= 3) {
                $p = 5;
            } else {
                $p = ($this->now_page + 2) >= $this->total_page ? $this->total_page : $this->now_page + 2;
            }
            for ($i = $p - 4; $i <= $p; $i++) {
                $class = $this->now_page == $i ? ' curpage' : '';
                $str   .= '<a href="'.$this->make_url($i).'" class="listpage'.$class.'">'.$i.'</a>';
            }
        }

        return $str;
    }

    /**
     * 获取全部列表---首页上页[1][2][3][4][5]下页尾页
     */
    public function getfull()
    {
        if ($this->total_rows == 0) {
            return '';
        }

        return ($this->gethome()).($this->getpre()).($this->getlist()).($this->getnext()).($this->getend());
    }

    /**
     * 分页显示
     *
     * @param $string
     */
    public function pages($total)
    {
        //当前页：$this->page->getpage();
        return '<span class="pageinfo">共<strong>'.$this->total().'</strong>页<strong>'.$total.'</strong>条记录</span>'.$this->getfull();
    }

}