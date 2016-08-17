<?php

namespace Wechat\ApiBundle\Modals\classes;

use Wechat\ApiBundle\Modals\classes\WechatResponse;

class Wechat{

  private $_container;
  private $_urls;
  private $_TOKEN = null;
  private $_appid = null;
  private $_secret = null;

  public function __construct($container){
    $this->_container = $container;
    $this->setUrls();
    $this->_TOKEN = $container->getParameter('wechat_Token');
    $this->_appid = $container->getParameter('wechat_AppID');
    $this->_secret = $container->getParameter('wechat_AppSecret');
  }

  public function valid($echoStr){
   if($this->checkSignature())
      return $echoStr;
  }

  private function checkSignature()
  {
    $signature = $_GET["signature"];
    $timestamp = $_GET["timestamp"];
    $nonce = $_GET["nonce"];
    $token = $this->_TOKEN;
    $tmpArr = array($token, $timestamp, $nonce);
    sort($tmpArr, SORT_STRING);
    $tmpStr = implode( $tmpArr );
    $tmpStr = sha1( $tmpStr );

    if( $tmpStr == $signature ){
      return true;
    }else{
      return false;
    }
  }

  // response function *******************************************************
  public function responseMsg($postStr){
    if (!empty($postStr)){
      $WechatResponse = new WechatResponse($postStr, $this->_container);
      return $WechatResponse->RequestFeedback();
    }
    return "";
  }
// response function end *****************************************************

// token and Ticket start
  public function getJsSDK($url){
    $this->getAccessToken();
    $redis = $this->_container->get('my.RedisLogic');
    $time = $redis->getString('token_time');
    $ticket = $redis->getString('access_ticket');
    $str = '1234567890abcdefghijklmnopqrstuvwxyz';
    $noncestr = '';
    for($i=0;$i<8;$i++){
      $randval = mt_rand(0,35);
      $noncestr .= $str[$randval];
    }
    $ticketstr="jsapi_ticket=". $ticket ."&noncestr=". $noncestr ."&timestamp=". $time ."&url=". $url;
    $sign = sha1($ticketstr);
    $jssdk = array("appid" => $this->_appid,"time" => $time, "noncestr" => $noncestr, "sign" => $sign, "url" => $url);
    return json_encode(array('code' => '10', 'msg' => 'success', 'jssdk' => $jssdk), JSON_UNESCAPED_UNICODE);
  }

  public function getTicket($access_token){
    $redis = $this->_container->get('my.RedisLogic');
    $url = $this->_urls['access_api_ticket'];
    $url = str_replace('ACCESS_TOKEN', $access_token ,$url);
    $ticketfile = $this->get_data($url);
    $redis->setString('access_ticket', $ticketfile['ticket'], 5000);
  }

  public function getAccessToken()
  {
    $time = 0;
    $access_token = 0;
    $redis = $this->_container->get('my.RedisLogic');
    if($redis->checkString('token_time')){
      $time = $redis->getString('token_time');
      $access_token = $redis->getString('access_token');
    }
    if (!$time || (time() - $time >= 3600) || !$access_token){
      $url = $this->_urls['access_token'];
      $url = str_replace('APPID',$this->_appid ,$url);
      $url = str_replace('APPSECRET',$this->_secret ,$url);
      $rs = $this->get_data($url);
      if(isset($rs['access_token'])){
        $access_token = $rs['access_token'];
        $this->getTicket($access_token);
        $redis->setString('token_time', time(), 5000);
        $redis->setString('access_token', $access_token, 5000);
        return $rs['access_token'];
      }else{
        return false;
      }
    }
    return $access_token;
  }
// token and Ticket start

// creat_menu start
  public function buildmenu(){
    if(!$access_token = $this->getAccessToken())
      return false;
    $url = $this->_urls['create_menu'];
    $url = str_replace('ACCESS_TOKEN', $access_token ,$url);
    $result = $this->post_data($url, json_encode($this->create_menu_array(), JSON_UNESCAPED_UNICODE));
    if(!$result['errcode']){
      return true;
    }
    return $result['errmsg'];
  }

  public function checkmenuarray(){
    $menus = $this->create_menu_array();
    $menus = $menus['button'];
    foreach($menus as $x){
      if(strlen($x['name']) > 16)
        return array('code' => '11', 'msg' => 'the length of menu "'.$x['name'].'" name not more than 16');
      if(!isset($x['sub_button']) && !isset($x['type'])){
        return array('code' => '11', 'msg' => 'the main menu "'.$x['name'].'" not have a feedback event');
      }
      if(isset($x['sub_button'])){
        foreach($x['sub_button'] as $xx){
          if(strlen($xx['name']) > 40)
            return array('code' => '11', 'msg' => 'the length of submenu "'.$xx['name'].'" name not more than 40');
          if(!isset($xx['type'])){
            return array('code' => '11', 'msg' => 'the submenu "'.$xx['name'].'" not have a feedback event');
          }
        }
      }
    }
    return true;
  }

  public function create_menu_array(){
    $dataSql = $this->_container->get('my.dataSql');
    $data = array();
    $menus = $dataSql->getmenus();
    foreach($menus as $x){
      if($x['subOrder'] == 0){
        if(!isset($data[$x['mOrder']]))
          $data[$x['mOrder']] = array();
        $data[$x['mOrder']] = $this->buildbutton($x, $data[$x['mOrder']]);
      }else{
        $k = intval($x['subOrder'])-1;
        if(!isset($data[$x['mOrder']]['sub_button']))
          $data[$x['mOrder']]['sub_button'] = array();
        $data[$x['mOrder']]['sub_button'] = $this->barraybefore($data[$x['mOrder']]['sub_button'], $k);
        $data[$x['mOrder']]['sub_button'][$k] = $this->buildbutton($x, $data[$x['mOrder']]['sub_button'][$k]);
      }
    }
    return $this->filterbutton($data);
  }

  public function barraybefore($data, $k){
    for($i = '0'; $i <= $k ;$i++){
      if(!isset($data[$i]))
        $data[$i] = array();
    }
    return $data;
  }

  public function filterbutton($data){
    $out = array();
    $ox = 0;
    foreach($data as $x){
      if(isset($x['sub_button'])){
        $out[$ox] = $this->deletekeys($x);
        $out[$ox]['sub_button'] = $this->rebuildArray($out[$ox]['sub_button']);
      }else{
        $out[$ox] = $x;
      }
      $ox++;
    }
    return array('button' => $out);
  }

  public function rebuildArray($data){
    $out = array();
    foreach($data as $x){
      $out[] = $x;
    }
    return $out;
  }

  public function deletekeys($data){
    foreach($data as $x => $x_val){
      if($x != 'sub_button' && $x != 'name' ){
          unset($data[$x]);
      }
    }
    return $data;
  }

  public function buildbutton($data, $out){
    unset($data['mOrder']);
    unset($data['subOrder']);
    foreach($data as $x => $x_val){
      if($x_val){
        if($x == 'menuName')
          $out['name'] = $x_val;
        if($x == 'eventtype')
          $out['type'] = $x_val;
        if($x == 'eventKey')
          $out['key'] = $x_val;
        if($x == 'eventUrl')
          $out['url'] = $x_val;
        if($x == 'eventmedia_id')
          $out['media_id'] = $x_val;
      }
    }
    return $out;
  }

// creat_menu end

//subfunction
  public function get_data($url, $return_array = true){
      if($return_array)
        return json_decode( file_get_contents($url), true );
      return file_get_contents($url);
  }

  public function post_data($url, $param, $is_file = false, $return_array = true){
    if (! $is_file && is_array ( $param )) {
    $param = $this->JSON ( $param );
    }
    if ($is_file) {
    $header [] = "content-type: multipart/form-data; charset=UTF-8";
    } else {
    $header [] = "content-type: application/json; charset=UTF-8";
    }
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
    curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
    curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
    curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
    curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)' );
    curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
    curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $param );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
    $res = curl_exec ( $ch );

    // 	$flat = curl_errno ( $ch );
    // 	if ($flat) {
    // 		$data = curl_error ( $ch );
    // 		addWeixinLog ( $flat, 'post_data flat' );
    // 		addWeixinLog ( $data, 'post_data msg' );
    // 	}

    curl_close ( $ch );

    if($return_array)
    $res = json_decode ( $res, true );
    return $res;
  }

  public function arrayRecursive(&$array, $function, $apply_to_keys_also = false) {
    static $recursive_counter = 0;
    if (++ $recursive_counter > 1000) {
    die ( 'possible deep recursion attack' );
    }
    foreach ( $array as $key => $value ) {
    if (is_array ( $value )) {
      $this->arrayRecursive ( $array [$key], $function, $apply_to_keys_also );
    } else {
      $array [$key] = $function ( $value );
    }

    if ($apply_to_keys_also && is_string ( $key )) {
      $new_key = $function ( $key );
      if ($new_key != $key) {
        $array [$new_key] = $array [$key];
        unset ( $array [$key] );
      }
    }
    }
    $recursive_counter --;
  }

  public function JSON($array) {
    $this->arrayRecursive ( $array, 'urlencode', true );
    $json = json_encode ( $array, JSON_UNESCAPED_UNICODE);
    return urldecode ( $json );
  }
// tag control
  public function adduserTags($data){
    $access_token = $this->getAccessToken();
    $url = $this->_urls['add_tags'];
    $url = str_replace('ACCESS_TOKEN', $access_token, $url);
    $this->post_data($url, $data);
    return true;
  }

  public function deluserTags($data){
    $access_token = $this->getAccessToken();
    $url = $this->_urls['del_tags'];
    $url = str_replace('ACCESS_TOKEN', $access_token, $url);
    $this->post_data($url, $data);
    return true;
  }

  // @$data 'https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140549&token=&lang=zh_CN'
  public function sendTagMsg($data){//push message by tags
    $access_token = $this->getAccessToken();
    $url = $this->_urls['tag_msg'];
    $url = str_replace('ACCESS_TOKEN', $access_token, $url);
    $result = $this->post_data($url, $data);
    return $result;
  }

  public function sendTagPreviewMsg($data){//push message by tags
    $access_token = $this->getAccessToken();
    $url = $this->_urls['tag_msg_preview'];
    $url = str_replace('ACCESS_TOKEN', $access_token, $url);
    $result = $this->post_data($url, $data);
    return $result;
  }

  public function getMateriallist($data){
    $access_token = $this->getAccessToken();
    $url = $this->_urls['batchget_material'];
    $url = str_replace('ACCESS_TOKEN', $access_token, $url);
    $result = $this->post_data($url, $data);
    return $result;
  }

  public function getWechatGroup(){
    $AccessToken = $this->getAccessToken();
    $url = $this->_urls['wechat_group'];
    $url = str_replace('ACCESS_TOKEN', $AccessToken, $url);
    return $this->get_data($url);
  }
// tag control end
// oauth2
  public function getoauth2url($goto, $state = ''){
     $url = $this->_urls['oauth2_code'];
     $url = str_replace('APPID', $this->_appid ,$url);
     $url = str_replace('REDIRECT_URI', $goto ,$url);
     $url = str_replace('SCOPE', 'snsapi_userinfo', $url);
     $url = str_replace('STATE', $state, $url);
     return $url;
   }

  public function getoauth2token(){
    $url = $this->_urls['oauth2_token'];
    $url = str_replace('APPID', $this->_appid ,$url);
    $url = str_replace('SECRET', $this->_secret ,$url);
    $url = str_replace('CODE', isset($_GET['code'])?$_GET['code']:'', $url);
    return $this->get_data($url);
  }

  public function getoauthuserinfo(){
    $oauth = $this->getoauth2token();
    $url = $this->_urls['oauth2_token_userinfo'];
    $url = str_replace('ACCESS_TOKEN', $oauth['access_token'], $url);
    $url = str_replace('OPENID', $oauth['openid'], $url);
    return $this->get_data($url);
  }

  public function getOpenidInfo($openid){
    $AccessToken = $this->getAccessToken();
    $url = $this->_urls['token_userinfo'];
    $url = str_replace('ACCESS_TOKEN', $AccessToken, $url);
    $url = str_replace('OPENID', $openid, $url);
    $info = $this->get_data($url);
    if(isset($info['errcode']))
      return false;
    return $info;
  }
// oauth2 end
  public function setUrls(){
    $this->_urls = array(
      'access_token' => 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET',
      'access_api_ticket' => 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=ACCESS_TOKEN&type=jsapi',
      'create_menu' => 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=ACCESS_TOKEN',
      'custom_msend' => 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=ACCESS_TOKEN',
      'oauth2_code' => 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE#wechat_redirect',
      'oauth2_token' => 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code',
      'oauth2_refresh_token' => 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=APPID&grant_type=refresh_token&refresh_token=REFRESH_TOKEN',
      'oauth2_token_userinfo' => 'https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN',
      'add_tags' => 'https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging?access_token=ACCESS_TOKEN',
      'del_tags' => 'https://api.weixin.qq.com/cgi-bin/tags/members/batchuntagging?access_token=ACCESS_TOKEN',
      'tag_msg' => 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=ACCESS_TOKEN',
      'tag_msg_preview' => 'https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=ACCESS_TOKEN',
      'batchget_material' => 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=ACCESS_TOKEN',
      'token_userinfo' => 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN ',
      'wechat_group' => 'https://api.weixin.qq.com/cgi-bin/tags/get?access_token=ACCESS_TOKEN',
    );
  }




}
