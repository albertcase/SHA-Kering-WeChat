<?php

namespace Wechat\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('WechatApiBundle:Default:index.html.twig', array('name' => $name));
    }

    public function wechatAction()
    {
      $wechatObj = $this->container->get('my.Wechat');
      if(isset($_GET["echostr"])){
      return new Response($wechatObj->valid($_GET["echostr"]));
      }
      $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
      $respose = new Response($wechatObj->responseMsg($postStr));
      return $respose->send();
    }

    public function empowerAction(Request $request)
    {
      $wechat = $this->container->get('my.Wechat');
      $state = urldecode($request->query->get('state'));
      if(!$this->container->get('my.functions')->allowurl($state)){
        return new Response('this domain not allow empower');
      }
      $userinfo = $wechat->getoauthuserinfo();
      unset($userinfo['privilege'],$userinfo['unionid'],$userinfo['language']);
      $data = "openid={$userinfo['openid']}&nickname={$userinfo['nickname']}&headimgurl={$userinfo['headimgurl']}";
      if(strpos($state, '?') === false)
        $data = '?'.$data;
      $goto = 'http://'.$state.$data;
      return $this->redirect($goto);
    }

    public function sharetokenAction(Request $request){//jsonp
      $wechat = $this->container->get('my.Wechat');
      $callback = $request->query->get('callback');
      $url = urldecode($request->query->get('url'));
      if(!$this->container->get('my.functions')->allowjssdk($url)){
        return new Response(json_encode(array('code' => '10', 'msg' => 'no permission domain')));
      }
      return new Response($callback.'('.$wechat->getJsSDK($url).')');
    }

    public function sharetoken2Action(Request $request){//json get
      $wechat = $this->container->get('my.Wechat');
      $url = urldecode($request->query->get('url'));
      if(!$this->container->get('my.functions')->allowjssdk($url)){
        return new Response(json_encode(array('code' => '10', 'msg' => 'no permission domain')));
      }
      return new Response($wechat->getJsSDK($url));
    }

    public function uploadstoreAction()
    {
      $form = $this->container->get('form.uploadstore');
      $data = $form->DoData();
      return new Response(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function reloadstoremapAction()
    {
      $sql = $this->container->get('my.dataSql');
      $fs = new \Symfony\Component\Filesystem\Filesystem();
      $stors = $sql->searchData(array() ,array(), 'stores');
      foreach($stors as $x){
        $center = explode('号', $x['address']);
        $center['0'] = str_replace(" ","",$center['0']);
        $x['storename'] = str_replace(" ","",$x['storename']);
        $url = "http://apis.map.qq.com/ws/staticmap/v2/?center={$center['0']}&key=T22BZ-4T3HX-4M64Y-7FRRM-5L7HT-MPBYF&zoom=17&markers=color:red|{$center['0']}&size=850*650&labels=border:0|size:13|color:0xff0000|anchor:0|offset:0_-5|{$x['storename']}|{$center['0']}";
        $image = file_get_contents($url);
        $path = 'source/change/store/'.$x['id'].'_map.jpg';
        $fs->dumpFile($path, $image);
      }
      return new Response(json_encode("success", JSON_UNESCAPED_UNICODE));
    }

    public function empowertestAction(Request $request)
    {
      print_r($request->query->all());
      return new Response("\nsuccess");
    }

    public function api1Action(Request $request)
    {

      // print_r($request->query->all());
      // hasParameter
      // $papis = array();
      // $bundles = $this->container->getParameter('bundles');
      // foreach ($bundles as $x) {
      //   if($this->container->hasParameter($x.'_papis'))
      //     $papis = array_merge($papis ,$this->container->getParameter($x.'_papis'));
      // }
      // $url = $request->get("urll");
      $data = '四川省成都市红星路3段1号国际金融中心L215号铺';
      $out = explode('号', $data);
      print_r($out);
      // print_r($this->container->get('request_stack')->getCurrentRequest()->getSchemeAndHttpHost());
      return new Response("\n123456789");
    }
}
