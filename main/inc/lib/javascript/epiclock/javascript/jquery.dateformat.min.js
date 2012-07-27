/*!
   Date formatting plugin
 
   Copyright (c) Eric Garside
   Dual licensed under:
       MIT: http://www.opensource.org/licenses/mit-license.php
       GPLv3: http://www.opensource.org/licenses/gpl-3.0.html
 */"use strict";(function($){var months=['January','February','March','April','May','June','July','August','September','October','November','December'],days=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],counts=[31,28,31,30,31,30,31,31,30,31,30,31],suffix=[null,'st','nd','rd'],_;function pad(format,string,length)
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
{return pad(_.p.apply(this),0);},E:function()
{return(_.X.apply(this)*60)+_.p.apply(this);},e:function()
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
{return formatDate(date||new Date(),format);},pad:pad,rpad:rpad};}(jQuery));