/* ASCIIsvgPI.js
==============
This version contains modifications needed for ASCIIsvg to work with
tinyMCE editor plugins.  Modifications made by David Lippman, (c) 2008
Revised 8/14/09

JavaScript routines to dynamically generate Scalable Vector Graphics
using a mathematical xy-coordinate system (y increases upwards) and
very intuitive JavaScript commands (no programming experience required).
ASCIIsvg.js is good for learning math and illustrating online math texts.
Works with Internet Explorer+Adobe SVGviewer and SVG enabled Mozilla/Firefox.

Ver 1.2.7 Oct 13, 2005 (c) Peter Jipsen http://www.chapman.edu/~jipsen
Latest version at http://www.chapman.edu/~jipsen/svg/ASCIIsvg.js
If you use it on a webpage, please send the URL to jipsen@chapman.edu

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation; either version 2.1 of the License, or (at
your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
General Public License (at http://www.gnu.org/copyleft/gpl.html)
for more details.*/

var AScgiloc = 'http://www.imathas.com/imathas/filter/graph/svgimg.php';
var ASnoSVG = false;
var checkIfSVGavailable = true;
var notifyIfNoSVG = false;
var alertIfNoSVG = false;
var xunitlength = 20;  // pixels
var yunitlength = 20;  // pixels
var origin = [0,0];   // in pixels (default is bottom left corner)
var defaultwidth = 300; defaultheight = 200; defaultborder = [0,0,0,0];
var border = defaultborder;
var strokewidth, strokedasharray, stroke, fill;
var fontstyle, fontfamily, fontsize, fontweight, fontstroke, fontfill, fontbackground;
var markerstrokewidth = "1";
var markerstroke = "black";
var markerfill = "yellow";
var marker = "none";
var arrowfill = stroke;
var dotradius = 4;
var ticklength = 4;
var axesstroke = "black";
var gridstroke = "grey";
var pointerpos = null;
var coordinates = null;
var above = "above";
var below = "below";
var left = "left";
var right = "right";
var aboveleft = "aboveleft";
var aboveright = "aboveright";
var belowleft = "belowleft";
var belowright = "belowright";
var cpi = "\u03C0", ctheta = "\u03B8";
var pi = Math.PI, ln = Math.log, e = Math.E;
var arcsin = Math.asin, arccos = Math.acos, arctan = Math.atan;
var sec = function(x) { return 1/Math.cos(x) };
var csc = function(x) { return 1/Math.sin(x) };
var cot = function(x) { return 1/Math.tan(x) };
var xmin, xmax, ymin, ymax, xscl, yscl,
    xgrid, ygrid, xtick, ytick, initialized;
var isIE = document.createElementNS==null;
var picture, svgpicture, doc, width, height, a, b, c, d, i, n, p, t, x, y;
var arcsec = function(x) { return arccos(1/x) };
var arccsc = function(x) { return arcsin(1/x) };
var arccot = function(x) { return arctan(1/x) };
var sinh = function(x) { return (Math.exp(x)-Math.exp(-x))/2 };
var cosh = function(x) { return (Math.exp(x)+Math.exp(-x))/2 };
var tanh =
  function(x) { return (Math.exp(x)-Math.exp(-x))/(Math.exp(x)+Math.exp(-x)) };
var sech = function(x) { return 1/cosh(x) };
var csch = function(x) { return 1/sinh(x) };
var coth = function(x) { return 1/tanh(x) };
var arcsinh = function(x) { return ln(x+Math.sqrt(x*x+1)) };
var arccosh = function(x) { return ln(x+Math.sqrt(x*x-1)) };
var arctanh = function(x) { return ln((1+x)/(1-x))/2 };
var sech = function(x) { return 1/cosh(x) };
var csch = function(x) { return 1/sinh(x) };
var coth = function(x) { return 1/tanh(x) };
var arcsech = function(x) { return arccosh(1/x) };
var arccsch = function(x) { return arcsinh(1/x) };
var arccoth = function(x) { return arctanh(1/x) };
var sign = function(x) { return (x==0?0:(x<0?-1:1)) };
var logten = function(x) { return (Math.LOG10E*Math.log(x)) };


function factorial(x,n) {
  if (n==null) n=1;
  for (var i=x-n; i>0; i-=n) x*=i;
  return (x<0?NaN:(x==0?1:x));
}


function C(x,k) {
  var res=1;
  for (var i=0; i<k; i++) res*=(x-i)/(k-i);
  return res;
}


function chop(x,n) {
  if (n==null) n=0;
  return Math.floor(x*Math.pow(10,n))/Math.pow(10,n);
}


function ran(a,b,n) {
  if (n==null) n=0;
  return chop((b+Math.pow(10,-n)-a)*Math.random()+a,n);
}


function myCreateElementXHTML(t) {
  if (isIE) return document.createElement(t);
  else return document.createElementNS("http://www.w3.org/1999/xhtml",t);
}


function isSVGavailable() {

// Disabled by Ivan Tcholakov, 06-JAN-2011.
/*
//Safari 3 can do SVG, but still has issues.
  if ((ver = navigator.userAgent.toLowerCase().match(/safari\/(\d+)/))!=null) {
        //if (ver[1]>524) {
        //	return null;
        //}
        return 1;
 }
//Opera can do SVG, but not very pretty, so might want to skip it
 if ((ver = navigator.userAgent.toLowerCase().match(/opera\/([\d\.]+)/))!=null) {
        if (ver[1]>9.1) {
            return null;
        }
 }
 //Mozillas.
  if (navigator.product && navigator.product=='Gecko') {
       var rv = navigator.userAgent.toLowerCase().match(/rv:\s*([\d\.]+)/);
       if (rv!=null) {
        rv = rv[1].split('.');
        if (rv.length<3) { rv[2] = 0;}
        if (rv.length<2) { rv[1] = 0;}
       }
       if (rv!=null && 10000*rv[0]+100*rv[1]+1*rv[2]>=10800) return null;
       else return 1;
  }
  //IE + AdobeSVGviewer
  if (navigator.appName.slice(0,9)=="Microsoft")
    try	{
      var oSVG=eval("new ActiveXObject('Adobe.SVGCtl.3');");
        return null;
    } catch (e) {
        return 1;
    }
  else return 1;
*/

    // Added by Ivan Tcholakov, 06-JAN-2011.
    if (navigator.appName.slice(0, 8) == "Netscape") { // Gecko, Chrome, Safari
        if (window['SVGElement']) {
            return null;
        } else {
            return 1;
        }
    } else if (navigator.appName.slice(0, 9) == "Microsoft") { // IE
        try {
            var oSVG = eval("new ActiveXObject('Adobe.SVGCtl.3');");
            return null;
        } catch (ex) {
            return 1;
        }
    } else if (navigator.appName.slice(0, 5) == "Opera") { // Opera 9.50b1 or higher
        return null;
    }
    return 1;
    //
}

function less(x,y) { return x < y }  // used for scripts in XML files
                                     // since IE does not handle CDATA well
function setText(st,id) {
  var node = document.getElementById(id);
  if (node!=null)
    if (node.childNodes.length!=0) node.childNodes[0].nodeValue = st;
    else node.appendChild(document.createTextNode(st));
}


function myCreateElementSVG(t) {
  if (isIE) return doc.createElement(t);
  else return doc.createElementNS("http://www.w3.org/2000/svg",t);
}


function getX() {
  return (doc.getElementById("pointerpos").getAttribute("cx")-origin[0])/xunitlength;
}


function getY() {
  return (height-origin[1]-doc.getElementById("pointerpos").getAttribute("cy"))/yunitlength;
}


function mousemove_listener(evt) {
  if (svgpicture.getAttribute("xbase")!=null)
    pointerpos.cx.baseVal.value = evt.clientX-svgpicture.getAttribute("xbase");
  if (svgpicture.getAttribute("ybase")!=null)
    pointerpos.cy.baseVal.value = evt.clientY-svgpicture.getAttribute("ybase");
}


function top_listener(evt) {
  svgpicture.setAttribute("ybase",evt.clientY);
}


function bottom_listener(evt) {
  svgpicture.setAttribute("ybase",evt.clientY-height+1);
}


function left_listener(evt) {
  svgpicture.setAttribute("xbase",evt.clientX);
}


function right_listener(evt) {
  svgpicture.setAttribute("xbase",evt.clientX-width+1);
}


function switchTo(id) {
//alert(id);
  picture = document.getElementById(id);
  width = picture.getAttribute("width")-0;
  height = picture.getAttribute("height")-0;
  strokewidth = "1" // pixel
  stroke = "black"; // default line color
  fill = "none";    // default fill color
  marker = "none";
  if ((picture.nodeName == "EMBED" || picture.nodeName == "embed") && isIE) {
    svgpicture = picture.getSVGDocument().getElementById("root");
    doc = picture.getSVGDocument();
  } else {
    picture.setAttribute("onmousemove","updateCoords"+(id.slice(id.length-1)-1)+"()");
//alert(picture.getAttribute("onmousemove")+"***");
    svgpicture = picture;
    doc = document;
  }
  xunitlength = svgpicture.getAttribute("xunitlength")-0;
  yunitlength = svgpicture.getAttribute("yunitlength")-0;
  xmin = svgpicture.getAttribute("xmin")-0;
  xmax = svgpicture.getAttribute("xmax")-0;
  ymin = svgpicture.getAttribute("ymin")-0;
  ymax = svgpicture.getAttribute("ymax")-0;
  origin = [svgpicture.getAttribute("ox")-0,svgpicture.getAttribute("oy")-0];
}


function updatePicture(obj) {
//alert(typeof obj)
  var src = document.getElementById((typeof obj=="string"?
              obj:"picture"+(obj+1)+"input")).value;
  xmin = null; xmax = null; ymin = null; ymax = null;
  xscl = null; xgrid = null; yscl = null; ygrid = null;
  initialized = false;
  switchTo((typeof obj=="string"?obj.slice(0,8):"picture"+(obj+1)));
  src = src.replace(/plot\(\x20*([^\"f\[][^\n\r]+?)\,/g,"plot\(\"$1\",");
  src = src.replace(/plot\(\x20*([^\"f\[][^\n\r]+)\)/g,"plot(\"$1\")");
  src = src.replace(/([0-9])([a-zA-Z])/g,"$1*$2");
  src = src.replace(/\)([\(0-9a-zA-Z])/g,"\)*$1");
//alert(src);
  try {
    with (Math) eval(src);
  } catch(err) {alert(err+"\n"+src)}
}


function showHideCode(obj) {
  var node = obj.nextSibling;
  while (node != null && node.nodeName != "BUTTON" &&

    node.nodeName != "button") node = node.nextSibling;
  if (node.style.display == "none") node.style.display = "";
  else node.style.display = "none";
  while (node != null && node.nodeName != "TEXTAREA" &&
    node.nodeName != "textarea") node = node.previousSibling;
  if (node.style.display == "none") node.style.display = "";
  else node.style.display = "none";
//  updatePicture(node.getAttribute("id"));
}


function hideCode() { //do nothing
}


function showcode() { //do nothing
}


function nobutton() { //do nothing
}


function setBorder(l,b,r,t) {
    if (t==null) {
        border = new Array(l,l,l,l);
    } else {
        border = new Array(l,b,r,t);
    }
}


function initPicture(x_min,x_max,y_min,y_max) {
 if (!initialized) {
  strokewidth = "1"; // pixel
  strokedasharray = null;
  stroke = "black"; // default line color
  fill = "none";    // default fill color
  fontstyle = "italic"; // default shape for text labels
  fontfamily = "times"; // default font
  fontsize = "16";      // default size
  fontweight = "normal";
  fontstroke = "black";  // default font outline color
  fontfill = "black";    // default font color
  fontbackground = "none";
  marker = "none";
  initialized = true;
  if (x_min!=null) xmin = x_min;
  if (x_max!=null) xmax = x_max;
  if (y_min!=null) ymin = y_min;
  if (y_max!=null) ymax = y_max;
  if (xmin==null) xmin = -5;
  if (xmax==null) xmax = 5;
 if (typeof xmin != "number" || typeof xmax != "number" || xmin >= xmax)
   alert("Picture requires at least two numbers: xmin < xmax");
 else if (y_max != null && (typeof y_min != "number" ||
          typeof y_max != "number" || y_min >= y_max))
   alert("initPicture(xmin,xmax,ymin,ymax) requires numbers ymin < ymax");
 else {
  //if (width==null)
  width = picture.getAttribute("width");
  //else picture.setAttribute("width",width);
  if (width==null || width=="") width=defaultwidth;
  //if (height==null)
  height = picture.getAttribute("height");
  //else picture.setAttribute("height",height);
  if (height==null || height=="") height=defaultheight;
  xunitlength = (width-border[0]-border[2])/(xmax-xmin);
  yunitlength = xunitlength;
//alert(xmin+" "+xmax+" "+ymin+" "+ymax)
  if (ymin==null) {
    origin = [-xmin*xunitlength+border[0],height/2];
    ymin = -(height-border[1]-border[3])/(2*yunitlength);
    ymax = -ymin;
  } else {
    if (ymax!=null) yunitlength = (height-border[1]-border[3])/(ymax-ymin);
    else ymax = (height-border[1]-border[3])/yunitlength + ymin;
    origin = [-xmin*xunitlength+border[0],-ymin*yunitlength+border[1]];
  }
  winxmin = Math.max(border[0]-5,0);
  winxmax = Math.min(width-border[2]+5,width);
  winymin = Math.max(border[3]-5,0);
  winymax = Math.min(height-border[1]+5,height);
//  if (true ||picture.nodeName == "EMBED" || picture.nodeName == "embed") {
    if (isIE) {
      svgpicture = picture.getSVGDocument().getElementById("root");
      while (svgpicture.childNodes.length()>5)
        svgpicture.removeChild(svgpicture.lastChild);
      svgpicture.setAttribute("width",width);
      svgpicture.setAttribute("height",height);
      doc = picture.getSVGDocument();
    } else {
      var qnode = document.createElementNS("http://www.w3.org/2000/svg","svg");
      qnode.setAttribute("id",picture.getAttribute("id"));
      qnode.setAttribute("style","display:inline; "+picture.getAttribute("style"));
      qnode.setAttribute("width",picture.getAttribute("width"));
      qnode.setAttribute("height",picture.getAttribute("height"));
      if (picture.parentNode!=null)
        picture.parentNode.replaceChild(qnode,picture);
      else
        svgpicture.parentNode.replaceChild(qnode,svgpicture);
      svgpicture = qnode;
      doc = document;
      pointerpos = doc.getElementById("pointerpos");
      if (pointerpos==null) {
        pointerpos = myCreateElementSVG("circle");
        pointerpos.setAttribute("id","pointerpos");
        pointerpos.setAttribute("cx",0);
        pointerpos.setAttribute("cy",0);
        pointerpos.setAttribute("r",0.5);
        pointerpos.setAttribute("fill","red");
        svgpicture.appendChild(pointerpos);
      }
    }
//  } else {
//    svgpicture = picture;
//    doc = document;
//  }
  svgpicture.setAttribute("xunitlength",xunitlength);
  svgpicture.setAttribute("yunitlength",yunitlength);
  svgpicture.setAttribute("xmin",xmin);
  svgpicture.setAttribute("xmax",xmax);
  svgpicture.setAttribute("ymin",ymin);
  svgpicture.setAttribute("ymax",ymax);
  svgpicture.setAttribute("ox",origin[0]);
  svgpicture.setAttribute("oy",origin[1]);
  var node = myCreateElementSVG("rect");
  node.setAttribute("x","0");
  node.setAttribute("y","0");
  node.setAttribute("width",width);
  node.setAttribute("height",height);
  node.setAttribute("style","stroke-width:1;fill:white");
  svgpicture.appendChild(node);
  if (!isIE && picture.getAttribute("onmousemove")!=null) {
    svgpicture.addEventListener("mousemove", mousemove_listener, true);
    var st = picture.getAttribute("onmousemove");
    svgpicture.addEventListener("mousemove", eval(st.slice(0,st.indexOf("("))), true);
    node = myCreateElementSVG("polyline");
    node.setAttribute("points","0,0 "+width+",0");
    node.setAttribute("style","stroke:white; stroke-width:3");
    node.addEventListener("mousemove", top_listener, true);
    svgpicture.appendChild(node);
    node = myCreateElementSVG("polyline");
    node.setAttribute("points","0,"+height+" "+width+","+height);
    node.setAttribute("style","stroke:white; stroke-width:3");
    node.addEventListener("mousemove", bottom_listener, true);
    svgpicture.appendChild(node);
    node = myCreateElementSVG("polyline");
    node.setAttribute("points","0,0 0,"+height);
    node.setAttribute("style","stroke:white; stroke-width:3");
    node.addEventListener("mousemove", left_listener, true);
    svgpicture.appendChild(node);
    node = myCreateElementSVG("polyline");
    node.setAttribute("points",(width-1)+",0 "+(width-1)+","+height);
    node.setAttribute("style","stroke:white; stroke-width:3");
    node.addEventListener("mousemove", right_listener, true);
    svgpicture.appendChild(node);
  }
  border = defaultborder;
 }
 }
}


function line(p,q,id) { // segment connecting points p,q (coordinates in units)
  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("path");
    node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  node.setAttribute("d","M"+(p[0]*xunitlength+origin[0])+","+
    (height-p[1]*yunitlength-origin[1])+" "+
    (q[0]*xunitlength+origin[0])+","+(height-q[1]*yunitlength-origin[1]));
  node.setAttribute("stroke-width", strokewidth);
  if (strokedasharray!=null)
    node.setAttribute("stroke-dasharray", strokedasharray);
  node.setAttribute("stroke", stroke);
  node.setAttribute("fill", fill);
  if (marker=="dot" || marker=="arrowdot") {
    ASdot(p,4,markerstroke,markerfill);
    if (marker=="arrowdot") arrowhead(p,q);
    ASdot(q,4,markerstroke,markerfill);
  } else if (marker=="arrow") arrowhead(p,q);
}


function path(plist,id,c) {
  if (c==null) c="";
  var node, st, i;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("path");
    node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  if (typeof plist == "string") st = plist;
  else {
    st = "M";
    st += (plist[0][0]*xunitlength+origin[0])+","+
          (height-plist[0][1]*yunitlength-origin[1])+" "+c;
    for (i=1; i<plist.length; i++)
      st += (plist[i][0]*xunitlength+origin[0])+","+
            (height-plist[i][1]*yunitlength-origin[1])+" ";
  }
  node.setAttribute("d", st);
  node.setAttribute("stroke-width", strokewidth);
  if (strokedasharray!=null)
    node.setAttribute("stroke-dasharray", strokedasharray);
  node.setAttribute("stroke", stroke);
  node.setAttribute("fill", fill);
  if (marker=="dot" || marker=="arrowdot")
    for (i=0; i<plist.length; i++)
      if (c!="C" && c!="T" || i!=1 && i!=2)
        ASdot(plist[i],4,markerstroke,markerfill);
}


function curve(plist,id) {
  path(plist,id,"T");
}


function circle(center,radius,id) { // coordinates in units
  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("circle");
    node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }

  node.setAttribute("cx",center[0]*xunitlength+origin[0]);
  node.setAttribute("cy",height-center[1]*yunitlength-origin[1]);
  node.setAttribute("r",radius*xunitlength);
  node.setAttribute("stroke-width", strokewidth);
  node.setAttribute("stroke", stroke);
  node.setAttribute("fill", fill);
}


function loop(p,d,id) {
// d is a direction vector e.g. [1,0] means loop starts in that direction
  if (d==null) d=[1,0];
  path([p,[p[0]+d[0],p[1]+d[1]],[p[0]-d[1],p[1]+d[0]],p],id,"C");
  if (marker=="arrow" || marker=="arrowdot")
    arrowhead([p[0]+Math.cos(1.4)*d[0]-Math.sin(1.4)*d[1],
               p[1]+Math.sin(1.4)*d[0]+Math.cos(1.4)*d[1]],p);
}


function arc(start,end,radius,id) { // coordinates in units
  var node, v;
//alert([fill, stroke, origin, xunitlength, yunitlength, height])
  if (id!=null) node = doc.getElementById(id);
  if (radius==null) {
    v=[end[0]-start[0],end[1]-start[1]];
    radius = Math.sqrt(v[0]*v[0]+v[1]*v[1]);
  }
  if (node==null) {
    node = myCreateElementSVG("path");
    node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  node.setAttribute("d","M"+(start[0]*xunitlength+origin[0])+","+
    (height-start[1]*yunitlength-origin[1])+" A"+radius*xunitlength+","+
     radius*yunitlength+" 0 0,0 "+(end[0]*xunitlength+origin[0])+","+
    (height-end[1]*yunitlength-origin[1]));
  node.setAttribute("stroke-width", strokewidth);
  node.setAttribute("stroke", stroke);
  node.setAttribute("fill", fill);
  if (marker=="arrow" || marker=="arrowdot") {
    u = [(end[1]-start[1])/4,(start[0]-end[0])/4];
    v = [(end[0]-start[0])/2,(end[1]-start[1])/2];
//alert([u,v])
    v = [start[0]+v[0]+u[0],start[1]+v[1]+u[1]];
  } else v=[start[0],start[1]];
  if (marker=="dot" || marker=="arrowdot") {
    ASdot(start,4,markerstroke,markerfill);
    if (marker=="arrowdot") arrowhead(v,end);
    ASdot(end,4,markerstroke,markerfill);
  } else if (marker=="arrow") arrowhead(v,end);
}


function ellipse(center,rx,ry,id) { // coordinates in units

  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("ellipse");
    node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  node.setAttribute("cx",center[0]*xunitlength+origin[0]);
  node.setAttribute("cy",height-center[1]*yunitlength-origin[1]);
  node.setAttribute("rx",rx*xunitlength);
  node.setAttribute("ry",ry*yunitlength);
  node.setAttribute("stroke-width", strokewidth);
  node.setAttribute("stroke", stroke);
  node.setAttribute("fill", fill);
}


function rect(p,q,id,rx,ry) { // opposite corners in units, rounded by radii
  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("rect");
    node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  node.setAttribute("x",p[0]*xunitlength+origin[0]);
  node.setAttribute("y",height-q[1]*yunitlength-origin[1]);
  node.setAttribute("width",(q[0]-p[0])*xunitlength);
  node.setAttribute("height",(q[1]-p[1])*yunitlength);
  if (rx!=null) node.setAttribute("rx",rx*xunitlength);
  if (ry!=null) node.setAttribute("ry",ry*yunitlength);
  node.setAttribute("stroke-width", strokewidth);
  node.setAttribute("stroke", stroke);
  node.setAttribute("fill", fill);
}

function text(p,st,pos,angle) {
    p[0] = p[0]*xunitlength+origin[0];
    p[1] = p[1]*yunitlength+origin[1];
    textabs(p,st,pos,angle);
}

function textabs(p,st,pos,angle,id,fontsty) {
  if (angle==null) {
      angle = 0;
  } else {
      angle = (360 - angle)%360;
  }
  var textanchor = "middle";
  var dx=0; var dy=0;
  if (angle==270) {
      var dy = 0; var dx = fontsize/3;
      if (pos!=null) {
        if (pos.match(/left/)) {dx = -fontsize/2;}
        if (pos.match(/right/)) {dx = fontsize-0;}
        if (pos.match(/above/)) {
          textanchor = "start";
          dy = -fontsize/2;
        }
        if (pos.match(/below/)) {
          textanchor = "end";
          dy = fontsize/2;
        }
      }
  }
  if (angle==90) {
      var dy = 0; var dx = -fontsize/3;
      if (pos!=null) {
        if (pos.match(/left/)) dx = -fontsize-0;
        if (pos.match(/right/)) dx = fontsize/2;
        if (pos.match(/above/)) {
          textanchor = "end";
          dy = -fontsize/2;
        }
        if (pos.match(/below/)) {
          textanchor = "start";
          dy = fontsize/2;
        }
      }
  }
  if (angle==0) {
      var dx = 0; var dy = fontsize/3;
      if (pos!=null) {
        if (pos.match(/above/)) { dy = -fontsize/3; }
        if (pos.match(/below/)) { dy = fontsize-0; }
        if (pos.match(/right/)) {
          textanchor = "start";
          dx = fontsize/3;
        }
        if (pos.match(/left/)) {
          textanchor = "end";
          dx = -fontsize/3;
        }
      }
  }

  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("text");
    node.setAttribute("id", id);
    svgpicture.appendChild(node);
    node.appendChild(doc.createTextNode(st));
  }
  node.lastChild.nodeValue = st;
  node.setAttribute("x",p[0]+dx);
  node.setAttribute("y",height-p[1]+dy);
  if (angle != 0) {
      node.setAttribute("transform","rotate("+angle+" "+(p[0]+dx)+" "+(height-p[1]+dy)+")");
  }
  node.setAttribute("font-style",(fontsty!=null?fontsty:fontstyle));
  node.setAttribute("font-family",fontfamily);
  node.setAttribute("font-size",fontsize);
  node.setAttribute("font-weight",fontweight);
  node.setAttribute("text-anchor",textanchor);
  if (fontstroke!="none") node.setAttribute("stroke",fontstroke);
  if (fontfill!="none") node.setAttribute("fill",fontfill);
  node.setAttribute("stroke-width","0px");
  if (fontbackground!="none") {
      var bgnode = myCreateElementSVG("rect");
      var bb = node.getBBox();
      bgnode.setAttribute("fill",fontbackground);
      bgnode.setAttribute("stroke-width","0px");
      bgnode.setAttribute("x",bb.x-2);
      bgnode.setAttribute("y",bb.y-2);
      bgnode.setAttribute("width",bb.width+4);
      bgnode.setAttribute("height",bb.height+4);
      if (angle != 0) {
           bgnode.setAttribute("transform","rotate("+angle+" "+(p[0]+dx)+" "+(height-p[1]+dy)+")");
      }
      svgpicture.insertBefore(bgnode,node);

  }
  return p;
}


function ASdot(center,radius,s,f) { // coordinates in units, radius in pixel
  if (s==null) s = stroke; if (f==null) f = fill;
  var node = myCreateElementSVG("circle");
  node.setAttribute("cx",center[0]*xunitlength+origin[0]);
  node.setAttribute("cy",height-center[1]*yunitlength-origin[1]);
  node.setAttribute("r",radius);
  node.setAttribute("stroke-width", strokewidth);
  node.setAttribute("stroke", s);
  node.setAttribute("fill", f);
  svgpicture.appendChild(node);
}


function dot(center, typ, label, pos, id) {
  var node;
  var cx = center[0]*xunitlength+origin[0];
  var cy = height-center[1]*yunitlength-origin[1];
  if (id!=null) node = doc.getElementById(id);
  if (typ=="+" || typ=="-" || typ=="|") {
    if (node==null) {
      node = myCreateElementSVG("path");
      node.setAttribute("id", id);
      svgpicture.appendChild(node);
    }
    if (typ=="+") {
      node.setAttribute("d",
        " M "+(cx-ticklength)+" "+cy+" L "+(cx+ticklength)+" "+cy+
        " M "+cx+" "+(cy-ticklength)+" L "+cx+" "+(cy+ticklength));
      node.setAttribute("stroke-width", .5);
      node.setAttribute("stroke", axesstroke);
    } else {
      if (typ=="-") node.setAttribute("d",
        " M "+(cx-ticklength)+" "+cy+" L "+(cx+ticklength)+" "+cy);
      else node.setAttribute("d",
        " M "+cx+" "+(cy-ticklength)+" L "+cx+" "+(cy+ticklength));
      node.setAttribute("stroke-width", strokewidth);
      node.setAttribute("stroke", stroke);
    }
  } else {
    if (node==null) {
      node = myCreateElementSVG("circle");
      node.setAttribute("id", id);
      svgpicture.appendChild(node);
    }
    node.setAttribute("cx",cx);
    node.setAttribute("cy",cy);
    node.setAttribute("r",dotradius);
    node.setAttribute("stroke-width", strokewidth);
    node.setAttribute("stroke", stroke);
    node.setAttribute("fill", (typ=="open"?"white":stroke));
  }
  if (label!=null)
    text(center,label,(pos==null?"below":pos),(id==null?id:id+"label"))
}


function arrowhead(p,q) { // draw arrowhead at q (in units)
  var up;
  var v = [p[0]*xunitlength+origin[0],height-p[1]*yunitlength-origin[1]];
  var w = [q[0]*xunitlength+origin[0],height-q[1]*yunitlength-origin[1]];
  var u = [w[0]-v[0],w[1]-v[1]];
  var d = Math.sqrt(u[0]*u[0]+u[1]*u[1]);
  if (d > 0.00000001) {
    u = [u[0]/d, u[1]/d];
    up = [-u[1],u[0]];
    var node = myCreateElementSVG("path");
    node.setAttribute("d","M "+(w[0]-15*u[0]-4*up[0])+" "+
      (w[1]-15*u[1]-4*up[1])+" L "+(w[0]-3*u[0])+" "+(w[1]-3*u[1])+" L "+
      (w[0]-15*u[0]+4*up[0])+" "+(w[1]-15*u[1]+4*up[1])+" z");
    node.setAttribute("stroke-width", markerstrokewidth);
    node.setAttribute("stroke", stroke); /*was markerstroke*/
    node.setAttribute("fill", stroke); /*was arrowfill*/
    svgpicture.appendChild(node);
  }
}


function chopZ(st) {
  var k = st.indexOf(".");
  if (k==-1) return st;
  for (var i=st.length-1; i>k && st.charAt(i)=="0"; i--);
  if (i==k) i--;
  return st.slice(0,i+1);
}


function grid(dx,dy) { // for backward compatibility
  axes(dx,dy,null,dx,dy)
}


function noaxes() {
  if (!initialized) initPicture();
}


function axes(dx,dy,labels,gdx,gdy,dox,doy) {
//xscl=x is equivalent to xtick=x; xgrid=x; labels=true;
  var x, y, ldx, ldy, lx, ly, lxp, lyp, pnode, st;
  if (!initialized) initPicture();
  if (typeof dx=="string") { labels = dx; dx = null; }
  if (typeof dy=="string") { gdx = dy; dy = null; }
  if (xscl!=null) {dx = xscl; gdx = xscl; labels = dx}
  if (yscl!=null) {dy = yscl; gdy = yscl}
  if (xtick!=null) {dx = xtick}
  if (ytick!=null) {dy = ytick}
  if (dox==null) {dox = true;}
  if (doy==null) {doy = true;}
  if (dox=="off" || dox==0) { dox = false;} else {dox = true;}
  if (doy=="off" || doy==0) { doy = false;} else {doy = true;}
//alert(null)
  dx = (dx==null?xunitlength:dx*xunitlength);
  dy = (dy==null?dx:dy*yunitlength);
  fontsize = Math.floor(Math.min(dx/1.5,dy/1.5,16));//alert(fontsize)
  ticklength = fontsize/4;
  if (xgrid!=null) gdx = xgrid;
  if (ygrid!=null) gdy = ygrid;
  if (gdx!=null) {
    gdx = (typeof gdx=="string"?dx:gdx*xunitlength);
    gdy = (gdy==null?dy:gdy*yunitlength);
    pnode = myCreateElementSVG("path");
    st="";
    if (dox && gdx>0) {
        for (x = origin[0]; x<=winxmax; x = x+gdx)
          if (x>=winxmin) st += " M"+x+","+winymin+" "+x+","+winymax;
        for (x = origin[0]-gdx; x>=winxmin; x = x-gdx)
          if (x<=winxmax) st += " M"+x+","+winymin+" "+x+","+winymax;
    }
    if (doy && gdy>0) {
        for (y = height-origin[1]; y<=winymax; y = y+gdy)
          if (y>=winymin) st += " M"+winxmin+","+y+" "+winxmax+","+y;
        for (y = height-origin[1]-gdy; y>=winymin; y = y-gdy)
          if (y<=winymax) st += " M"+winxmin+","+y+" "+winxmax+","+y;
    }
    pnode.setAttribute("d",st);
    pnode.setAttribute("stroke-width", .5);
    pnode.setAttribute("stroke", gridstroke);
    pnode.setAttribute("fill", fill);
    svgpicture.appendChild(pnode);
  }
  pnode = myCreateElementSVG("path");
  if (dox) {
      st="M"+winxmin+","+(height-origin[1])+" "+winxmax+","+
    (height-origin[1]);
  }
  if (doy) {
      st += " M"+origin[0]+","+winymin+" "+origin[0]+","+winymax;
  }
  if (dox) {
      for (x = origin[0]; x<winxmax; x = x+dx)
        if (x>=winymin) st += " M"+x+","+(height-origin[1]+ticklength)+" "+x+","+
           (height-origin[1]-ticklength);
      for (x = origin[0]-dx; x>winxmin; x = x-dx)
       if (x<=winxmax) st += " M"+x+","+(height-origin[1]+ticklength)+" "+x+","+
           (height-origin[1]-ticklength);
  }
  if (doy) {
      for (y = height-origin[1]; y<winymax; y = y+dy)
        if (y>=winymin) st += " M"+(origin[0]+ticklength)+","+y+" "+(origin[0]-ticklength)+","+y;
      for (y = height-origin[1]-dy; y>winymin; y = y-dy)
        if (y<=winymax) st += " M"+(origin[0]+ticklength)+","+y+" "+(origin[0]-ticklength)+","+y;
  }
  if (labels!=null) with (Math) {
    ldx = dx/xunitlength;
    ldy = dy/yunitlength;
    lx = (xmin>0 || xmax<0?xmin:0);
    ly = (ymin>0 || ymax<0?ymin:0);
    lxp = (ly==0?"below":"above");
    lyp = (lx==0?"left":"right");
    var ddx = floor(1.1-log(ldx)/log(10))+1;
    var ddy = floor(1.1-log(ldy)/log(10))+1;
    if (ddy<0) { ddy = 0;}
    if (ddx<0) { ddx = 0;}
    if (dox) {
        for (x = (doy?ldx:0); x<=xmax; x = x+ldx)
          if (x>=xmin) text([x,ly],chopZ(x.toFixed(ddx)),lxp);
        for (x = -ldx; xmin<=x; x = x-ldx)
          if (x<=xmax) text([x,ly],chopZ(x.toFixed(ddx)),lxp);
    }
    if (doy) {
        for (y = (dox?ldy:0); y<=ymax; y = y+ldy)
          if (y>=ymin) text([lx,y],chopZ(y.toFixed(ddy)),lyp);
        for (y = -ldy; ymin<=y; y = y-ldy)
          if (y<=ymax) text([lx,y],chopZ(y.toFixed(ddy)),lyp);
    }
  }
  pnode.setAttribute("d",st);
  pnode.setAttribute("stroke-width", .5);
  pnode.setAttribute("stroke", axesstroke);
  pnode.setAttribute("fill", fill);
  svgpicture.appendChild(pnode);
}

function safepow(base,power) {
    if (base<0 && Math.floor(power)!=power) {
        for (var j=3; j<50; j+=2) {
            if (Math.abs(Math.round(j*power)-(j*power))<.000001) {
                if (Math.round(j*power)%2==0) {
                    return Math.pow(Math.abs(base),power);
                } else {
                    return -1*Math.pow(Math.abs(base),power);
                }
            }
        }
        return sqrt(-1);
    } else {
        return Math.pow(base,power);
    }
}

function nthroot(n,base) {
    return safepow(base,1/n);
}
function nthlogten(n,v) {
    return ((Math.log(v))/(Math.log(n)));
}
function matchtolower(match) {
    return match.toLowerCase();
}

function mathjs(st,varlist) {
  //translate a math formula to js function notation
  // a^b --> pow(a,b)
  // na --> n*a
  // (...)d --> (...)*d
  // n! --> factorial(n)
  // sin^-1 --> arcsin etc.
  //while ^ in string, find term on left and right
  //slice and concat new formula string
  //parenthesizes the function variables
  st = st.replace("[","(");
  st = st.replace("]",")");
  st = st.replace(/arc(sin|cos|tan)/g,"a#r#c $1");
  if (varlist != null) {
      var reg = new RegExp("(sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs)[\(]","g");
      st = st.replace(reg,"$1#(");
      var reg = new RegExp("("+varlist+")("+varlist+")$","g");
      st = st.replace(reg,"($1)($2)");
      var reg = new RegExp("("+varlist+")(a#|sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs)","g");
      st = st.replace(reg,"($1)$2");
      var reg = new RegExp("("+varlist+")("+varlist+")([^a-df-zA-Z\(#])","g"); // 6/1/09 readded \( for f(350/x)
      st = st.replace(reg,"($1)($2)$3"); //get xy3
      //var reg = new RegExp("("+varlist+")("+varlist+")(\w*[^\(#])","g");
      //st = st.replace(reg,"($1)($2)$3"); //get xysin
      var reg = new RegExp("([^a-df-zA-Z#])("+varlist+")([^a-df-zA-Z#])","g");
      st = st.replace(reg,"$1($2)$3");
      var reg = new RegExp("^("+varlist+")([^a-df-zA-Z])","g");
      st = st.replace(reg,"($1)$2");
      var reg = new RegExp("([^a-df-zA-Z])("+varlist+")$","g");
      st = st.replace(reg,"$1($2)");
  }
  st = st.replace(/#/g,"");
  st = st.replace(/a#r#c\s+(sin|cos|tan)/g,"arc$1");
  st = st.replace(/\s/g,"");
  st = st.replace(/(Sin|Cos|Tan|Sec|Csc|Cot|Arc|Abs|Log|Ln)/g, matchtolower);
  st = st.replace(/log_(\d+)\(/,"nthlog($1,");
  st = st.replace(/log/g,"logten");

  if (st.indexOf("^-1")!=-1) {
    st = st.replace(/sin\^-1/g,"arcsin");
    st = st.replace(/cos\^-1/g,"arccos");
    st = st.replace(/tan\^-1/g,"arctan");
    st = st.replace(/sec\^-1/g,"arcsec");
    st = st.replace(/csc\^-1/g,"arccsc");
    st = st.replace(/cot\^-1/g,"arccot");
    st = st.replace(/sinh\^-1/g,"arcsinh");
    st = st.replace(/cosh\^-1/g,"arccosh");
    st = st.replace(/tanh\^-1/g,"arctanh");
    st = st.replace(/sech\^-1/g,"arcsech");
    st = st.replace(/csch\^-1/g,"arccsch");
    st = st.replace(/coth\^-1/g,"arccoth");
  }

  st = st.replace(/root\((\d+)\)\(/,"nthroot($1,");
  //st = st.replace(/E/g,"(EE)");
  st = st.replace(/([0-9])E([\-0-9])/g,"$1(EE)$2");

  st = st.replace(/^e$/g,"(E)");
  st = st.replace(/pi/g,"(pi)");
  st = st.replace(/^e([^a-zA-Z])/g,"(E)$1");
  st = st.replace(/([^a-zA-Z])e$/g,"$1(E)");

  st = st.replace(/([^a-zA-Z])e([^a-zA-Z])/g,"$1(E)$2");
  st = st.replace(/([0-9])([\(a-zA-Z])/g,"$1*$2");
  st = st.replace(/(!)([0-9\(])/g,"$1*$2");
  //want to keep scientific notation
  st= st.replace(/([0-9])\*\(EE\)([\-0-9])/,"$1e$2");


  st = st.replace(/\)([\(0-9a-zA-Z])/g,"\)*$1");

  var i,j,k, ch, nested;
  while ((i=st.indexOf("^"))!=-1) {

    //find left argument
    if (i==0) return "Error: missing argument";
    j = i-1;
    ch = st.charAt(j);
    if (ch>="0" && ch<="9") {// look for (decimal) number
      j--;
      while (j>=0 && (ch=st.charAt(j))>="0" && ch<="9") j--;
      if (ch==".") {
        j--;
        while (j>=0 && (ch=st.charAt(j))>="0" && ch<="9") j--;
      }
    } else if (ch==")") {// look for matching opening bracket and function name
      nested = 1;
      j--;
      while (j>=0 && nested>0) {
        ch = st.charAt(j);
        if (ch=="(") nested--;
        else if (ch==")") nested++;
        j--;
      }
      while (j>=0 && (ch=st.charAt(j))>="a" && ch<="z" || ch>="A" && ch<="Z")
        j--;
    } else if (ch>="a" && ch<="z" || ch>="A" && ch<="Z") {// look for variable
      j--;
      while (j>=0 && (ch=st.charAt(j))>="a" && ch<="z" || ch>="A" && ch<="Z")
        j--;
    } else {
      return "Error: incorrect syntax in "+st+" at position "+j;
    }
    //find right argument
    if (i==st.length-1) return "Error: missing argument";
    k = i+1;
    ch = st.charAt(k);
    nch = st.charAt(k+1);
    if (ch>="0" && ch<="9" || (ch=="-" && nch!="(") || ch==".") {// look for signed (decimal) number
      k++;
      while (k<st.length && (ch=st.charAt(k))>="0" && ch<="9") k++;
      if (ch==".") {
        k++;
        while (k<st.length && (ch=st.charAt(k))>="0" && ch<="9") k++;
      }
    } else if (ch=="(" || (ch=="-" && nch=="(")) {// look for matching closing bracket and function name
      if (ch=="-") { k++;}
      nested = 1;
      k++;
      while (k<st.length && nested>0) {
        ch = st.charAt(k);
        if (ch=="(") nested++;
        else if (ch==")") nested--;
        k++;
      }
    } else if (ch>="a" && ch<="z" || ch>="A" && ch<="Z") {// look for variable
      k++;
      while (k<st.length && (ch=st.charAt(k))>="a" && ch<="z" ||
               ch>="A" && ch<="Z") k++;
      if (ch=='(' && st.slice(i+1,k).match(/^(sin|cos|tan|sec|csc|cot|logten|log|ln|exp|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|sqrt|abs|nthroot)$/)) {
          nested = 1;
          k++;
          while (k<st.length && nested>0) {
        ch = st.charAt(k);
        if (ch=="(") nested++;
        else if (ch==")") nested--;
        k++;
          }
      }
    } else {
      return "Error: incorrect syntax in "+st+" at position "+k;
    }
    st = st.slice(0,j+1)+"safepow("+st.slice(j+1,i)+","+st.slice(i+1,k)+")"+
           st.slice(k);
  }
  while ((i=st.indexOf("!"))!=-1) {
    //find left argument
    if (i==0) return "Error: missing argument";
    j = i-1;
    ch = st.charAt(j);
    if (ch>="0" && ch<="9") {// look for (decimal) number
      j--;
      while (j>=0 && (ch=st.charAt(j))>="0" && ch<="9") j--;
      if (ch==".") {
        j--;
        while (j>=0 && (ch=st.charAt(j))>="0" && ch<="9") j--;
      }
    } else if (ch==")") {// look for matching opening bracket and function name
      nested = 1;
      j--;
      while (j>=0 && nested>0) {
        ch = st.charAt(j);
        if (ch=="(") nested--;
        else if (ch==")") nested++;
        j--;
      }
      while (j>=0 && (ch=st.charAt(j))>="a" && ch<="z" || ch>="A" && ch<="Z")
        j--;
    } else if (ch>="a" && ch<="z" || ch>="A" && ch<="Z") {// look for variable
      j--;
      while (j>=0 && (ch=st.charAt(j))>="a" && ch<="z" || ch>="A" && ch<="Z")
        j--;
    } else {
      return "Error: incorrect syntax in "+st+" at position "+j;
    }
    st = st.slice(0,j+1)+"factorial("+st.slice(j+1,i)+")"+st.slice(i+1);
  }
  return st;
}


function slopefield(fun,dx,dy) {
  var g = fun;
  if (typeof fun=="string")
    eval("g = function(x,y){ with(Math) return "+mathjs(fun)+" }");
  var gxy,x,y,u,v,dz;
  if (dx==null) dx=1;
  if (dy==null) dy=1;
  dz = Math.sqrt(dx*dx+dy*dy)/6;
  var x_min = Math.ceil(xmin/dx);
  var y_min = Math.ceil(ymin/dy);
  for (x = x_min; x <= xmax; x += dx)
    for (y = y_min; y <= ymax; y += dy) {
      gxy = g(x,y);
      if (!isNaN(gxy)) {
        if (Math.abs(gxy)=="Infinity") {u = 0; v = dz;}
        else {u = dz/Math.sqrt(1+gxy*gxy); v = gxy*u;}
        line([x-u,y-v],[x+u,y+v]);
      }
    }
}


//ASCIIsvgAddon.js dumped here
function drawPictures() {
    drawPics()
}

//ShortScript format:
//xmin,xmax,ymin,ymax,xscl,yscl,labels,xgscl,ygscl,width,height plotcommands(see blow)
//plotcommands: type,eq1,eq2,startmaker,endmarker,xmin,xmax,color,strokewidth,strokedash
function parseShortScript(sscript,gw,gh) {

    // Added by Ivan Tcholakov, 07-JAN-2011.
    if (typeof picture == 'undefined') {
        return;
    }
    //

    if (sscript == null) {
        initialized = false;
        sscript = picture.sscr;
    }

    var sa= sscript.split(",");

    if (gw && gh) {
        sa[9] = gw;
        sa[10] = gh;
        sscript = sa.join(",");
        picture.setAttribute("sscr", sscript);
    }
    picture.setAttribute("width", sa[9]);
    picture.setAttribute("height", sa[10]);
    picture.style.width = sa[9] + "px";
    picture.style.height = sa[10] + "px";

    if (sa.length > 10) {
        commands = 'setBorder(5);';
        commands += 'width=' +sa[9] + '; height=' +sa[10] + ';';
        commands += 'initPicture(' + sa[0] +','+ sa[1] +','+ sa[2] +','+ sa[3] + ');';
        commands += 'axes(' + sa[4] +','+ sa[5] +','+ sa[6] +','+ sa[7] +','+ sa[8]+ ');';

        var inx = 11;
        var eqnlist = 'Graphs: ';

        while (sa.length > inx+9) {
           commands += 'stroke="' + sa[inx+7] + '";';
           commands += 'strokewidth="' + sa[inx+8] + '";'
           //commands += 'strokedasharray="' + sa[inx+9] + '";'
           if (sa[inx+9] != "") {
               commands += 'strokedasharray="' + sa[inx+9].replace(/\s+/g,',') + '";';
           }
           if (sa[inx]=="slope") {
               eqnlist += "dy/dx="+sa[inx+1] + "; ";
            commands += 'slopefield("' + sa[inx+1] + '",' + sa[inx+2] + ',' + sa[inx+2] + ');';
           } else {
            if (sa[inx]=="func") {
                eqnlist += "y="+sa[inx+1] + "; ";
                eqn = '"' + sa[inx+1] + '"';
            } else if (sa[inx] == "polar") {
                eqnlist += "r="+sa[inx+1] + "; ";
                eqn = '["cos(t)*(' + sa[inx+1] + ')","sin(t)*(' + sa[inx+1] + ')"]';
            } else if (sa[inx] == "param") {
                eqnlist += "[x,y]=["+sa[inx+1] + "," + sa[inx+2] + "]; ";
                eqn = '["' + sa[inx+1] + '","'+ sa[inx+2] + '"]';
            }


            if (typeof eval(sa[inx+5]) == "number") {
        //	if ((sa[inx+5]!='null')&&(sa[inx+5].length>0)) {
                //commands += 'myplot(' + eqn +',"' + sa[inx+3] +  '","' + sa[inx+4]+'",' + sa[inx+5] + ',' + sa[inx+6]  +');';
                commands += 'plot(' + eqn +',' + sa[inx+5] + ',' + sa[inx+6] +',null,null,' + sa[inx+3] +  ',' + sa[inx+4] +');';

            } else {
                commands += 'plot(' + eqn +',null,null,null,null,' + sa[inx+3] +  ',' + sa[inx+4]+');';
            }
           }
           inx += 10;
        }

        try {
            eval(commands);
        } catch (e) {
            setTimeout(function() {parseShortScript(sscript,gw,gh)},100);
            //alert("Graph not ready");
        }

        picture.setAttribute("alt",eqnlist);
        //picture.setAttribute("width", sa[9]);
        //picture.setAttribute("height", sa[9]);

        return commands;
    }
}




function drawPics() {
  var index, nd;
  pictures = document.getElementsByTagName("embed");
 // might be needed if setTimeout on parseShortScript isn't working
  if (!ASnoSVG) {
       try {
          for (var i = 0; i < pictures.length; i++) {
              if (pictures[i].getAttribute("sscr")!='' || pictures[i].getAttribute("script")!='') {
                  if (pictures[i].getSVGDocument().getElementById("root") == null) {
                    setTimeout(drawPics,100);
                    return;
                  }
              }
          }
      } catch (e) {
          setTimeout(drawPics,100);
          return;
      }
 }
  var len = pictures.length;


  for (index = 0; index < len; index++) {
      picture = ((!ASnoSVG && isIE) ? pictures[index] : pictures[0]);
   // for (index = len-1; index >=0; index--) {
    //  picture = pictures[index];

      if (!ASnoSVG) {
          initialized = false;
          var sscr = picture.getAttribute("sscr");
          if ((sscr != null) && (sscr != "")) { //sscr from editor
              try {
                  parseShortScript(sscr);
              } catch (e) {}
          } else {
              src = picture.getAttribute("script"); //script from showplot
              if ((src!=null) && (src != "")) {
                  try {
                      with (Math) eval(src);
                  } catch(err) {alert(err+"\n"+src)}
              }
          }
      } else {
          if (picture.getAttribute("sscr")!='') {
              n = document.createElement('img');
              n.setAttribute("style",picture.getAttribute("style"));
              n.setAttribute("src",AScgiloc+'?sscr='+encodeURIComponent(picture.getAttribute("sscr")));
              pn = picture.parentNode;
              pn.replaceChild(n,picture);
          }

      }

  }
}

//modified by David Lippman from original in AsciiSVG.js by Peter Jipsen
//added min/max type:  0:nothing, 1:arrow, 2:open dot, 3:closed dot
function plot(fun,x_min,x_max,points,id,min_type,max_type) {
  var pth = [];
  var f = function(x) { return x }, g = fun;
  var name = null;
  if (typeof fun=="string")
    eval("g = function(x){ with(Math) return "+mathjs(fun)+" }");
  else if (typeof fun=="object") {
    eval("f = function(t){ with(Math) return "+mathjs(fun[0])+" }");
    eval("g = function(t){ with(Math) return "+mathjs(fun[1])+" }");
  }
  if (typeof x_min=="string") { name = x_min; x_min = xmin }
  else name = id;
  var min = (x_min==null?xmin:x_min);
  var max = (x_max==null?xmax:x_max);
  if (max <= min) { return null;}
  //else {
  var inc = max-min-0.000001*(max-min);
  inc = (points==null?inc/200:inc/points);
  var gt;
//alert(typeof g(min))
  for (var t = min; t <= max; t += inc) {
    gt = g(t);
    if (!(isNaN(gt)||Math.abs(gt)=="Infinity")) pth[pth.length] = [f(t), gt];
  }
  path(pth,name);
  if (min_type == 1) {
    arrowhead(pth[1],pth[0]);
  } else if (min_type == 2) {
    dot(pth[0], "open");
  } else if (min_type == 3) {
    dot(pth[0], "closed");
  }
  if (max_type == 1) {
    arrowhead(pth[pth.length-2],pth[pth.length-1]);
  } else if (max_type == 2) {
    dot(pth[pth.length-1], "open");
  } else if (max_type == 3) {
    dot(pth[pth.length-1], "closed");
  }

  return p;
  //}
}

//end ASCIIsvgAddon.js dump

function updateCoords(ind) {
  switchTo("picture"+(ind+1));
  var gx=getX(), gy=getY();
  if ((xmax-gx)*xunitlength > 6*fontsize || (gy-ymin)*yunitlength > 2*fontsize)
    text([xmax,ymin],"("+gx.toFixed(2)+", "+gy.toFixed(2)+")",
         "aboveleft","AScoord"+ind,"");
  else text([xmax,ymin]," ","aboveleft","AScoord"+ind,"");
}


function updateCoords0() {updateCoords(0)}
function updateCoords1() {updateCoords(1)}
function updateCoords2() {updateCoords(2)}
function updateCoords3() {updateCoords(3)}
function updateCoords4() {updateCoords(4)}
function updateCoords5() {updateCoords(5)}
function updateCoords6() {updateCoords(6)}
function updateCoords7() {updateCoords(7)}
function updateCoords8() {updateCoords(8)}
function updateCoords9() {updateCoords(9)}
ASfn = [function() {updatePicture(0)},
  function() {updatePicture(1)},
  function() {updatePicture(2)},
  function() {updatePicture(3)},
  function() {updatePicture(4)},
  function() {updatePicture(5)},
  function() {updatePicture(6)},
  function() {updatePicture(7)},

  function() {updatePicture(8)},
  function() {updatePicture(9)}];
ASupdateCoords = [function() {updateCoords(0)},
  function() {updateCoords(1)},
  function() {updateCoords(2)},
  function() {updateCoords(3)},
  function() {updateCoords(4)},
  function() {updateCoords(5)},
  function() {updateCoords(6)},
  function() {updateCoords(7)},
  function() {updateCoords(8)},
  function() {updateCoords(9)}];


// GO1.1 Generic onload by Brothercake
// http://www.brothercake.com/
//onload function
function generic()
{
  drawPictures();
};
//setup onload function
if(typeof window.addEventListener != 'undefined')
{
  //.. gecko, safari, konqueror and standard
  window.addEventListener('load', generic, false);
}
else if(typeof document.addEventListener != 'undefined')
{
  //.. opera 7
  document.addEventListener('load', generic, false);
}
else if(typeof window.attachEvent != 'undefined')
{
  //.. win/ie
  window.attachEvent('onload', generic);
}
//** remove this condition to degrade older browsers
else
{
  //.. mac/ie5 and anything else that gets this far
  //if there's an existing onload function
  if(typeof window.onload == 'function')
  {
    //store it
    var existing = onload;
    //add new onload handler
    window.onload = function()
    {
      //call existing onload function
      existing();
      //call generic onload function
      generic();
    };
  }
  else
  {
    //setup onload function
    window.onload = generic;
  }
}


if (checkIfSVGavailable) {
  checkifSVGavailable = false;
  nd = isSVGavailable();
  ASnoSVG = nd!=null;
}
