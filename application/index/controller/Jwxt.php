<?php
/**
 * Created by PhpStorm.
 * User: Mustard
 * Date: 2019/4/14
 * Time: 12:07
 */

namespace app\index\controller;
use app\helper\JsonOut;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use think\Controller;
use think\Request;
use think\Session;
use QL\QueryList;


class Jwxt extends Controller{
    protected $client;
    public $jar;

    function __construct(Request $request = null){
        parent::__construct($request);
        $this->client = new Client();
        $jar = Session::get('jar');
        $this->jar = $jar?unserialize($jar):new CookieJar();
    }


    public function getGradeHtml($xnArr=['xn'=>2018,'xq'=>0],$client=null)
{
    $client = $client ?: $this->client;
    $user = $client->request('POST', 'http://jwxt.dgut.edu.cn/dglgjw/student/xscj.stuckcj_data.jsp',
        ['form_params' => [
            'sjxz' => 'sjxz3',
            'ysyx' => 'yscj',
            'zx' => '1',
            'fx' => '1',
            'xypjwchcnckcj' => '0',
            'pjwchckcjklpbcj' => '0',
            'xn' => $xnArr['xn'],
            'xn1' => $xnArr['xn']+1,
            'xq' => $xnArr['xq'],
            'ysyxS' => 'on',
            'sjxzS' => 'on',
            'zxC' => 'on',
            'fxC' => 'on',
            'menucode_current' => ''
        ],'cookies'=>$this->jar]);
    $mes = $user->getBody();
    $content = iconv("gb2312", "utf-8//IGNORE", $mes);
    return $content;
}

    public function getRealGrade($xnArr=['xn'=>2018,'xq'=>0],$client=null)
    {
        $client = $client ?: $this->client;
        $user = $client->request('POST', 'http://jwxt.dgut.edu.cn/dglgjw/student/xscj.stuckcj_data.jsp',
            ['form_params' => [
                'sjxz' => 'sjxz3',
                'ysyx' => 'yxcj',
                'zx' => '1',
                'fx' => '1',
                'xypjwchcnckcj' => '0',
                'pjwchckcjklpbcj' => '0',
                'xn' => $xnArr['xn'],
                'xn1' => $xnArr['xn']+1,
                'xq' => $xnArr['xq'],
                'ysyxS' => 'on',
                'sjxzS' => 'on',
                'zxC' => 'on',
                'fxC' => 'on',
                'menucode_current' => ''
            ],'cookies'=>$this->jar]);
        $mes = $user->getBody();
        $html = iconv("gb2312", "utf-8//IGNORE", $mes);
        $cjRules = ['cj'=>['tr:last','text']];
        $cjData = QueryList::html($html)->rules($cjRules)->removeHead()->query()->getData();
//        $cjData = $cjData->map(function ($e){dump($e);});
        $cjData = $cjData->flatten()->all();
        $cjList = explode("\r\n\t\t\t\t\t",$cjData[0]);
//         dump($cjData);
//        print_r($cjList);
        return ['pjjd'=>$cjList[6],'pjcj'=>$cjList[7]];
    }


    public function getGradeJson($xnArr=['xn'=>2018,'xq'=>0],$client=null){
        $html = $this->getGradeHtml($xnArr,$client);
//        $html = iconv("gb2312", "utf-8//IGNORE", $html);
//        echo $html;
        $infoRules = ['info'=>['div','text']];
        $cjRules = ['cj'=>['tr','text']];
        $infoData = QueryList::html($html)->rules($infoRules)->removeHead()->query()->getData();
        $cjData = QueryList::html($html)->rules($cjRules)->removeHead()->query()->getData();
        $infoData = $infoData->flatten()->all();
//        print_r($infoData);exit;
        $cjData = $cjData->flatten()->all();
//        print_r($infoData);
        /*序号
        课程/环节
        学分
        类别
        修读性质
        考核方式
        取得方式
        成绩
        备注*/
        foreach ($cjData as $k=>$v){
            if(!($k == 0 || $k==1)){
                $list[] = array_combine(['xh','km','xf','lb','xdxz','khfs','qdfs','cj','bz'],explode("
				",$v));
            }
        }
        foreach ($list as $k=>$v){
            $list[$k] = $re = preg_replace('/^\[\d*\]/','',$list[$k]);
        }
//        print_r($list);
        $real = $this->getRealGrade();
        $jsonArr = [
            'info'=>[
                'name'=>str_replace('姓名：','',$infoData[6]),
                'num'=>str_replace('学号：','',$infoData[5]),
                'xy'=>str_replace('院(系)/部：','',$infoData[3]),
                'class'=>str_replace('行政班级：','',$infoData[4]),
                'time'=>str_replace('打印时间：','',$infoData[7]),
                'xq' =>str_replace('学年学期：','',$cjData[0]),
                'pjjd'=> $real['pjjd'],
                'pjcj'=>$real['pjcj']
            ],
            'data'=>$list
        ];
//        print_r($jsonArr);
        JsonOut::index(200,'success',$jsonArr);
    }
}