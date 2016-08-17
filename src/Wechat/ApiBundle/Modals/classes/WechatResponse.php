<?php

namespace Wechat\ApiBundle\Modals\classes;
use Wechat\ApiBundle\Modals\Database\dataSql;
use Wechat\ApiBundle\Modals\classes\WechatMsg;

class WechatResponse{

  private $postStr = null;
  private $postObj = null;
  private $fromUsername = null;
  private $toUsername = null;
  private $msgType = null;
  private $dataSql;
  private $container;

  public function __construct($postStr, $container){
    $this->postStr = $postStr;
    $this->postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
    $this->msgType = strtolower($this->postObj->MsgType);
    $this->fromUsername = trim($this->postObj->FromUserName);
    $this->toUsername = $this->postObj->ToUserName;
    $this->container = $container;
    $this->dataSql = $container->get('my.dataSql');
  }

  public function RequestFeedback(){
    if(method_exists($this, $this->msgType.'Request')){
      $backxml =  call_user_func_array(array($this, $this->msgType.'Request'), array());
    }
    if($backxml)
        return $backxml;
    return $this->defaultfeedback();
  }

  public function msgResponse($rs){
    if(!isset($rs[0]['MsgType']))
      return false;
    $WechatMsg = new WechatMsg($this->fromUsername, $this->toUsername);
    return $WechatMsg->sendMsgxml($rs);
  }
//request functions start
  public function textRequest(){
    $tempmsg = $this->doTempevent($this->postObj->Content);//temp listener
    if($tempmsg)
      return $tempmsg;
    $rs = $this->dataSql->textField($this->postObj->Content);
    if(is_array($rs) && count($rs)> 0 ){
      return $this->msgResponse($rs);
    }
    return "";
  }

  private function sendMsgForText($fromUsername, $toUsername, $time, $msgType, $contentStr)
  {
    $textTpl = "<xml>
          <ToUserName><![CDATA[%s]]></ToUserName>
          <FromUserName><![CDATA[%s]]></FromUserName>
          <CreateTime>%s</CreateTime>
          <MsgType><![CDATA[%s]]></MsgType>
          <Content><![CDATA[%s]]></Content>
          <FuncFlag>0</FuncFlag>
          </xml>";
    return sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
  }

  public function imageRequest(){
    return "";
  }

  public function voiceRequest(){
    return "";
  }

  public function videoRequest(){
    return "";
  }

  public function shortvideoRequest(){
    return "";
  }

  public function locationRequest(){
    // $this->systemLog();
    //LBS
    return $this->feedbackStores($this->postObj->Location_X, $this->postObj->Location_Y);
  }

  public function linkRequest(){
    return "";
  }

  public function eventRequest(){
    $event = strtolower($this->postObj->Event);
    if(method_exists($this, $event.'Event')){
      return call_user_func_array(array($this, $event.'Event'), array());
    }
    return "";
  }
//request function end

//event function start
  public function subscribeEvent(){
    $rs = $this->dataSql->subscribeField();
    if(is_array($rs) && count($rs)> 0 ){
      return $this->msgResponse($rs);
    }
    return "";
  }

  public function scanEvent(){
    return "";
  }

  public function locationEvent(){
    return "";
  }

  public function location_selectEvent(){
    // $this->systemLog();
    return "";
  }

  public function clickEvent(){
    $eventKey = $this->postObj->EventKey;
    $rs = $this->dataSql->clickField($eventKey);
    if(is_array($rs) && count($rs)> 0 ){
      return $this->msgResponse($rs);
    }
    return "";
  }

  public function viewEvent(){
    return "";
  }

//event function end
  public function systemLog(){
    $this->dataSql->systemLog($this->postStr, $this->fromUsername, $this->msgType);
  }

  public function comfirmkeycode($msgType){

  }

  public function defaultfeedback(){
    $rs = $this->dataSql->defaultField();
    if(is_array($rs) && count($rs)> 0 ){
      return $this->msgResponse($rs);
    }
    return "";
  }

/**
 * goto temp event. 'tempid' is a default value.
 *
 * @var    $data = array(
 *    'groupname' => $temp['groupname'],
 *    'grouptagid' => $temp['grouptagid'],
 *    'mediaid' => $temp['mediaid'],
 *    'tempid' => $tempid,
 *    'eventname' => 'tagnewssend',
 *    'fromopenid' => $this->fromUsername,
 *  );
 */
  public function doTempevent($tempid){
    $redis = $this->container->get('my.RedisLogic');
    if($redis->checkString('wechattemplistener')){
      $temp = json_decode($redis->getString('wechattemplistener'), true);
      if($temp['tempid'] == $tempid){
        if(method_exists($this, $temp['eventname'].'Tempevent')){
          return call_user_func_array(array($this,  $temp['eventname'].'Tempevent'), array($temp));
        }
      }
    }
    return "";
  }
// Temp Events
  public function sendpreviewnewsTempevent($temp){
    $redis = $this->container->get('my.RedisLogic');
    $tempid = mt_rand(0,9).mt_rand(0,9).mt_rand(0,9).mt_rand(0,9).mt_rand(0,9).mt_rand(0,9);
    $data = array(
      'groupname' => $temp['groupname'],
      'grouptagid' => $temp['grouptagid'],
      'mediaid' => $temp['mediaid'],
      'tempid' => $tempid,
      'eventname' => 'tagnewssend',
      'fromopenid' => $this->fromUsername,
    );
    $redis->setString('wechattemplistener', json_encode($data, JSON_UNESCAPED_UNICODE), 120);
    $wehcat = $this->container->get('my.Wechat');
    $prev = array(
      "touser" => $this->fromUsername,
      "mpnews" => array(
         "media_id" => $temp['mediaid'],
       ),
      "msgtype" => "mpnews",
    );
    $wehcat->sendTagPreviewMsg($prev);
    return $this->sendMsgForText(
      $this->fromUsername,
      $this->toUsername,
      time(),
      'text',
      "pls confirm this news preview \n if you make true \n pls within 80s feedback code <a href='#'>".$tempid."</a> \n to send this news to group [{$temp['groupname']}]"
    );
  }

  public function tagnewssendTempevent($temp){
    $redis = $this->container->get('my.RedisLogic');
    if($temp['fromopenid'] == $this->fromUsername){
      $wehcat = $this->container->get('my.Wechat');
      $data = array(
        "filter" => array(
          "is_to_all" => false,
          "tag_id" => $temp['grouptagid'],
        ),
        "mpnews" => array(
          "media_id" => $temp['mediaid']
        ),
        "msgtype" => "mpnews"
      );
      $result = $wehcat->sendTagMsg(json_encode($data, JSON_UNESCAPED_UNICODE));
      $dataSql = $this->container->get('my.dataSql');
      $dataSql->tempEventLog($this->fromUsername, $temp['tempid'],json_encode($data, JSON_UNESCAPED_UNICODE),json_encode($result));
      $redis->delkey('wechattemplistener');
      return $this->sendMsgForText(
        $this->fromUsername,
        $this->toUsername,
        time(),
        'text',
        "your send a news to group [{$temp['groupname']}]"
      );
    }
    return '';
  }


  //subfunction
  public function returnSquarePoint($lng, $lat,$distance = 0.5){
    $earthRadius = 6378138;
    $dlng =  2 * asin(sin($distance / (2 * $earthRadius)) / cos(deg2rad($lat)));
    $dlng = rad2deg($dlng);
    $dlat = $distance/$earthRadius;
    $dlat = rad2deg($dlat);
    return array(
                  'left-top'=>array('lat'=>$lat + $dlat,'lng'=>$lng-$dlng),
                  'right-top'=>array('lat'=>$lat + $dlat, 'lng'=>$lng + $dlng),
                  'left-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng - $dlng),
                  'right-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng + $dlng)
    );
  }

  public function getDistance($lat1, $lng1, $lat2, $lng2){
    $earthRadius = 6378138; //近似地球半径米
    // 转换为弧度
    $lat1 = ($lat1 * pi()) / 180;
    $lng1 = ($lng1 * pi()) / 180;
    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;
    // 使用半正矢公式  用尺规来计算
    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;
    return round($calculatedDistance);
  }

  public function feedbackStores($l_x, $l_y){
    $baidu = file_get_contents("http://api.map.baidu.com/geoconv/v1/?coords={$l_x},{$l_y}&from=3&to=5&ak=Z5FOXZbjH3AEIukiiRTtD7Xy");
    $baidu = json_decode($baidu, true);
    $lat = $baidu['result'][0]['x'];
    $lng = $baidu['result'][0]['y'];
    $squares = $this->returnSquarePoint($lng,$lat,100000);
    $latbig = $squares['right-bottom']['lat'] > $squares['left-top']['lat'] ? $squares['right-bottom']['lat'] : $squares['left-top']['lat'];
    $latsmall = $squares['right-bottom']['lat'] > $squares['left-top']['lat'] ? $squares['left-top']['lat'] : $squares['right-bottom']['lat'];
    $lngbig = $squares['left-top']['lng'] > $squares['right-bottom']['lng'] ? $squares['left-top']['lng'] : $squares['right-bottom']['lng'];
    $lngsmall = $squares['left-top']['lng'] > $squares['right-bottom']['lng'] ? $squares['right-bottom']['lng'] : $squares['left-top']['lng'];
    $info_sql = "select * from `stores` where lat<>0 and (lat between {$latsmall} and {$latbig}) and (lng between {$lngsmall} and {$lngbig})";
    $dataSql = $this->container->get('my.dataSql');
    $rs = $dataSql->querysql($info_sql);
    if(!$rs){
      return $this->sendMsgForText($this->fromUsername, $this->toUsername, time(), "text", '很抱歉，您的附近没有门店');
    }
    $datas = array();
    $fs = new \Symfony\Component\Filesystem\Filesystem();
    $data = array();
      for($i=0;$i<count($rs);$i++){
        $meter = $this->getDistance($lat,$lng,$rs[$i]['lat'],$rs[$i]['lng']);
        $meters = "(距离约" . $meter ."米)";
        $pisurl = 'source/change/store/'.$rs[$i]['id'].'.jpg';
        if(!$fs->exists($pisurl)){
          $pisurl = 'source/change/store/'.$rs[$i]['id'].'_map.jpg';
        }
        $datas[$meter] = array(
          'Title' => $rs[$i]['storename'].$meters,
          'Description' => $rs[$i]['storename'],
          'PicUrl' => $this->container->get('request_stack')->getCurrentRequest()->getSchemeAndHttpHost().'/'.$pisurl,
          'Url' => $this->container->get('request_stack')->getCurrentRequest()->getSchemeAndHttpHost().'/wechat/store/'.$rs[$i]['id'],
        );
      }
    ksort($datas);
    $i=0;
    foreach($datas as $value){
      $data[$i] = $value;
      $i++;
    }
    $xml = array();
    $xml['0'] = array(
      "MsgType" => "news",
      "MsgData" => json_encode(array("Articles" => $data), JSON_UNESCAPED_UNICODE),
    );
    $WechatMsg = new WechatMsg($this->fromUsername, $this->toUsername);
    return $WechatMsg->sendMsgxml($xml);
  }

}
