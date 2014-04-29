/*!
 *  epiClock 3.0
 *
 *  Copyright (c) 2008 Eric Garside (http://eric.garside.name)
 *  Dual licensed under:
 *      MIT: http://www.opensource.org/licenses/mit-license.php
 *      GPLv3: http://www.opensource.org/licenses/gpl-3.0.html
 */
"use strict";(function($){var months=['January','February','March','April','May','June','July','August','September','October','November','December'],days=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],counts=[31,28,31,30,31,30,31,31,30,31,30,31],suffix=[null,'st','nd','rd'],_;function pad(format,string,length)
{format=format+'';length=length||2;return format.length<length?new Array(1+length-format.length).join(string)+format:format;}
function rpad(format,string,length)
{format=format+'';length=length||2;return format.length<length?format+new Array(1+length-format.length).join(string):format;}
function modCalc(date,mod1,mod2)
{return(Math.floor(Math.floor(date.valueOf()/1e3)/mod1)%mod2);}
function formatDate(date,format)
{format=format.split('');var output='',buffering=false,char='',index=0;for(;index<format.length;index++)
{char=format[index]+'';switch(char)
{case' ':output+=char;break;case'{':case'}':buffering=char==='{';break;default:if(!buffering&&_[char])
{output+=_[char].apply(date);}
else
{output+=char;}
break;}}
return output;}
_={V:function()
{return modCalc(this,864e2,1e5);},v:function()
{return pad(_.V.apply(this),0);},K:function()
{return _.V.apply(this)%365;},k:function()
{return pad(_.K.apply(this),0);},X:function()
{return modCalc(this,36e2,24);},x:function()
{return pad(_.X.apply(this),0);},p:function()
{return modCalc(this,60,60);},C:function()
{return pad(_.p.apply(this),0);},e:function()
{return(_.X.apply(this)*60)+_.p.apply(this);},E:function()
{return pad(_.e.apply(this),0);},d:function()
{return pad(this.getDate(),0);},D:function()
{return days[this.getDay()].substring(0,3);},j:function()
{return this.getDate();},l:function()
{return days[this.getDay()];},N:function()
{return this.getDay()+1;},S:function()
{return suffix[this.getDate()]||'th';},w:function()
{return this.getDay();},z:function()
{return Math.round((this-_.f.apply(this))/864e5);},W:function()
{return Math.ceil(((((this-_.f.apply(this))/864e5)+_.w.apply(_.f.apply(this)))/7));},F:function()
{return months[this.getMonth()];},m:function()
{return pad((this.getMonth()+1),0);},M:function()
{return months[this.getMonth()].substring(0,3);},n:function()
{return this.getMonth()+1;},t:function()
{if(this.getMonth()===1&&_.L.apply(this)===1)
{return 29;}
return counts[this.getMonth()];},L:function()
{var Y=_.Y.apply(this);return Y%4?0:Y%100?1:Y%400?0:1;},f:function()
{return new Date(this.getFullYear(),0,1);},Y:function()
{return this.getFullYear();},y:function()
{return(''+this.getFullYear()).substr(2);},a:function()
{return this.getHours()<12?'am':'pm';},A:function()
{return _.a.apply(this).toUpperCase();},B:function()
{return pad(Math.floor((((this.getHours())*36e5)+(this.getMinutes()*6e4)+(this.getSeconds()*1e3))/864e2),0,3);},g:function()
{return this.getHours()%12||12;},G:function()
{return this.getHours();},h:function()
{return pad(_.g.apply(this),0);},H:function()
{return pad(this.getHours(),0);},i:function()
{return pad(this.getMinutes(),0);},s:function()
{return pad(this.getSeconds(),0);},u:function()
{return this.getTime()%1e3;},O:function()
{var t=this.getTimezoneOffset()/60;return rpad(pad((t>=0?'+':'-')+Math.abs(t),0),0,4);},P:function()
{var t=_.O.apply(this);return t.subst(0,3)+':'+t.substr(3);},Z:function()
{return this.getTimezoneOffset()*60;},c:function()
{return _.Y.apply(this)+'-'+_.m.apply(this)+'-'+_.d.apply(this)+'T'+_.H.apply(this)+':'+_.i.apply(this)+':'+_.s.apply(this)+_.P.apply(this);},r:function()
{return this.toString();},U:function()
{return this.getTime()/1e3;}};$.extend(Date.prototype,{format:function(format)
{return formatDate(this,format);}});$.dateformat={rules:function(custom)
{if(custom!==undefined)
{_=$.extend(_,custom);}
return _;},hasRule:function(rule)
{return _[rule]!==undefined;},get:function(type,date)
{return _[type].apply(date||new Date());},format:function(format,date)
{return formatDate(date||new Date(),format);},pad:pad,rpad:rpad};}(jQuery));"use strict";(function($){var mode={clock:'clock',explicit:'explicit',countdown:'countdown',countup:'countup',rollover:'rollover',expire:'expire',loop:'loop',stopwatch:'stopwatch',holdup:'holdup',timer:'timer'},_=function(selector)
{return $(selector).data('epiclock');},events={},instances={},uid=0,now,zero=new Date(0),intervalID,Clock=function()
{this.__uid=uid++;instances[this.__uid]={clock:this,frame:{}};};function triggerEvent(uid,event,params)
{instances[uid].clock.container.triggerHandler(event,params);if(events[uid]===undefined||events[uid][event]===undefined)
{return;}
$.each(events[uid][event],function(index,value)
{if($.isFunction(value))
{value.apply(instances[uid].clock,params||[]);}});}
function terminate(clock,current)
{triggerEvent(clock.__uid,'timer');switch(clock.mode)
{case mode.holdup:case mode.rollover:clock.mode=mode.countup;clock.restart(0);return zero;case mode.expire:case mode.countdown:case mode.timer:clock.destroy();return zero;case mode.loop:clock.restart();return zero;}}
function tick(clock)
{if(clock.__paused!==undefined&&clock.mode)
{return false;}
var current=now+clock.__displacement,days;switch(clock.mode)
{case mode.holdup:current-=clock.time;if(current>0||current>-1e3)
{return terminate(clock,current);}
return zero;case mode.countup:case mode.stopwatch:current-=clock.time;break;case mode.explicit:current+=clock.time;break;case mode.rollover:case mode.loop:case mode.expire:case mode.countdown:case mode.timer:current=clock.time-current;if(current<1e3)
{return terminate(clock,current);}
break;}
if(clock.displayOffset!==undefined)
{days=parseInt($.dateformat.get('V',current),10);if(days>clock.__days_added)
{clock.__days_added=days;clock.displayOffset.days+=days;if(clock.displayOffset.days>=365)
{clock.displayOffset.years+=Math.floor(clock.displayOffset.days/365.4%365.4);clock.displayOffset.days=Math.floor(clock.displayOffset.days%365.4);}}}
return new Date(current);}
function evaluate(clock,symbol,current)
{switch(symbol)
{case'Q':return clock.displayOffset.years;case'E':return clock.displayOffset.days;case'e':return $.dateformat.pad(clock.displayOffset.days,0);default:return $.dateformat.get(symbol,current);}}
function defaultRenderer(frame,value)
{frame.text(value);}
function render(clock)
{var time=tick(clock);if(time===false)
{return false;}
$.each(instances[clock.__uid].frame,function(symbol,frame)
{var value=evaluate(clock,symbol,time)+'';if(frame.data('epiclock-last')!==value)
{(clock.__render||defaultRenderer)(frame,value);}
frame.data('epiclock-last',value);});triggerEvent(clock.__uid,'rendered');if(clock.container.hasClass('epiclock-wait-for-render'))
{clock.container.removeClass('epiclock-wait-for-render');}}
function cycleClocks()
{now=new Date().valueOf();$.each(instances,function()
{render(this.clock);if(this.clock.__destroy)
{this.clock.container.removeData('epiclock');delete instances[this.clock.__uid];}});}
function startManager()
{if(intervalID!==undefined)
{return;}
$.each(instances,function()
{this.clock.resume();});intervalID=setInterval(cycleClocks,_.precision);}
function haltManager()
{clearInterval(intervalID);intervalID=undefined;$.each(instances,function()
{this.clock.pause();});}
function defaultFormat(format)
{switch(format)
{case mode.clock:case mode.explicit:return'F j, Y g:i:s a';case mode.countdown:return'V{d} x{h} i{m} s{s}';case mode.countup:return'Q{y} K{d} x{h} i{m} s{s}';case mode.rollover:return'V{d} x{h} i{m} s{s}';case mode.expire:case mode.timer:return'x{h} i{m} s{s}';case mode.loop:return'i{m} s{s}';case mode.stopwatch:return'x{h} C{m} s{s}';case mode.holdup:return'Q{y} K{d} x{h} i{m} s{s}';}}
function configureInstance(properties)
{var clock=new Clock(),modifier=1;clock.mode=properties.mode||mode.clock;if(properties.offset!==undefined)
{clock.__offset=((properties.offset.years||0)*3157056e4+
(properties.offset.days||0)*864e5+
(properties.offset.hours||0)*36e5+
(properties.offset.minutes||0)*6e4+
(properties.offset.seconds||0)*1e3);}
if(properties.startpaused)
{clock.__paused=new Date().valueOf();}
clock.__displacement=properties.utc===true||properties.gmt===true?new Date().getTimezoneOffset()*6e4:0;clock.__render=$.isFunction(properties.renderer)?properties.renderer:_.renderers[properties.renderer];clock.__days_added=0;clock.__tare=properties.tare||false;clock.format=properties.format||defaultFormat(clock.mode);clock.time=(properties.time||properties.target?new Date(properties.time||properties.target):new Date()).valueOf();clock.displayOffset=$.extend({years:0,days:0},properties.displayOffset);switch(clock.mode)
{case mode.clock:case mode.countup:break;case mode.explicit:clock.__displacement-=new Date().valueOf();break;case mode.timer:case mode.loop:modifier=-1;clock.__tare=true;clock.__displacement-=1e3;break;case mode.expire:case mode.countdown:case mode.rollover:modifier=-1;clock.__displacement-=1e3;break;case mode.holdup:if(clock.time<new Date().valueOf())
{clock.mode=mode.countup;}
else
{clock.__displacement-=1e3;}
break;case mode.stopwatch:clock.__tare=true;break;default:throw'EPICLOCK_INVALID_MODE';}
clock.__displacement+=modifier*clock.__offset;return clock;}
function printTick(clock)
{now=new Date().valueOf();return tick(clock);}
$.extend(_,{precision:500,modes:mode,renderers:{},addRenderer:function(key,renderer,setup)
{_.renderers[key]=renderer;if($.isFunction(setup))
{_.renderers[key].setup=setup;}
return _;},make:function(properties,container,template)
{var clock=configureInstance(properties),output='',buffering=false,char='',index=0,format=clock.format.split(''),containerClass=typeof properties.renderer==="string"?' epiclock-'+properties.renderer:'';container=$(container).data('epiclock',clock).addClass('epiclock-container epiclock-wait-for-render'+containerClass);template=$(template||'<span></span>');for(;index<format.length;index++)
{char=format[index]+'';switch(char)
{case' ':if(buffering)
{output+=char;}
else
{template.clone(true).addClass('epiclock epiclock-spacer').appendTo(container);}
break;case'{':case'}':buffering=char==='{';if(!buffering)
{template.clone(true).addClass('epiclock').html(output).appendTo(container);output='';}
break;default:if(!buffering&&$.dateformat.hasRule(char))
{instances[clock.__uid].frame[char]=template.clone(true).addClass('epiclock epiclock-digit').data('epiclock-encoding',char).appendTo(container);}
else if(!buffering)
{template.clone(true).addClass('epiclock epiclock-separator').html(char).appendTo(container);}
else
{output+=char;}
break;}}
clock.container=container;if(clock.__render!==undefined&&$.isFunction(clock.__render.setup))
{clock.__render.setup.apply(clock,[container]);}
startManager();return clock;},pause:function()
{haltManager();},resume:function()
{startManager();},destroy:function()
{clearInterval(intervalID);$.each(instances,function()
{this.clock.destroy();});},restart:function()
{$.each(instances,function()
{this.clock.restart();});}});$.dateformat.rules({Q:function()
{return'%displayOffset-years%';},E:function()
{return'%displayOffset-days%';},e:function()
{return'%displayOffset-days-pad%';}});$.extend(Clock.prototype,{__uid:undefined,__render:undefined,__displacement:undefined,__days_added:undefined,__paused:undefined,__offset:0,__tare:false,__destroy:false,displayOffset:undefined,time:undefined,format:undefined,mode:undefined,container:undefined,bind:function(event,listener)
{if(events[this.__uid]===undefined)
{events[this.__uid]={};}
if(events[this.__uid][event]===undefined)
{events[this.__uid][event]=[listener];}
else
{events[this.__uid][event].push(listener);}
return this;},pause:function()
{if(this.__paused===undefined)
{this.__paused=new Date().valueOf();}
triggerEvent(this.__uid,'pause');},resume:function()
{if(this.__paused===undefined)
{return;}
triggerEvent(this.__uid,'resume');if(this.__tare)
{this.__displacement+=(this.__paused-new Date().valueOf());}
this.__paused=undefined;},toggle:function()
{if(this.__paused===undefined)
{this.pause();}
else
{this.resume();}},destroy:function()
{this.__destroy=true;triggerEvent(this.__uid,'destroy');},restart:function(displacement)
{if(displacement!==undefined)
{this.__displacement=displacement;}
this.time=now.valueOf();},print:function(format)
{var value=$.dateformat.format(this.format,printTick(this));if(this.displayOffset!==undefined)
{return value.replace('%displayOffset-days%',this.displayOffset.years).replace('%displayOffset-days%',this.displayOffset.days).replace('%displayOffset-days-pad%',$.dateformat.pad(this.displayOffset.days,0));}
return value;}});$.epiclock=_;$.fn.epiclock=function(properties)
{return this.each(function()
{var container=$(this),template;properties=properties||{};if(properties.template!==undefined)
{template=properties.template;delete properties.template;}
else if(container.children().length>0)
{template=container.children().remove().eq(0);}
$.epiclock.make(properties,container,template);});};}(jQuery));