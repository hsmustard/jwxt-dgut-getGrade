<?php
/**
 * Created by PhpStorm.
 * User: Mustard
 * Date: 2019/4/14
 * Time: 9:56
 */

namespace app\helper;
use think\Request;


class GetParam{
    static function get(){
        $r = Request::instance();
        $params = $r->param();
        foreach ($params as $k=>$v){
//            echo '$k=>'.$k.'  $v=>' . $v;
            //过滤输入
            #code
        }
        return $params;
    }

}