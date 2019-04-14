<?php
namespace app\index\controller;
use think\Controller;
use GuzzleHttp\Client;
use QL\QueryList;
use GuzzleHttp\Cookie\CookieJar;
use think\Request;
use app\helper\JsonOut;
use think\Session;
use app\helper\GetParam;

class Index extends Controller{

    protected $token;
    protected $userinfo;
    protected $client;
    public $jar;
    function __construct(Request $request = null){
        parent::__construct($request);
        $this->client = new Client(['cookies' => true]);//让cookies通用
        $jar = Session::get('jar');
        $this->jar = $jar?unserialize($jar):new CookieJar();
    }

    function index(){
        echo 'done';
    }

    function getLogin(){
        $this->info =$info =  GetParam::get();
//        dump($this->info);
        $this->LoginCas();
        $this->userinfo = ['username' => $info['username'],
            'password' => $info['password'],
            '__token__' => $info['__token__'],
            'wechat_verify' => $info['wechat_verify']];
//        var_dump($this->getGradeHtml());
//        echo $this->getGradeJson();
    }

    function getToken(){
        $url = 'https://cas.dgut.edu.cn/home/Oauth/getToken/appid/jwxt.html';
        $client =$this->client;
        $response = $client->request('GET',$url,['cookies'=>$this->jar]);
        session('jar',serialize($this->jar));
        $phpssid = sprintf($this->jar->getCookieByName('PHPSESSID'));
        $data = $response->getBody();
        $ql = QueryList::html($data);
        $htmls = $ql->find('script')->htmls();
        $Tokens = $htmls->all()[7];
//         var_dump($Tokens);
        $token = substr($Tokens, 124, 32);
//        var_dump($token);
        $this->token = $token ?: false;
        JsonOut::index(200,$phpssid,$token);
        return $token ?: false;
    }

    function LoginCas($wechat = null, $client = null)
    {
        $client = $client ?: $this->client;
//        dump($this->info);
        $this->jar = unserialize(Session::get('jar'));
        if ($this->info) {
            $response = $client->request('POST','https://cas.dgut.edu.cn/home/Oauth/getToken/appid/jwxt.html',
                ['form_params' => $this->info,
                  'headers'=>
                        ['User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
                        'Referer'=>'https://cas.dgut.edu.cn/home/Oauth/getToken/appid/jwxt.html',
                        'X-Requested-With'=>'XMLHttpRequest',
//                            'Cookies'=>$phpssid
                        ],
                 'cookies'=>$this->jar,
                 'allow_redirects' => true
                ]);
            $casToJwxtMessage = $response->getBody();
            $casToJwxtMessage = json_decode($casToJwxtMessage,true);
            $casToJwxtMessage = json_decode($casToJwxtMessage);
//            var_dump($casToJwxtMessage);exit;
            if ($casToJwxtMessage->code == 1) {
                $jar = new CookieJar();
                $response = $reditUrl = $client->request('GET',$casToJwxtMessage->info,['allow_redirects' => false]);
//                var_dump($casToJwxtMessage->info);
                $location = 'http://jwxt.dgut.edu.cn'.$response->getHeader('Location')[0];
                //请求跳转后的地址

                $reditUrl = $client->request('GET',$location,['allow_redirects' => false,'cookies'=>$jar]);
                $this->jar= $jar;
                Session::set('jar',serialize($jar));
//                return $reditUrl;
                JsonOut::index(302,'success',['href'=>'./grade.html']);
            }else if ($casToJwxtMessage->code == 23){
                JsonOut::index(23,'enter wechat code');
            }
        }
    }

}
