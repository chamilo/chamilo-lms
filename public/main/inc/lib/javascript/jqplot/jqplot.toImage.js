/**
 * jqPlot
 * Pure JavaScript plotting plugin using jQuery
 *
 * Version: @VERSION
 * Revision: @REVISION
 *
 * Copyright (c) 2009-2013 Chris Leonello
 * jqPlot is currently available for use in all personal or commercial projects 
 * under both the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL 
 * version 2.0 (http://www.gnu.org/licenses/gpl-2.0.html) licenses. This means that you can 
 * choose the license that best suits your project and use it accordingly. 
 *
 * Although not required, the author would appreciate an email letting him 
 * know of any substantial use of jqPlot.  You can reach the author at: 
 * chris at jqplot dot com or see http://www.jqplot.com/info.php .
 *
 * If you are feeling kind and generous, consider supporting the project by
 * making a donation at: http://www.jqplot.com/donate.php .
 *
 * sprintf functions contained in jqplot.sprintf.js by Ash Searle:
 *
 *     version 2007.04.27
 *     author Ash Searle
 *     http://hexmen.com/blog/2007/03/printf-sprintf/
 *     http://hexmen.com/js/sprintf.js
 *     The author (Ash Searle) has placed this code in the public domain:
 *     "This code is unrestricted: you are free to use it however you like."
 *
 * jsDate library by Chris Leonello:
 *
 * Copyright (c) 2010-2013 Chris Leonello
 *
 * jsDate is currently available for use in all personal or commercial projects 
 * under both the MIT and GPL version 2.0 licenses. This means that you can 
 * choose the license that best suits your project and use it accordingly.
 *
 * jsDate borrows many concepts and ideas from the Date Instance 
 * Methods by Ken Snyder along with some parts of Ken's actual code.
 * Ken has generously given permission to adapt his code and release
 * under the MIT and GPL V2 licenses.
 * 
 * Ken's original Date Instance Methods and copyright notice:
 * 
 * Ken Snyder (ken d snyder at gmail dot com)
 * 2008-09-10
 * version 2.0.2 (http://kendsnyder.com/sandbox/date/)     
 * Creative Commons Attribution License 3.0 (http://creativecommons.org/licenses/by/3.0/)
 *
 * jqplotToImage function based on Larry Siden's export-jqplot-to-png.js.
 * Larry has generously given permission to adapt his code for inclusion
 * into jqPlot.
 *
 * Larry's original code can be found here:
 *
 * https://github.com/lsiden/export-jqplot-to-png
 * 
 * 
 */

(function($) {

    $.fn.jqplotChildText = function() {
        return $(this).contents().filter(function() {
            return this.nodeType == 3;  // Node.TEXT_NODE not defined in I7
        }).text();
    };

    // Returns font style as abbreviation for "font" property.
    $.fn.jqplotGetComputedFontStyle = function() {
        var css = window.getComputedStyle ?  window.getComputedStyle(this[0], "") : this[0].currentStyle;
        var attrs = css['font-style'] ? ['font-style', 'font-weight', 'font-size', 'font-family'] : ['fontStyle', 'fontWeight', 'fontSize', 'fontFamily'];
        var style = [];

        for (var i=0 ; i < attrs.length; ++i) {
            var attr = String(css[attrs[i]]);

            if (attr && attr != 'normal') {
                style.push(attr);
            }
        }
        return style.join(' ');
    };

    /**
     * Namespace: $.fn
     * jQuery namespace to attach functions to jQuery elements.
     *  
     */

    $.fn.jqplotToImageCanvas = function(options) {

        options = options || {};
        var x_offset = (options.x_offset == null) ? 0 : options.x_offset;
        var y_offset = (options.y_offset == null) ? 0 : options.y_offset;
        var backgroundColor = (options.backgroundColor == null) ? 'rgb(255,255,255)' : options.backgroundColor;

        if ($(this).width() == 0 || $(this).height() == 0) {
            return null;
        }

        // excanvas and hence IE < 9 do not support toDataURL and cannot export images.
        if ($.jqplot.use_excanvas) {
            return null;
        }
        
        var newCanvas = document.createElement("canvas");
        var h = $(this).outerHeight(true);
        var w = $(this).outerWidth(true);
        var offs = $(this).offset();
        var plotleft = offs.left;
        var plottop = offs.top;
        var transx = 0, transy = 0;

        // have to check if any elements are hanging outside of plot area before rendering,
        // since changing width of canvas will erase canvas.

        var clses = ['jqplot-table-legend', 'jqplot-xaxis-tick', 'jqplot-x2axis-tick', 'jqplot-yaxis-tick', 'jqplot-y2axis-tick', 'jqplot-y3axis-tick', 
        'jqplot-y4axis-tick', 'jqplot-y5axis-tick', 'jqplot-y6axis-tick', 'jqplot-y7axis-tick', 'jqplot-y8axis-tick', 'jqplot-y9axis-tick',
        'jqplot-xaxis-label', 'jqplot-x2axis-label', 'jqplot-yaxis-label', 'jqplot-y2axis-label', 'jqplot-y3axis-label', 'jqplot-y4axis-label', 
        'jqplot-y5axis-label', 'jqplot-y6axis-label', 'jqplot-y7axis-label', 'jqplot-y8axis-label', 'jqplot-y9axis-label' ];

        var temptop, templeft, tempbottom, tempright;

        for (var i = 0; i < clses.length; i++) {
            $(this).find('.'+clses[i]).each(function() {
                temptop = $(this).offset().top - plottop;
                templeft = $(this).offset().left - plotleft;
                tempright = templeft + $(this).outerWidth(true) + transx;
                tempbottom = temptop + $(this).outerHeight(true) + transy;
                if (templeft < -transx) {
                    w = w - transx - templeft;
                    transx = -templeft;
                }
                if (temptop < -transy) {
                    h = h - transy - temptop;
                    transy = - temptop;
                }
                if (tempright > w) {
                    w = tempright;
                }
                if (tempbottom > h) {
                    h =  tempbottom;
                }
            });
        }

        newCanvas.width = w + Number(x_offset);
        newCanvas.height = h + Number(y_offset);

        var newContext = newCanvas.getContext("2d"); 

        newContext.save();
        newContext.fillStyle = backgroundColor;
        newContext.fillRect(0,0, newCanvas.width, newCanvas.height);
        newContext.restore();

        newContext.translate(transx, transy);
        newContext.textAlign = 'left';
        newContext.textBaseline = 'top';

        function getLineheight(el) {
            var lineheight = parseInt($(el).css('line-height'), 10);

            if (isNaN(lineheight)) {
                lineheight = parseInt($(el).css('font-size'), 10) * 1.2;
            }
            return lineheight;
        }

        function writeWrappedText (el, context, text, left, top, canvasWidth) {
            var lineheight = getLineheight(el);
            var tagwidth = $(el).innerWidth();
            var tagheight = $(el).innerHeight();
            var words = text.split(/\s+/);
            var wl = words.length;
            var w = '';
            var breaks = [];
            var temptop = top;
            var templeft = left;

            for (var i=0; i<wl; i++) {
                w += words[i];
                if (context.measureText(w).width > tagwidth) {
                    breaks.push(i);
                    w = '';
                    i--;
                }   
            }
            if (breaks.length === 0) {
                // center text if necessary
                if ($(el).css('textAlign') === 'center') {
                    templeft = left + (canvasWidth - context.measureText(w).width)/2  - transx;
                }
                context.fillText(text, templeft, top);
            }
            else {
                w = words.slice(0, breaks[0]).join(' ');
                // center text if necessary
                if ($(el).css('textAlign') === 'center') {
                    templeft = left + (canvasWidth - context.measureText(w).width)/2  - transx;
                }
                context.fillText(w, templeft, temptop);
                temptop += lineheight;
                for (var i=1, l=breaks.length; i<l; i++) {
                    w = words.slice(breaks[i-1], breaks[i]).join(' ');
                    // center text if necessary
                    if ($(el).css('textAlign') === 'center') {
                        templeft = left + (canvasWidth - context.measureText(w).width)/2  - transx;
                    }
                    context.fillText(w, templeft, temptop);
                    temptop += lineheight;
                }
                w = words.slice(breaks[i-1], words.length).join(' ');
                // center text if necessary
                if ($(el).css('textAlign') === 'center') {
                    templeft = left + (canvasWidth - context.measureText(w).width)/2  - transx;
                }
                context.fillText(w, templeft, temptop);
            }

        }

        function _jqpToImage(el, x_offset, y_offset) {
            var tagname = el.tagName.toLowerCase();
            var p = $(el).position();
            var css = window.getComputedStyle ?  window.getComputedStyle(el, "") : el.currentStyle; // for IE < 9
            var left = x_offset + p.left + parseInt(css.marginLeft, 10) + parseInt(css.borderLeftWidth, 10) + parseInt(css.paddingLeft, 10);
            var top = y_offset + p.top + parseInt(css.marginTop, 10) + parseInt(css.borderTopWidth, 10)+ parseInt(css.paddingTop, 10);
            var w = newCanvas.width;
            // var left = x_offset + p.left + $(el).css('marginLeft') + $(el).css('borderLeftWidth') 

            // somehow in here, for divs within divs, the width of the inner div should be used instead of the canvas.

            if ((tagname == 'div' || tagname == 'span') && !$(el).hasClass('jqplot-highlighter-tooltip')) {
                $(el).children().each(function() {
                    _jqpToImage(this, left, top);
                });
                var text = $(el).jqplotChildText();

                if (text) {
                    newContext.font = $(el).jqplotGetComputedFontStyle();
                    newContext.fillStyle = $(el).css('color');

                    writeWrappedText(el, newContext, text, left, top, w);
                }
            }

            // handle the standard table legend

            else if (tagname === 'table' && $(el).hasClass('jqplot-table-legend')) {
                newContext.strokeStyle = $(el).css('border-top-color');
                newContext.fillStyle = $(el).css('background-color');
                newContext.fillRect(left, top, $(el).innerWidth(), $(el).innerHeight());
                if (parseInt($(el).css('border-top-width'), 10) > 0) {
                    newContext.strokeRect(left, top, $(el).innerWidth(), $(el).innerHeight());
                }

                // find all the swatches
                $(el).find('div.jqplot-table-legend-swatch-outline').each(function() {
                    // get the first div and stroke it
                    var elem = $(this);
                    newContext.strokeStyle = elem.css('border-top-color');
                    var l = left + elem.position().left;
                    var t = top + elem.position().top;
                    newContext.strokeRect(l, t, elem.innerWidth(), elem.innerHeight());

                    // now fill the swatch
                    
                    l += parseInt(elem.css('padding-left'), 10);
                    t += parseInt(elem.css('padding-top'), 10);
                    var h = elem.innerHeight() - 2 * parseInt(elem.css('padding-top'), 10);
                    var w = elem.innerWidth() - 2 * parseInt(elem.css('padding-left'), 10);

                    var swatch = elem.children('div.jqplot-table-legend-swatch');
                    newContext.fillStyle = swatch.css('background-color');
                    newContext.fillRect(l, t, w, h);
                });

                // now add text

                $(el).find('td.jqplot-table-legend-label').each(function(){
                    var elem = $(this);
                    var l = left + elem.position().left;
                    var t = top + elem.position().top + parseInt(elem.css('padding-top'), 10);
                    newContext.font = elem.jqplotGetComputedFontStyle();
                    newContext.fillStyle = elem.css('color');
                    writeWrappedText(elem, newContext, elem.text(), l, t, w);
                });

                var elem = null;
            }

            else if (tagname == 'canvas') {
                newContext.drawImage(el, left, top);
            }
        }
        $(this).children().each(function() {
            _jqpToImage(this, x_offset, y_offset);
        });
        return newCanvas;
    };

    // return the raw image data string.
    // Should work on canvas supporting browsers.
    $.fn.jqplotToImageStr = function(options) {
        var imgCanvas = $(this).jqplotToImageCanvas(options);
        if (imgCanvas) {
            return imgCanvas.toDataURL("image/png");
        }
        else {
            return null;
        }
    };

    // return a DOM <img> element and return it.
    // Should work on canvas supporting browsers.
    $.fn.jqplotToImageElem = function(options) {
        var elem = document.createElement("img");
        var str = $(this).jqplotToImageStr(options);
        elem.src = str;
        return elem;
    };

    // return a string for an <img> element and return it.
    // Should work on canvas supporting browsers.
    $.fn.jqplotToImageElemStr = function(options) {
        var str = '<img src='+$(this).jqplotToImageStr(options)+' />';
        return str;
    };

    // Not guaranteed to work, even on canvas supporting browsers due to 
    // limitations with location.href and browser support.
    $.fn.jqplotSaveImage = function() {
        var imgData = $(this).jqplotToImageStr({});
        if (imgData) {
            window.location.href = imgData.replace("image/png", "image/octet-stream");
        }

    };

    // Not guaranteed to work, even on canvas supporting browsers due to
    // limitations with window.open and arbitrary data.
    $.fn.jqplotViewImage = function() {
        var imgStr = $(this).jqplotToImageElemStr({});
        var imgData = $(this).jqplotToImageStr({});
        if (imgStr) {
            var w = window.open('');
            w.document.open("image/png");
            w.document.write(imgStr);
            w.document.close();
            w = null;
        }
    };
    
})(jQuery);
