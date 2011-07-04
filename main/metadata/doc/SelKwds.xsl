<?xml version="1.0" encoding="iso-8859-1"?>                 <!-- SelKwds.xsl --> 
                                                             <!-- 2005/04/14 -->

<!-- Copyright (C) 2005 rene.haentjens@UGent.be - see note at end of text    -->
<!-- Released under the GNU GPL V2, see http://www.gnu.org/licenses/gpl.html -->

<!-- W3C browsers only; Moz1.7,NN7: security popup (UniversalXPConnect)      -->

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"  version="1.0">

  <xsl:template match="/*">
  

    <html>

    <head><title><xsl:value-of select="@title"/></title>
    
    <style>
        body    {font-family: sans-serif; font-size: x-small}
        .kwl    {width: 92%; color: black; font-size: x-small; font-weight: bold}
        .dvc    {display: none;  margin-left: 3ex}
        .dvo    {display: block; margin-left: 3ex}
        .btm    {height: 3.5ex; width: 5ex; font-family: monospace; font-size: xx-small}
        .btn    {height: 3.5ex; width: 3ex; font-family: monospace; font-size: xx-small}
        .lfn    {height: 3.5ex; width: 3ex; font-family: monospace; font-size: xx-small; visibility: hidden}
        .lbl    {background-color: white; cursor: pointer}
        .lbs    {background-color: white; cursor: pointer; font-weight: bold; color: red}
        .pup    {position:absolute; visibility:hidden; width: 50%; border: 1px solid black; overflow:auto; background-color: #FFF8DC}
        .pul    {background-color: #FFF8DC}
    </style>
    
    <script language="JavaScript"><![CDATA[
    
        String.prototype.trim = function()
        {
            return this.replace(/^\s*/,"").replace(/\s*$/,""); // \f\n\r\t\v
        }   // Dave Anderson, dbforums.com/arch/195/2003/3/724117
        
        String.prototype.removeExtraSpaces = function()
        {
            return this.replace(/\s+/g, ' ').trim();
        }
        
        function makeWindow(url, htmlText)
        {
            var newWindow = window.open(url, '', 
                'toolbar=no, location=no, directories=no, status=yes, '+ 
                'menubar=yes, scrollbars=yes, resizable=yes, ' + 
                'width=800, height=600, left=10, top=10');
            if (url == '') newWindow.document.write('<html><body>' + 
                    htmlText + '</body></html>');
            return newWindow;
        }
        
        function isNotW3C()
        {
            if (!document.getElementById)
                alert('Sorry, the buttons only work with W3C browsers. ' +
                    'Use Moz1.7 or IE6 or NN7 or type in keywords manually...');
            return !document.getElementById;
        }
        
        
        function openOrCloseHelp(btn)
        {
            if (isNotW3C()) return false;
            
            document.getElementById('moreHelp').className =
                (btn.value == "?")              ? "dvo" : "dvc";
            btn.value = (btn.value == "?")      ?  "¿"  :  "?";
        }
        
        function hasTagAndClass(obj, tag, cl)
        {
            return obj.tagName && (obj.tagName.toUpperCase() == tag) && 
                (obj.className == cl);
        }
    
        
        function openOrClose(btn)  // show or hide part of keyword-tree
        {
            var oldcl = (btn.value == "-") ? "dvo" : "dvc";
            var newcl = (oldcl == "dvo")   ? "dvc" : "dvo";
            btn.value = (oldcl == "dvo")   ?  "+"  :  "-" ;
            
            var ch = btn.parentNode.childNodes;  // opera crashes on with()
            for (var i = 0; i < ch.length; i++)  // netscape requires .item
                if (hasTagAndClass(ch.item(i), 'DIV', oldcl))
                    ch.item(i).className = newcl;
        }
        
        function openOrCloseHere(div, wrong)  // show or hide recursively
        {
            var ch = div.childNodes;
            for (var i = 0; i < ch.length; i++)
            {
                var thisCh = ch.item(i);
                if (thisCh.className == 'btn' && thisCh.value == wrong)
                    openOrClose(thisCh)
                else if (thisCh.className == 'dvo' || thisCh.className == 'dvc')
                    openOrCloseHere(thisCh, wrong);
            }
        }
        
        function openOrCloseAll(btn)  // show or hide whole keyword-tree
        {
            if (isNotW3C()) return false;
            
            var wrong = (btn.value == "--") ? "-"  : "+" ;
            btn.value = (wrong == "-")      ? "++" : "--";
            
            openOrCloseHere(btn.parentNode, wrong);
        }
        
        
        var selspans = new Array;   // selected SPANs with keywords
        
        function deselect(span)
        {
            for (var s in selspans) if (selspans[s] == span) delete selspans[s];
        }
        
        function copyToClipboard(allKwds)
        {
            if (window.clipboardData)
            {
                window.clipboardData.setData("Text", '<' + allKwds + '>\r\n');
                return;
            }
            
            netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect');
            var gClipboardHelper = Components.classes['@mozilla.org/widget/clipboardhelper;1']
                .getService(Components.interfaces.nsIClipboardHelper);
            gClipboardHelper.copyString('<' + allKwds + '>\n');
        }
        
        function getSelectedKeywords()
        {
            var sortedKwds = new Array, allKwds = '';
            
            for (var s in selspans)
                sortedKwds.push(selspans[s].parentNode.getAttribute('level')
                     + '.' + selspans[s].innerHTML)
            
            sortedKwds.sort();  // according to level, which follows the tree
            
            for (var k in sortedKwds)
            {
                var someWords = sortedKwds[k];
                allKwds += ', ' + someWords.substr(someWords.indexOf('.')+1);
            }
            
            return allKwds.replace(/[,_\s]+/g, ', ').substr(2);  // strip ', '
        }

                
        var orkwds;  // array of ', kw, ' strings
        
        function selectOriginal(div)
        {
            var ch = div.childNodes;
            for (var i = 0; i < ch.length; i++)
            {
                var thisCh = ch.item(i); if (!thisCh.tagName) continue;
                thisTag = thisCh.tagName.toUpperCase();
                
                if (thisTag == 'SPAN')
                {
                    var parkwds = ','+ thisCh.innerHTML.replace(/\s*/g,"") +',';
                    
                    for (var k = 0; k < orkwds.length; k++)
                    if (parkwds.indexOf(orkwds[k]) >=0 )
                    {
                        deselect(thisCh); selspans.push(thisCh);
                        thisCh.className = "lbs"; 
                        
                        openToTop(div, div.className); break;
                    }
                }
                else if (thisTag == 'DIV')
                {
                    selectOriginal(thisCh);
                }
            }
        }
        
        function openToTop(div, divclass)
        {
            if (!div.parentNode) return; var pdiv = div.parentNode;
            
            if (!pdiv.className) return; var pclass = pdiv.className;
            if (pclass != 'dvo' && pclass != 'dvc') return;
            
            if (divclass == 'dvc')
            {
                var ch = pdiv.childNodes;
                for (var i = 0; i < ch.length; i++)
                {
                    var btn = ch.item(i);
                    if (hasTagAndClass(btn, 'INPUT', 'btn'))
                        if (btn.value == '+') openOrClose(btn);
                }
            }
            
            openToTop(pdiv, pclass);
        }
        
        function deselectAll(ev, btn)
        {
            if (isNotW3C()) return false;
            
        	if (!ev) var ev = window.event;
        	
            var kws = document.getElementById('kwds_string');
            
            for (var s in selspans) selspans[s].className = "lbl";
            selspans = new Array;
            
            document.getElementById('btnOpenOrCloseAll').value = "--";
            
            if (!ev.altKey) { kws.value = ''; return; }
            
            if (ev.ctrlKey) kws.value = getPptKw();
            var kwdswere = kws.value;  // typed in by user or fetched from PPT
            
            var kwdsarray = kwdswere.split(','), allKwds = '';
            
            for (var k = 0; k < kwdsarray.length; k++)
            {
                var kwd = kwdsarray[k].trim();
                if (kwd.substr(0,1) == '-') kwd = kwd.substr(1);
                if (kwd != '') allKwds += '§,' + kwd + ',';
            }
            
            if (allKwds == '') return;
            
            orkwds = allKwds.substr(1).split('§');
            
            selectOriginal(btn.parentNode);
            
            allKwds = getSelectedKeywords(); kws.value = allKwds;
            copyToClipboard(allKwds);
            
            allKwds = ','+ allKwds.replace(/\s*/g,"") +','; var missing = '';
            for (k = 0; k < orkwds.length; k++)
            if (allKwds.indexOf(orkwds[k]) < 0 ) missing += orkwds[k];
            
            if (missing != '') alert('!= ' + missing.replace(/,+/g," "));
        }
        
        function selectOrDeselect(span, newcl)
        {
            span.className = newcl; deselect(span);
            if (newcl == "lbs") selspans.push(span);
        }

        function alsoParents(div, oldcl, newcl)
        {
            while (div.parentNode)
            {
                div = div.parentNode; var ch = div.childNodes;
                for (var i = 0; i < ch.length; i++)
                    if (hasTagAndClass(ch.item(i), 'SPAN', oldcl))
                        selectOrDeselect(ch.item(i), newcl);
            }
        }

        function spanClick(span, ev)
        {
                                        if (!ev) ev = window.event;
            
            if (ev.shiftKey && ev.altKey)
            {
                makeWindow('', '<pre>' + span.parentNode.innerHTML
                    .replace(/&/g, "&amp;").replace(/"/g, "&quot;")
                    .replace(/</g, "&lt;") .replace(/>/g, "&gt;") + '</pre>');
                return;  // debugging...
            }
            
            if (ev.ctrlKey || span.className == "lbs")
            {
                selectOrDeselect(span, "lbl");  // deselect
                if (document.selection) document.selection.empty();
                if (ev.altKey) alsoParents(span.parentNode, "lbs", "lbl");
            }
            else
            {
                selectOrDeselect(span, "lbs");  // select
                if (!ev.altKey) alsoParents(span.parentNode, "lbl", "lbs");
                //
                // in md_script, there is some code here
                // to negate keywords for search
                //
            }
            
            var allKwds = getSelectedKeywords(); copyToClipboard(allKwds);
            document.getElementById('kwds_string').value = allKwds;
        }
        
    
        var KWDS_ARRAY = new Array, nkw = 0, pU;  // alphabetic list popup
        
        function makeAlphaList(div)
        {
            var ch = div.childNodes;
            for (var i = 0; i < ch.length; i++)
            {
                var thisCh = ch.item(i); if (!thisCh.tagName) continue;
                thisTag = thisCh.tagName.toUpperCase();
                
                if (thisTag == 'SPAN')
                {
                    var parkwds= thisCh.innerHTML.replace(/\s*/g,"").split(',');
                    for (k in parkwds) KWDS_ARRAY[nkw++] = parkwds[k];
                }
                else if (thisTag == 'DIV') makeAlphaList(thisCh);
            }
        }
        
        function pU_show(anchor, offsetX, offsetY, defH)  // XY: rel. to anchor
        {
            if (!anchor) return;
            
            thisx = anchor.offsetLeft; thisy = anchor.offsetTop;
            
            while ((anchor = anchor.offsetParent))
            { 
                thisx += anchor.offsetLeft; thisy += anchor.offsetTop; 
            }
            
            thisx += offsetX; thisy += offsetY;
            
            pU.style.left = thisx + "px";
            pU.style.top =  thisy + "px";
            pU.style.height = defH; var maxH = pU.offsetHeight;
            for (var curH = 20; curH <= maxH; curH += 20)
            {
                pU.style.height = curH + 'px';
                if (curH >= pU.scrollHeight) break;
            }
            // scrollHeight can be smaller than current in IE, not in Moz
            pU.style.visibility = "visible";
        }
         
        function pU_hide()
        {
            if (pU) pU.style.visibility = "hidden";
        }
        
        function takeTypeIn(kws, oX, oY, defH)
        {
            if (isNotW3C()) return;
            
            if (!pU)
            {
                pU = document.getElementById('popup');
                makeAlphaList(document.getElementById('maindiv'));
                KWDS_ARRAY.sort();
            }
            
            var curValue = kws.value.toLowerCase(), kwLines = '';
            
            for (pos = 0; pos < KWDS_ARRAY.length; pos++)
                if (KWDS_ARRAY[pos].toLowerCase().indexOf(curValue) == 0)
                    kwLines += '<div class="pul" onMouseOver="this.className=' + 
                        "'lbl'" + '"; onMouseOut="this.className=' + "'pul'" + 
                        '">' + KWDS_ARRAY[pos] + '</div>';
            
            if (kwLines == '') {pU_hide(); return; }
            
            pU.innerHTML = kwLines; pU_show(kws, oX, oY, defH);
        }
        
        function pU_clicked(ev)
        {
            if (!pU) return false; if (!ev) var ev = window.event;
            
            var t = (ev.srcElement) ? ev.srcElement : ev.originalTarget;
            try {var kw = t.innerHTML;} catch(exc) {return false;}  // Moz
            
            while (true) try
            {
                if (t.id == pU.id) return kw ? kw : true; t = t.parentNode;
            } 
            catch(exc) {return false;}  // Moz: t.parentNode uncatched exc.
        }
        
        function pU_select(kw)
        {
            if (kw === true) return;
            
            var kws = document.getElementById('kwds_string');
            var maindiv = document.getElementById('maindiv');
            
            var ch = maindiv.childNodes;
            for (var i = 0; i < ch.length; i++)
            {
                var btn = ch.item(i);
                if (hasTagAndClass(btn, 'INPUT', 'btn'))
                    if (btn.value == '+') openOrClose(btn);
            }
            orkwds = new Array(',' + kw + ','); selectOriginal(maindiv);
            
            kws.value = getSelectedKeywords(); copyToClipboard(kws.value);
         
            pU_hide();
        }
        
        
        function getPptKw()  // SelKwds specific, IE only!
        {
            if (typeof window.ActiveXObject == 'undefined') return ''; 
            
            with (new ActiveXObject('Powerpoint.Application'))
            {
                if (Presentations.Count == 0) return '';
                
                var slKw = ActiveWindow.View.Slide.NotesPage(1)
                    .Shapes.Placeholders(2).TextFrame.TextRange.Text
                    .replace(/[\x00-\x1F\x7F-\x9F]/g, ' ')
                    .replace(/\s+/g, ' ').trim();
            }
            
            if (slKw.substr(0,1) == '<')
            {
                var slKwEnd = slKw.indexOf('>');
                if (slKwEnd <= 0) return '';
                return slKw.substr(1, slKwEnd - 1);
            }
            else
            {
                var slKwLen = slKw.length;
                if (slKw.substr(slKwLen - 1) != '>') return '';
                var slKwBegin = slKw.lastIndexOf('<');
                if (slKwBegin <= 0) return '';
                return slKw.substr(slKwBegin + 1, slKwLen - slKwBegin - 2);
            }
        }
        
    ]]></script>
    
    </head>

    
    <body onMouseUp="if ((kw = pU_clicked(event))) pU_select(kw); else pU_hide();">
    
    <!-- Note: style on DIV in TD, but keep vertical-align on TD -->
    
    <h3><xsl:value-of select="@title"/></h3>
    
    <div>
        <input type="button" class="btn" value="?" onClick="openOrCloseHelp(this)"/>
        &#xa0;Click a keyword in the tree to select or deselect it.
    </div>
    
    <div id='moreHelp' class='dvc'>
        <br/>
        Click '+' button to open, '-' button to close, '++' button to open all, '--' button to close all.<br/>
        <br/>
        Clear all selected keywords by closing the tree and opening it again with the '+' button.<br/>
        Alt-click '+' searches the current keywords in the tree.<br/>
        Control-Alt-click '+' searches the keywords of the current slide.<br/>
        <br/>
        Alt-click keyword selects a keyword without broader terms or 
        deselects a keyword with broader terms.<br/><br/>
        Selected keywords are available in the Clipboard.<br/>
        <br/>
	    'nei' stands for 'not elswhere included'.<br/><br/>
    </div>
    
    <div noWrap="1" id="maindiv">
        <input type="button" class="btn" value="+" onClick="if (this.value == '+') deselectAll(event, this); openOrClose(this);"/>
        <input type="button" class="btm" id="btnOpenOrCloseAll" value="++" onClick="openOrCloseAll(this);"/>
        &#xa0;
        <input type="text" id="kwds_string" class="kwl" 
               onKeyUp="takeTypeIn(this, 200, 30, '60%'); return true;"/><br/>
        <xsl:apply-templates/>
    </div>
    
    <div id="popup" noWrap="1" class="pup">
        Working...
    </div>
    
    </body>
    

    </html>
  </xsl:template>

  <!-- par(ent) lev(el) and cur(rent) lev(el): '001', '002', '002001', ... -->
  
  <xsl:template match="*"><xsl:param name="parlev" select="''"/>
    <xsl:variable name="tmplev" select="concat('00', position())"/>
    <xsl:variable name="curlev" select="concat($parlev, substring($tmplev, string-length($tmplev) - 2))"/>
    <div noWrap="1" class="dvc">
        <xsl:attribute name="level"><xsl:value-of select="$curlev"/></xsl:attribute>
        <xsl:if test="*">
            <input type="button" class="btn" value="+" onClick="openOrClose(this);"/>
        </xsl:if>
        <xsl:if test="not(*)">
            <input type="button" class="lfn" value=" "/>
        </xsl:if>
        &#xa0;
        <span class="lbl" onClick="spanClick(this, event);">
            <xsl:if test="@postit">
                <xsl:attribute name="title"><xsl:value-of select="@postit"/></xsl:attribute>
            </xsl:if>
            <xsl:value-of select="translate(name(), '_', ',')"/>
        </span>
        <xsl:if test="@comment">
            <i><xsl:value-of select="@comment"/></i>
        </xsl:if>
        <br/>
        <xsl:apply-templates>
            <xsl:with-param name="parlev" select="$curlev"/>
        </xsl:apply-templates>
        </div>
  </xsl:template>

</xsl:stylesheet>

<!--
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

  -->