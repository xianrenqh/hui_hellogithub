<?php
/**
 * Created by PhpStorm.
 * User: 小灰灰
 * Date: 2021-12-17
 * Time: 下午4:50:29
 * Info: 伪原创插件。词库位于当前目录 words.ini文件【utf-8】
 */

namespace lib;

class Original
{

    private $replaced = [];

    /**
     * 替换正文
     *
     * @param $content
     *
     * @return void
     */
    public function update_content($text)
    {
        $reWords = $this->getWords();
        foreach ($reWords as $key => $val) {
            if (preg_match("/".$key."/", $text) && ! in_array($key, $this->replaced)) {
                $text = str_replace($key, (is_array($val) ? $val[array_rand($val)] : $val), $text);
                array_push($this->replaced, $val);
            }
        }

        halt($text);
    }

    /**
     * 取词库
     * @return void
     */
    private function getWords()
    {
        $words     = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'words.ini');
        $wordsArr  = array_filter(explode(PHP_EOL, $words));
        $wordsArr1 = [];
        foreach ($wordsArr as $key => $v) {
            $val                = explode(',', $v);
            $wordsArr1[$val[0]] = $val[1];
        }

        return $wordsArr1;
    }

}