<?php
/**
 * Created by PhpStorm.
 * User: Mustard
 * Date: 2019/4/14
 * Time: 9:15
 */
namespace app\helper;
class JsonOut
{
    function __construct($code,$message,$data){

    }

    static function index($code,$message,$data=''){
        header('Content-Type:application/json; charset=utf-8');
        echo(json_encode(['code'=>$code,'message'=>$message,'data'=>$data]));
        exit;
    }
}