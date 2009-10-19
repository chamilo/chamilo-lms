var GB_CURRENT=null;
GB_hide=function(cb){
GB_CURRENT.hide(cb);
};
GreyBox=new AJS.Class({init:function(_2){
this.use_fx=AJS.fx;
this.type="page";
this.overlay_click_close=false;
this.salt=0;
this.root_dir=GB_ROOT_DIR;
this.callback_fns=[];
this.reload_on_close=false;
this.src_loader=this.root_dir+"loader_frame.html";
var _3=window.location.hostname.indexOf("www");
var _4=this.src_loader.indexOf("www");
if(_3!=-1&&_4==-1){
this.src_loader=this.src_loader.replace("://","://www.");
}
if(_3==-1&&_4!=-1){
this.src_loader=this.src_loader.replace("://www.","://");
}
this.show_loading=true;
AJS.update(this,_2);
},addCallback:function(fn){
if(fn){
this.callback_fns.push(fn);
}
},show:function(_6){
GB_CURRENT=this;
this.url=_6;
var _7=[AJS.$bytc("object"),AJS.$bytc("select")];
AJS.map(AJS.flattenList(_7),function(_8){
_8.style.visibility="hidden";
});
this.createElements();
return false;
},hide:function(cb){
var me=this;
AJS.callLater(function(){
var _b=me.callback_fns;
if(_b!=[]){
AJS.map(_b,function(fn){
fn();
});
}
me.onHide();
if(me.use_fx){
var _d=me.overlay;
AJS.fx.fadeOut(me.overlay,{onComplete:function(){
AJS.removeElement(_d);
_d=null;
},duration:300});
AJS.removeElement(me.g_window);
}else{
AJS.removeElement(me.g_window,me.overlay);
}
me.removeFrame();
AJS.REV(window,"scroll",_GB_setOverlayDimension);
AJS.REV(window,"resize",_GB_update);
var _e=[AJS.$bytc("object"),AJS.$bytc("select")];
AJS.map(AJS.flattenList(_e),function(_f){
_f.style.visibility="visible";
});
GB_CURRENT=null;
if(me.reload_on_close){
window.location.reload();
}
if(AJS.isFunction(cb)){
cb();
}
},10);
},update:function(){
this.setOverlayDimension();
this.setFrameSize();
this.setWindowPosition();
},createElements:function(){
this.initOverlay();
this.g_window=AJS.DIV({"id":"GB_window"});
AJS.hideElement(this.g_window);
AJS.getBody().insertBefore(this.g_window,this.overlay.nextSibling);
this.initFrame();
this.initHook();
this.update();
var me=this;
if(this.use_fx){
AJS.fx.fadeIn(this.overlay,{duration:300,to:0.7,onComplete:function(){
me.onShow();
AJS.showElement(me.g_window);
me.startLoading();
}});
}else{
AJS.setOpacity(this.overlay,0.7);
AJS.showElement(this.g_window);
this.onShow();
this.startLoading();
}
AJS.AEV(window,"scroll",_GB_setOverlayDimension);
AJS.AEV(window,"resize",_GB_update);
},removeFrame:function(){
try{
AJS.removeElement(this.iframe);
}
catch(e){
}
this.iframe=null;
},startLoading:function(){
this.iframe.src=this.src_loader+"?s="+this.salt++;
AJS.showElement(this.iframe);
},setOverlayDimension:function(){
var _11=AJS.getWindowSize();
if(AJS.isMozilla()||AJS.isOpera()){
AJS.setWidth(this.overlay,"100%");
}else{
AJS.setWidth(this.overlay,_11.w);
}
var _12=Math.max(AJS.getScrollTop()+_11.h,AJS.getScrollTop()+this.height);
if(_12<AJS.getScrollTop()){
AJS.setHeight(this.overlay,_12);
}else{
AJS.setHeight(this.overlay,AJS.getScrollTop()+_11.h);
}
},initOverlay:function(){
this.overlay=AJS.DIV({"id":"GB_overlay"});
if(this.overlay_click_close){
AJS.AEV(this.overlay,"click",GB_hide);
}
AJS.setOpacity(this.overlay,0);
AJS.getBody().insertBefore(this.overlay,AJS.getBody().firstChild);
},initFrame:function(){
if(!this.iframe){
var d={"name":"GB_frame","class":"GB_frame","frameBorder":0};
if(AJS.isIe()){
d.src="javascript:false;document.write(\"\");";
}
this.iframe=AJS.IFRAME(d);
this.middle_cnt=AJS.DIV({"class":"content"},this.iframe);
this.top_cnt=AJS.DIV();
this.bottom_cnt=AJS.DIV();
AJS.ACN(this.g_window,this.top_cnt,this.middle_cnt,this.bottom_cnt);
}
},onHide:function(){
},onShow:function(){
},setFrameSize:function(){
},setWindowPosition:function(){
},initHook:function(){
}});
_GB_update=function(){
if(GB_CURRENT){
GB_CURRENT.update();
}
};
_GB_setOverlayDimension=function(){
if(GB_CURRENT){
GB_CURRENT.setOverlayDimension();
}
};
AJS.preloadImages(GB_ROOT_DIR+"indicator.gif");
script_loaded=true;
var GB_SETS={};
function decoGreyboxLinks(){
var as=AJS.$bytc("a");
AJS.map(as,function(a){
if(a.getAttribute("href")&&a.getAttribute("rel")){
var rel=a.getAttribute("rel");
if(rel.indexOf("gb_")==0){
var _17=rel.match(/\w+/)[0];
var _18=rel.match(/\[(.*)\]/)[1];
var _19=0;
var _1a={"caption":a.title||"","url":a.href};
if(_17=="gb_pageset"||_17=="gb_imageset"){
if(!GB_SETS[_18]){
GB_SETS[_18]=[];
}
GB_SETS[_18].push(_1a);
_19=GB_SETS[_18].length;
}
if(_17=="gb_pageset"){
a.onclick=function(){
GB_showFullScreenSet(GB_SETS[_18],_19);
return false;
};
}
if(_17=="gb_imageset"){
a.onclick=function(){
GB_showImageSet(GB_SETS[_18],_19);
return false;
};
}
if(_17=="gb_image"){
a.onclick=function(){
GB_showImage(_1a.caption,_1a.url);
return false;
};
}
if(_17=="gb_page"){
a.onclick=function(){
var sp=_18.split(/, ?/);
GB_show(_1a.caption,_1a.url,parseInt(sp[1]),parseInt(sp[0]));
return false;
};
}
if(_17=="gb_page_fs"){
a.onclick=function(){
GB_showFullScreen(_1a.caption,_1a.url);
return false;
};
}
if(_17=="gb_page_center"){
a.onclick=function(){
var sp=_18.split(/, ?/);
GB_showCenter(_1a.caption,_1a.url,parseInt(sp[1]),parseInt(sp[0]));
return false;
};
}
}
}
});
}
AJS.AEV(window,"load",decoGreyboxLinks);
GB_showImage=function(_1d,url,_1f){
var _20={width:300,height:300,type:"image",fullscreen:false,center_win:true,caption:_1d,callback_fn:_1f};
var win=new GB_Gallery(_20);
return win.show(url);
};
GB_showPage=function(_22,url,_24){
var _25={type:"page",caption:_22,callback_fn:_24,fullscreen:true,center_win:false};
var win=new GB_Gallery(_25);
return win.show(url);
};
GB_Gallery=GreyBox.extend({init:function(_27){
this.parent({});
this.img_close=this.root_dir+"g_close.gif";
AJS.update(this,_27);
this.addCallback(this.callback_fn);
},initHook:function(){
AJS.addClass(this.g_window,"GB_Gallery");
var _28=AJS.DIV({"class":"inner"});
this.header=AJS.DIV({"class":"GB_header"},_28);
AJS.setOpacity(this.header,0);
AJS.getBody().insertBefore(this.header,this.overlay.nextSibling);
var _29=AJS.TD({"id":"GB_caption","class":"caption","width":"40%"},this.caption);
var _2a=AJS.TD({"id":"GB_middle","class":"middle","width":"20%"});
var _2b=AJS.IMG({"src":this.img_close});
AJS.AEV(_2b,"click",GB_hide);
var _2c=AJS.TD({"class":"close","width":"40%"},_2b);
var _2d=AJS.TBODY(AJS.TR(_29,_2a,_2c));
var _2e=AJS.TABLE({"cellspacing":"0","cellpadding":0,"border":0},_2d);
AJS.ACN(_28,_2e);
if(this.fullscreen){
AJS.AEV(window,"scroll",AJS.$b(this.setWindowPosition,this));
}else{
AJS.AEV(window,"scroll",AJS.$b(this._setHeaderPos,this));
}
},setFrameSize:function(){
var _2f=this.overlay.offsetWidth;
var _30=AJS.getWindowSize();
if(this.fullscreen){
this.width=_2f-40;
this.height=_30.h-80;
}
AJS.setWidth(this.iframe,this.width);
AJS.setHeight(this.iframe,this.height);
AJS.setWidth(this.header,_2f);
},_setHeaderPos:function(){
AJS.setTop(this.header,AJS.getScrollTop()+10);
},setWindowPosition:function(){
var _31=this.overlay.offsetWidth;
var _32=AJS.getWindowSize();
AJS.setLeft(this.g_window,((_31-50-this.width)/2));
var _33=AJS.getScrollTop()+55;
if(!this.center_win){
AJS.setTop(this.g_window,_33);
}else{
var fl=((_32.h-this.height)/2)+20+AJS.getScrollTop();
if(fl<0){
fl=0;
}
if(_33>fl){
fl=_33;
}
AJS.setTop(this.g_window,fl);
}
this._setHeaderPos();
},onHide:function(){
AJS.removeElement(this.header);
AJS.removeClass(this.g_window,"GB_Gallery");
},onShow:function(){
if(this.use_fx){
AJS.fx.fadeIn(this.header,{to:1});
}else{
AJS.setOpacity(this.header,1);
}
}});
AJS.preloadImages(GB_ROOT_DIR+"g_close.gif");
GB_showFullScreenSet=function(set,_36,_37){
var _38={type:"page",fullscreen:true,center_win:false};
var _39=new GB_Sets(_38,set);
_39.addCallback(_37);
_39.showSet(_36-1);
return false;
};
GB_showImageSet=function(set,_3b,_3c){
var _3d={type:"image",fullscreen:false,center_win:true,width:300,height:300};
var _3e=new GB_Sets(_3d,set);
_3e.addCallback(_3c);
_3e.showSet(_3b-1);
return false;
};
GB_Sets=GB_Gallery.extend({init:function(_3f,set){
this.parent(_3f);
if(!this.img_next){
this.img_next=this.root_dir+"next.gif";
}
if(!this.img_prev){
this.img_prev=this.root_dir+"prev.gif";
}
this.current_set=set;
},showSet:function(_41){
this.current_index=_41;
var _42=this.current_set[this.current_index];
this.show(_42.url);
this._setCaption(_42.caption);
this.btn_prev=AJS.IMG({"class":"left",src:this.img_prev});
this.btn_next=AJS.IMG({"class":"right",src:this.img_next});
AJS.AEV(this.btn_prev,"click",AJS.$b(this.switchPrev,this));
AJS.AEV(this.btn_next,"click",AJS.$b(this.switchNext,this));
GB_STATUS=AJS.SPAN({"class":"GB_navStatus"});
AJS.ACN(AJS.$("GB_middle"),this.btn_prev,GB_STATUS,this.btn_next);
this.updateStatus();
},updateStatus:function(){
AJS.setHTML(GB_STATUS,(this.current_index+1)+" / "+this.current_set.length);
if(this.current_index==0){
AJS.addClass(this.btn_prev,"disabled");
}else{
AJS.removeClass(this.btn_prev,"disabled");
}
if(this.current_index==this.current_set.length-1){
AJS.addClass(this.btn_next,"disabled");
}else{
AJS.removeClass(this.btn_next,"disabled");
}
},_setCaption:function(_43){
AJS.setHTML(AJS.$("GB_caption"),_43);
},updateFrame:function(){
var _44=this.current_set[this.current_index];
this._setCaption(_44.caption);
this.url=_44.url;
this.startLoading();
},switchPrev:function(){
if(this.current_index!=0){
this.current_index--;
this.updateFrame();
this.updateStatus();
}
},switchNext:function(){
if(this.current_index!=this.current_set.length-1){
this.current_index++;
this.updateFrame();
this.updateStatus();
}
}});
AJS.AEV(window,"load",function(){
AJS.preloadImages(GB_ROOT_DIR+"next.gif",GB_ROOT_DIR+"prev.gif");
});
GB_show=function(_45,url,_47,_48,_49){
var _4a={caption:_45,height:_47||500,width:_48||500,fullscreen:false,callback_fn:_49};
var win=new GB_Window(_4a);
return win.show(url);
};
GB_showCenter=function(_4c,url,_4e,_4f,_50){
var _51={caption:_4c,center_win:true,height:_4e||500,width:_4f||500,fullscreen:false,callback_fn:_50};
var win=new GB_Window(_51);
return win.show(url);
};
GB_showFullScreen=function(_53,url,_55){
var _56={caption:_53,fullscreen:true,callback_fn:_55};
var win=new GB_Window(_56);
return win.show(url);
};
GB_Window=GreyBox.extend({init:function(_58){
this.parent({});
this.img_header=this.root_dir+"header_bg.gif";
this.img_close=this.root_dir+"w_close.gif";
this.show_close_img=true;
AJS.update(this,_58);
this.addCallback(this.callback_fn);
},initHook:function(){
AJS.addClass(this.g_window,"GB_Window");
this.header=AJS.TABLE({"class":"header"});
this.header.style.backgroundImage="url("+this.img_header+")";
var _59=AJS.TD({"class":"caption"},this.caption);
var _5a=AJS.TD({"class":"close"});
if(this.show_close_img){
var _5b=AJS.IMG({"src":this.img_close});
var _5c=AJS.SPAN("");
var btn=AJS.DIV(_5b,_5c);
/*
AJS.AEV([_5b,_5c],"mouseover",function(){
AJS.addClass(_5c,"on");
});
AJS.AEV([_5b,_5c],"mouseout",function(){
AJS.removeClass(_5c,"on");
});
*/
AJS.AEV([_5b,_5c],"mousedown",function(){
AJS.addClass(_5c,"click");
});
AJS.AEV([_5b,_5c],"mouseup",function(){
AJS.removeClass(_5c,"click");
});
AJS.AEV([_5b,_5c],"click",GB_hide);
AJS.ACN(_5a,btn);
}
tbody_header=AJS.TBODY();
AJS.ACN(tbody_header,AJS.TR(_59,_5a));
AJS.ACN(this.header,tbody_header);
AJS.ACN(this.top_cnt,this.header);
if(this.fullscreen){
AJS.AEV(window,"scroll",AJS.$b(this.setWindowPosition,this));
}
},setFrameSize:function(){
if(this.fullscreen){
var _5e=AJS.getWindowSize();
overlay_h=_5e.h;
this.width=Math.round(this.overlay.offsetWidth-(this.overlay.offsetWidth/100)*10);
this.height=Math.round(overlay_h-(overlay_h/100)*10);
}
AJS.setWidth(this.header,this.width+6);
AJS.setWidth(this.iframe,this.width);
AJS.setHeight(this.iframe,this.height);
},setWindowPosition:function(){
var _5f=AJS.getWindowSize();
AJS.setLeft(this.g_window,((_5f.w-this.width)/2)-13);
if(!this.center_win){
AJS.setTop(this.g_window,AJS.getScrollTop());
}else{
var fl=((_5f.h-this.height)/2)-20+AJS.getScrollTop();
if(fl<0){
fl=0;
}
AJS.setTop(this.g_window,fl);
}
}});
AJS.preloadImages(GB_ROOT_DIR+"w_close.gif",GB_ROOT_DIR+"header_bg.gif");


script_loaded=true;