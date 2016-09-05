<?php

namespace Wechat\ApiBundle\Modals\Dltempmedia;

class tempMedia{

  public function downloadVoice($url){
    exec("nohup ".dirname(__FILE__)."/downloadmedia.sh ".urlencode($url)." >>".dirname(__FILE__)."/downloadmedia.log 2>&1 &");
  }


}
