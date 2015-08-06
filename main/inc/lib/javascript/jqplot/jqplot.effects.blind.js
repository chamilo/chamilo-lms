/*
 * jQuery UI Effects Blind 1.9pre
 *
 * Copyright 2011, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Blind
 *
 * Depends:
 *  jquery.effects.core.js
 */

 //////
 // jquery ui blind effect used for series animation in jqplot.
 //////
(function($) {

    var rvertical = /up|down|vertical/,
        rpositivemotion = /up|left|vertical|horizontal/;

    $.jqplot.effects.effect.blind = function( o, done ) {
        // Create element
        var el = $( this ),
            props = [ "position", "top", "bottom", "left", "right", "height", "width" ],
            mode = $.jqplot.effects.setMode( el, o.mode || "hide" ),
            direction = o.direction || "up",
            vertical = rvertical.test( direction ),
            ref = vertical ? "height" : "width",
            ref2 = vertical ? "top" : "left",
            motion = rpositivemotion.test( direction ),
            animation = {},
            show = mode === "show",
            wrapper, distance, top;

        // // if already wrapped, the wrapper's properties are my property. #6245
        if ( el.parent().is( ".ui-effects-wrapper" ) ) {
            $.jqplot.effects.save( el.parent(), props );
        } else {
            $.jqplot.effects.save( el, props );
        }
        el.show();
        top = parseInt(el.css('top'), 10);
        wrapper = $.jqplot.effects.createWrapper( el ).css({
            overflow: "hidden"
        });

        distance = vertical ? wrapper[ ref ]() + top : wrapper[ ref ]();

        animation[ ref ] = show ? String(distance) : '0';
        if ( !motion ) {
            el
                .css( vertical ? "bottom" : "right", 0 )
                .css( vertical ? "top" : "left", "" )
                .css({ position: "absolute" });
            animation[ ref2 ] = show ? '0' : String(distance);
        }

        // // start at 0 if we are showing
        if ( show ) {
            wrapper.css( ref, 0 );
            if ( ! motion ) {
                wrapper.css( ref2, distance );
            }
        }

        // // Animate
        wrapper.animate( animation, {
            duration: o.duration,
            easing: o.easing,
            queue: false,
            complete: function() {
                if ( mode === "hide" ) {
                    el.hide();
                }
                $.jqplot.effects.restore( el, props );
                $.jqplot.effects.removeWrapper( el );
                done();
            }
        });

    };

})(jQuery);
