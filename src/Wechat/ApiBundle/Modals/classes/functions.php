<?php

namespace Wechat\ApiBundle\Modals\classes;

use Wechat\ApiBundle\Modals\classes\WechatResponse;

class functions{

  private $_container;

  public function __construct($container){
    $this->_container = $container;
  }

  public function getmenus(){
    $dataSql = $this->_container->get('my.dataSql');
    $menus = $dataSql->getmenusDb();
    $main = array();
    foreach($menus as $x => $x_val){
      if($x_val['subOrder'] == '0'){
        $menus[$x]['belongto'] = 'Main Menu';
        $main[$x_val['mOrder']] = $x_val['menuName'];
      }
    }
    foreach($menus as $x => $x_val){
      if($x_val['subOrder'] != '0'){
        $menus[$x]['belongto'] = $main[$x_val['mOrder']];
      }
    }
    return $menus;
  }

  public function getmmenu(){
    $dataSql = $this->_container->get('my.dataSql');
    $menus = $dataSql->getmenusDb();
    $main = array();
    foreach($menus as $x => $x_val){
      if($x_val['subOrder'] == '0'){
        $main[$x_val['mOrder']] = $x_val['menuName'];
      }
    }
    return $main;
  }

  public function getOnlineImage($url){
    $dataSql = $this->_container->get('my.dataSql');
    $path = $dataSql->getLocalpath($url);
    if($path)
      return $path;
    $fs = new \Symfony\Component\Filesystem\Filesystem();
    $dir = date('Ym' ,strtotime("now"));
    if(!$fs->exists('upload/image/'.$dir)){
      $fs->mkdir('upload/image/'.$dir);
    }
    $image = file_get_contents($url);
    $path = 'upload/image/'.$dir.'/'.uniqid();
    $fs->dumpFile($path, $image);
    $path = '/'.$path;
    $dataSql->setLocalpath($url, $path);
    return $path;
  }

  public function allowurl($url){ // allow empower domain
    $allow = array(
      'shangrilawechat.samesamechina.com',
    );
    foreach($allow as $x){
      if(preg_match("/^".$x."/i", $url))
      return true;
    }
    return false;
  }

  public function allowjssdk($url){
    $allow = array(
      'shangrilawechat.samesamechina.com',
    );
    foreach($allow as $x){
      if(preg_match("/^".$x."/i", $url))
      return true;
    }
    return false;
  }
}
