<?php if ( ! defined('BASE_PATH')) exit('No direct script access allowed');?>

<!DOCTYPE html> 
<html lang="en"> 
  <head> 
    <meta charset="utf-8"> 
    <title>SaeLayer CDN</title> 
    <meta name="description" content=""> 
    <meta name="author" content=""> 
    <style> 
*{
    margin:  0;
    padding:0;
    border:0;
}
 
body{
    font-size:14px;
    font-family:"微软雅黑","Microsoft Yahei";
    color: #222;
    background-color:#fff;
    word-wrap:word-break;
    word-break:break-all;
}
.small{
    font-size:0.8em;
    color:#666;
}
.text-right{
    text-align:right;
}
a{
    color: #f44;
    text-decoration:none;
}
h1,h2{
    font-weight:600;
}
blockquote{
    padding:10px 5px;
    border:1px solid #ddd;
    border-radius:3px;
    color: #f44;
    background-color:#f0f0f0;
    margin: 10px 0px;
}
pre{
    font-size:13px;
    font-family:Consolas;
    line-height:16px;
    display: block;
    margin:5px 0;
    border-left:2px solid #a44;
    padding-left:5px;
}
ul{
    list-style:inside square;
}
ul,p{
    margin-bottom:8px;
}
#body{
    width:800px;
    margin:0 auto;
    
    padding:5px 10px;
    margin-top:30px;
    border-radius:5px;
}
.unit{
    margin-top:20px;
    line-height:22px;
}
.unit h2{
    padding-bottom:5px;
    margin-bottom:5px;
    border-bottom:1px dashed #ddd;
    font-size:16px;
}
h1.page-header{
    text-align:center;
    font-size:28px;
    padding:10px 0;
    border-bottom:1px solid #ccc;
    margin-bottom:10px;
}
    </style> 
  </head> 
  <body> 
    <div id="body"> 
        <h1 class="page-header">SaeLayer CDN</h1> 
        <div id="content"> 
            <blockquote>SaeLayer CDN已经成功安装！</blockquote>
            <div class="unit"> 
                <h2>关于SaeLayerCDN</h2> 
                <div class="description"> 
                    <p>SaeLayerCDN是一个基于SAE的轻巧的CDN程序，你可以利用SaeLayerCDN快速地为你的博客或者网站搭建自己的CDN，从而加速网站的加载，提升用户体验。关于CDN，更多介绍在<a href="#">这里</a>。</p> 
                    <p>SaeLayerCDN的优势在于：</p> 
                    <ul> 
                        <li>基于SAE云平台，充分利用其强大的分布式架构，CDN加速效果甚至好于大部分的专业、收费的CDN。程序免费开源。</li> 
                        <li>一次修改，永久适用。</li> 
                        <li>你可以不会编程也能够为你的网站添加CDN，添加SaeLayerCDN不用修改网站的核心程序。</li> 
                        <li>如果不想使用CDN，能很容易就能改回来，而且数据仍在自己网站的服务器上。</li> 
                        <li>SaeLayerCDN架构超轻量，全部代码只有200余行，非常便于自己修改和定制。</li> 
                    </ul> 
                </div> 
            </div> 
            <div class="unit"> 
                <h2>安装和使用</h2> 
                <div class="description"> 
                    <ul> 
                        <li><strong>部署代码：</strong>可以手动部署（即创建应用、上传代码）或者在线安装<span class="small">（还没有通过审核o(︶︿︶)o）</span>。</li> 
                        <li><strong>配置程序：</strong>如果是手动部署，则要先在SAE后台创建一个storage的domain。然后设置index.php，修改下面的四个常量：
<pre> 
/**
 * 网站静态文件的根目录对应的URL地址
 * */
define('STATIC_URL','http://www.baidu.com/');
 
/**
 * SAE storage的domain
 * */
define('DOMAIN','cdn');
 
/**
 * 空请求时是否显示本文档
 * */
define('WELCOME_DOC',TRUE);
 
/**
 * 运行环境:development/testing/production
 * */
define('ENVIRONMENT','production');
</pre></li> 
                        <li><strong>使用CDN：</strong>将网站模板中静态文件的的根目录URL替换成你的SAE应用地址，如将<strong>http://blog.creatist.cn/</strong>logo.jpg换成<strong>http://mysqecdn.sinaapp.com/</strong>logo.jpg。<span class="small">对于网站程序的一个建议是，设置一个$cdn_base配置变量，然后静态文件的URL根据$cdn_base生成，以后修改CDN只要配置这个变量就可以了。</span></li> 
                        <li>最后，刷新你的网站。然后感受网站加载速度的飞跃。</li> 
                    </ul> 
                    <p></p> 
                </div> 
            </div> 
            <div class="unit"> 
                <h2>原理</h2> 
                <div class="description"> 
                    <p>大致就是取静态内容的过程中增加一个SAE层：前端从SAE取静态文件，当该文件是第一次被访问时，SAE从源服务器上取文件并保存到自己的storage里，然后返回给前端；之后就直接从SAE取而不需要再访问源服务器了。这样的好处就是在源服务器端可以实现无痛切换，不用使用像又拍CDN之类的API进行专门的编程，用户资源仍保存在源服务器上，只要将静态资源的前缀改为SAE的网址就行了，当不想使用CDN时可以再改回来。</p><p>例如，源文件地址是http://www.creatist.cn/avatar/21223.jpg（或者相对地址/avatar/21223.jpg），前端使用CDN，访问地址改成http://cdnxxx.sinaapp.com/avatar/21223.jpg（这通常是非常容易修改的）。SAE首先检查/avatar/21223.jpg在storage中是否已存在，如果存在即以前已经被访问过的话就直接取出来；如果不存在就从http://www.creatist.cn/avatar/21223.jpg保存到storage，以后就直接从storage里面取了。</p><p>SAE层还能实现其他很多功能，例如设置浏览器缓存、防盗链等等。</p> 
                </div> 
            </div> 
            <div class="unit"> 
                <h2>其他</h2> 
                <div class="description"> 
                    <p>SaeLayerCDN还在完善中，项目的Github地址是：<a href="https://github.com/Slacken/cdn">https://github.com/Slacken/cdn</a>，欢迎fork和贡献代码。</p> 
                    <p>我的博客是：<a href="http://blog.creatist.cn/">http://blog.creatist.cn/</a></p> 
                    <p><a href="http://sae.sina.com.cn/"><img src="http://static.sae.sina.com.cn/image/poweredby/120X33_transparent.gif" width="120" height="33" /></a></p> 
                </div> 
            </div> 
        </div> 
    </div> 
  </body> 
  </html>