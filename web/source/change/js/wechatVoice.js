var voicestatus = {
  clickccs: 0,
  mediaid: null,
  serverId: null,
};
$(function(){
  $("#getvoice").click(function(){
    if(voicestatus.clickccs == 0){
      alert("startRecord");
      wx.startRecord();
      voicestatus.clickccs = 1;
    }else{
      wx.stopRecord({
          success: function (res) {
              voicestatus.mediaid = res.localId;
          }
      });
      alert("stopRecord");
      voicestatus.clickccs = 0;
    }
  });
  $("#uploadvoice").click(function(){
    if(voicestatus.mediaid){
        wx.uploadVoice({
          localId: voicestatus.mediaid, // 需要上传的音频的本地ID，由stopRecord接口获得
          isShowProgressTips: 1, // 默认为1，显示进度提示
              success: function (res) {
                voicestatus.serverId = res.serverId; // 返回音频的服务器端ID
                $("#mediaid").text(voicestatus.serverId);
          }
      });
    }
  });
});
