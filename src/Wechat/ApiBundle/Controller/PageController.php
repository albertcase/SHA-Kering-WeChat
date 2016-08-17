<?php

namespace Wechat\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class PageController extends Controller
{
  public function menuAction(){
    $functions = $this->container->get('my.functions');
    $menus = $functions->getmenus();
    return $this->render('WechatApiBundle:Page:menu.html.twig', array('menus' => $menus));
  }

  public function keywordAction(){
    $sql = $this->container->get('my.dataSql');
    $wordlist = $sql->getkeywordlist();
    return $this->render('WechatApiBundle:Page:keyword.html.twig', array('wordlist' => $wordlist));
  }

  public function replyAction(){
    return $this->render('WechatApiBundle:Page:replay.html.twig');
  }

  public function groupnewsAction(){
    $wehcat = $this->container->get('my.Wechat');
    $search = array(
      'type' => 'news',
      'offset' => '0',
      'count' => '18',
    );
    $check = $wehcat->getMateriallist($search);
    if(isset($check['errcode'])){
      $data = array();
    }else{
      $data = $check['item'];
      $functions = $this->container->get('my.functions');
      foreach($data as $x => $x_val){
        foreach($x_val['content']['news_item'] as $xx => $xx_val){
          $data[$x]['content']['news_item'][$xx]['thumb_url'] = $functions->getOnlineImage($data[$x]['content']['news_item'][$xx]['thumb_url']);
        }
      }
    }
    return $this->render('WechatApiBundle:Page:groupnews.html.twig', array('newslist' => $data));
  }

  public function storeAction($id){
    $store = $this->container->get('my.dataSql')->searchData(array('id' => $id) ,array(), 'stores');
    if(!$store)
      return $this->render('UserBundle:Page:notfound.html.twig');
    $store = $store[0];
    $fs = new \Symfony\Component\Filesystem\Filesystem();
    $pisurl = 'source/change/store/'.$store['id'].'.jpg';
    if($fs->exists($pisurl))
      $store['image'] = '/'.$pisurl;
    return $this->render('WechatApiBundle:Page:store.html.twig', $store);
  }
}
