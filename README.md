hui_hellogithub

把自己在浏览 GitHub 过程中，发现的有意思、高质量、容易上手的项目收集起来，这样便于以后查找和学习。并把这些有意思、有价值的开源项目分享给大家。最后就写了这个网站，便于查看和分享。

===============

**【基于ThinkPHP6.0和layui的快速开发的后台管理系统。】**

**仅用于学习使用。**
> 运行环境要求PHP7.1+，兼容PHP8.0。
>
> 数据库要求：mysql5.5+，推荐5.7。
>
> 编辑器使用了 editor.md | UEeditor | icEditor
>
by:xiaohuihui

## 使用说明

1、后台控制器都需要继承：AdminController

2、使用了注解权限：

# 注解权限

> 注解权限只能获取后台的控制器，也就是该app/admin/controller下

## 控制器注解权限

> 控制器类注解tag @ControllerAnnotation

- 注解类： HuiCMF\annotation\ControllerAnnotation
- 作用范围： CLASS
- 参数说明： title 控制器的名称（必填） auth 是否开启权限控制，默认为true （选填，Enum:{true, false}）

#### 示例

> 备注：注解前请先引用： use app\admin\annotation\ControllerAnnotation;

~~~
/**
 * @ControllerAnnotation(title="菜单管理")
 * Class Node
 * @package app\admin\controller\system
 */
class MenuController extends AdminController
{

}
~~~

## 方法节点注解权限

> 方法节点类注解tag @NodeAnotation

- 注解类： HuiCMF\annotation\NodeAnotation
- 作用范围： METHOD
- 参数说明： title 方法节点的名称（必填） auth 是否开启权限控制，默认为true （选填，Enum:{true, false}）

#### 示例：

> 备注：注解前请先引用： use app\admin\annotation\NodeAnotation;

~~~
/**
 * @NodeAnotation(title="菜单列表")
 */
public function index()
{
}
~~~

### 3、方法中重写了url()，更改为：__url()

所有原方法中的url()不要使用，要使用__url()方法来处理路由。

目的：隐藏模块名（admin）、后台入口。

更改为：http://你的域名/admin.php (admin.php可以自定义)

## 后台前端问题

### 1、前端auth权限验证

> 为什么前端也做权限认证，权限认证不应该是后端做的吗？ 这里的权限认证指的是前端判断是否有权限查看的数据（例如：添加、删除、编辑之类的按钮），这些只有在点击到对应的url之后，后端才会进行权限认证。 为了避免用户困扰，可以在此用上前端的权限认证，判断是否显示还是隐藏

**第一种示例, 通过php的auth()方法生成layui-hide样式属性。**

~~~
<a class="layui-btn layui-btn-sm layui-btn-normal {if !check_auth('system.admin/edit')}layui-hide{/if}" data-open="{:__url('system.admin/edit')}?id={{d.id}}"
               data-title="编辑管理">编辑</a>
~~~

**第二种, 通过php的auth()方法判断, 是否显示html**

~~~
{if check_auth('system.admin/edit')}
<a class="layui-btn layui-btn-sm layui-btn-normal " data-open="{:__url('system.admin/edit')}?id={{d.id}}"
               data-title="编辑管理">编辑</a>
{/if}
~~~

### 2、按钮属性

data-open：弹出层打开:width:90%，height:80%

data-open-full：弹出层打开全屏:width:100%，height:100%

data-confirm：普通询问对话框

data-delete：删除询问对话框

- data-reload="1"  刷新父级页面【例如点击编辑按钮弹出窗口后保存或者关闭窗口在列表页（父级）页面刷新。默认不写或者 data-reload="0"为不刷新】
- data-reload="2"  刷新当前页面【例如点击编辑按钮弹出窗口后保存或者关闭窗口在当前页面刷新。默认不写或者 data-reload="0"为不刷新】 【实例：】

~~~
//弹出层打开:width:90%，height:80%
<a href="javascript:;" data-open="{:__url('system.node/index')}" data-title="测试编辑打开" data-reload="1">编辑</a>

//弹出层打开全屏:width:100%，height:100%
<a href="javascript:;" data-open-full="{:__url('system.node/index')}" data-title="测试添加打开">添加</a>

//删除询问对话框
<a href="javascript:;" data-delete="{:__url('system.node/index')}" data-title="您确定要删除吗？">1231232313</a>

//普通询问对话框
<a href="javascript:;" data-confirm="{:__url('system.node/index')}" data-title="您确定要取消收藏吗？">1231232313</a>

~~~

### 前端模板标签

#### 0、通用标签

~~~
//调用模板标签
{include file='index/index'}

//模板调用css、js等：
__STATIC_INDEX__
例如：__STATIC_INDEX__css/base.css

//调用栏目ID为1的栏目名称及链接
<a href="{:get_category(1, 'url')}">{:get_category(1, 'cate_name')}</a>

//调用地步版权信息：
{:get_config('site_copyright')}

//字符串截取
{:str_cut($vo.description, 20)}

//获取当前位置面包屑导航
<a href="{$siteurl}">首页</a>&gt; {:catpos($catid)}

//上一篇、下一篇
上一篇：{$pre|raw}
下一篇：{$next|raw}

//内容页url地址
{$page_url}

//单页面获取内容
{huicmf:page_content}

~~~

#### 1、万能标签

~~~
{huicmf:get sql="SELECT * FROM cmf_article" order="id desc" limit="2" return="data" page="1"}
{volist name='data' id='vo'}
{$vo.title}<br>
{/volist}
{$pages|raw}
~~~

~~~
{huicmf:get table="article" limit="2" page="1" return="data"}
{volist name='data' id='vo'}
{$vo.title}<br>
{/volist}
<!--分页-->
{$pages|raw}
~~~

#### 2、获取栏目导航

> 栏目导航有缓存，3600秒；修改后需要清除缓存才能生效

~~~
{huicmf:nav field="id,cate_name,parent_id,cate_en,type" where="parent_id=0" limit="20" return="data"}
{volist name="data" id="v"}
<li>
    <a href="{$v.url}" target="_blank" title="{$v.cate_name}" {if ($v.id eq $catid)}class="selected"{/if}>{$v.cate_name}</a>
    {if $v['parent_id']!=$v['id']}
    {php} $r = get_childcat($v['id']);{/php}
    <ul class="sub_nav">
        {volist name='r' id='v'}
        <li><a href="{$v.url}">{$v.cate_name}</a></li>
        {/volist}
    </ul>
    {/if}
</li>
{/volist}
~~~

#### 3、友情链接标签

> 栏目导航有缓存，3600秒；修改后需要清除缓存才能生效

~~~
{huicmf:link field="url,name" typeid="0" limit="20"}
{volist name='data' id='vo'}
<li><a href="{$vo.url}" target="_blank">{$vo.name}</a></li>
{/volist}
~~~

#### 4、调用栏目ID为1的栏目下的二级栏目

~~~
{php}$data = get_childcat(12);dump($data);{/php}
{foreach $data as $key=>$vo }
{$vo.id}:{$vo.cate_name}
{/foreach}
~~~

#### 5、获取栏目下的列表数据（带分页）

**typeid里面的值：**

1、带 $ 符号，则默认自动获取对应栏目id

2、其他则为字符串，多条用 , 隔开。如： typeid="2,3"；如果默认获取当前栏目：typeid="$catid"

3、默认order排序方式：is_top DESC,update_time DESC,id DESC；

随机排序：order="rand()"
点击量排序：order="hits"

4、flag属性 5、thumb属性： 当 thumb="1"时；只获取带有缩略图的数据

~~~
{huicmf:lists field="*" limit="10" page="1" return="data" order="rand()" flag="1" hits="1" typeid="$catid" /}
{volist name='data' id='vo'}
{$vo.title} | {$vo.create_time|date='Y-m-d H:i:s'}<br>
{$vo.image|default='/static/water/nopic.jpeg'}<br>
{:__url('show',['id'=>$vo.id])}
{:__url('lists',['catdir'=>$vo.cate_en])}
<br>
{/volist}
{$pages|raw}
~~~

#### 6、轮播图标签

~~~
{huicmf:banner field="*" typeid="0" limit="20"}
{volist name='data' id='vo'}
<li><a href="{$vo.url}" target="_blank"><img src="{$vo.image}" alt="{$vo.title}"></a></li>
{/volist}
~~~

#### 7、内容页tag标签

> 栏目导航有缓存，3600秒；修改后需要清除缓存才能生效

~~~
{huicmf:centent_tag id="$id" limit="10" return="tags"/}
{volist name="tags" id="vo"}
    <a href="{:__url('index/index/tags',['tag'=>$vo.tag])}" target="_blank">{$vo.tag}</a>
{/volist}
~~~

#### 8、全站tag标签

~~~
{huicmf:tag limit="20" return="tags"}
{volist name='tags' id='vo'}
    <a href="{$vo.url}" target="_blank">{$vo.tag}({$vo.total})</a>
{/volist}
~~~

## 备注前端页面使用md解析方法：

> 本前端页面默认直接解析md转换后的html，可能和原md直接显示的效果不一样

### 1、引用文件

~~~
<link rel="stylesheet" href="__LIB__/editor.md-1.5.0/css/editormd.preview.css" />
<script  src="__LIB__/editor.md-1.5.0/editormd.js"></script>
<script  src="__LIB__/editor.md-1.5.0/lib/marked.min.js"></script>
<script  src="__LIB__/editor.md-1.5.0/lib/prettify.min.js"></script>
<script  src="__LIB__/editor.md-1.5.0/lib/underscore.min.js"></script>
<script  src="__LIB__/editor.md-1.5.0/lib/flowchart.min.js"></script>
<script  src="__LIB__/editor.md-1.5.0/lib/jquery.flowchart.min.js"></script>
~~~

### 2、html

~~~
<div id="content">
    <textarea style="display:none;">
        {$info.content_md|raw}
    </textarea>
</div>
~~~

### 3、js

~~~
  var testEditor;
  $(function () {
    testEditor = editormd.markdownToHTML("content", {//注意：这里是上面DIV的id
      htmlDecode: "style,script,iframe",
      emoji: true,
      taskList: true,
      tex: true, // 默认不解析
      flowChart: true, // 默认不解析
      sequenceDiagram: true, // 默认不解析
      codeFold: true,
    });});
~~~

## 特别感谢

以下项目排名不分先后

* ThinkPHP：[https://github.com/top-think/framework](https://github.com/top-think/framework)

* EasyAdmnin：[https://gitee.com/zhongshaofa/easyadmin](https://gitee.com/zhongshaofa/easyadmin)

* Layuimini：[https://github.com/zhongshaofa/layuimini](https://github.com/zhongshaofa/layuimini)

* Annotations：[https://github.com/doctrine/annotations](https://github.com/doctrine/annotations)

* Layui：[https://github.com/sentsin/layui](https://github.com/sentsin/layui)

* Jquery：[https://github.com/jquery/jquery](https://github.com/jquery/jquery)

* NKeditor：[https://gitee.com/blackfox/NKeditor](https://gitee.com/blackfox/NKeditor)

* CKEditor：[https://github.com/ckeditor/ckeditor4](https://github.com/ckeditor/ckeditor4)

## 免责声明

> 任何用户在使用`HuiCMF`后台框架前，请您仔细阅读并透彻理解本声明。您可以选择不使用`HuiCMF`后台框架，若您一旦使用`HuiCMF`后台框架，您的使用行为即被视为对本声明全部内容的认可和接受。

* `HuiCMF`后台框架是一款开源免费的后台快速开发框架 ，主要用于更便捷地开发后台管理；其尊重并保护所有用户的个人隐私权，不窃取任何用户计算机中的信息。更不具备用户数据存储等网络传输功能。
* 您承诺秉着合法、合理的原则使用`HuiCMF`后台框架，不利用`HuiCMF`后台框架进行任何违法、侵害他人合法利益等恶意的行为，亦不将`HuiCMF`后台框架运用于任何违反我国法律法规的 Web 平台。
* 任何单位或个人因下载使用`HuiCMF`后台框架而产生的任何意外、疏忽、合约毁坏、诽谤、版权或知识产权侵犯及其造成的损失 (包括但不限于直接、间接、附带或衍生的损失等)，本开源项目不承担任何法律责任。
* 用户明确并同意本声明条款列举的全部内容，对使用`HuiCMF`后台框架可能存在的风险和相关后果将完全由用户自行承担，本开源项目不承担任何法律责任。
* 任何单位或个人在阅读本免责声明后，应在《MIT 开源许可证》所允许的范围内进行合法的发布、传播和使用`HuiCMF`后台框架等行为，若违反本免责声明条款或违反法律法规所造成的法律责任(包括但不限于民事赔偿和刑事责任），由违约者自行承担。
* 如果本声明的任何部分被认为无效或不可执行，其余部分仍具有完全效力。不可执行的部分声明，并不构成我们放弃执行该声明的权利。
* 本开源项目有权随时对本声明条款及附件内容进行单方面的变更，并以消息推送、网页公告等方式予以公布，公布后立即自动生效，无需另行单独通知；若您在本声明内容公告变更后继续使用的，表示您已充分阅读、理解并接受修改后的声明内容。

