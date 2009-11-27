AJS={BASE_URL:"",drag_obj:null,drag_elm:null,_drop_zones:[],_cur_pos:null,getScrollTop:function(){
var t;
if(document.documentElement&&document.documentElement.scrollTop){
t=document.documentElement.scrollTop;
}else{
if(document.body){
t=document.body.scrollTop;
}
}
return t;
},addClass:function(){
var _2=AJS.forceArray(arguments);
var _3=_2.pop();
var _4=function(o){
if(!new RegExp("(^|\\s)"+_3+"(\\s|$)").test(o.className)){
o.className+=(o.className?" ":"")+_3;
}
};
AJS.map(_2,function(_6){
_4(_6);
});
},setStyle:function(){
var _7=AJS.forceArray(arguments);
var _8=_7.pop();
var _9=_7.pop();
AJS.map(_7,function(_a){
_a.style[_9]=AJS.getCssDim(_8);
});
},extend:function(_b){
var _c=new this("no_init");
for(k in _b){
var _d=_c[k];
var _e=_b[k];
if(_d&&_d!=_e&&typeof _e=="function"){
_e=this._parentize(_e,_d);
}
_c[k]=_e;
}
return new AJS.Class(_c);
},log:function(o){
if(window.console){
console.log(o);
}else{
var div=AJS.$("ajs_logger");
if(!div){
div=AJS.DIV({id:"ajs_logger","style":"color: green; position: absolute; left: 0"});
div.style.top=AJS.getScrollTop()+"px";
AJS.ACN(AJS.getBody(),div);
}
AJS.setHTML(div,""+o);
}
},setHeight:function(){
var _11=AJS.forceArray(arguments);
_11.splice(_11.length-1,0,"height");
AJS.setStyle.apply(null,_11);
},_getRealScope:function(fn,_13){
_13=AJS.$A(_13);
var _14=fn._cscope||window;
return function(){
var _15=AJS.$FA(arguments).concat(_13);
return fn.apply(_14,_15);
};
},documentInsert:function(elm){
if(typeof (elm)=="string"){
elm=AJS.HTML2DOM(elm);
}
document.write("<span id=\"dummy_holder\"></span>");
AJS.swapDOM(AJS.$("dummy_holder"),elm);
},getWindowSize:function(doc){
doc=doc||document;
var _18,_19;
if(self.innerHeight){
_18=self.innerWidth;
_19=self.innerHeight;
}else{
if(doc.documentElement&&doc.documentElement.clientHeight){
_18=doc.documentElement.clientWidth;
_19=doc.documentElement.clientHeight;
}else{
if(doc.body){
_18=doc.body.clientWidth;
_19=doc.body.clientHeight;
}
}
}
return {"w":_18,"h":_19};
},flattenList:function(_1a){
var r=[];
var _1c=function(r,l){
AJS.map(l,function(o){
if(o==null){
}else{
if(AJS.isArray(o)){
_1c(r,o);
}else{
r.push(o);
}
}
});
};
_1c(r,_1a);
return r;
},isFunction:function(obj){
return (typeof obj=="function");
},setEventKey:function(e){
e.key=e.keyCode?e.keyCode:e.charCode;
if(window.event){
e.ctrl=window.event.ctrlKey;
e.shift=window.event.shiftKey;
}else{
e.ctrl=e.ctrlKey;
e.shift=e.shiftKey;
}
switch(e.key){
case 63232:
e.key=38;
break;
case 63233:
e.key=40;
break;
case 63235:
e.key=39;
break;
case 63234:
e.key=37;
break;
}
},removeElement:function(){
var _22=AJS.forceArray(arguments);
AJS.map(_22,function(elm){
AJS.swapDOM(elm,null);
});
},_unloadListeners:function(){
if(AJS.listeners){
AJS.map(AJS.listeners,function(elm,_25,fn){
AJS.REV(elm,_25,fn);
});
}
AJS.listeners=[];
},join:function(_27,_28){
try{
return _28.join(_27);
}
catch(e){
var r=_28[0]||"";
AJS.map(_28,function(elm){
r+=_27+elm;
},1);
return r+"";
}
},getIndex:function(elm,_2c,_2d){
for(var i=0;i<_2c.length;i++){
if(_2d&&_2d(_2c[i])||elm==_2c[i]){
return i;
}
}
return -1;
},isIn:function(elm,_30){
var i=AJS.getIndex(elm,_30);
if(i!=-1){
return true;
}else{
return false;
}
},isArray:function(obj){
return obj instanceof Array;
},setLeft:function(){
var _33=AJS.forceArray(arguments);
_33.splice(_33.length-1,0,"left");
AJS.setStyle.apply(null,_33);
},appendChildNodes:function(elm){
if(arguments.length>=2){
AJS.map(arguments,function(n){
if(AJS.isString(n)){
n=AJS.TN(n);
}
if(AJS.isDefined(n)){
elm.appendChild(n);
}
},1);
}
return elm;
},getElementsByTagAndClassName:function(_36,_37,_38,_39){
var _3a=[];
if(!AJS.isDefined(_38)){
_38=document;
}
if(!AJS.isDefined(_36)){
_36="*";
}
var els=_38.getElementsByTagName(_36);
var _3c=els.length;
var _3d=new RegExp("(^|\\s)"+_37+"(\\s|$)");
for(i=0,j=0;i<_3c;i++){
if(_3d.test(els[i].className)||_37==null){
_3a[j]=els[i];
j++;
}
}
if(_39){
return _3a[0];
}else{
return _3a;
}
},isOpera:function(){
return (navigator.userAgent.toLowerCase().indexOf("opera")!=-1);
},isString:function(obj){
return (typeof obj=="string");
},hideElement:function(elm){
var _40=AJS.forceArray(arguments);
AJS.map(_40,function(elm){
elm.style.display="none";
});
},setOpacity:function(elm,p){
elm.style.opacity=p;
elm.style.filter="alpha(opacity="+p*100+")";
},insertBefore:function(elm,_45){
_45.parentNode.insertBefore(elm,_45);
return elm;
},setWidth:function(){
var _46=AJS.forceArray(arguments);
_46.splice(_46.length-1,0,"width");
AJS.setStyle.apply(null,_46);
},createArray:function(v){
if(AJS.isArray(v)&&!AJS.isString(v)){
return v;
}else{
if(!v){
return [];
}else{
return [v];
}
}
},isDict:function(o){
var _49=String(o);
return _49.indexOf(" Object")!=-1;
},isMozilla:function(){
return (navigator.userAgent.toLowerCase().indexOf("gecko")!=-1&&navigator.productSub>=20030210);
},removeEventListener:function(elm,_4b,fn,_4d){
var _4e="ajsl_"+_4b+fn;
if(!_4d){
_4d=false;
}
fn=elm[_4e]||fn;
if(elm["on"+_4b]==fn){
elm["on"+_4b]=elm[_4e+"old"];
}
if(elm.removeEventListener){
elm.removeEventListener(_4b,fn,_4d);
if(AJS.isOpera()){
elm.removeEventListener(_4b,fn,!_4d);
}
}else{
if(elm.detachEvent){
elm.detachEvent("on"+_4b,fn);
}
}
},callLater:function(fn,_50){
var _51=function(){
fn();
};
window.setTimeout(_51,_50);
},setTop:function(){
var _52=AJS.forceArray(arguments);
_52.splice(_52.length-1,0,"top");
AJS.setStyle.apply(null,_52);
},_createDomShortcuts:function(){
var _53=["ul","li","td","tr","th","tbody","table","input","span","b","a","div","img","button","h1","h2","h3","h4","h5","h6","br","textarea","form","p","select","option","optgroup","iframe","script","center","dl","dt","dd","small","pre","i"];
var _54=function(elm){
AJS[elm.toUpperCase()]=function(){
return AJS.createDOM.apply(null,[elm,arguments]);
};
};
AJS.map(_53,_54);
AJS.TN=function(_56){
return document.createTextNode(_56);
};
},addCallback:function(fn){
this.callbacks.unshift(fn);
},bindMethods:function(_58){
for(var k in _58){
var _5a=_58[k];
if(typeof (_5a)=="function"){
_58[k]=AJS.$b(_5a,_58);
}
}
},partial:function(fn){
var _5c=AJS.$FA(arguments);
_5c.shift();
return function(){
_5c=_5c.concat(AJS.$FA(arguments));
return fn.apply(window,_5c);
};
},isNumber:function(obj){
return (typeof obj=="number");
},getCssDim:function(dim){
if(AJS.isString(dim)){
return dim;
}else{
return dim+"px";
}
},isIe:function(){
return (navigator.userAgent.toLowerCase().indexOf("msie")!=-1&&navigator.userAgent.toLowerCase().indexOf("opera")==-1);
},removeClass:function(){
var _5f=AJS.forceArray(arguments);
var cls=_5f.pop();
var _61=function(o){
o.className=o.className.replace(new RegExp("\\s?"+cls,"g"),"");
};
AJS.map(_5f,function(elm){
_61(elm);
});
},setHTML:function(elm,_65){
elm.innerHTML=_65;
return elm;
},map:function(_66,fn,_68,_69){
var i=0,l=_66.length;
if(_68){
i=_68;
}
if(_69){
l=_69;
}
for(i;i<l;i++){
var val=fn(_66[i],i);
if(val!=undefined){
return val;
}
}
},addEventListener:function(elm,_6e,fn,_70,_71){
var _72="ajsl_"+_6e+fn;
if(!_71){
_71=false;
}
AJS.listeners=AJS.$A(AJS.listeners);
if(AJS.isIn(_6e,["keypress","keydown","keyup","click"])){
var _73=fn;
fn=function(e){
AJS.setEventKey(e);
return _73.apply(window,arguments);
};
}
var _75=AJS.isIn(_6e,["submit","load","scroll","resize"]);
var _76=AJS.$A(elm);
AJS.map(_76,function(_77){
if(_70){
var _78=fn;
fn=function(e){
AJS.REV(_77,_6e,fn);
return _78.apply(window,arguments);
};
}
if(_75){
var _7a=_77["on"+_6e];
var _7b=function(){
if(_7a){
fn(arguments);
return _7a(arguments);
}else{
return fn(arguments);
}
};
_77[_72]=_7b;
_77[_72+"old"]=_7a;
elm["on"+_6e]=_7b;
}else{
_77[_72]=fn;
if(_77.attachEvent){
_77.attachEvent("on"+_6e,fn);
}else{
if(_77.addEventListener){
_77.addEventListener(_6e,fn,_71);
}
}
AJS.listeners.push([_77,_6e,fn]);
}
});
},preloadImages:function(){
AJS.AEV(window,"load",AJS.$p(function(_7c){
AJS.map(_7c,function(src){
var pic=new Image();
pic.src=src;
});
},arguments));
},forceArray:function(_7f){
var r=[];
AJS.map(_7f,function(elm){
r.push(elm);
});
return r;
},update:function(l1,l2){
for(var i in l2){
l1[i]=l2[i];
}
return l1;
},getBody:function(){
return AJS.$bytc("body")[0];
},HTML2DOM:function(_85,_86){
var d=AJS.DIV();
d.innerHTML=_85;
if(_86){
return d.childNodes[0];
}else{
return d;
}
},getElement:function(id){
if(AJS.isString(id)||AJS.isNumber(id)){
return document.getElementById(id);
}else{
return id;
}
},showElement:function(){
var _89=AJS.forceArray(arguments);
AJS.map(_89,function(elm){
elm.style.display="";
});
},bind:function(fn,_8c,_8d){
fn._cscope=_8c;
return AJS._getRealScope(fn,_8d);
},createDOM:function(_8e,_8f){
var i=0,_91;
var elm=document.createElement(_8e);
var _93=_8f[0];
if(AJS.isDict(_8f[i])){
for(k in _93){
_91=_93[k];
if(k=="style"||k=="s"){
elm.style.cssText=_91;
}else{
if(k=="c"||k=="class"||k=="className"){
elm.className=_91;
}else{
elm.setAttribute(k,_91);
}
}
}
i++;
}
if(_93==null){
i=1;
}
for(var j=i;j<_8f.length;j++){
var _91=_8f[j];
if(_91){
var _95=typeof (_91);
if(_95=="string"||_95=="number"){
_91=AJS.TN(_91);
}
elm.appendChild(_91);
}
}
return elm;
},swapDOM:function(_96,src){
_96=AJS.getElement(_96);
var _98=_96.parentNode;
if(src){
src=AJS.getElement(src);
_98.replaceChild(src,_96);
}else{
_98.removeChild(_96);
}
return src;
},isDefined:function(o){
return (o!="undefined"&&o!=null);
}};
AJS.$=AJS.getElement;
AJS.$$=AJS.getElements;
AJS.$f=AJS.getFormElement;
AJS.$p=AJS.partial;
AJS.$b=AJS.bind;
AJS.$A=AJS.createArray;
AJS.DI=AJS.documentInsert;
AJS.ACN=AJS.appendChildNodes;
AJS.RCN=AJS.replaceChildNodes;
AJS.AEV=AJS.addEventListener;
AJS.REV=AJS.removeEventListener;
AJS.$bytc=AJS.getElementsByTagAndClassName;
AJS.$AP=AJS.absolutePosition;
AJS.$FA=AJS.forceArray;
AJS.addEventListener(window,"unload",AJS._unloadListeners);
AJS._createDomShortcuts();
AJS.Class=function(_9a){
var fn=function(){
if(arguments[0]!="no_init"){
return this.init.apply(this,arguments);
}
};
fn.prototype=_9a;
AJS.update(fn,AJS.Class.prototype);
return fn;
};
AJS.Class.prototype={extend:function(_9c){
var _9d=new this("no_init");
for(k in _9c){
var _9e=_9d[k];
var cur=_9c[k];
if(_9e&&_9e!=cur&&typeof cur=="function"){
cur=this._parentize(cur,_9e);
}
_9d[k]=cur;
}
return new AJS.Class(_9d);
},implement:function(_a0){
AJS.update(this.prototype,_a0);
},_parentize:function(cur,_a2){
return function(){
this.parent=_a2;
return cur.apply(this,arguments);
};
}};
script_loaded=true;


script_loaded=true;