function imgmap(_1){
this.version="2.0beta3";
this.buildDate="03-12-2007";
this.buildNumber="29";
this.config=new Object();
this.is_drawing=0;
this.strings=new Array();
this.memory=new Array();
this.areas=new Array();
this.props=new Array();
this.logStore=new Array();
this.currentid=0;
this.draggedId=null;
this.selectedId=null;
this.nextShape="rectangle";
this.viewmode=0;
this.loadedScripts=new Array();
this.isLoaded=false;
this.cntReloads=0;
this.mapname="";
this.mapid="";
this.DM_RECTANGLE_DRAW=1;
this.DM_RECTANGLE_MOVE=11;
this.DM_RECTANGLE_RESIZE_TOP=12;
this.DM_RECTANGLE_RESIZE_RIGHT=13;
this.DM_RECTANGLE_RESIZE_BOTTOM=14;
this.DM_RECTANGLE_RESIZE_LEFT=15;
this.DM_SQUARE_DRAW=2;
this.DM_SQUARE_MOVE=21;
this.DM_SQUARE_RESIZE_TOP=22;
this.DM_SQUARE_RESIZE_RIGHT=23;
this.DM_SQUARE_RESIZE_BOTTOM=24;
this.DM_SQUARE_RESIZE_LEFT=25;
this.DM_POLYGON_DRAW=3;
this.DM_POLYGON_LASTDRAW=30;
this.DM_POLYGON_MOVE=31;
this.config.mode="editor";
this.config.imgroot="";
this.config.baseroot="";
this.config.lang="en";
this.config.loglevel=0;
this.config.buttons=["add","delete","preview","html"];
this.config.custom_callbacks=new Object();
this.config.CL_DRAW_BOX="#dd2400";
this.config.CL_DRAW_SHAPE="#d00";
this.config.CL_DRAW_BG="#fff";
this.config.CL_NORM_BOX="#dd2400";
this.config.CL_NORM_SHAPE="#d00";
this.config.CL_NORM_BG="#fff";
this.config.CL_HIGHLIGHT_BOX="#dd2400";
this.config.CL_HIGHLIGHT_SHAPE="#d00";
this.config.CL_HIGHLIGHT_BG="#fff";
this.config.CL_KNOB="#ffeeee";
this.config.CL_HIGHLIGHT_PROPS="#e7e7e7";
this.config.bounding_box=true;
this.config.label="%n";
this.config.label_class="imgmap_label";
this.config.label_style="font: bold 10px Arial";
this.config.hint="#%n %h";
this.config.draw_opacity="35";
this.config.norm_opacity="50";
this.config.highlight_opacity="65";
this.config.cursor_default="crosshair";
var ua=navigator.userAgent;
this.isMSIE=(navigator.appName=="Microsoft Internet Explorer");
this.isMSIE5=this.isMSIE&&(ua.indexOf("MSIE 5")!=-1);
this.isMSIE5_0=this.isMSIE&&(ua.indexOf("MSIE 5.0")!=-1);
this.isMSIE7=this.isMSIE&&(ua.indexOf("MSIE 7")!=-1);
this.isGecko=ua.indexOf("Gecko")!=-1;
this.isSafari=ua.indexOf("Safari")!=-1;
this.isOpera=(typeof window.opera!="undefined");
this.addEvent(document,"keydown",this.doc_keydown.bind(this));
this.addEvent(document,"keyup",this.doc_keyup.bind(this));
this.addEvent(document,"mousedown",this.doc_mousedown.bind(this));
if(_1){
this.setup(_1);
}
}
imgmap.prototype.assignOID=function(_3){
try{
if(typeof _3=="undefined"){
this.log("Undefined object passed to assignOID.");
return null;
}else{
if(typeof _3=="object"){
return _3;
}else{
if(typeof _3=="string"){
return document.getElementById(_3);
}
}
}
}
catch(err){
this.log("Error in assignOID",1);
}
return null;
};
imgmap.prototype.setup=function(_4){
for(var i in _4){
this.config[i]=_4[i];
}
if(_4){
this.pic_container=this.assignOID(_4.pic_container);
if(this.pic_container){
this.preview=document.createElement("DIV");
this.pic_container.appendChild(this.preview);
}
this.form_container=this.assignOID(_4.form_container);
this.html_container=this.assignOID(_4.html_container);
if(this.html_container){
this.addEvent(this.html_container,"blur",this.html_container_blur.bind(this));
this.addEvent(this.html_container,"focus",this.html_container_focus.bind(this));
}
this.status_container=this.assignOID(_4.status_container);
this.button_container=this.assignOID(_4.button_container);
}
if(!this.config.baseroot){
var _6=document.getElementsByTagName("base");
var _7="";
for(var i=0;i<_6.length;i++){
if(_6[i].href!=""){
_7=_6[i].href;
if(_7.charAt(_7.length-1)!="/"){
_7+="/";
}
break;
}
}
var _8=document.getElementsByTagName("script");
for(var i=0;i<_8.length;i++){
if(_8[i].src&&_8[i].src.match(/imgmap\w*\.js(\?.*?)?$/)){
var _9=_8[i].src;
_9=_9.substring(0,_9.lastIndexOf("/")+1);
if(_7!=""&&_9.indexOf("://")==-1){
this.config.baseroot=_7+_9;
}else{
this.config.baseroot=_9;
}
break;
}
}
}
if(this.isMSIE&&typeof window.CanvasRenderingContext2D=="undefined"&&typeof G_vmlCanvasManager=="undefined"){
this.loadScript(this.config.baseroot+"excanvas.js");
}
if(this.config.lang==""){
this.config.lang="en";
}

// Modified by Ivan Tcholakov.
//this.loadScript(this.config.baseroot+"lang_"+this.config.lang+".js");
this.loadScript(this.config.baseroot.toString().substring(0, this.config.baseroot.toString().length - 9) + "lang/" + this.config.lang + ".js");

if(!this.config.imgroot){
this.config.imgroot=this.config.baseroot;
}
this.addEvent(window,"load",this.onLoad.bind(this));
return true;
};
imgmap.prototype.retryDelayed=function(fn,_b,_c){
if(typeof fn.tries=="undefined"){
fn.tries=0;
}
if(fn.tries++<_c){
window.setTimeout(function(){
fn.apply(this);
},_b);
}
};
imgmap.prototype.onLoad=function(e){
if(this.isLoaded){
return true;
}
if(typeof imgmapStrings=="undefined"){
if(this.cntReloads++<5){
var _e=this;
window.setTimeout(function(){
_e.onLoad(e);
},1200);
this.log("Delaying onload (language not loaded, try: "+this.cntReloads+")");
return false;
}
}
try{
this.loadStrings(imgmapStrings);
}
catch(err){
this.log("Unable to load language strings",1);
}
if(this.isMSIE){
if(typeof window.CanvasRenderingContext2D=="undefined"&&typeof G_vmlCanvasManager=="undefined"){
this.log(this.strings["ERR_EXCANVAS_LOAD"],2);
}
}
if(this.config.mode=="highlighter"){
imgmap_spawnObjects(this.config);
}else{
if(this.button_container){
for(var i=0;i<this.config.buttons.length;i++){
if(this.config.buttons[i]=="add"){
try{
var img=document.createElement("IMG");
img.src=this.config.imgroot+"add.gif";
this.addEvent(img,"click",this.addNewArea.bind(this));
img.alt=this.strings["HINT_ADD"];
img.title=this.strings["HINT_ADD"];
img.style.cursor="pointer";
img.style.margin="0 2px";
this.button_container.appendChild(img);
}
catch(err){
this.log("Unable to add button ("+this.config.buttons[i]+")",1);
}
}else{
if(this.config.buttons[i]=="delete"){
try{
var img=document.createElement("IMG");
img.src=this.config.imgroot+"delete.gif";
this.addEvent(img,"click",this.removeArea.bind(this));
img.alt=this.strings["HINT_DELETE"];
img.title=this.strings["HINT_DELETE"];
img.style.cursor="pointer";
img.style.margin="0 2px";
this.button_container.appendChild(img);
}
catch(err){
this.log("Unable to add button ("+this.config.buttons[i]+")",1);
}
}else{
if(this.config.buttons[i]=="preview"){
try{
var img=document.createElement("IMG");
img.src=this.config.imgroot+"zoom.gif";
this.addEvent(img,"click",this.togglePreview.bind(this));
img.alt=this.strings["HINT_PREVIEW"];
img.title=this.strings["HINT_PREVIEW"];
img.style.cursor="pointer";
img.style.margin="0 2px";
this.i_preview=img;
this.button_container.appendChild(img);
}
catch(err){
this.log("Unable to add button ("+this.config.buttons[i]+")",1);
}
}else{
if(this.config.buttons[i]=="html"){
try{
var img=document.createElement("IMG");
img.src=this.config.imgroot+"html.gif";
this.addEvent(img,"click",this.clickHtml.bind(this));
img.alt=this.strings["HINT_HTML"];
img.title=this.strings["HINT_HTML"];
img.style.cursor="pointer";
img.style.margin="0 2px";
this.button_container.appendChild(img);
}
catch(err){
this.log("Unable to add button ("+this.config.buttons[i]+")",1);
}
}else{
if(this.config.buttons[i]=="clipboard"){
try{
var img=document.createElement("IMG");
img.src=this.config.imgroot+"clipboard.gif";
this.addEvent(img,"click",this.toClipBoard.bind(this));
img.alt=this.strings["HINT_CLIPBOARD"];
img.title=this.strings["HINT_CLIPBOARD"];
img.style.cursor="pointer";
img.style.margin="0 2px";
this.button_container.appendChild(img);
}
catch(err){
this.log("Unable to add button ("+this.config.buttons[i]+")",1);
}
}
}
}
}
}
}
}
}
this.isLoaded=true;
return true;
};
imgmap.prototype.addEvent=function(obj,evt,_13){
if(obj.attachEvent){
return obj.attachEvent("on"+evt,_13);
}else{
if(obj.addEventListener){
obj.addEventListener(evt,_13,false);
return true;
}else{
obj["on"+evt]=_13;
}
}
};
imgmap.prototype.removeEvent=function(obj,evt,_16){
if(obj.detachEvent){
return obj.detachEvent("on"+evt,_16);
}else{
if(obj.removeEventListener){
obj.removeEventListener(evt,_16,false);
return true;
}else{
obj["on"+evt]=null;
}
}
};
imgmap.prototype.addLoadEvent=function(obj,_18){
if(obj.attachEvent){
return obj.attachEvent("onreadystatechange",_18);
}else{
if(obj.addEventListener){
obj.addEventListener("load",_18,false);
return true;
}else{
obj["onload"]=_18;
}
}
};
imgmap.prototype.loadScript=function(url){
if(url==""){
return false;
}
if(this.loadedScripts[url]==1){
return true;
}
this.log("Loading script: "+url);
var _1a=document.getElementsByTagName("head")[0];
var _1b=document.createElement("SCRIPT");
_1b.setAttribute("language","javascript");
_1b.setAttribute("type","text/javascript");
_1b.setAttribute("src",url);
_1a.appendChild(_1b);
this.addLoadEvent(_1b,this.script_load.bind(this));
return true;
};
imgmap.prototype.script_load=function(e){
var obj=(this.isMSIE)?window.event.srcElement:e.currentTarget;
var url=obj.src;
var _1f=false;
if(typeof obj.readyState!="undefined"){
if(obj.readyState=="complete"){
_1f=true;
}
}else{
_1f=true;
}
if(_1f){
this.loadedScripts[url]=1;
this.log("Loaded script: "+url);
return true;
}
};
imgmap.prototype.loadStrings=function(obj){
for(var key in obj){
this.strings[key]=obj[key];
}
};
imgmap.prototype.loadImage=function(img,_23,_24){
this.removeAllAreas();
if(!this._getLastArea()){
if(this.config.mode!="editor2"){
this.addNewArea();
}
}
if(typeof img=="string"){
if(typeof this.pic=="undefined"){
this.pic=document.createElement("IMG");
this.pic_container.appendChild(this.pic);
this.addEvent(this.pic,"mousedown",this.img_mousedown.bind(this));
this.addEvent(this.pic,"mouseup",this.img_mouseup.bind(this));
this.addEvent(this.pic,"mousemove",this.img_mousemove.bind(this));
this.pic.style.cursor=this.config.cursor_default;
}
this.log("Loading image: "+img,0);
this.pic.src=img+"? "+(new Date().getTime());
if(_23&&_23>0){
this.pic.setAttribute("width",_23);
}
if(_24&&_24>0){
this.pic.setAttribute("height",_24);
}
this.fireEvent("onLoadImage",this.pic);
}else{
if(typeof img=="object"){
var src=img.src;
if(src==""&&img.getAttribute("mce_src")!=""){
src=img.getAttribute("mce_src");
}else{
if(src==""&&img.getAttribute("_fcksavedurl")!=""){
src=img.getAttribute("_fcksavedurl");
}
}
if(!_23){
_23=img.clientWidth;
}
if(!_24){
_24=img.clientHeight;
}
this.loadImage(src,_23,_24);
}
}
};
imgmap.prototype.useImage=function(img){
this.removeAllAreas();
if(!this._getLastArea()){
if(this.config.mode!="editor2"){
this.addNewArea();
}
}
img=this.assignOID(img);
if(typeof img=="object"){
this.pic=img;
this.addEvent(this.pic,"mousedown",this.img_mousedown.bind(this));
this.addEvent(this.pic,"mouseup",this.img_mouseup.bind(this));
this.addEvent(this.pic,"mousemove",this.img_mousemove.bind(this));
this.pic.style.cursor=this.config.cursor_default;
this.pic_container=this.pic.parentNode;
this.fireEvent("onLoadImage",this.pic);
}
};
imgmap.prototype.statusMessage=function(str){
if(this.status_container){
this.status_container.innerHTML=str;
}
window.defaultStatus=str;
};
imgmap.prototype.log=function(obj,_29){
if(_29==""||typeof _29=="undefined"){
_29=0;
}
if(this.config.loglevel!=-1&&_29>=this.config.loglevel){
this.logStore.push({level:_29,obj:obj});
}
if(typeof console=="object"){
console.log(obj);
}else{
if(this.isOpera){
opera.postError(_29+": "+obj);
}else{
if(_29>1){
var msg="";
for(var i=0;i<this.logStore.length;i++){
msg+=this.logStore[i].level+": "+this.logStore[i].obj+"\n";
}
alert(msg);
}else{
window.defaultStatus=(_29+": "+obj);
}
}
}
};
imgmap.prototype.getMapHTML=function(){
var _2c="<map id=\""+this.getMapId()+"\" name=\""+this.getMapName()+"\">"+this.getMapInnerHTML()+"</map>";
this.fireEvent("onGetMap",_2c);
return (_2c);
};
imgmap.prototype.getMapInnerHTML=function(){
var _2d="";
for(var i=0;i<this.areas.length;i++){
if(this.areas[i]){
if(this.areas[i].shape!=""&&this.areas[i].shape!="undefined"){
_2d+="<area shape=\""+this.areas[i].shape+"\""+" alt=\""+this.areas[i].aalt+"\""+" title=\""+this.areas[i].atitle+"\""+" coords=\""+this.areas[i].lastInput+"\""+" href=\""+this.areas[i].ahref+"\""+" target=\""+this.areas[i].atarget+"\" />";
}
}
}
return (_2d);
};
imgmap.prototype.getMapName=function(){
if(this.mapname==""){
if(this.mapid!=""){
return this.mapid;
}
var now=new Date();
this.mapname="imgmap"+now.getFullYear()+(now.getMonth()+1)+now.getDate()+now.getHours()+now.getMinutes()+now.getSeconds();
}
return this.mapname;
};
imgmap.prototype.getMapId=function(){
if(this.mapid==""){
this.mapid=this.getMapName();
}
return this.mapid;
};
imgmap.prototype._normCoords=function(_30,_31){
_30=_30.replace(/(\d)(\D)+(\d)/g,"$1,$3");
_30=_30.replace(/,(\D|0)+(\d)/g,",$2");
_30=_30.replace(/(\d)(\D)+,/g,"$1,");
_30=_30.replace(/^(\D|0)+(\d)/g,"$2");
var _32=_30.split(",");
if(_31=="rectangle"){
if(!(parseInt(_32[1])>0)){
_32[1]=_32[0];
}
if(!(parseInt(_32[2])>0)){
_32[2]=parseInt(_32[0])+10;
}
if(!(parseInt(_32[3])>0)){
_32[3]=parseInt(_32[1])+10;
}
if(parseInt(_32[0])>parseInt(_32[2])){
var _33=_32[0];
_32[0]=_32[2];
_32[2]=_33;
}
if(parseInt(_32[1])>parseInt(_32[3])){
var _33=_32[1];
_32[1]=_32[3];
_32[3]=_33;
}
_30=_32[0]+","+_32[1]+","+_32[2]+","+_32[3];
}else{
if(_31=="circle"){
if(!(parseInt(_32[1])>0)){
_32[1]=_32[0];
}
if(!(parseInt(_32[2])>0)){
_32[2]=10;
}
_30=_32[0]+","+_32[1]+","+_32[2];
}
}
return _30;
};
imgmap.prototype.setMapHTML=function(map){
this.fireEvent("onSetMap",map);
this.removeAllAreas();
var _35;
if(typeof map=="string"){
var _36=document.createElement("DIV");
_36.innerHTML=map;
_35=_36.firstChild;
}else{
if(typeof map=="object"){
_35=map;
}
}
if(!_35||_35.nodeName.toLowerCase()!=="map"){
return false;
}
this.mapname=_35.name;
this.mapid=_35.id;
var _37=_35.getElementsByTagName("area");
for(var i=0;i<_37.length;i++){
id=this.addNewArea();
if(_37[i].getAttribute("shape",2)){
shape=_37[i].getAttribute("shape",2).toLowerCase();
if(shape=="rect"){
shape="rectangle";
}else{
if(shape=="circ"){
shape="circle";
}else{
if(shape=="poly"){
shape="polygon";
}
}
}
}else{
shape="rectangle";
}
if(this.props[id]){
this.props[id].getElementsByTagName("select")[0].value=shape;
}
this.initArea(id,shape);
if(_37[i].getAttribute("coords",2)){
var _39=this._normCoords(_37[i].getAttribute("coords",2),shape);
if(this.props[id]){
this.props[id].getElementsByTagName("input")[2].value=_39;
}
this.areas[id].lastInput=_39;
}
var _3a=_37[i].getAttribute("href",2);
var _3b=_37[i].getAttribute("_fcksavedurl");
if(_3b!=null){
_3a=_3b;
}
if(_3a){
if(this.props[id]){
this.props[id].getElementsByTagName("input")[3].value=_3a;
}
this.areas[id].ahref=_3a;
}
var alt=_37[i].getAttribute("alt");
if(alt){
if(this.props[id]){
this.props[id].getElementsByTagName("input")[4].value=alt;
}
this.areas[id].aalt=alt;
}
var _3d=_37[i].getAttribute("title");
if(!_3d){
_3d=alt;
}
if(_3d){
this.areas[id].atitle=_3d;
}
var _3e=_37[i].getAttribute("target");
if(_3e){
_3e=_3e.toLowerCase();
}
if(this.props[id]){
this.props[id].getElementsByTagName("select")[1].value=_3e;
}
this.areas[id].atarget=_3e;
this._recalculate(id);
this.relaxArea(id);
if(this.html_container){
this.html_container.value=this.getMapHTML();
}
}
};
imgmap.prototype.clickHtml=function(){
this.fireEvent("onHtml");
return true;
};
imgmap.prototype.togglePreview=function(){
if(!this.pic){
return false;
}
if(this.viewmode==0){
this.fireEvent("onPreview");
for(var i=0;i<this.areas.length;i++){
if(this.areas[i]){
this.areas[i].style.display="none";
if(this.areas[i].label){
this.areas[i].label.style.display="none";
}
}
}
var _40=this.form_container.getElementsByTagName("input");
for(var i=0;i<_40.length;i++){
_40[i].disabled=true;
}
var _40=this.form_container.getElementsByTagName("select");
for(var i=0;i<_40.length;i++){
_40[i].disabled=true;
}
this.preview.innerHTML=this.getMapHTML();
this.pic.setAttribute("usemap","#"+this.mapname,0);
this.pic.style.cursor="auto";
this.viewmode=1;
this.i_preview.src=this.config.imgroot+"edit.gif";
this.statusMessage(this.strings["PREVIEW_MODE"]);
}else{
for(var i=0;i<this.areas.length;i++){
if(this.areas[i]){
this.areas[i].style.display="";
if(this.areas[i].label&&this.config.label){
this.areas[i].label.style.display="";
}
}
}
var _40=this.form_container.getElementsByTagName("input");
for(var i=0;i<_40.length;i++){
_40[i].disabled=false;
}
var _40=this.form_container.getElementsByTagName("select");
for(var i=0;i<_40.length;i++){
_40[i].disabled=false;
}
this.preview.innerHTML="";
this.pic.style.cursor=this.config.cursor_default;
this.pic.removeAttribute("usemap",0);
this.viewmode=0;
this.i_preview.src=this.config.imgroot+"zoom.gif";
this.statusMessage(this.strings["DESIGN_MODE"]);
this.is_drawing=0;
}
};
imgmap.prototype.addNewArea=function(){
if(this.viewmode==1){
return;
}
var _41=this._getLastArea();
var id=(_41)?_41.aid+1:0;
this.fireEvent("onAddArea",id);
this.areas[id]=document.createElement("DIV");
this.areas[id].id=this.mapname+"area"+id;
this.areas[id].aid=id;
this.areas[id].shape="undefined";
if(this.form_container){
this.props[id]=document.createElement("DIV");
this.form_container.appendChild(this.props[id]);
this.props[id].id="img_area_"+id;
this.props[id].aid=id;
this.props[id].className="img_area";
this.addEvent(this.props[id],"mouseover",this.img_area_mouseover.bind(this));
this.addEvent(this.props[id],"mouseout",this.img_area_mouseout.bind(this));
this.addEvent(this.props[id],"click",this.img_area_click.bind(this));
this.props[id].innerHTML="\t\t\t\t<input type=\"text\"  name=\"img_id\" class=\"img_id\" value=\""+id+"\" readonly=\"1\"/>\t\t\t\t<input type=\"radio\" name=\"img_active\" class=\"img_active\" id=\"img_active_"+id+"\" value=\""+id+"\">\t\t\t\tShape:\t<select name=\"img_shape\" class=\"img_shape\">\t\t\t\t\t<option value=\"rectangle\" >rectangle</option>\t\t\t\t\t<option value=\"circle\"    >circle</option>\t\t\t\t\t<option value=\"polygon\"   >polygon</option>\t\t\t\t\t</select>\t\t\t\tCoords: <input type=\"text\" name=\"img_coords\" class=\"img_coords\" value=\"\">\t\t\t\tHref: <input type=\"text\" name=\"img_href\" class=\"img_href\" value=\"\">\t\t\t\tAlt: <input type=\"text\" name=\"img_alt\" class=\"img_alt\" value=\"\">\t\t\t\tTarget:\t<select name=\"img_target\" class=\"img_target\">\t\t\t\t\t<option value=\"\"  >&lt;not set&gt;</option>\t\t\t\t\t<option value=\"_self\"  >this window</option>\t\t\t\t\t<option value=\"_blank\" >new window</option>\t\t\t\t\t<option value=\"_top\"   >top window</option>\t\t\t\t\t</select>";
this.addEvent(this.props[id].getElementsByTagName("input")[1],"keydown",this.img_area_keydown.bind(this));
this.addEvent(this.props[id].getElementsByTagName("input")[2],"keydown",this.img_coords_keydown.bind(this));
this.addEvent(this.props[id].getElementsByTagName("input")[2],"blur",this.img_area_blur.bind(this));
this.addEvent(this.props[id].getElementsByTagName("input")[3],"blur",this.img_area_blur.bind(this));
this.addEvent(this.props[id].getElementsByTagName("input")[4],"blur",this.img_area_blur.bind(this));
this.addEvent(this.props[id].getElementsByTagName("select")[0],"blur",this.img_area_blur.bind(this));
this.addEvent(this.props[id].getElementsByTagName("select")[1],"blur",this.img_area_blur.bind(this));
if(this.isSafari){
this.addEvent(this.props[id].getElementsByTagName("select")[0],"change",this.img_area_click.bind(this));
this.addEvent(this.props[id].getElementsByTagName("select")[1],"change",this.img_area_click.bind(this));
}
if(_41&&this.config.mode=="editor"){
this.props[id].getElementsByTagName("select")[0].value=_41.shape;
}else{
if(this.nextShape){
this.props[id].getElementsByTagName("select")[0].value=this.nextShape;
}
}
this.form_selectRow(id,true);
}
this.currentid=id;
return (id);
};
imgmap.prototype.initArea=function(id,_44){
if(!this.areas[id]){
return false;
}
if(this.areas[id].parentNode){
this.areas[id].parentNode.removeChild(this.areas[id]);
}
if(this.areas[id].label){
this.areas[id].label.parentNode.removeChild(this.areas[id].label);
}
this.areas[id]=null;
this.areas[id]=document.createElement("CANVAS");
this.pic.parentNode.appendChild(this.areas[id]);
this.pic.parentNode.style.position="relative";
if(typeof G_vmlCanvasManager!="undefined"){
this.areas[id]=G_vmlCanvasManager.initElement(this.areas[id]);
}
this.areas[id].id=this.mapname+"area"+id;
this.areas[id].aid=id;
this.areas[id].shape=_44;
this.areas[id].ahref="";
this.areas[id].atitle="";
this.areas[id].aalt="";
this.areas[id].atarget="";
this.areas[id].style.position="absolute";
this.areas[id].style.top=this.pic.offsetTop+"px";
this.areas[id].style.left=this.pic.offsetLeft+"px";
this._setopacity(this.areas[id],this.config.CL_DRAW_BG,this.config.draw_opacity);
this.areas[id].onmousedown=this.area_mousedown.bind(this);
this.areas[id].onmouseup=this.area_mouseup.bind(this);
this.areas[id].onmousemove=this.area_mousemove.bind(this);
this.memory[id]=new Object();
this.memory[id].downx=0;
this.memory[id].downy=0;
this.memory[id].left=0;
this.memory[id].top=0;
this.memory[id].width=0;
this.memory[id].height=0;
this.memory[id].xpoints=new Array();
this.memory[id].ypoints=new Array();
this.areas[id].label=document.createElement("DIV");
this.pic.parentNode.appendChild(this.areas[id].label);
this.areas[id].label.className=this.config.label_class;
this.assignCSS(this.areas[id].label,this.config.label_style);
this.areas[id].label.style.position="absolute";
};
imgmap.prototype.relaxArea=function(id){
if(!this.areas[id]){
return;
}
this.fireEvent("onRelaxArea",id);
if(this.areas[id].shape=="rectangle"){
this.areas[id].style.borderWidth="1px";
this.areas[id].style.borderStyle="solid";
this.areas[id].style.borderColor=this.config.CL_NORM_SHAPE;
}else{
if(this.areas[id].shape=="circle"||this.areas[id].shape=="polygon"){
if(this.config.bounding_box==true){
this.areas[id].style.borderWidth="1px";
this.areas[id].style.borderStyle="solid";
this.areas[id].style.borderColor=this.config.CL_NORM_BOX;
}else{
this.areas[id].style.border="";
}
}
}
this._setopacity(this.areas[id],this.config.CL_NORM_BG,this.config.norm_opacity);
};
imgmap.prototype.relaxAllAreas=function(){
for(var i=0;i<this.areas.length;i++){
if(this.areas[i]){
this.relaxArea(i);
}
}
};
imgmap.prototype._setopacity=function(_47,_48,pct){
_47.style.backgroundColor=_48;
_47.style.opacity="."+pct;
_47.style.filter="alpha(opacity="+pct+")";
};
imgmap.prototype.removeArea=function(){
if(this.viewmode==1){
return;
}
var id=this.currentid;
this.fireEvent("onRemoveArea",id);
if(this.props[id]){
var _4b=this.props[id].parentNode;
_4b.removeChild(this.props[id]);
var _4c=_4b.lastChild.aid;
this.props[id]=null;
try{
this.form_selectRow(_4c,true);
this.currentid=_4c;
}
catch(err){
}
}
try{
this.areas[id].parentNode.removeChild(this.areas[id]);
this.areas[id].label.parentNode.removeChild(this.areas[id].label);
}
catch(err){
}
this.areas[id]=null;
if(this.html_container){
this.html_container.value=this.getMapHTML();
}
};
imgmap.prototype.removeAllAreas=function(){
for(var i=0;i<this.props.length;i++){
if(this.props[i]){
if(this.props[i].parentNode){
this.props[i].parentNode.removeChild(this.props[i]);
}
if(this.areas[i].parentNode){
this.areas[i].parentNode.removeChild(this.areas[i]);
}
if(this.areas[i].label){
this.areas[i].label.parentNode.removeChild(this.areas[i].label);
}
this.props[i]=null;
this.areas[i]=null;
if(this.props.length>0&&this.props[i]){
this.form_selectRow((this.props.length-1),true);
}
}
}
};
imgmap.prototype._putlabel=function(id){
if(this.viewmode==1){
return;
}
if(!this.areas[id].label){
return;
}
try{
if(this.config.label==""||this.config.label==false){
this.areas[id].label.innerHTML="";
this.areas[id].label.style.display="none";
}else{
this.areas[id].label.style.display="";
var _4f=this.config.label;
_4f=_4f.replace(/%n/g,String(id));
_4f=_4f.replace(/%c/g,String(this.areas[id].lastInput));
_4f=_4f.replace(/%h/g,String(this.areas[id].ahref));
_4f=_4f.replace(/%a/g,String(this.areas[id].aalt));
_4f=_4f.replace(/%t/g,String(this.areas[id].atitle));
this.areas[id].label.innerHTML=_4f;
}
this.areas[id].label.style.top=this.areas[id].style.top;
this.areas[id].label.style.left=this.areas[id].style.left;
}
catch(err){
this.log("Error putting label",1);
}
};
imgmap.prototype._puthint=function(id){
try{
if(this.config.hint==""||this.config.hint==false){
this.areas[id].title="";
this.areas[id].alt="";
}else{
var _51=this.config.hint;
_51=_51.replace(/%n/g,String(id));
_51=_51.replace(/%c/g,String(this.areas[id].lastInput));
_51=_51.replace(/%h/g,String(this.areas[id].ahref));
_51=_51.replace(/%a/g,String(this.areas[id].aalt));
_51=_51.replace(/%t/g,String(this.areas[id].atitle));
this.areas[id].title=_51;
this.areas[id].alt=_51;
}
}
catch(err){
this.log("Error putting hint",1);
}
};
imgmap.prototype._repaintAll=function(){
for(var i=0;i<this.areas.length;i++){
if(this.areas[i]){
this._repaint(this.areas[i],this.config.CL_NORM_SHAPE);
}
}
};
imgmap.prototype._repaint=function(_53,_54,x,y){
if(_53.shape=="circle"){
var _57=parseInt(_53.style.width);
var _58=Math.floor(_57/2)-1;
var ctx=_53.getContext("2d");
ctx.clearRect(0,0,_57,_57);
ctx.beginPath();
ctx.strokeStyle=_54;
ctx.arc(_58,_58,_58,0,Math.PI*2,0);
ctx.stroke();
ctx.closePath();
ctx.strokeStyle=this.config.CL_KNOB;
ctx.strokeRect(_58,_58,1,1);
this._putlabel(_53.aid);
this._puthint(_53.aid);
}else{
if(_53.shape=="rectangle"){
this._putlabel(_53.aid);
this._puthint(_53.aid);
}else{
if(_53.shape=="polygon"){
var _57=parseInt(_53.style.width);
var _5a=parseInt(_53.style.height);
var _5b=parseInt(_53.style.left);
var top=parseInt(_53.style.top);
var ctx=_53.getContext("2d");
ctx.clearRect(0,0,_57,_5a);
ctx.beginPath();
ctx.strokeStyle=_54;
ctx.moveTo(_53.xpoints[0]-_5b,_53.ypoints[0]-top);
for(var i=1;i<_53.xpoints.length;i++){
ctx.lineTo(_53.xpoints[i]-_5b,_53.ypoints[i]-top);
}
if(this.is_drawing==this.DM_POLYGON_DRAW||this.is_drawing==this.DM_POLYGON_LASTDRAW){
ctx.lineTo(x-_5b-5,y-top-5);
}
ctx.lineTo(_53.xpoints[0]-_5b,_53.ypoints[0]-top);
ctx.stroke();
ctx.closePath();
this._putlabel(_53.aid);
this._puthint(_53.aid);
}
}
}
};
imgmap.prototype._updatecoords=function(){
var _5e=parseInt(this.areas[this.currentid].style.left);
var top=parseInt(this.areas[this.currentid].style.top);
var _60=parseInt(this.areas[this.currentid].style.height);
var _61=parseInt(this.areas[this.currentid].style.width);
var _62="";
if(this.areas[this.currentid].shape=="rectangle"){
_62=_5e+","+top+","+(_5e+_61)+","+(top+_60);
this.areas[this.currentid].lastInput=_62;
}else{
if(this.areas[this.currentid].shape=="circle"){
var _63=Math.floor(_61/2)-1;
_62=(_5e+_63)+","+(top+_63)+","+_63;
this.areas[this.currentid].lastInput=_62;
}else{
if(this.areas[this.currentid].shape=="polygon"){
_62="";
for(var i=0;i<this.areas[this.currentid].xpoints.length;i++){
_62+=this.areas[this.currentid].xpoints[i]+","+this.areas[this.currentid].ypoints[i]+",";
}
_62=_62.substring(0,_62.length-1);
this.areas[this.currentid].lastInput=_62;
}
}
}
if(this.props[this.currentid]){
this.props[this.currentid].getElementsByTagName("input")[2].value=_62;
}
if(this.html_container){
this.html_container.value=this.getMapHTML();
}
};
imgmap.prototype._recalculate=function(id){
var _66="";
var _67=null;
if(this.props[id]){
var _67=this.props[id].getElementsByTagName("input")[2];
_67.value=this._normCoords(_67.value,this.areas[id].shape);
_66=_67.value;
}else{
_66=this.areas[id].lastInput||"";
}
var _68=_66.split(",");
try{
if(this.areas[id].shape=="rectangle"){
if(_68.length!=4){
throw "invalid coords";
}
if(parseInt(_68[0])>parseInt(_68[2])){
throw "invalid coords";
}
if(parseInt(_68[1])>parseInt(_68[3])){
throw "invalid coords";
}
this.areas[id].style.left=this.pic.offsetLeft+parseInt(_68[0])+"px";
this.areas[id].style.top=this.pic.offsetTop+parseInt(_68[1])+"px";
this.areas[id].style.width=(_68[2]-_68[0])+"px";
this.areas[id].style.height=(_68[3]-_68[1])+"px";
this.areas[id].setAttribute("width",(_68[2]-_68[0]));
this.areas[id].setAttribute("height",(_68[3]-_68[1]));
this._repaint(this.areas[id],this.config.CL_NORM_SHAPE);
}else{
if(this.areas[id].shape=="circle"){
if(_68.length!=3){
throw "invalid coords";
}
if(parseInt(_68[2])<0){
throw "invalid coords";
}
var _69=2*(1*_68[2]+1);
this.areas[id].style.width=_69+"px";
this.areas[id].style.height=_69+"px";
this.areas[id].setAttribute("width",_69);
this.areas[id].setAttribute("height",_69);
this.areas[id].style.left=this.pic.offsetLeft+parseInt(_68[0])-_69/2+"px";
this.areas[id].style.top=this.pic.offsetTop+parseInt(_68[1])-_69/2+"px";
this._repaint(this.areas[id],this.config.CL_NORM_SHAPE);
}else{
if(this.areas[id].shape=="polygon"){
if(_68.length<2){
throw "invalid coords";
}
this.areas[id].xpoints=new Array();
this.areas[id].ypoints=new Array();
for(var i=0;i<_68.length;i+=2){
this.areas[id].xpoints[this.areas[id].xpoints.length]=this.pic.offsetLeft+parseInt(_68[i]);
this.areas[id].ypoints[this.areas[id].ypoints.length]=this.pic.offsetTop+parseInt(_68[i+1]);
this._polygongrow(this.areas[id],_68[i],_68[i+1]);
}
this._polygonshrink(this.areas[id]);
}
}
}
}
catch(err){
this.log(err.message,1);
this.statusMessage(this.strings["ERR_INVALID_COORDS"]);
if(this.areas[id].lastInput&&_67){
_67.value=this.areas[id].lastInput;
}
this._repaint(this.areas[id],this.config.CL_NORM_SHAPE);
return;
}
this.areas[id].lastInput=_66;
};
imgmap.prototype._polygongrow=function(_6b,_6c,_6d){
var _6e=_6c-parseInt(_6b.style.left);
var _6f=_6d-parseInt(_6b.style.top);
var pad=2;
var _71=pad*2;
if(_6c<parseInt(_6b.style.left)){
_6b.style.left=_6c-pad+"px";
_6b.style.width=parseInt(_6b.style.width)+Math.abs(_6e)+_71+"px";
_6b.setAttribute("width",parseInt(_6b.style.width));
}
if(_6d<parseInt(_6b.style.top)){
_6b.style.top=_6d-pad+"px";
_6b.style.height=parseInt(_6b.style.height)+Math.abs(_6f)+_71+"px";
_6b.setAttribute("height",parseInt(_6b.style.height));
}
if(_6c>parseInt(_6b.style.left)+parseInt(_6b.style.width)){
_6b.style.width=_6c-parseInt(_6b.style.left)+_71+"px";
_6b.setAttribute("width",parseInt(_6b.style.width));
}
if(_6d>parseInt(_6b.style.top)+parseInt(_6b.style.height)){
_6b.style.height=_6d-parseInt(_6b.style.top)+_71+"px";
_6b.setAttribute("height",parseInt(_6b.style.height));
}
};
imgmap.prototype._polygonshrink=function(_72){
_72.style.left=(_72.xpoints[0]+1)+"px";
_72.style.top=(_72.ypoints[0]+1)+"px";
_72.style.height="0px";
_72.style.width="0px";
_72.setAttribute("height","0");
_72.setAttribute("width","0");
for(var i=0;i<_72.xpoints.length;i++){
this._polygongrow(_72,_72.xpoints[i],_72.ypoints[i]);
}
this._repaint(_72,this.config.CL_NORM_SHAPE);
};
imgmap.prototype.img_mousemove=function(e){
if(this.viewmode==1){
return;
}
var pos=this._getPos(this.pic);
var x=(this.isMSIE)?(window.event.x-this.pic.offsetLeft):(e.pageX-pos.x);
var y=(this.isMSIE)?(window.event.y-this.pic.offsetTop):(e.pageY-pos.y);
x=x+this.pic_container.scrollLeft;
y=y+this.pic_container.scrollTop;
if(x<0||y<0||x>this.pic.width||y>this.pic.height){
return;
}
if(this.memory[this.currentid]){
var top=this.memory[this.currentid].top;
var _79=this.memory[this.currentid].left;
var _7a=this.memory[this.currentid].height;
var _7b=this.memory[this.currentid].width;
}
if(this.isSafari){
if(e.shiftKey){
if(this.is_drawing==this.DM_RECTANGLE_DRAW){
this.is_drawing=this.DM_SQUARE_DRAW;
this.statusMessage(this.strings["SQUARE2_DRAW"]);
}
}else{
if(this.is_drawing==this.DM_SQUARE_DRAW&&this.areas[this.currentid].shape=="rectangle"){
this.is_drawing=this.DM_RECTANGLE_DRAW;
this.statusMessage(this.strings["RECTANGLE_DRAW"]);
}
}
}
if(this.is_drawing==this.DM_RECTANGLE_DRAW){
this.fireEvent("onDrawArea",this.currentid);
var _7c=x-this.memory[this.currentid].downx;
var _7d=y-this.memory[this.currentid].downy;
this.areas[this.currentid].style.width=Math.abs(_7c)+"px";
this.areas[this.currentid].style.height=Math.abs(_7d)+"px";
this.areas[this.currentid].setAttribute("width",Math.abs(_7c));
this.areas[this.currentid].setAttribute("height",Math.abs(_7d));
if(_7c<0){
this.areas[this.currentid].style.left=(x+1)+"px";
}
if(_7d<0){
this.areas[this.currentid].style.top=(y+1)+"px";
}
}else{
if(this.is_drawing==this.DM_SQUARE_DRAW){
this.fireEvent("onDrawArea",this.currentid);
var _7c=x-this.memory[this.currentid].downx;
var _7d=y-this.memory[this.currentid].downy;
var _7e;
if(Math.abs(_7c)<Math.abs(_7d)){
_7e=Math.abs(_7c);
}else{
_7e=Math.abs(_7d);
}
this.areas[this.currentid].style.width=_7e+"px";
this.areas[this.currentid].style.height=_7e+"px";
this.areas[this.currentid].setAttribute("width",_7e);
this.areas[this.currentid].setAttribute("height",_7e);
if(_7c<0){
this.areas[this.currentid].style.left=(this.memory[this.currentid].downx+_7e*-1)+"px";
}
if(_7d<0){
this.areas[this.currentid].style.top=(this.memory[this.currentid].downy+_7e*-1+1)+"px";
}
}else{
if(this.is_drawing==this.DM_POLYGON_DRAW){
this.fireEvent("onDrawArea",this.currentid);
this._polygongrow(this.areas[this.currentid],x,y);
}else{
if(this.is_drawing==this.DM_RECTANGLE_MOVE||this.is_drawing==this.DM_SQUARE_MOVE){
this.fireEvent("onMoveArea",this.currentid);
var x=x-this.memory[this.currentid].rdownx;
var y=y-this.memory[this.currentid].rdowny;
if(x+_7b>this.pic.width||y+_7a>this.pic.height){
return;
}
if(x<0||y<0){
return;
}
this.areas[this.currentid].style.left=x+1+"px";
this.areas[this.currentid].style.top=y+1+"px";
}else{
if(this.is_drawing==this.DM_POLYGON_MOVE){
this.fireEvent("onMoveArea",this.currentid);
var x=x-this.memory[this.currentid].rdownx;
var y=y-this.memory[this.currentid].rdowny;
if(x+_7b>this.pic.width||y+_7a>this.pic.height){
return;
}
if(x<0||y<0){
return;
}
var _7c=x-_79;
var _7d=y-top;
for(var i=0;i<this.areas[this.currentid].xpoints.length;i++){
this.areas[this.currentid].xpoints[i]=this.memory[this.currentid].xpoints[i]+_7c;
this.areas[this.currentid].ypoints[i]=this.memory[this.currentid].ypoints[i]+_7d;
}
this.areas[this.currentid].style.left=x+1+"px";
this.areas[this.currentid].style.top=y+1+"px";
}else{
if(this.is_drawing==this.DM_SQUARE_RESIZE_LEFT){
this.fireEvent("onResizeArea",this.currentid);
var _7e=x-_79;
if((_7b+(-1*_7e))>0){
this.areas[this.currentid].style.left=x+1+"px";
this.areas[this.currentid].style.top=(top+(_7e/2))+"px";
this.areas[this.currentid].style.width=(_7b+(-1*_7e))+"px";
this.areas[this.currentid].style.height=(_7a+(-1*_7e))+"px";
this.areas[this.currentid].setAttribute("width",parseInt(this.areas[this.currentid].style.width));
this.areas[this.currentid].setAttribute("height",parseInt(this.areas[this.currentid].style.height));
}else{
this.memory[this.currentid].width=0;
this.memory[this.currentid].height=0;
this.memory[this.currentid].left=x;
this.memory[this.currentid].top=y;
this.is_drawing=this.DM_SQUARE_RESIZE_RIGHT;
}
}else{
if(this.is_drawing==this.DM_SQUARE_RESIZE_RIGHT){
this.fireEvent("onResizeArea",this.currentid);
var _7e=x-_79-_7b;
if((_7b+(_7e))-1>0){
this.areas[this.currentid].style.top=(top+(-1*_7e/2))+"px";
this.areas[this.currentid].style.width=(_7b+(_7e))-1+"px";
this.areas[this.currentid].style.height=(_7a+(_7e))+"px";
this.areas[this.currentid].setAttribute("width",parseInt(this.areas[this.currentid].style.width));
this.areas[this.currentid].setAttribute("height",parseInt(this.areas[this.currentid].style.height));
}else{
this.memory[this.currentid].width=0;
this.memory[this.currentid].height=0;
this.memory[this.currentid].left=x;
this.memory[this.currentid].top=y;
this.is_drawing=this.DM_SQUARE_RESIZE_LEFT;
}
}else{
if(this.is_drawing==this.DM_SQUARE_RESIZE_TOP){
this.fireEvent("onResizeArea",this.currentid);
var _7e=y-top;
if((_7b+(-1*_7e))>0){
this.areas[this.currentid].style.top=y+1+"px";
this.areas[this.currentid].style.left=(_79+(_7e/2))+"px";
this.areas[this.currentid].style.width=(_7b+(-1*_7e))+"px";
this.areas[this.currentid].style.height=(_7a+(-1*_7e))+"px";
this.areas[this.currentid].setAttribute("width",parseInt(this.areas[this.currentid].style.width));
this.areas[this.currentid].setAttribute("height",parseInt(this.areas[this.currentid].style.height));
}else{
this.memory[this.currentid].width=0;
this.memory[this.currentid].height=0;
this.memory[this.currentid].left=x;
this.memory[this.currentid].top=y;
this.is_drawing=this.DM_SQUARE_RESIZE_BOTTOM;
}
}else{
if(this.is_drawing==this.DM_SQUARE_RESIZE_BOTTOM){
this.fireEvent("onResizeArea",this.currentid);
var _7e=y-top-_7a;
if((_7b+(_7e))-1>0){
this.areas[this.currentid].style.left=(_79+(-1*_7e/2))+"px";
this.areas[this.currentid].style.width=(_7b+(_7e))-1+"px";
this.areas[this.currentid].style.height=(_7a+(_7e))-1+"px";
this.areas[this.currentid].setAttribute("width",parseInt(this.areas[this.currentid].style.width));
this.areas[this.currentid].setAttribute("height",parseInt(this.areas[this.currentid].style.height));
}else{
this.memory[this.currentid].width=0;
this.memory[this.currentid].height=0;
this.memory[this.currentid].left=x;
this.memory[this.currentid].top=y;
this.is_drawing=this.DM_SQUARE_RESIZE_TOP;
}
}else{
if(this.is_drawing==this.DM_RECTANGLE_RESIZE_LEFT){
this.fireEvent("onResizeArea",this.currentid);
var _7c=x-_79;
if(_7b+(-1*_7c)>0){
this.areas[this.currentid].style.left=x+1+"px";
this.areas[this.currentid].style.width=_7b+(-1*_7c)+"px";
this.areas[this.currentid].setAttribute("width",parseInt(this.areas[this.currentid].style.width));
}else{
this.memory[this.currentid].width=0;
this.memory[this.currentid].left=x;
this.is_drawing=this.DM_RECTANGLE_RESIZE_RIGHT;
}
}else{
if(this.is_drawing==this.DM_RECTANGLE_RESIZE_RIGHT){
this.fireEvent("onResizeArea",this.currentid);
var _7c=x-_79-_7b;
if((_7b+(_7c))-1>0){
this.areas[this.currentid].style.width=(_7b+(_7c))-1+"px";
this.areas[this.currentid].setAttribute("width",parseInt(this.areas[this.currentid].style.width));
}else{
this.memory[this.currentid].width=0;
this.memory[this.currentid].left=x;
this.is_drawing=this.DM_RECTANGLE_RESIZE_LEFT;
}
}else{
if(this.is_drawing==this.DM_RECTANGLE_RESIZE_TOP){
this.fireEvent("onResizeArea",this.currentid);
var _7d=y-top;
if((_7a+(-1*_7d))>0){
this.areas[this.currentid].style.top=y+1+"px";
this.areas[this.currentid].style.height=(_7a+(-1*_7d))+"px";
this.areas[this.currentid].setAttribute("height",parseInt(this.areas[this.currentid].style.height));
}else{
this.memory[this.currentid].height=0;
this.memory[this.currentid].top=y;
this.is_drawing=this.DM_RECTANGLE_RESIZE_BOTTOM;
}
}else{
if(this.is_drawing==this.DM_RECTANGLE_RESIZE_BOTTOM){
this.fireEvent("onResizeArea",this.currentid);
var _7d=y-top-_7a;
if((_7a+(_7d))-1>0){
this.areas[this.currentid].style.height=(_7a+(_7d))-1+"px";
this.areas[this.currentid].setAttribute("height",parseInt(this.areas[this.currentid].style.height));
}else{
this.memory[this.currentid].height=0;
this.memory[this.currentid].top=y;
this.is_drawing=this.DM_RECTANGLE_RESIZE_TOP;
}
}
}
}
}
}
}
}
}
}
}
}
}
}
if(this.is_drawing){
this._repaint(this.areas[this.currentid],this.config.CL_DRAW_SHAPE,x,y);
this._updatecoords();
}
};
imgmap.prototype.img_mouseup=function(e){
if(this.viewmode==1){
return;
}
var pos=this._getPos(this.pic);
var x=(this.isMSIE)?(window.event.x-this.pic.offsetLeft):(e.pageX-pos.x);
var y=(this.isMSIE)?(window.event.y-this.pic.offsetTop):(e.pageY-pos.y);
x=x+this.pic_container.scrollLeft;
y=y+this.pic_container.scrollTop;
if(this.is_drawing!=this.DM_RECTANGLE_DRAW&&this.is_drawing!=this.DM_SQUARE_DRAW&&this.is_drawing!=this.DM_POLYGON_DRAW&&this.is_drawing!=this.DM_POLYGON_LASTDRAW){
this.draggedId=null;
this.is_drawing=0;
this.statusMessage(this.strings["READY"]);
this.relaxArea(this.currentid);
if(this.areas[this.currentid]==this._getLastArea()){
return;
}
this.memory[this.currentid].downx=x;
this.memory[this.currentid].downy=y;
}
};
imgmap.prototype.img_mousedown=function(e){
if(this.viewmode==1){
return;
}
if(!this.areas[this.currentid]&&this.config.mode!="editor2"){
return;
}
var pos=this._getPos(this.pic);
var x=(this.isMSIE)?(window.event.x-this.pic.offsetLeft):(e.pageX-pos.x);
var y=(this.isMSIE)?(window.event.y-this.pic.offsetTop):(e.pageY-pos.y);
x=x+this.pic_container.scrollLeft;
y=y+this.pic_container.scrollTop;
if(!e){
e=window.event;
}
if(e.shiftKey){
if(this.is_drawing==this.DM_POLYGON_DRAW){
this.is_drawing=this.DM_POLYGON_LASTDRAW;
}
}
if(this.is_drawing==this.DM_POLYGON_DRAW){
this.areas[this.currentid].xpoints[this.areas[this.currentid].xpoints.length]=x-5;
this.areas[this.currentid].ypoints[this.areas[this.currentid].ypoints.length]=y-5;
this.memory[this.currentid].downx=x;
this.memory[this.currentid].downy=y;
return;
}else{
if(this.is_drawing&&this.is_drawing!=this.DM_POLYGON_DRAW){
if(this.is_drawing==this.DM_POLYGON_LASTDRAW){
this.areas[this.currentid].xpoints[this.areas[this.currentid].xpoints.length]=x-5;
this.areas[this.currentid].ypoints[this.areas[this.currentid].ypoints.length]=y-5;
this._updatecoords();
this.is_drawing=0;
this._polygonshrink(this.areas[this.currentid]);
}
this.is_drawing=0;
this.statusMessage(this.strings["READY"]);
this.relaxArea(this.currentid);
if(this.areas[this.currentid]==this._getLastArea()){
if(this.config.mode!="editor2"){
this.addNewArea();
}
return;
}
return;
}
}
if(this.config.mode=="editor2"){
if(this.nextShape==""){
return;
}
this.addNewArea();
this.initArea(this.currentid,this.nextShape);
}else{
var _88=(this.props[this.currentid])?this.props[this.currentid].getElementsByTagName("select")[0].value:this.nextShape;
if(_88==""){
_88="rectangle";
}
this.initArea(this.currentid,_88);
}
if(this.areas[this.currentid].shape=="polygon"){
this.is_drawing=this.DM_POLYGON_DRAW;
this.statusMessage(this.strings["POLYGON_DRAW"]);
this.areas[this.currentid].style.left=x+"px";
this.areas[this.currentid].style.top=y+"px";
if(this.config.bounding_box==true){
this.areas[this.currentid].style.borderWidth="1px";
this.areas[this.currentid].style.borderStyle="dotted";
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_BOX;
}
this.areas[this.currentid].style.width=0;
this.areas[this.currentid].style.height=0;
this.areas[this.currentid].xpoints=new Array();
this.areas[this.currentid].ypoints=new Array();
this.areas[this.currentid].xpoints[0]=x;
this.areas[this.currentid].ypoints[0]=y;
}else{
if(this.areas[this.currentid].shape=="rectangle"){
this.is_drawing=this.DM_RECTANGLE_DRAW;
this.statusMessage(this.strings["RECTANGLE_DRAW"]);
this.areas[this.currentid].style.left=x+"px";
this.areas[this.currentid].style.top=y+"px";
this.areas[this.currentid].style.borderWidth="1px";
this.areas[this.currentid].style.borderStyle="dotted";
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_SHAPE;
this.areas[this.currentid].style.width=0;
this.areas[this.currentid].style.height=0;
}else{
if(this.areas[this.currentid].shape=="circle"){
this.is_drawing=this.DM_SQUARE_DRAW;
this.statusMessage(this.strings["SQUARE_DRAW"]);
this.areas[this.currentid].style.left=x+"px";
this.areas[this.currentid].style.top=y+"px";
if(this.config.bounding_box==true){
this.areas[this.currentid].style.borderWidth="1px";
this.areas[this.currentid].style.borderStyle="dotted";
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_BOX;
}
this.areas[this.currentid].style.width=0;
this.areas[this.currentid].style.height=0;
}
}
}
this.memory[this.currentid].downx=x;
this.memory[this.currentid].downy=y;
};
imgmap.prototype.img_area_mouseover=function(e){
if(this.is_drawing){
return;
}
if(this.viewmode==1){
return;
}
var obj=(this.isMSIE)?window.event.srcElement:e.currentTarget;
if(typeof obj.aid=="undefined"){
obj=obj.parentNode;
}
var id=obj.aid;
if(this.areas[id]&&this.areas[id].shape!="undefined"){
this.fireEvent("onFocusArea",this.areas[id]);
if(this.areas[id].shape=="rectangle"){
this.areas[id].style.borderWidth="1px";
this.areas[id].style.borderStyle="solid";
this.areas[id].style.borderColor=this.config.CL_HIGHLIGHT_SHAPE;
}else{
if(this.areas[id].shape=="circle"||this.areas[id].shape=="polygon"){
if(this.config.bounding_box==true){
this.areas[id].style.borderWidth="1px";
this.areas[id].style.borderStyle="solid";
this.areas[id].style.borderColor=this.config.CL_HIGHLIGHT_BOX;
}
}
}
this._setopacity(this.areas[id],this.config.CL_HIGHLIGHT_BG,this.config.highlight_opacity);
this._repaint(this.areas[id],this.config.CL_HIGHLIGHT_SHAPE);
}
};
imgmap.prototype.img_area_mouseout=function(e){
if(this.is_drawing){
return;
}
if(this.viewmode==1){
return;
}
var obj=(this.isMSIE)?window.event.srcElement:e.currentTarget;
if(typeof obj.aid=="undefined"){
obj=obj.parentNode;
}
var id=obj.aid;
if(this.areas[id]&&this.areas[id].shape!="undefined"){
this.fireEvent("onBlurArea",this.areas[id]);
if(this.areas[id].shape=="rectangle"){
this.areas[id].style.borderWidth="1px";
this.areas[id].style.borderStyle="solid";
this.areas[id].style.borderColor=this.config.CL_NORM_SHAPE;
}else{
if(this.areas[id].shape=="circle"||this.areas[id].shape=="polygon"){
if(this.config.bounding_box==true){
this.areas[id].style.borderWidth="1px";
this.areas[id].style.borderStyle="solid";
this.areas[id].style.borderColor=this.config.CL_NORM_BOX;
}
}
}
this._setopacity(this.areas[id],this.config.CL_NORM_BG,this.config.norm_opacity);
this._repaint(this.areas[id],this.config.CL_NORM_SHAPE);
}
};
imgmap.prototype.img_area_click=function(e){
if(this.viewmode==1){
return;
}
var obj=(this.isMSIE)?window.event.srcElement:e.currentTarget;
if(typeof obj.aid=="undefined"){
obj=obj.parentNode;
}
this.form_selectRow(obj.aid,false);
this.currentid=obj.aid;
};
imgmap.prototype.form_selectRow=function(id,_92){
if(this.is_drawing){
return;
}
if(this.viewmode==1){
return;
}
if(!this.form_container){
return;
}
if(!document.getElementById("img_active_"+id)){
return;
}
document.getElementById("img_active_"+id).checked=1;
if(_92){
document.getElementById("img_active_"+id).focus();
}
for(var i=0;i<this.props.length;i++){
if(this.props[i]){
this.props[i].style.background="";
}
}
this.props[id].style.background=this.config.CL_HIGHLIGHT_PROPS;
this.fireEvent("onSelectRow",this.props[id]);
};
imgmap.prototype.img_area_keydown=function(e){
if(this.viewmode==1){
return;
}
var key=(this.isMSIE)?event.keyCode:e.keyCode;
if(key==46){
this.removeArea();
}
};
imgmap.prototype.img_area_blur=function(e){
if(this.viewmode==1){
return;
}
if(this.is_drawing!=0){
return;
}
var obj=(this.isMSIE)?window.event.srcElement:e.currentTarget;
var id=obj.parentNode.aid;
if(obj.name=="img_href"){
this.areas[id].ahref=obj.value;
}
if(obj.name=="img_alt"){
this.areas[id].aalt=obj.value;
}
if(obj.name=="img_title"){
this.areas[id].atitle=obj.value;
}
if(obj.name=="img_target"){
this.areas[id].atarget=obj.value;
}
if(obj.name=="img_shape"){
this.areas[id].shape=obj.value;
}
if(this.areas[id]&&this.areas[id].shape!="undefined"){
this._recalculate(id);
if(this.html_container){
this.html_container.value=this.getMapHTML();
}
}
};
imgmap.prototype.html_container_blur=function(e){
var _9a=this.html_container.getAttribute("oldvalue");
if(_9a!=this.html_container.value){
if(this.viewmode==1){
return;
}
this.setMapHTML(this.html_container.value);
}
};
imgmap.prototype.html_container_focus=function(e){
this.html_container.setAttribute("oldvalue",this.html_container.value);
this.html_container.select();
};
imgmap.prototype.area_mousemove=function(e){
if(this.viewmode==1){
return;
}
if(this.is_drawing==0){
var obj=(this.isMSIE)?window.event.srcElement:e.currentTarget;
if(obj.tagName=="DIV"){
obj=obj.parentNode;
}
if(obj.tagName=="image"||obj.tagName=="group"||obj.tagName=="shape"||obj.tagName=="stroke"){
obj=obj.parentNode.parentNode;
}
var _9e=(this.isMSIE)?(window.event.offsetX):(e.layerX);
var _9f=(this.isMSIE)?(window.event.offsetY):(e.layerY);
if(_9e<6&&_9f>6){
if(obj.shape!="polygon"){
obj.style.cursor="w-resize";
}
}else{
if(_9e>parseInt(obj.style.width)-6&&_9f>6){
if(obj.shape!="polygon"){
obj.style.cursor="e-resize";
}
}else{
if(_9e>6&&_9f<6){
if(obj.shape!="polygon"){
obj.style.cursor="n-resize";
}
}else{
if(_9f>parseInt(obj.style.height)-6&&_9e>6){
if(obj.shape!="polygon"){
obj.style.cursor="s-resize";
}
}else{
obj.style.cursor="move";
}
}
}
}
if(obj.aid!=this.draggedId){
if(obj.style.cursor=="move"){
obj.style.cursor="default";
}
return;
}
if(_9e<6&&_9f>6){
if(this.areas[this.currentid].shape=="circle"){
this.is_drawing=this.DM_SQUARE_RESIZE_LEFT;
this.statusMessage(this.strings["SQUARE_RESIZE_LEFT"]);
if(this.config.bounding_box==true){
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_BOX;
}
}else{
if(this.areas[this.currentid].shape=="rectangle"){
this.is_drawing=this.DM_RECTANGLE_RESIZE_LEFT;
this.statusMessage(this.strings["RECTANGLE_RESIZE_LEFT"]);
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_SHAPE;
}
}
}else{
if(_9e>parseInt(this.areas[this.currentid].style.width)-6&&_9f>6){
if(this.areas[this.currentid].shape=="circle"){
this.is_drawing=this.DM_SQUARE_RESIZE_RIGHT;
this.statusMessage(this.strings["SQUARE_RESIZE_RIGHT"]);
if(this.config.bounding_box==true){
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_BOX;
}
}else{
if(this.areas[this.currentid].shape=="rectangle"){
this.is_drawing=this.DM_RECTANGLE_RESIZE_RIGHT;
this.statusMessage(this.strings["RECTANGLE_RESIZE_RIGHT"]);
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_SHAPE;
}
}
}else{
if(_9e>6&&_9f<6){
if(this.areas[this.currentid].shape=="circle"){
this.is_drawing=this.DM_SQUARE_RESIZE_TOP;
this.statusMessage(this.strings["SQUARE_RESIZE_TOP"]);
if(this.config.bounding_box==true){
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_BOX;
}
}else{
if(this.areas[this.currentid].shape=="rectangle"){
this.is_drawing=this.DM_RECTANGLE_RESIZE_TOP;
this.statusMessage(this.strings["RECTANGLE_RESIZE_TOP"]);
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_SHAPE;
}
}
}else{
if(_9f>parseInt(this.areas[this.currentid].style.height)-6&&_9e>6){
if(this.areas[this.currentid].shape=="circle"){
this.is_drawing=this.DM_SQUARE_RESIZE_BOTTOM;
this.statusMessage(this.strings["SQUARE_RESIZE_BOTTOM"]);
if(this.config.bounding_box==true){
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_BOX;
}
}else{
if(this.areas[this.currentid].shape=="rectangle"){
this.is_drawing=this.DM_RECTANGLE_RESIZE_BOTTOM;
this.statusMessage(this.strings["RECTANGLE_RESIZE_BOTTOM"]);
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_SHAPE;
}
}
}else{
if(this.areas[this.currentid].shape=="circle"){
this.is_drawing=this.DM_SQUARE_MOVE;
this.statusMessage(this.strings["SQUARE_MOVE"]);
if(this.config.bounding_box==true){
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_BOX;
}
this.memory[this.currentid].rdownx=_9e;
this.memory[this.currentid].rdowny=_9f;
}else{
if(this.areas[this.currentid].shape=="rectangle"){
this.is_drawing=this.DM_RECTANGLE_MOVE;
this.statusMessage(this.strings["RECTANGLE_MOVE"]);
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_SHAPE;
this.memory[this.currentid].rdownx=_9e;
this.memory[this.currentid].rdowny=_9f;
}else{
if(this.areas[this.currentid].shape=="polygon"){
for(var i=0;i<this.areas[this.currentid].xpoints.length;i++){
this.memory[this.currentid].xpoints[i]=this.areas[this.currentid].xpoints[i];
this.memory[this.currentid].ypoints[i]=this.areas[this.currentid].ypoints[i];
}
this.is_drawing=this.DM_POLYGON_MOVE;
this.statusMessage(this.strings["POLYGON_MOVE"]);
if(this.config.bounding_box==true){
this.areas[this.currentid].style.borderColor=this.config.CL_DRAW_BOX;
}
this.memory[this.currentid].rdownx=_9e;
this.memory[this.currentid].rdowny=_9f;
}
}
}
}
}
}
}
this.memory[this.currentid].width=parseInt(this.areas[this.currentid].style.width);
this.memory[this.currentid].height=parseInt(this.areas[this.currentid].style.height);
this.memory[this.currentid].top=parseInt(this.areas[this.currentid].style.top);
this.memory[this.currentid].left=parseInt(this.areas[this.currentid].style.left);
if(this.areas[this.currentid].shape=="rectangle"){
this.areas[this.currentid].style.borderWidth="1px";
this.areas[this.currentid].style.borderStyle="dotted";
}else{
if(this.areas[this.currentid].shape=="circle"||this.areas[this.currentid].shape=="polygon"){
if(this.config.bounding_box==true){
this.areas[this.currentid].style.borderWidth="1px";
this.areas[this.currentid].style.borderStyle="dotted";
}
}
}
this._setopacity(this.areas[this.currentid],this.config.CL_DRAW_BG,this.config.draw_opacity);
}else{
this.img_mousemove(e);
}
};
imgmap.prototype.area_mouseup=function(e){
if(this.viewmode==1){
return;
}
if(this.is_drawing==0){
var obj=(this.isMSIE)?window.event.srcElement:e.currentTarget;
if(obj.tagName=="DIV"){
obj=obj.parentNode;
}
if(obj.tagName=="image"||obj.tagName=="group"||obj.tagName=="shape"||obj.tagName=="stroke"){
obj=obj.parentNode.parentNode;
}
if(this.areas[this.currentid]!=obj){
if(typeof obj.aid=="undefined"){
this.log("Cannot identify target area",1);
return;
}
}
this.draggedId=null;
}else{
this.img_mouseup(e);
}
};
imgmap.prototype.area_mousedown=function(e){
if(this.viewmode==1){
return;
}
if(this.is_drawing==0){
var obj=(this.isMSIE)?window.event.srcElement:e.currentTarget;
if(obj.tagName=="DIV"){
obj=obj.parentNode;
}
if(obj.tagName=="image"||obj.tagName=="group"||obj.tagName=="shape"||obj.tagName=="stroke"){
obj=obj.parentNode.parentNode;
}
if(this.areas[this.currentid]!=obj){
if(typeof obj.aid=="undefined"){
this.log("Cannot identify target area",1);
return;
}
this.form_selectRow(obj.aid,true);
this.currentid=obj.aid;
}
this.fireEvent("onSelectArea",this.areas[this.currentid]);
this.draggedId=this.currentid;
this.selectedId=this.currentid;
(this.isMSIE)?window.event.cancelBubble=true:e.stopPropagation();
}else{
this.img_mousedown(e);
}
};
imgmap.prototype.getSelectionStart=function(obj){
if(obj.createTextRange){
var r=document.selection.createRange().duplicate();
r.moveEnd("character",obj.value.length);
if(r.text==""){
return obj.value.length;
}
return obj.value.lastIndexOf(r.text);
}else{
return obj.selectionStart;
}
};
imgmap.prototype.setSelectionRange=function(obj,_a8,end){
if(typeof end=="undefined"){
end=_a8;
}
if(obj.selectionStart){
obj.setSelectionRange(_a8,end);
obj.focus();
}else{
if(document.selection){
var _aa=obj.createTextRange();
_aa.collapse(true);
_aa.moveStart("character",_a8);
_aa.moveEnd("character",end-_a8);
_aa.select();
}
}
};
imgmap.prototype.img_coords_keydown=function(e){
if(this.viewmode==1){
return;
}
var key=(this.isMSIE)?event.keyCode:e.keyCode;
var obj=(this.isMSIE)?window.event.srcElement:e.originalTarget;
if(key==40||key==38){
this.fireEvent("onResizeArea",this.areas[this.currentid]);
var _ae=obj.value;
_ae=_ae.split(",");
var s=this.getSelectionStart(obj);
var j=0;
for(var i=0;i<_ae.length;i++){
j+=_ae[i].length;
if(j>s){
if(key==40&&_ae[i]>0){
_ae[i]--;
}
if(key==38){
_ae[i]++;
}
this._recalculate(this.currentid);
break;
}
j++;
}
obj.value=_ae.join(",");
this.setSelectionRange(obj,s);
return true;
}
};
imgmap.prototype.doc_keydown=function(e){
if(this.viewmode==1){
return;
}
var key=(this.isMSIE)?event.keyCode:e.keyCode;
if(key==46){
if(this.selectedId!=null&&!this.is_drawing){
this.removeArea();
}
}else{
if(key==16){
if(this.is_drawing==this.DM_RECTANGLE_DRAW){
this.is_drawing=this.DM_SQUARE_DRAW;
this.statusMessage(this.strings["SQUARE2_DRAW"]);
}
}
}
};
imgmap.prototype.doc_keyup=function(e){
var key=(this.isMSIE)?event.keyCode:e.keyCode;
if(key==16){
if(this.is_drawing==this.DM_SQUARE_DRAW&&this.areas[this.currentid].shape=="rectangle"){
this.is_drawing=this.DM_RECTANGLE_DRAW;
this.statusMessage(this.strings["RECTANGLE_DRAW"]);
}
}
};
imgmap.prototype.doc_mousedown=function(e){
if(this.viewmode==1){
return;
}
if(this.is_drawing==0){
this.selectedId=null;
}
};
imgmap.prototype._getPos=function(_b7){
var _b8=0;
var _b9=0;
if(_b7){
var _ba=_b7.offsetParent;
if(_ba){
while((_ba=_b7.offsetParent)!=null){
_b8+=_b7.offsetLeft;
_b9+=_b7.offsetTop;
_b7=_ba;
}
}else{
_b8=_b7.offsetLeft;
_b9=_b7.offsetTop;
}
}
return new Object({x:_b8,y:_b9});
};
imgmap.prototype._getLastArea=function(){
for(var i=this.areas.length-1;i>=0;i--){
if(this.areas[i]){
return this.areas[i];
}
}
return null;
};
imgmap.prototype.toClipBoard=function(_bc){
this.fireEvent("onClipboard",_bc);
if(typeof _bc=="undefined"){
_bc=this.getMapHTML();
}
try{
if(window.clipboardData){
window.clipboardData.setData("Text",_bc);
}else{
if(window.netscape){
netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
var str=Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
if(!str){
return false;
}
str.data=_bc;
var _be=Components.classes["@mozilla.org/widget/transferable;1"].createInstance(Components.interfaces.nsITransferable);
if(!_be){
return false;
}
_be.addDataFlavor("text/unicode");
_be.setTransferData("text/unicode",str,_bc.length*2);
var _bf=Components.interfaces.nsIClipboard;
var _c0=Components.classes["@mozilla.org/widget/clipboard;1"].getService(_bf);
if(!_c0){
return false;
}
_c0.setData(_be,null,_bf.kGlobalClipboard);
}
}
}
catch(err){
this.log("Unable to set clipboard data",1);
}
};
imgmap.prototype.assignCSS=function(obj,_c2){
var _c3=_c2.split(";");
for(var i=0;i<_c3.length;i++){
var p=_c3[i].split(":");
var pp=p[0].trim().split("-");
var _c7=pp[0];
for(var j=1;j<pp.length;j++){
_c7+=pp[j].replace(/^./,pp[j].substring(0,1).toUpperCase());
}
_c7=_c7.trim();
value=p[1].trim();
eval("obj.style."+_c7+" = '"+value+"';");
}
};
imgmap.prototype.fireEvent=function(evt,obj){
if(typeof this.config.custom_callbacks[evt]=="function"){
return this.config.custom_callbacks[evt](obj);
}
};
Function.prototype.bind=function(_cb){
var _cc=this;
return function(){
return _cc.apply(_cb,arguments);
};
};
String.prototype.trim=function(){
return this.replace(/^\s+|\s+$/g,"");
};
String.prototype.ltrim=function(){
return this.replace(/^\s+/,"");
};
String.prototype.rtrim=function(){
return this.replace(/\s+$/,"");
};
function imgmap_spawnObjects(_cd){
var _ce=document.getElementsByTagName("map");
var _cf=document.getElementsByTagName("img");
var _d0=new Array();
for(var i=0;i<_ce.length;i++){
for(var j=0;j<_cf.length;j++){
if("#"+_ce[i].name==_cf[j].getAttribute("usemap")){
_cd.mode="";
imapn=new imgmap(_cd);
imapn.useImage(_cf[j]);
imapn.setMapHTML(_ce[i]);
imapn.viewmode=1;
_d0.push(imapn);
}
}
}
}

