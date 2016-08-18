

function wechatFun(_appId, _timestamp, _nonceStr, _signature,sharetitle,sharelink,sharedes,shareimg){
    wx.config({
        debug: false,
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
    wx.ready(function() {
        wx.onMenuShareTimeline({
            title: sharetitle,
            link: sharelink,
            imgUrl: shareimg,
            success: function () {

            },
            cancel: function () {
            }
        });
        wx.onMenuShareAppMessage({
            title: sharetitle,
            desc: sharedes,
            link: sharelink,
            imgUrl: shareimg,
            type: '',
            dataUrl: '',
            success: function () {

            },
            cancel: function () {
            }
        });
        $.ajax({
            type: "GET",
            dataType: "jsonp",
            url: "http://shangrilawechat.samesamechina.com/wechat/sharetoken?
            url="+encodeURIComponent(window.location), //this url need urlencode
            async: false,
            success: function (data) {
            wechatFun(data.appid, data.time, data.noncestr, data.sign);
            }
    });
}
