// md_script.js                               <!-- for Dokeos metadata/*.php -->
//                                                           <!-- 2006/05/16 -->

//   Copyright (C) 2006 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->


// Part 1: General funcs & Keyword Tree: copied (with modifs) from SelKwds.xsl

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
                    'Use FireFox or IE6 or Moz1.7 or type in keywords manually...');
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

        function copyToClipboard(allKwds)  // md_script: not used
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

            // md_script: the calling HTML should define var kwdswere!
            // in SelKwds.xsl, they are   typed in by user or fetched from PPT

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
            // no copyToClipboard(allKwds);

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

        function spanClick(span, ev)  // md_script: no parents in search
        {
            var mda = getObj("mda");    if (!ev) ev = window.event;

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
                selectOrDeselect(span, "lbs");  // select (search: no parents)
                if (mda && !ev.altKey) alsoParents(span.parentNode, "lbl", "lbs");
                if (!mda && ev.altKey)  // mda does not exist in search form
                    if (span.innerHTML.substr(0, 1) == '-')
                         span.innerHTML = (', ' + span.innerHTML).replace(/, -/g, ", ").substr(2);
                    else span.innerHTML = (', ' + span.innerHTML).replace(/, /g, ", -").substr(2);
            }

            var allKwds = getSelectedKeywords(); // no copyToClipboard(allKwds);
            document.getElementById('kwds_string').value = allKwds;
        }


        var KWDS_ARRAY = new Array, nkw = 0, pU;  // alphabetic list popup

        function makeAlphaList(div)  // md_script: not used (hopefully)
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

                if (!KWDS_ARRAY.length)
                {
                    makeAlphaList(document.getElementById('maindiv'));
                    KWDS_ARRAY.sort();
                }
            }

            if (!(curValue = kws.value.toLowerCase())) return;

            var kwLines = '';

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

            kws.value = getSelectedKeywords(); // no copyToClipboard(kws.value);

            pU_hide();
        }


// Part 2: Metadata Updates: W3C, IE4 and NS4 browsers

        function isValidChar(ev, pattern, flags)
        {
            // e.g. onKeyPress="return isValidChar(event, '[a-z]', 'i');"

            if (!ev) var ev = window.event;                 // PPK, see below
            var kc = (ev.keyCode) ? ev.keyCode : ev.which;  // PPK
            return (new RegExp(pattern, flags)).test(String.fromCharCode(kc));

            // PPK= Peter-Paul Koch, www.quirksmode.org
        }

        function checkValid(inputField, pattern, flags)
        {
            // e.g. onKeyUp="checkValid(this, '^[a-z]{2,8}$', 'i');"

            var fieldColor = (new RegExp(pattern, flags)).test(inputField.value) ? '#000000' : '#D8366C';
            var fieldStyle = (document.getElementById || document.all) ?
                inputField.style : inputField;
            if (fieldStyle) fieldStyle.color = fieldColor;

            // OK for all browsers (see devedge.netscape.com
            //          /library/xref/2003/css-support/css1/mastergrid.html):
            // color, background-color (not on NN4), display block/none (NN4?),
            // overflow hidden/scroll/auto (not on NN4),
            // position relative/static,
            // text-align left/right/center, text-indent,
            // font-style normal/italic, font-weight normal/bold,
            // font-family serif/sans-serif/monospace,
            // border-style none/solid/double/groove/ridge/inset/outset.
        }

        function getObj(name)  // PPK
        {
            return (document.getElementById) ? document.getElementById(name)
            : (document.all) ?                 document.all[name]        // IE4
            : (document.layers) ?              document.layers[name]     // NS4
            : null;  // With NS4, nested layers are not supported!
        }

        function spc(path, value)  // set pending change in form field mda
        {
            var mda = getObj("mda");
            if (mda) mda.value += "\n" + path + '=' + value;
        }

        function spcSel(path, selbox)  // set pending change, language selection
        {
            var mda = getObj("mda");
            if (mda) mda.value += "\n" + path + '=' +
                selbox.options[selbox.selectedIndex].value;
        }

        function checkBeforeSubmit(ev)
        {
            if (!ev) var ev = window.event;

            if (ev.ctrlKey && ev.altKey)
            {
                var mdt = getObj("mdt"); if (!mdt) return false;

                makeWindow('', '<pre>' +
                    ('<?xml version="1.0" encoding="UTF-16"?>' +
                     '  <!-- From browser, save as Text/Unicode -->\n\n' +
                     mdt.value)
                        .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                        .replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                    '</pre>');
                return false;
            }

            var kwdsnow = getObj("kwds_string"); if (!kwdsnow) return true;
            if (kwdsnow.value == kwdswere) return true;  // unchanged
            // note: calling HTML should define var kwdswere!

            var language = kwdsnow.title;

            var mda = getObj("mda");
            if (!mda) { alert('? Form does not contain mda'); return false; }

            var kwdsarray = kwdswere.split(',');

            for (var k = 0; k < kwdsarray.length; k++)  // delete old
                if (kwdsarray[k].trim() != '')
                    mda.value += "\nmetadata/lom/general/keyword[-1]~";

            kwdsarray = kwdsnow.value
                .replace(/[!-,:-@\[-\^{-~\s]+/g, ',').split(',');

            for (k = 0; k < kwdsarray.length; k++)
            {
                var newkw = kwdsarray[k].trim();
                if (newkw != '') mda.value +=
                    "\nmetadata/lom/general!keyword" +
                    "\nmetadata/lom/general/keyword[-1]!string=" + newkw +
                    "\nmetadata/lom/general/keyword[-1]/string/@language=" + language;
            }

            return true;

        }

        function setPendingOperation(op, ev)
        {
            if (!ev) var ev = window.event;

            var mda = getObj("mda");
            if (!mda) { alert('? Form does not contain mda'); return false; }

            if (op == '!!' || (op == '~~' && confirm(mda.title)))
            {
                mda.value = op; return true;
            }

            return false;
        }

        function prepSearch(ev)
        {
            if (!ev) var ev = window.event;

            var mdsc = getObj("mdsc");
            if (!mdsc) { alert('? Form does not contain mdsc'); return false; }

            var kwdsnow = getObj("kwds_string"); if (!kwdsnow) return true;
            if (kwdsnow.value == '') return true;

            if (!KWDS_ARRAY.length)
            {
                makeAlphaList(getObj('maindiv'));
                KWDS_ARRAY.sort();
            }

            var restricttokwds = false, checkbox = getObj("restricttokwds");
            if (checkbox) restricttokwds = checkbox.checked;

            var kwdsarray = kwdsnow.value
                .replace(/[!-,:-@\[-\^{-~\s]+/g, ',').split(',');

            for (var k = 0; k < kwdsarray.length; k++)
            {
                var newkw = kwdsarray[k].trim().toLowerCase();

                if (newkw != '')
                {
                    var realkw = false;

                    if (restricttokwds)
                        for (pos = 0; pos < KWDS_ARRAY.length; pos++)
                            if (KWDS_ARRAY[pos].toLowerCase() == newkw)
                                { realkw = true; break; }
                    mdsc.value += "\n" + newkw + (realkw ? '-kw' : '');

                }
            }

            return true;
        }

        var CRLF = "\n";  // generates clickable tree and populates KWDS_ARRAY

        function traverseKwObj(node, parlev, num)  // see KWTREE_OBJECT in md_funcs
        {
            var curlev = '00' + (num+1), kwn = '', html = '';
            curlev = parlev + curlev.substr(curlev.length-3);

            for (i in (names = node.n.split("_")))
                if (nn = names[i]) { KWDS_ARRAY.push(nn); kwn += ', ' + nn; }

            for (j in node.c) html += traverseKwObj(node.c[j], curlev, Math.abs(j));

            return (parlev == '') ? html :
                '<div noWrap="1" class="dvc" level="' + curlev + '">' + CRLF +
                    '<input type="button" class="' +
                        (html ? 'btn" value="+" onClick="openOrClose(this);"/>' :
                                'lfn" value=" "/>') + '&#xa0;' + CRLF +
                    '<span class="lbl" onClick="spanClick(this, event);"' +
                        (node.pt ? ' title="' + node.pt + '">' : '>') +
                        kwn.substr(2) + '</span>' + CRLF +
                    (node.cm ? '<i>' + node.cm + '</i>' : '') +
                    html +
                '</div>' + CRLF;
        }


// The End