/***********************************************************************
** Title.........:  Online Image Editor Interface
** Version.......:  1.0
** Author........:  Xiang Wei ZHUO <wei@zhuo.org>
** Filename......:  EditorContents.js
** Last changed..:  31 Mar 2004 
** Notes.........:  Handles most of the interface routines for the ImageEditor.
*
* Added:  29 Mar 2004  - Constrainted resizing/scaling
**/ 


function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

var pic_x, pic_y;
function P7_Snap() { //v2.62 by PVII
  var x,y,ox,bx,oy,p,tx,a,b,k,d,da,e,el,args=P7_Snap.arguments;a=parseInt(a);
  for (k=0; k<(args.length-3); k+=4)
   if ((g=MM_findObj(args[k]))!=null) {
    el=eval(MM_findObj(args[k+1]));
    a=parseInt(args[k+2]);b=parseInt(args[k+3]);
    x=0;y=0;ox=0;oy=0;p="";tx=1;da="document.all['"+args[k]+"']";
    if(document.getElementById) {
     d="document.getElementsByName('"+args[k]+"')[0]";
     if(!eval(d)) {d="document.getElementById('"+args[k]+"')";if(!eval(d)) {d=da;}}
    }else if(document.all) {d=da;} 
    if (document.all || document.getElementById) {
     while (tx==1) {p+=".offsetParent";
      if(eval(d+p)) {x+=parseInt(eval(d+p+".offsetLeft"));y+=parseInt(eval(d+p+".offsetTop"));
      }else{tx=0;}}
     ox=parseInt(g.offsetLeft);oy=parseInt(g.offsetTop);var tw=x+ox+y+oy;
     if(tw==0 || (navigator.appVersion.indexOf("MSIE 4")>-1 && navigator.appVersion.indexOf("Mac")>-1)) {
      ox=0;oy=0;if(g.style.left){x=parseInt(g.style.left);y=parseInt(g.style.top);
      }else{var w1=parseInt(el.style.width);bx=(a<0)?-5-w1:-10;
      a=(Math.abs(a)<1000)?0:a;b=(Math.abs(b)<1000)?0:b;
      //alert(event.clientX);
	  if (window.event == null) x=document.body.scrollLeft + bx;
		else x=document.body.scrollLeft + event.clientX + bx;
	  if (window.event == null) y=document.body.scrollTop;
		else y=document.body.scrollTop + event.clientY;}}
   }else if (document.layers) {x=g.x;y=g.y;var q0=document.layers,dd="";
    for(var s=0;s<q0.length;s++) {dd='document.'+q0[s].name;
     if(eval(dd+'.document.'+args[k])) {x+=eval(dd+'.left');y+=eval(dd+'.top');break;}}}
   if(el) {e=(document.layers)?el:el.style;
   var xx=parseInt(x+ox+a),yy=parseInt(y+oy+b);
   //alert(xx+":"+yy);
   if(navigator.appName=="Netscape" && parseInt(navigator.appVersion)>4){xx+="px";yy+="px";}
   if(navigator.appVersion.indexOf("MSIE 5")>-1 && navigator.appVersion.indexOf("Mac")>-1){
    xx+=parseInt(document.body.leftMargin);yy+=parseInt(document.body.topMargin);
    xx+="px";yy+="px";}e.left=xx;e.top=yy;}
    pic_x = parseInt(xx); pic_y = parseInt(yy);
    //alert(xx+":"+yy);
    }
}

var ie=document.all
var ns6=document.getElementById&&!document.all

var dragapproved=false
var z,x,y,status, ant, canvas, content, pic_width, pic_height, image, resizeHandle, oa_w, oa_h, oa_x, oa_y, mx2, my2;


function init_resize() 
{
    if(mode == "scale") 
    {
        P7_Snap('theImage','ant',0,0);

        if (canvas == null)
            canvas = MM_findObj("imgCanvas");

        if (pic_width == null || pic_height == null)
        {
            image = MM_findObj("theImage");
            pic_width = image.width;
            pic_height = image.height;
        }
        
        if (ant == null)
            ant = MM_findObj("ant");

        ant.style.left = pic_x; ant.style.top = pic_y;
        ant.style.width = pic_width; ant.style.height = pic_height;
        ant.style.visibility = "visible";

        drawBoundHandle();
        jg_doc.paint();
    }
}

initEditor = function () 
{
    init_crop();
    init_resize();
    //var markerImg = MM_findObj('markerImg', window.top.document);
    var markerImg = MM_findObj('markerImg', window.parent.document);

    if (markerImg.src.indexOf("img/t_white.gif")>0)
        toggleMarker() ;
}

function init_crop() 
{
    //if(mode == "crop") {
        P7_Snap('theImage','ant',0,0);
    //}
}

function setMode(newMode) 
{
    mode = newMode;
    reset();
}

function reset() 
{
    if (ant == null)
        ant = MM_findObj("ant");

    ant.style.visibility = "hidden";
    ant.style.left = 0;
    ant.style.top = 0;
    ant.style.width = 0;
    ant.style.height = 0;

    mx2 = null;
    my2 = null;

    jg_doc.clear();
    if(mode != 'measure')
        showStatus();   

    if(mode == "scale") {
        init_resize();
    }

    P7_Snap('theImage','ant',0,0);
}

function toggleMarker() 
{
    //alert("Toggle");
    if (ant == null)
        ant = MM_findObj("ant");

    if(ant.className=="selection")
        ant.className="selectionWhite";
    else
        ant.className="selection";
    
    if (jg_doc.getColor() == "#000000")
        jg_doc.setColor("#FFFFFF");
    else
        jg_doc.setColor("#000000");
    
    drawBoundHandle
    jg_doc.paint();
}


function move(e)
{
    if (dragapproved)
    {
        //z.style.left=ns6? temp1+e.clientX-x: temp1+event.clientX-x
        //z.style.top=ns6? temp2+e.clientY-y : temp2+event.clientY-y
        var w = ns6? temp1+e.clientX - x : temp1+event.clientX - x;
        var h = ns6? temp2+e.clientY - y : temp2+event.clientY - y;

        //alert(canvas.style.left);
        /*if (status !=null)
        {
            status.innerHTML  = "x:"+x+" y:"+y+" w:"+w+" h:"+h+" can_h:"+pic_height;
            status.innerHTML += " can_w:"+pic_width+" px:"+pic_x+" py:"+pic_y;
            status.innerHTML += " pix:"+image.style.left+" piy:"+image.style.top+" obj:"+obj.id;
        }*/

        /*jg_doc.clear();
        jg_doc.fillRectPattern(0,0,Math.abs(w),Math.abs(h),pattern);
        jg_doc.paint();
*/
        if (ant != null)
        {
            if (w >= 0)
            {
                ant.style.left = x;
                ant.style.width = w;
            }
            else
            {
                ant.style.left = x+w;
                ant.style.width = -1*w;
            }

            if (h >= 0)
            {
                ant.style.top = y;
                ant.style.height = h;
            }
            else
            {
                ant.style.top = y+h;
                ant.style.height = -1*h
            }
        }
        
        showStatus();
        return false
    }
}

function moveContent(e) 
{
    if (dragapproved)
    {

        var dx =ns6? oa_x + e.clientX-x: oa_x + event.clientX-x
        var dy =ns6? oa_y + e.clientY-y : oa_y + event.clientY-y


        /*if (status !=null)
        {
            status.innerHTML  = "x:"+x+" y:"+y+" dx:"+dx+" dy:"+dy;
        }*/

        ant.style.left = dx;
        ant.style.top = dy;

        showStatus();

        return false;
    }
}

//Code add for constraints by Frédéric Klee <fklee@isuisse.com>
function moveHandle(e) 
{
    if (dragapproved)
    {
        var w = ns6? e.clientX - x : event.clientX - x;
        var h = ns6? e.clientY - y : event.clientY - y;
        
		//var constrained = MM_findObj('constProp', window.top.document);
		var constrained = MM_findObj('constProp', window.parent.document);
        var orginal_height = document.theImage.height ;
        var orginal_width = document.theImage.width ;
        rapp = orginal_width/orginal_height ;
        rapp_inv = orginal_height / orginal_width ;

        switch(resizeHandle) 
        {

            case "s-resize":
                if (oa_h + h >= 0) 
				{
                    ant.style.height = oa_h + h;
					if(constrained.checked) 
					{
						ant.style.width = rapp * (oa_h + h) ;
                        ant.style.left = oa_x - rapp * h/2;
					}

				}
                break;
            case "e-resize":
                if(oa_w + w >= 0)
				{
                    ant.style.width = oa_w + w;
					if(constrained.checked) 
					{
                        ant.style.height = rapp_inv * (oa_w + w) ;
                        ant.style.top = oa_y - rapp_inv * w/2;
                    }

				}
                break;
            case "n-resize":
                if (oa_h - h >= 0)
                {
                    ant.style.top = oa_y + h;
                    ant.style.height = oa_h - h;
					if(constrained.checked) 
					{
						ant.style.width = rapp * (oa_h - h) ;
                        ant.style.left = oa_x + rapp * h/2;
                    }

                }
                break;
            case "w-resize":
                if(oa_w - w >= 0) 
				{
                    ant.style.left = oa_x + w;
                    ant.style.width = oa_w - w;
					if(constrained.checked) 
					{
						ant.style.height = rapp_inv * (oa_w - w) ;
						ant.style.top = oa_y + rapp_inv * w/2;
                    }

                }break;
            case "nw-resize":
                if(oa_h - h >= 0 && oa_w - w >= 0) {
                    ant.style.left = oa_x + w;
                    ant.style.width = oa_w - w;
                    ant.style.top = oa_y + h;
                    if(constrained.checked) 
                        ant.style.height = rapp_inv * (oa_w - w) ;
                    else
                        ant.style.height = oa_h - h;
                }
            break;
            case "ne-resize":
                if (oa_h - h >= 0 && oa_w + w >= 0){
                    ant.style.top = oa_y + h;
					ant.style.width = oa_w + w;
					if(constrained.checked) 
                        ant.style.height = rapp_inv * (oa_w + w) ;
                    else
                        ant.style.height = oa_h - h;
                }
                break;
            case "se-resize":
                if (oa_h + h >= 0 && oa_w + w >= 0) 
                {
                    ant.style.width = oa_w + w;
                    if(constrained.checked)
                        ant.style.height = rapp_inv * (oa_w + w) ;
                    else
                        ant.style.height = oa_h + h;
                }
                break;
            case "sw-resize":
                if (oa_h + h >= 0 && oa_w - w >= 0)
                {
                    ant.style.left = oa_x + w;
                    ant.style.width = oa_w - w;
					if(constrained.checked)
                        ant.style.height = rapp_inv * (oa_w - w) ;
                    else
                       ant.style.height = oa_h + h;
				}
        }
        
        showStatus();
        return false;
        
    }
}

function drags(e)
{
    if (!ie&&!ns6)
        return
    
    var firedobj=ns6? e.target : event.srcElement
    var topelement=ns6? "HTML" : "BODY"

    while (firedobj.tagName!=topelement&&
            !(firedobj.className=="crop" 
                || firedobj.className=="handleBox" 
                || firedobj.className=="selection" || firedobj.className=="selectionWhite"))
    {
        firedobj=ns6? firedobj.parentNode : firedobj.parentElement
    }

    if(firedobj.className=="handleBox") {
        
        if(content != null) {
            if(content.width != null && content.height != null) {
                content.width = 0;
                content.height = 0;
            }
            //alert(content.width+":"+content.height);
        }
        resizeHandle = firedobj.id;
        
        /*if(status!=null) {
            status.innerHTML  = " obj:"+firedobj.id;
        }*/

        x=ns6? e.clientX: event.clientX
        y=ns6? e.clientY: event.clientY

        oa_w = parseInt(ant.style.width);
        oa_h = parseInt(ant.style.height);
        oa_x = parseInt(ant.style.left);
        oa_y = parseInt(ant.style.top);

        dragapproved=true
        document.onmousemove=moveHandle;
        return false;
    }
    else
    if((firedobj.className == "selection" || firedobj.className=="selectionWhite")&& mode == "crop") {
        
        x=ns6? e.clientX: event.clientX
        y=ns6? e.clientY: event.clientY
        
        oa_x = parseInt(ant.style.left);
        oa_y = parseInt(ant.style.top);

        dragapproved=true
        document.onmousemove=moveContent;
        return false;
    }
    else
    if (firedobj.className=="crop" && mode == "crop")
    {
        if(content != null) {
            if(content.width != null && content.height != null) {
                content.width = 0;
                content.height = 0;
            }
            //alert(content.width+":"+content.height);
        }

        if (status == null)
            status = MM_findObj("status");

        if (ant == null)
            ant = MM_findObj("ant");

        if (canvas == null)
            canvas = MM_findObj("imgCanvas");
        if(content == null) {
            content = MM_findObj("cropContent");
        }

        if (pic_width == null || pic_height == null)
        {
            image = MM_findObj("theImage");
            pic_width = image.width;
            pic_height = image.height;
        }

        ant.style.visibility = "visible";

        obj = firedobj;
        dragapproved=true
        z=firedobj
        temp1=parseInt(z.style.left+0)
        temp2=parseInt(z.style.top+0)
        x=ns6? e.clientX: event.clientX
        y=ns6? e.clientY: event.clientY
        document.onmousemove=move
        return false
    }
        else if(firedobj.className=="crop" && mode == "measure") {

            if (ant == null)
                ant = MM_findObj("ant");

            if (canvas == null)
                canvas = MM_findObj("imgCanvas");

            x=ns6? e.clientX: event.clientX
            y=ns6? e.clientY: event.clientY

                //jg_doc.draw
            dragapproved=true
            document.onmousemove=measure

            return false
        }
}

function measure(e) 
{
    if (dragapproved)
    {
        mx2 = ns6? e.clientX : event.clientX;
        my2 = ns6? e.clientY : event.clientY;
        
        jg_doc.clear();
        jg_doc.setStroke(Stroke.DOTTED); 
        jg_doc.drawLine(x,y,mx2,my2);
        jg_doc.paint();
        showStatus();
        return false;
    }
}

function setMarker(nx,ny,nw,nh) 
{
    if (ant == null)
        ant = MM_findObj("ant");

    if (canvas == null)
        canvas = MM_findObj("imgCanvas");
    if(content == null) {
        content = MM_findObj("cropContent");
    }

    if (pic_width == null || pic_height == null)
    {
        image = MM_findObj("theImage");
        pic_width = image.width;
        pic_height = image.height;
    }

    ant.style.visibility = "visible";

    nx = pic_x + nx;
    ny = pic_y + ny;

    if (nw >= 0)
    {
        ant.style.left = nx;
        ant.style.width = nw;
    }
    else
    {
        ant.style.left = nx+nw;
        ant.style.width = -1*nw;
    }

    if (nh >= 0)
    {
        ant.style.top = ny;
        ant.style.height = nh;
    }
    else
    {
        ant.style.top = ny+nh;
        ant.style.height = -1*nh
    }

    
}

function max(x,y) 
{
    if(y > x)
        return x;
    else 
        return y;
}

function drawBoundHandle() 
{
    if(ant == null || ant.style == null) 
        return false;

    var ah = parseInt(ant.style.height);
    var aw = parseInt(ant.style.width);
    var ax = parseInt(ant.style.left);
    var ay = parseInt(ant.style.top);

    jg_doc.drawHandle(ax-15,ay-15,30,30,"nw-resize"); //upper left
    jg_doc.drawHandle(ax-15,ay+ah-15,30,30,"sw-resize"); //lower left
    jg_doc.drawHandle(ax+aw-15,ay-15,30,30,"ne-resize"); //upper right
    jg_doc.drawHandle(ax+aw-15,ay+ah-15,30,30,"se-resize"); //lower right

    jg_doc.drawHandle(ax+max(15,aw/10),ay-8,aw-2*max(15,aw/10),8,"n-resize"); //top middle
    jg_doc.drawHandle(ax+max(15,aw/10),ay+ah,aw-2*max(15,aw/10),8,"s-resize"); //bottom middle
    jg_doc.drawHandle(ax-8, ay+max(15,ah/10),8,ah-2*max(15,ah/10),"w-resize"); //left middle
    jg_doc.drawHandle(ax+aw, ay+max(15,ah/10),8,ah-2*max(15,ah/10),"e-resize"); //right middle



    jg_doc.drawHandleBox(ax-4,ay-4,8,8,"nw-resize"); //upper left
    jg_doc.drawHandleBox(ax-4,ay+ah-4,8,8,"sw-resize"); //lower left
    jg_doc.drawHandleBox(ax+aw-4,ay-4,8,8,"ne-resize"); //upper right
    jg_doc.drawHandleBox(ax+aw-4,ay+ah-4,8,8,"se-resize"); //lower right

    jg_doc.drawHandleBox(ax+aw/2-4,ay-4,8,8,"n-resize"); //top middle
    jg_doc.drawHandleBox(ax+aw/2-4,ay+ah-4,8,8,"s-resize"); //bottom middle
    jg_doc.drawHandleBox(ax-4, ay+ah/2-4,8,8,"w-resize"); //left middle
    jg_doc.drawHandleBox(ax+aw-4, ay+ah/2-4,8,8,"e-resize"); //right middle

    //jg_doc.paint();
}

function showStatus() 
{
    if(ant == null || ant.style == null) {
        return false;
    }

    if(mode == "measure") {
        //alert(pic_x);
        mx1 = x - pic_x;
        my1 = y - pic_y;

        mw = mx2 - x;
        mh = my2 - y;

        md = parseInt(Math.sqrt(mw*mw + mh*mh)*100)/100;

        ma = (Math.atan(-1*mh/mw)/Math.PI)*180;
        if(mw < 0 && mh < 0)
            ma = ma+180;

        if (mw <0 && mh >0)
            ma = ma - 180;

        ma = parseInt(ma*100)/100;

        if (m_sx != null && !isNaN(mx1))
            m_sx.value = mx1+"px";
        if (m_sy != null && !isNaN(my1))
            m_sy.value = my1+"px";
        if(m_w != null && !isNaN(mw))
            m_w.value = mw + "px";
        if(m_h != null && !isNaN(mh))
            m_h.value = mh + "px";

        if(m_d != null && !isNaN(md))
            m_d.value = md + "px";
        if(m_a != null && !isNaN(ma))
            m_a.value = ma + "";

        if(r_ra != null &&!isNaN(ma))
            r_ra.value = ma;            

        //alert("mx1:"+mx1+" my1"+my1);
        return false;
    }

    var ah = parseInt(ant.style.height);
    var aw = parseInt(ant.style.width);
    var ax = parseInt(ant.style.left);
    var ay = parseInt(ant.style.top);

    var cx = ax-pic_x<0?0:ax-pic_x;
    var cy = ay-pic_y<0?0:ay-pic_y;
    cx = cx>pic_width?pic_width:cx;
    cy = cy>pic_height?pic_height:cy;
    
    var cw = ax-pic_x>0?aw:aw-(pic_x-ax);
    var ch = ay-pic_y>0?ah:ah-(pic_y-ay);

    ch = ay+ah<pic_y+pic_height?ch:ch-(ay+ah-pic_y-pic_height);
    cw = ax+aw<pic_x+pic_width?cw:cw-(ax+aw-pic_x-pic_width);

    ch = ch<0?0:ch; cw = cw<0?0:cw;
    
    if (ant.style.visibility == "hidden")
    {
        cx = ""; cy = ""; cw=""; ch="";
    }

    if(mode == 'crop') {
        if(t_cx != null)
            t_cx.value = cx;
        if (t_cy != null)   
            t_cy.value = cy;
        if(t_cw != null)
            t_cw.value = cw;
        if (t_ch != null)   
            t_ch.value = ch;
    }
    else if(mode == 'scale') {

        var sw = aw, sh = ah;

        if (s_sw.value.indexOf('%')>0 && s_sh.value.indexOf('%')>0)
        {   
            sw = cw/pic_width;
            sh = ch/pic_height;
        }
        if (s_sw != null)
            s_sw.value = sw;
        if (s_sh != null)
            s_sh.value = sh;
    }

}

function dragStopped()
{
    dragapproved=false;

    if(ant == null || ant.style == null) {
        return false;
    }

    if(mode == "measure") {
        jg_doc.drawLine(x-4,y,x+4,y);
        jg_doc.drawLine(x,y-4,x,y+4);
        jg_doc.drawLine(mx2-4,my2,mx2+4,my2);
        jg_doc.drawLine(mx2,my2-4,mx2,my2+4);

        jg_doc.paint();
        showStatus();
        return false;
    }
        var ah = parseInt(ant.style.height);
        var aw = parseInt(ant.style.width);
        var ax = parseInt(ant.style.left);
        var ay = parseInt(ant.style.top);
        jg_doc.clear();
        
        if(content != null) {
            if(content.width != null && content.height != null) {
                content.width = aw-1;
                content.height = ah-1;
            }
            //alert(content.width+":"+content.height);
        }
        if(mode == "crop") {
            //alert(pic_y);
            jg_doc.fillRectPattern(pic_x,pic_y,pic_width,ay-pic_y,pattern);
            
            var h1 = ah;
            var y1 = ay;
            if (ah+ay >= pic_height+pic_y)
                h1 = pic_height+pic_y-ay;
            else if (ay <= pic_y)
            {
                h1 = ay+ah-pic_y;
                y1 = pic_y;
            }
            jg_doc.fillRectPattern(pic_x,y1,ax-pic_x,h1,pattern);
            jg_doc.fillRectPattern(ax+aw,y1,pic_x+pic_width-ax-aw,h1,pattern);
            jg_doc.fillRectPattern(pic_x,ay+ah,pic_width,pic_height+pic_y-ay-ah,pattern);
        }
        else if(mode == "scale") {
            //alert("Resizing: iw:"+image.width+" nw:"+aw);
            document.theImage.height = ah;
            document.theImage.width = aw;
            document.theImage.style.height = ah+" px";
            document.theImage.style.width = aw+" px";

            P7_Snap('theImage','ant',0,0);

            //alert("After Resizing: iw:"+image.width+" nw:"+aw);
        }

        drawBoundHandle();
        jg_doc.paint();
    
        showStatus();
    return false;
}

document.onmousedown=drags
document.onmouseup=dragStopped;

