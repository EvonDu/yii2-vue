<?php
namespace yiivue\core;

use yii\web\View;

class VueComponent{
    private $content;

    //构造函数
    public function __construct($content){
        $this->content = $content;
    }

    //引入视图
    public function export(View $view){
        $this->begin();
        print $this->content;
        $is_success = $this->end($view);
        return $is_success;
    }

    //组件开始
    public function begin(){
        //开启缓冲区
        ob_start();
    }

    //组件结束
    public function end(View $view){
        //读取并关闭缓冲区
        $content = ob_get_contents();
        ob_end_clean();

        //提取template元素
        $template = $this->_popElement('component-template',$content,true);
        $template = $this->_extractRegisterJsCss($template, $view);
        $template = $this->_getTemplateStr($template);
        $template = $this->_htmlCompress($template);

        //判断是否有template进行后续操作
        if($template){
            //提取script元素，并添加
            $script = $this->_popElement('script',$content,true);
            $script = str_replace("{{component-template}}",$template,$script);
            $script = $this->_jsCompress($script);
            $view->registerJs($script, View::POS_HEAD);

            //提取style元素，并添加
            $style = $this->_popElement('style',$content,true);
            $view->registerCss($style);
        }

        //判断是否导入成功
        if($template)
            return true;
        else
            return false;
    }

    //提取元素(提取完会删除原数组中)
    private function _popElement($tag, &$str, $inner=false){
        preg_match("/<$tag>[\w\W]+?<\/$tag>/", $str, $matches);
        if($matches){
            //获取元素内容
            $content = $matches[0];
            //移除内容中元素
            $str = str_replace("$content","",$str);
            //剥离标签
            if($inner)
                $content = preg_replace("/<$tag>|<\/$tag>/","",$content);
            //返回元素内容
            return $content;
        }
        else{
            return "";
        }
    }

    //分离并导入模板中的JS和CSS,并返回HTML
    private function _extractRegisterJsCss($str, View $view){
        //定义处理串
        $result = $str;

        //循环处理JS脚本
        while($script = $this->_popElement("script", $result, true)){
            $view->registerJs($script, View::POS_END);
        }

        //循环处理CSS部分
        while($style = $this->_popElement("style", $result, true)){
            $view->registerCss($style);
        }

        //返回结果
        return $result;
    }

    //处理模板字符串
    private function _getTemplateStr($template){
        //转义所有'\'标签
        $str = preg_replace("/'/",'\\\'',$template);

        //返回结果
        return $str;
    }

    //HTML压缩
    private function _htmlCompress($string){
        $string = str_replace("\r\n", '', $string); //清除换行符
        $string = str_replace("\n", '', $string); //清除换行符
        $string = str_replace("\t", '', $string); //清除制表符
        $pattern = array (
            "/> *([^ ]*) *</",
            "/[\s]+/",
            "/<!--[\\w\\W\r\\n]*?-->/",
            "/\" /",
            "/ \"/",
            "'/\*[^*]*\*/'"
        );
        $replace = array (
            ">\\1<",
            " ",
            "",
            "\"",
            "\"",
            ""
        );
        return preg_replace($pattern, $replace, $string);
    }

    //JS压缩
    private function _jsCompress($string){
        return JSMin::minify($string);
    }
}