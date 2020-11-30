/*global kampfer*/
kampfer.provide('dom');

kampfer.dom.create = function(name) {
    return kampfer.global.document.createElement(name);
};

kampfer.dom.addClass = function(elem, value) {

    var classNames, i, l,
        setClass, c, cl;

    if ( value && typeof value === "string" ) {
        classNames = value.split( /\s+/ );

        if ( elem.nodeType === 1 ) {
            if ( !elem.className && classNames.length === 1 ) {
                elem.className = value;

            } else {
                setClass = " " + elem.className + " ";

                for ( c = 0, cl = classNames.length; c < cl; c++ ) {
                    if ( !~setClass.indexOf( " " + classNames[ c ] + " " ) ) {
                        setClass += classNames[ c ] + " ";
                    }
                }
                elem.className = setClass.trim();
            }
        }
    }

};


kampfer.dom.removeClass = function(elem, value) {
    
    var classNames, i, l, className, c, cl;
    
    if ( (value && typeof value === "string") || value === undefined ) {
        classNames = ( value || "" ).split( /\s+/ );
    
        if ( elem.nodeType === 1 && elem.className ) {
            if ( value ) {
                className = (" " + elem.className + " ").replace( /[\n\t\r]/g, " " );
                for ( c = 0, cl = classNames.length; c < cl; c++ ) {
                    className = className.replace(" " + classNames[ c ] + " ", " ");
                }
                //elem.className = kampfer.string.trim( className );
                elem.className = className.trim();
            } else {
                elem.className = "";
            }
        }
    }
};


kampfer.dom.getComputedStyle = function(element, property) {
    var doc = kampfer.global.document;
    if(doc.defaultView && doc.defaultView.getComputedStyle) {
        var styles = doc.defaultView.getComputedStyle(element, null);
        if(styles) {
            // element.style[..] is undefined for browser specific styles
            // as 'filter'.
            return styles[property] || styles.getPropertyValue(property);
        }
    }
    return '';
};


//for IE
kampfer.dom.getCascadedStyle = function(element, style) {
    return element.currentStyle ? element.currentStyle[style] : null;
};


kampfer.dom.getStyle = function(element, style) {
    return kampfer.dom.getComputedStyle(element, style) ||
        kampfer.dom.getCascadedStyle(element, style) ||
        element.style[style];
};


//需要输入正确的javascript格式名
//TODO 处理样式名
kampfer.dom.setStyle = function(element, name, value) {
    if( kampfer.isDefAndNotNull(value) &&
        kampfer.type(name) === 'string' ) {
        element.style[name] = value;
    } else if( kampfer.isObject(name) ) {
        for(var attr in name) {
            kampfer.dom.setStyle(element, attr, name[attr]);
        }
    }
};

// http://code.jquery.com/jquery-1.8.3.js
kampfer.dom.getWindow = function(elem) {
    return kampfer.isWindow(elem) ?
        elem :
        elem.nodeType === 9 ?
            elem.defaultView || elem.parentWindow :
            false;
};


// http://code.jquery.com/jquery-1.8.3.js
kampfer.dom.scrollLeft = function(elem, val) {
    var win = kampfer.dom.getWindow( elem ),
        prop = 'pageXOffset',
        method = 'scrollLeft';

    if ( val === undefined ) {
        return win ? (prop in win) ? win[ prop ] :
            win.document.documentElement[ method ] :
            elem[ method ];
    }

    if ( win ) {
        win.scrollTo( val, kampfer.dom.scrollTop( win ));
    } else {
        elem[ method ] = val;
    }
};

// http://code.jquery.com/jquery-1.8.3.js
kampfer.dom.scrollTop = function(elem, val) {
    var win = kampfer.dom.getWindow( elem ),
        prop = 'pageYOffset',
        method = 'scrollTop';

    if ( val === undefined ) {
        return win ? (prop in win) ? win[ prop ] :
            win.document.documentElement[ method ] :
            elem[ method ];
    }

    if ( win ) {
        win.scrollTo( kampfer.dom.scrollLeft( win ), val);
    } else {
        elem[ method ] = val;
    }
};


//http://www.cnblogs.com/rubylouvre/archive/2011/05/30/1583523.html
kampfer.dom.contains = function(parent, child) {
    if(parent.compareDocumentPosition) {
        return parent === child || !!(parent.compareDocumentPosition(child) & 16);
    }

    if(parent.contains && child.nodeType === 1) {
        return parent.contains(child) && parent !== child;
    }

    while( (child = child.parentNode) ) {
        if(child === parent) {
            return true;
        }
    }

    return false;
};