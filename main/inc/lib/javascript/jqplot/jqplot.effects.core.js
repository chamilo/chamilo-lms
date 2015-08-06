/*
 * jQuery UI Effects 1.9pre
 *
 * Copyright 2011, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/
 */

 //////
 // Much reduced version of jquery ui effects core used
 // for series animation in jqplot.
 //////
(function($) {

    var backCompat = $.uiBackCompat !== false;

    $.jqplot.effects = {
        effect: {}
    };

    // prefix used for storing data on .data()
    var dataSpace = "jqplot.storage.";

    /******************************************************************************/
    /*********************************** EFFECTS **********************************/
    /******************************************************************************/

    $.extend( $.jqplot.effects, {
        version: "1.9pre",

        // Saves a set of properties in a data storage
        save: function( element, set ) {
            for( var i=0; i < set.length; i++ ) {
                if ( set[ i ] !== null ) {
                    element.data( dataSpace + set[ i ], element[ 0 ].style[ set[ i ] ] );
                }
            }
        },

        // Restores a set of previously saved properties from a data storage
        restore: function( element, set ) {
            for( var i=0; i < set.length; i++ ) {
                if ( set[ i ] !== null ) {
                    element.css( set[ i ], element.data( dataSpace + set[ i ] ) );
                }
            }
        },

        setMode: function( el, mode ) {
            if (mode === "toggle") {
                mode = el.is( ":hidden" ) ? "show" : "hide";
            }
            return mode;
        },

        // Wraps the element around a wrapper that copies position properties
        createWrapper: function( element ) {

            // if the element is already wrapped, return it
            if ( element.parent().is( ".ui-effects-wrapper" )) {
                return element.parent();
            }

            // wrap the element
            var props = {
                    width: element.outerWidth(true),
                    height: element.outerHeight(true),
                    "float": element.css( "float" )
                },
                wrapper = $( "<div></div>" )
                    .addClass( "ui-effects-wrapper" )
                    .css({
                        fontSize: "100%",
                        background: "transparent",
                        border: "none",
                        margin: 0,
                        padding: 0
                    }),
                // Store the size in case width/height are defined in % - Fixes #5245
                size = {
                    width: element.width(),
                    height: element.height()
                },
                active = document.activeElement;

            element.wrap( wrapper );

            // Fixes #7595 - Elements lose focus when wrapped.
            if ( element[ 0 ] === active || $.contains( element[ 0 ], active ) ) {
                $( active ).focus();
            }

            wrapper = element.parent(); //Hotfix for jQuery 1.4 since some change in wrap() seems to actually loose the reference to the wrapped element

            // transfer positioning properties to the wrapper
            if ( element.css( "position" ) === "static" ) {
                wrapper.css({ position: "relative" });
                element.css({ position: "relative" });
            } else {
                $.extend( props, {
                    position: element.css( "position" ),
                    zIndex: element.css( "z-index" )
                });
                $.each([ "top", "left", "bottom", "right" ], function(i, pos) {
                    props[ pos ] = element.css( pos );
                    if ( isNaN( parseInt( props[ pos ], 10 ) ) ) {
                        props[ pos ] = "auto";
                    }
                });
                element.css({
                    position: "relative",
                    top: 0,
                    left: 0,
                    right: "auto",
                    bottom: "auto"
                });
            }
            element.css(size);

            return wrapper.css( props ).show();
        },

        removeWrapper: function( element ) {
            var active = document.activeElement;

            if ( element.parent().is( ".ui-effects-wrapper" ) ) {
                element.parent().replaceWith( element );

                // Fixes #7595 - Elements lose focus when wrapped.
                if ( element[ 0 ] === active || $.contains( element[ 0 ], active ) ) {
                    $( active ).focus();
                }
            }


            return element;
        }
    });

    // return an effect options object for the given parameters:
    function _normalizeArguments( effect, options, speed, callback ) {

        // short path for passing an effect options object:
        if ( $.isPlainObject( effect ) ) {
            return effect;
        }

        // convert to an object
        effect = { effect: effect };

        // catch (effect)
        if ( options === undefined ) {
            options = {};
        }

        // catch (effect, callback)
        if ( $.isFunction( options ) ) {
            callback = options;
            speed = null;
            options = {};
        }

        // catch (effect, speed, ?)
        if ( $.type( options ) === "number" || $.fx.speeds[ options ]) {
            callback = speed;
            speed = options;
            options = {};
        }

        // catch (effect, options, callback)
        if ( $.isFunction( speed ) ) {
            callback = speed;
            speed = null;
        }

        // add options to effect
        if ( options ) {
            $.extend( effect, options );
        }

        speed = speed || options.duration;
        effect.duration = $.fx.off ? 0 : typeof speed === "number"
            ? speed : speed in $.fx.speeds ? $.fx.speeds[ speed ] : $.fx.speeds._default;

        effect.complete = callback || options.complete;

        return effect;
    }

    function standardSpeed( speed ) {
        // valid standard speeds
        if ( !speed || typeof speed === "number" || $.fx.speeds[ speed ] ) {
            return true;
        }

        // invalid strings - treat as "normal" speed
        if ( typeof speed === "string" && !$.jqplot.effects.effect[ speed ] ) {
            // TODO: remove in 2.0 (#7115)
            if ( backCompat && $.jqplot.effects[ speed ] ) {
                return false;
            }
            return true;
        }

        return false;
    }

    $.fn.extend({
        jqplotEffect: function( effect, options, speed, callback ) {
            var args = _normalizeArguments.apply( this, arguments ),
                mode = args.mode,
                queue = args.queue,
                effectMethod = $.jqplot.effects.effect[ args.effect ],

                // DEPRECATED: remove in 2.0 (#7115)
                oldEffectMethod = !effectMethod && backCompat && $.jqplot.effects[ args.effect ];

            if ( $.fx.off || !( effectMethod || oldEffectMethod ) ) {
                // delegate to the original method (e.g., .show()) if possible
                if ( mode ) {
                    return this[ mode ]( args.duration, args.complete );
                } else {
                    return this.each( function() {
                        if ( args.complete ) {
                            args.complete.call( this );
                        }
                    });
                }
            }

            function run( next ) {
                var elem = $( this ),
                    complete = args.complete,
                    mode = args.mode;

                function done() {
                    if ( $.isFunction( complete ) ) {
                        complete.call( elem[0] );
                    }
                    if ( $.isFunction( next ) ) {
                        next();
                    }
                }

                // if the element is hiddden and mode is hide,
                // or element is visible and mode is show
                if ( elem.is( ":hidden" ) ? mode === "hide" : mode === "show" ) {
                    done();
                } else {
                    effectMethod.call( elem[0], args, done );
                }
            }

            // TODO: remove this check in 2.0, effectMethod will always be true
            if ( effectMethod ) {
                return queue === false ? this.each( run ) : this.queue( queue || "fx", run );
            } else {
                // DEPRECATED: remove in 2.0 (#7115)
                return oldEffectMethod.call(this, {
                    options: args,
                    duration: args.duration,
                    callback: args.complete,
                    mode: args.mode
                });
            }
        }
    });

})(jQuery);
