AJS.fx={_shades:{0:"ffffff",1:"ffffee",2:"ffffdd",3:"ffffcc",4:"ffffbb",5:"ffffaa",6:"ffff99"},highlight:function(_1,_2){
var _3=new AJS.fx.Base();
_3.elm=AJS.$(_1);
_3.options.duration=600;
_3.setOptions(_2);
AJS.update(_3,{increase:function(){
if(this.now==7){
_1.style.backgroundColor="#fff";
}else{
_1.style.backgroundColor="#"+AJS.fx._shades[Math.floor(this.now)];
}
}});
return _3.custom(6,0);
},fadeIn:function(_4,_5){
_5=_5||{};
if(!_5.from){
_5.from=0;
AJS.setOpacity(_4,0);
}
if(!_5.to){
_5.to=1;
}
var s=new AJS.fx.Style(_4,"opacity",_5);
return s.custom(_5.from,_5.to);
},fadeOut:function(_7,_8){
_8=_8||{};
if(!_8.from){
_8.from=1;
}
if(!_8.to){
_8.to=0;
}
_8.duration=300;
var s=new AJS.fx.Style(_7,"opacity",_8);
return s.custom(_8.from,_8.to);
},setWidth:function(_a,_b){
var s=new AJS.fx.Style(_a,"width",_b);
return s.custom(_b.from,_b.to);
},setHeight:function(_d,_e){
var s=new AJS.fx.Style(_d,"height",_e);
return s.custom(_e.from,_e.to);
}};
AJS.fx.Base=new AJS.Class({init:function(_10){
this.options={onStart:function(){
},onComplete:function(){
},transition:AJS.fx.Transitions.sineInOut,duration:500,wait:true,fps:50};
AJS.update(this.options,_10);
AJS.bindMethods(this);
},setOptions:function(_11){
AJS.update(this.options,_11);
},step:function(){
var _12=new Date().getTime();
if(_12<this.time+this.options.duration){
this.cTime=_12-this.time;
this.setNow();
}else{
setTimeout(AJS.$b(this.options.onComplete,this,[this.elm]),10);
this.clearTimer();
this.now=this.to;
}
this.increase();
},setNow:function(){
this.now=this.compute(this.from,this.to);
},compute:function(_13,to){
var _15=to-_13;
return this.options.transition(this.cTime,_13,_15,this.options.duration);
},clearTimer:function(){
clearInterval(this.timer);
this.timer=null;
return this;
},_start:function(_16,to){
if(!this.options.wait){
this.clearTimer();
}
if(this.timer){
return;
}
setTimeout(AJS.$p(this.options.onStart,this.elm),10);
this.from=_16;
this.to=to;
this.time=new Date().getTime();
this.timer=setInterval(this.step,Math.round(1000/this.options.fps));
return this;
},custom:function(_18,to){
return this._start(_18,to);
},set:function(to){
this.now=to;
this.increase();
return this;
},setStyle:function(elm,_1c,val){
if(this.property=="opacity"){
AJS.setOpacity(elm,val);
}else{
AJS.setStyle(elm,_1c,val);
}
}});
AJS.fx.Style=AJS.fx.Base.extend({init:function(elm,_1f,_20){
this.parent();
this.elm=elm;
this.setOptions(_20);
this.property=_1f;
},increase:function(){
this.setStyle(this.elm,this.property,this.now);
}});
AJS.fx.Styles=AJS.fx.Base.extend({init:function(elm,_22){
this.parent();
this.elm=AJS.$(elm);
this.setOptions(_22);
this.now={};
},setNow:function(){
for(p in this.from){
this.now[p]=this.compute(this.from[p],this.to[p]);
}
},custom:function(obj){
if(this.timer&&this.options.wait){
return;
}
var _24={};
var to={};
for(p in obj){
_24[p]=obj[p][0];
to[p]=obj[p][1];
}
return this._start(_24,to);
},increase:function(){
for(var p in this.now){
this.setStyle(this.elm,p,this.now[p]);
}
}});
AJS.fx.Transitions={linear:function(t,b,c,d){
return c*t/d+b;
},sineInOut:function(t,b,c,d){
return -c/2*(Math.cos(Math.PI*t/d)-1)+b;
}};
script_loaded=true;


script_loaded=true;