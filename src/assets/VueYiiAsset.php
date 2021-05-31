<?php

namespace yiivue\assets;

use Yii;
use \yii\web\View;
use yii\web\AssetBundle;

/**
 * Vue原型(prototype)注入Yii相关操作方法属性
 * Class YiiPrototypeAsset
 * @package vuelte\vue\assets
 */
class VueYiiAsset extends AssetBundle {
    /**
     * 参数配置
     */
    public $sourcePath = __DIR__.'/../static/csrf';
    public $js = ['form-submit.js',];
    public $jsOptions = ['position' => View::POS_HEAD];

    /**
     * @param \yii\web\View $view
     */
    public function registerAssetFiles($view)
    {
        //parent
        parent::registerAssetFiles($view); // TODO: Change the autogenerated stub

        //submit form js
        $this->registerCsrfAuthentication($view);
    }

    /**
     * 注入CSRF认证的表单提交
     * @param $view
     */
    public function registerCsrfAuthentication($view){
        //get request
        $request = Yii::$app->getRequest();

        //submit form js
        $js[] = "\n";
        $js[] = 'Vue.prototype.$yii = Vue.prototype.$yii || {};';
        $js[] = 'Vue.prototype.$yii.submit=function(data,formName,method,action){';
        $js[] = "var form = new formSubmit();";
        $js[] = "form.setAction(action || '');";
        $js[] = "form.setMethod(method || 'post');";
        $js[] = "form.setFormName(formName);";
        //post handle
        $js[] = "if(method && method.toLowerCase() === 'get') {";
        $js[] = "form.setCsrfEnable(false);";
        $js[] = "data = Object.assign(".json_encode($request->get()).", data);";
        $js[] = "}";
        //get handle
        $js[] = "else{";
        $js[] = "form.setCsrfEnable(".($request->enableCsrfValidation?"true":"false").");";
        $js[] = "form.setCsrfParam('".$request->csrfParam."');";
        $js[] = "form.setCsrfToken('".$request->getCsrfToken()."');";
        $js[] = "}";
        //submit
        $js[] = "form.submit(data);";
        $js[] = "}";

        //register js
        $view->registerJs(implode("",$js),View::POS_HEAD);
    }
}