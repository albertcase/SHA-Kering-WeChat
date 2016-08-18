

function wechatFun(_appId, _timestamp, _nonceStr, _signature,sharetitle,sharelink,sharedes,shareimg){
    wx.config({
        debug: true,
        appId: _appId,
        timestamp: _timestamp,
        nonceStr: _nonceStr,
        signature: _signature,
        jsApiList: [
            // 所有要调用的 API 都要加到这个列表中
            'checkJsApi',
            'onMenuShareTimeline',
            'onMenuShareAppMessage',
            'onMenuShareQQ',
            'onMenuShareWeibo',
            'hideMenuItems',
            'showMenuItems',
            'hideAllNonBaseMenuItem',
            'showAllNonBaseMenuItem',
            'getNetworkType',
            'openLocation',
            'getLocation',
            'hideOptionMenu',
            'showOptionMenu',
            'closeWindow'
        ]
    });
  }
    wx.ready(function() {
        $.ajax({
            type: "GET",
            dataType: "jsonp",
            url: "http://keringwechat.samesamechina.com/sharetoken?url="+encodeURIComponent(window.location), //this url need urlencode
            async: false,
            success: function (data) {
            wechatFun(data.appid, data.time, data.noncestr, data.sign);
            }
    });
});
