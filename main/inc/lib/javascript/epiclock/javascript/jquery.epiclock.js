/*!
 *  epiClock 3.0
 *
 *  Copyright (c) 2008 Eric Garside
 *  Dual licensed under:
 *      MIT: http://www.opensource.org/licenses/mit-license.php
 *      GPLv3: http://www.opensource.org/licenses/gpl-3.0.html
 */

"use strict";

/*global Class, window, jQuery */

/*jslint white: true, browser: true, onevar: true, undef: true, eqeqeq: true, bitwise: true, regexp: true, strict: true, newcap: true, immed: true, maxerr: 50, indent: 4 */

/**
 *  This plugin requires the jquery.dateformat plugin, which is included alongside this 
 *  script, and is packed into the minified version.
 */
(function ($) {
    
    //------------------------------
    //
    //  Constants
    //
    //------------------------------
    
        /**
         *  Clock modes for epiclock. As of epiclock 3.0, users can add: "epiclock epiclock-<mode>" classes
         *  to an element to automate the setting of a clock on the page.
         */
    var mode = 
        {
            /**
             *  Default clock type. Renders a clock which displays the current time, based
             *  on the client's clock.
             */
            clock: 'clock',
            
            /**
             *  Renders a clock which displays the current time, based on the datetime value
             *  passed to the clock. Allows for some server to explicitly set the starting value
             *  of the clock (to show the proper time, regardless of the value of the client's clock).
             */
            explicit: 'explicit',
            
            /**
             *  Countdown clock, displaying the time remaining until a certain datetime. Expires
             *  when the countdown finishes.
             */
            countdown: 'countdown',
            
            /**
             *  Countup clock, displaying the time since a certain datetime. If the datetime has not
             *  yet arrived, the clock will display negative numbers.
             */
            countup: 'countup',
            
            /**
             *  Exactly like the "countdown" clock, except on expiry, the clock will become a
             *  "countup" clock.
             */
            rollover: 'rollover',
            
            /**
             *  A clock which displays the number of remaining seconds until the expiry.
             */
            expire: 'expire',
            
            /**
             *  Similar to the "expire" clock, only it will refresh the duration when the expiry hits. Also, this clock
             *  tares on pause.
             */
            loop: 'loop',
            
            /**
             *  A counter which displays the number of seconds since the clock was started. Tares any
             *  time when paused. (if the clock is at 10 and is paused for 3 seconds, when resumed, it will
             *  be at 10 seconds).
             */
            stopwatch: 'stopwatch',
            
            /**
             *  A countup clock, displaying the time since a certain datetime. If the datetime has not
             *  yet arrived, the clock will display all zeroes. 
             */
            holdup: 'holdup',
            
            /**
             *	A countdown clock displaying the remaining time on a timer. Similar to the expire counter, except it
             *  tares on pause, like the stopwatch clock.
             */
            timer: 'timer'
        },
    
    //------------------------------
    //
    //  Property Declaration
    //
    //------------------------------
    
        /**
         *  Create a placeholder object for the epiclock manager.
         *
         *  @param selector The selector to grab an epiclock object for.
         *
         *  @return The epiclock instance for the given selector, or null.
         */
        _ = function (selector)
        {
            return $(selector).data('epiclock');
        },
        
        /**
         *  A collection of listeners for clock events.
         *
         *  Signature:
         *  {
         *      <clockInstance>:
         *      {
         *          <eventType>: [listener, ...],
         *
         *          ...,
         *      },
         *
         *      ...
         *  }
         */
        events = {},
        
        /**
         *  A collection of the clock instances created. By default, Clocks render each formatting
         *  character and label in their own HTML element. These elements are indexed by their
         *  formatting character and stored as elements of the "frame" object in this object.
         *
         *  Non-formatting characters (such as labels) are still contained within their own HTML
         *  elements, but are not tracked in the frame object, as they are not intended to change.
         *
         *  Signature:
         *  {
         *      <clockInstance>: 
         *      {
         *          clock: <clockObject>,
         *
         *          frame:
         *          {
         *              <formattingCharacter>: <jQueryObject>,
         *
         *              ...
         *          }
         *      },
         *
         *      ...
         *  }
         */
        instances = {},
        
        /**
         *  A unique identifier for each created clock.
         */
        uid = 0,
        
        /**
         *  Whenever a tick process begins, this variable will contain the current datetime value.
         */
        now,
        
        /**
         *  A "zero" date for clocks.
         */
        zero = new Date(0),
        
        /**
         *	The interval for tracking the clock timeout.
         */
        intervalID,
        
        /**
         *  Create the Clock class, defined below.
         */
        Clock = function () 
        {
            this.__uid = uid++;
            instances[this.__uid] = {clock: this, frame: {}};
        };
        
    //------------------------------
    //
    //  Internal Methods
    //
    //------------------------------
    
    /**
     *  Trigger an event for a clock instance.
     *
     *  @param uid      The unique id of the clock instance.
     *
     *  @param event    The event being triggered.
     *
     *  @param params   The parameters for the event listener.
     */
    function triggerEvent(uid, event, params)
    {
        instances[uid].clock.container.triggerHandler(event, params);
        
        if (events[uid] === undefined || events[uid][event] === undefined)
        {
            return;
        }
    
        $.each(events[uid][event], function (index, value)
        {
            if ($.isFunction(value))
            {
                value.apply(instances[uid].clock, params || []);
            }
        });
    }
    
    /**
     *  Terminate a counter clock.
     *
     *  @param clock    The clock to terminate.
     *
     *  @param current  The current time.
     *
     *  @return The time to render.
     */
    function terminate(clock, current)
    {
        triggerEvent(clock.__uid, 'timer');
        
        switch (clock.mode)
        {
        
        case mode.holdup:
        case mode.rollover:
            clock.mode = mode.countup;
            clock.restart(0);
            return zero;
            
        case mode.expire:
        case mode.countdown:
        case mode.timer:
            clock.destroy();
            return zero;
            
        case mode.loop:
            clock.restart();
            return zero;

        }
    }
    
    /**
     *  Given the settings for a clock, determine what the state would be if the clock ticked forward.
     *
     *  @param clock    The clock to tick foward using the current value of "now".
     *
     *  @return False to indicate rendering should be skipped, or the date to render for the clock.
     */
    function tick(clock)
    {
        if (clock.__paused !== undefined && clock.mode)
        {
            return false;
        }

        var current = now + clock.__displacement,
            days;
        
        switch (clock.mode)
        {
        
        case mode.holdup:
            current -= clock.time;
            if (current > 0 || current > -1e3)
            {
                return terminate(clock, current);
            }
            return zero;
            
        case mode.countup:
        case mode.stopwatch:
            current -= clock.time;
            break;
            
        case mode.explicit:
            current += clock.time;
            break;
        
        case mode.rollover:
        case mode.loop:
        case mode.expire:
        case mode.countdown:
        case mode.timer:
            current = clock.time - current;
            
            if (current < 1e3)
            {
                return terminate(clock, current);
            }
            break;

        }
        
        if (clock.displayOffset !== undefined)
        {
            days = parseInt($.dateformat.get('V', current), 10);
            
            if (days > clock.__days_added)
            {
                clock.__days_added = days;
                clock.displayOffset.days += days;
                
                if (clock.displayOffset.days >= 365)
                {
                    clock.displayOffset.years += Math.floor(clock.displayOffset.days / 365.4 % 365.4);
                    clock.displayOffset.days = Math.floor(clock.displayOffset.days % 365.4);
                }
    
            }
        }    
        
        return new Date(current);
    }
    
    /**
     *  Compute the value for a formatting symbol for a given clock using the provided current time.
     *
     *  @param clock    The clock object.
     *
     *  @param symbol   The formatting symbol.
     *
     *  @param current  The current time.
     */
    function evaluate(clock, symbol, current)
    {
        switch (symbol)
        {
        
        case 'Q':
            return clock.displayOffset.years;
        
        case 'E':
            return clock.displayOffset.days;
        
        case 'e':
            return $.dateformat.pad(clock.displayOffset.days, 0);
        
        default:
            return $.dateformat.get(symbol, current);
            
        }
    }
    
    /**
     *  The default operation for rendering an epiclock.
     *
     *  @param frame    The frame getting updated.
     *
     *  @param value    The current value for the frame.
     */
    function defaultRenderer(frame, value)
    {
        frame.text(value);
    }
    
    /**
     *  Render a clock.
     *
     *  @param clock    The clock to render.
     */
    function render(clock)
    {    
        var time = tick(clock);
        
        if (time === false)
        {
            return false;
        }
        
        $.each(instances[clock.__uid].frame, function (symbol, frame)
        {
            var value = evaluate(clock, symbol, time) + '';
        
            if (frame.data('epiclock-last') !== value)
            {
                (clock.__render || defaultRenderer)(frame, value);
            }
            
            frame.data('epiclock-last', value);
        });
        
        triggerEvent(clock.__uid, 'rendered');
        
        if (clock.container.hasClass('epiclock-wait-for-render'))
        {
            clock.container.removeClass('epiclock-wait-for-render');
        }
    }
    
    /**
     *	Process all the clocks currently available.
     */
    function cycleClocks()
    {
        now = new Date().valueOf();
        
        $.each(instances, function ()
        {
            render(this.clock);
            
            if (this.clock.__destroy)
            {
                this.clock.container.removeData('epiclock');
                delete instances[this.clock.__uid];
            }
        });
    }
    
    /**
     *	Start the epiclock manager.
     */
    function startManager()
    {
        if (intervalID !== undefined)
        {
            return;
        }
        
        $.each(instances, function ()
        {
            this.clock.resume();
        });
        
        intervalID = setInterval(cycleClocks, _.precision);
    }
    
    /**
     *	Halt the epiclock manager.
     */
    function haltManager()
    {
        clearInterval(intervalID);
        
        intervalID = undefined;
        
        $.each(instances, function ()
        {
            this.clock.pause();
        });
    }
    
    /**
     *	The the default formatting string for a clock.
     *
     *  @param format   The format of the clock.
     *
     *  @return The default dateformat string to use.
     */
    function defaultFormat(format)
    {
        switch (format)
        {
            
        case mode.clock:
        case mode.explicit:
            return 'F j, Y g:i:s a';
            
        case mode.countdown:
            return 'V{d} x{h} i{m} s{s}';
            
        case mode.countup:
            return 'Q{y} K{d} x{h} i{m} s{s}';
            
        case mode.rollover:
            return 'V{d} x{h} i{m} s{s}';
            
        case mode.expire:
        case mode.timer:
            return 'x{h} i{m} s{s}';
            
        case mode.loop:
            return 'i{m} s{s}';
            
        case mode.stopwatch:
            return 'x{h} C{m} s{s}';
            
        case mode.holdup:
            return 'Q{y} K{d} x{h} i{m} s{s}';
            
        }
    }
    
    /**
     *  Given a properties object, configure a clock instance.
     *
     *  @param properties   The configuration for the clock.
     *
     *  @return Clock instance.
     */
    function configureInstance(properties)
    {
        var clock = new Clock(),
            modifier = 1;
        
        clock.mode = properties.mode || mode.clock;
        
        if (properties.offset !== undefined)
        {
            clock.__offset = (
                (properties.offset.years || 0) * 3157056e4 +
                (properties.offset.days || 0) * 864e5 +
                (properties.offset.hours || 0) * 36e5 +
                (properties.offset.minutes || 0) * 6e4 +
                (properties.offset.seconds || 0) * 1e3
            );
        }
        
        if (properties.startpaused)
        {
            clock.__paused = new Date().valueOf();
        }
        
        clock.__displacement = properties.utc === true || properties.gmt === true ? new Date().getTimezoneOffset() * 6e4 : 0;
        clock.__render = $.isFunction(properties.renderer) ? properties.renderer : _.renderers[properties.renderer];
        clock.__days_added = 0;
        clock.__tare = properties.tare || false;
        clock.format = properties.format || defaultFormat(clock.mode);
        clock.time = (properties.time || properties.target ? new Date(properties.time || properties.target) : new Date()).valueOf();
        clock.displayOffset = $.extend({years: 0, days: 0}, properties.displayOffset);
        
        switch (clock.mode)
        {
        
        case mode.clock:
        case mode.countup:
            break;
            
        case mode.explicit:
            clock.__displacement -= new Date().valueOf();
            break;
            
        case mode.timer:
        case mode.loop:
            modifier = -1;
            clock.__tare = true;
            clock.__displacement -= 1e3;
            break;

        case mode.expire:
        case mode.countdown:
        case mode.rollover:
            modifier = -1;
            clock.__displacement -= 1e3;
            break;
            
        case mode.holdup:
            if (clock.time < new Date().valueOf())
            {
                clock.mode = mode.countup;
            }
            else
            {
                clock.__displacement -= 1e3;
            }
            break;
            
        case mode.stopwatch:
            clock.__tare = true;
            break;
            
        default:
            throw 'EPICLOCK_INVALID_MODE';

        }

        clock.__displacement += modifier * clock.__offset;
        
        return clock;
    }
    
    /**
     *  Perform a tick for printing on the given clock.
     *
     *  @param clock    The clock to tick for printing.
     *
     *  @return The current time.
     */
    function printTick(clock)
    {
        now = new Date().valueOf();
        return tick(clock);
    }

    //------------------------------
    //
    //  Plugin Definition
    //
    //------------------------------
    
    //------------------------------
    //  Error Declaration
    //------------------------------
    
    //------------------------------
    //  Plugin Creation
    //------------------------------
    
    $.extend(_, {
    
        //------------------------------
        //  Properties
        //------------------------------
        
        /**
         *	The precision for timing out the clocks.
         */
        precision: 500,
        
        /**
         *	The modes for epiclocks.
         */
        modes: mode,
        
        /**
         *	Custom renderers for epiclock.
         *
         *  Signature:
         *  {
         *      <renderer>: <renderingFunction>,
         *
         *      ...
         *  }
         */
        renderers: {},
    
        //------------------------------
        //  Methods
        //------------------------------
        
        /**
         *	Add a renderer.
         *
         *  @param key      The key for this rendering function.
         *
         *  @param renderer The rendering function.
         *
         *  @param setup    The setup function for the renderer.
         */
        addRenderer: function (key, renderer, setup)
        {
            _.renderers[key] = renderer;
            
            if ($.isFunction(setup))
            {
                _.renderers[key].setup = setup;
            }
            
            return _;
        },
        
        /**
         *	Make a clock inside the given container using the given template html with the provided properties.
         *
         *  @param properties   The properties of the clock.
         *
         *  @param container    The container the clock will exist within.
         *
         *  @param template     The element template.
         */
        make: function (properties, container, template)
        {
            var clock = configureInstance(properties),
            
                output = '',
            
                /**
                 *  The characters '{' and '}' are the start and end characters of an escape. Anything between these
                 *  characters will not be treated as a formatting commands, and will merely be appended to the output
                 *  string. When the buffering property here is true, we are in the midst of appending escaped characters
                 *  to the output, and the formatting check should therefore be skipped.
                 */
                buffering = false,
         
                char = '',
                
                index = 0,
                
                format = clock.format.split(''),
                
                containerClass = typeof properties.renderer === "string" ? ' epiclock-' + properties.renderer : '';
            
            container = $(container).data('epiclock', clock).addClass('epiclock-container epiclock-wait-for-render' + containerClass);
            template = $(template || '<span></span>');
                
            for (; index < format.length; index++)
            {
                char = format[index] + '';
                
                switch (char)
                {
    
                case ' ':
                    if (buffering)
                    {
                        output += char;
                    }
                    else
                    {
                        template.clone(true).addClass('epiclock epiclock-spacer').appendTo(container);
                    }
                    break;
                    
                case '{':
                case '}':
                    buffering = char === '{';
                    
                    if (!buffering)
                    {
                        template.clone(true).addClass('epiclock').html(output).appendTo(container);
                        output = '';
                    }
                    
                    break;
                
                default:
                    if (!buffering && $.dateformat.hasRule(char))
                    {
                        instances[clock.__uid].frame[char] = template
                            .clone(true).addClass('epiclock epiclock-digit').data('epiclock-encoding', char).appendTo(container);
                    }
                    else if (!buffering)
                    {
                        template.clone(true).addClass('epiclock epiclock-separator').html(char).appendTo(container);
                    }
                    else
                    {
                        output += char;
                    }
                    break;
                    
                }
            }
            
            clock.container = container;
            
            if (clock.__render !== undefined && $.isFunction(clock.__render.setup))
            {
                clock.__render.setup.apply(clock, [container]);
            }
            
            startManager();
            
            return clock;
        },
        
        /**
         *	Bounce the manager.
         *
         *  @param precision    The precision to set in the manager upon restart.
         */
        bounce: function (precision)
        {
            if (precision !== undefined)
            {
                _.precision = precision;
            }
            
            _.pause().resume();
        },
        
        /**
         *	Pause all clocks.
         */
        pause: function ()
        {
            haltManager();
            
            return _;
        },
        
        /**
         *	Start all clocks.
         */
        resume: function ()
        {
            startManager();
            
            return _;
        },
        
        /**
         *	Pause the manager and destroy all clocks.
         */
        destroy: function ()
        {
            clearInterval(intervalID);
            
            $.each(instances, function ()
            {
                this.clock.destroy();
            });
            
            return _;
        },
        
        /**
         *	Restart all clocks.
         */
        restart: function ()
        {
            $.each(instances, function ()
            {
                this.clock.restart();
            });
            
            return _;
        }
    });
    
    //------------------------------
    //  Core Extension
    //------------------------------
    
    $.dateformat.rules({

        //------------------------------
        //  Custom Date Rules
        //------------------------------
    
        Q: function ()
        {
            return '%displayOffset-years%';
        },
        
        E: function ()
        {
            return '%displayOffset-days%';
        },
        
        e: function ()
        {
            return '%displayOffset-days-pad%';   
        }
    });
    
    //------------------------------
    //
    //  Class Definition
    //
    //------------------------------
    
    $.extend(Clock.prototype, {
    
        //------------------------------
        //  Internal Properties
        //------------------------------
    
        /**
         *  A unique identifier for this clock instance.
         */
        __uid: undefined,
        
        /**
         *  The rendering function used by this clock instance.
         */
        __render: undefined,
        
        /**
         *  The displacement of a clock is some number of milliseconds
         *  which is added to the computed value of the clock before rendering.
         *
         *  This property allows for timers to use the default "tick" mechanics
         *  which drive all clocks. By setting a negative displacement equal to
         *  the number of seconds since the epoch when the clock was started, we
         *  basically zero out the date object, and can then compute timers from
         *  zero seconds.
         *
         *  This property is generated automatically based on whatever values are
         *  passed into the "offset" hash, depending on what mode of clock this is.
         */
        __displacement: undefined,
        
        /**
         *  When dealing with display offset days, the clock adds the actual number of days
         *  since the start time to the display offset days, to update them whenever the day
         *  actually rolls over. We need to keep track of how many days have been added to the
         *  display offset, so we can keep computation and display separate, while still letting
         *  computation impact display.
         */
        __days_added: undefined,
        
        /**
         *  Whenever a clock is paused, this variable tracks the datetime when the pause first
         *  started. Whenever the clock is resumed, this value is marked as undefined. By the nature
         *  of this interaction, it should be considered an implicit flag. If it is undefined, the clock
         *  is not paused. If it is any value other than undefined, the clock is paused.
         */
        __paused: undefined,
        
        /**
         *  Offset to the current time. These values are applied to the
         *  computed time when running, and are used to add some displacement
         *  to the clock value. These values directly impact the computed time.
         */
        __offset: 0,
        
        /**
         *	If true, the clock will tare on pause, to reset the start time on the clock to represent a change
         *  in the start time of the clock, to allow for pauses not to count against the rendered time of a
         *  clock.
         */
        __tare: false,
        
        /**
         *	When true, the clock will be destroyed after the next render action occurs.
         */
        __destroy: false,
    
        //------------------------------
        //  Properties
        //------------------------------
        
        /**
         *  Tracks extra offsets for days and years for counter clocks. Unlike the
         *  offset object, displayOffset has no impact on computation, and is merely
         *  used to add untrackable date support (pre 1970) to the clock.
         *
         *  Signature:
         *  {
         *      days: <dayDisplayOffset>,
         *
         *      years: <yearDisplayOffset>
         *  }   
         */
        displayOffset: undefined,
        
        /**
         *  Date object representing the base time used for computation in the clock.
         */
        time: undefined,
        
        /**
         *  String describing the format this clock should be rendered with.
         *
         *  See the jquery.dateformat plugin for formatting characters.
         */
        format: undefined,
    
        /**
         *  The mode this clock is in.
         */
        mode: undefined,
        
        /**
         *	The element representing the clocks container in the DOM.
         */
        container: undefined,
        
        //------------------------------
        //  Methods
        //------------------------------
        
        /**
         *  Bind an event listener to this clock.
         *
         *  @param event    The event to bind on.
         *
         *  @param listener The listener function to notify when the event occurs.
         */
        bind: function (event, listener)
        {
            if (events[this.__uid] === undefined)
            {
                events[this.__uid] = {};
            }
            
            if (events[this.__uid][event] === undefined)
            {
                events[this.__uid][event] = [listener];
            }
            else
            {
                events[this.__uid][event].push(listener);
            }
            
            return this;
        },
        
        /**
         *  Pause the clock.
         */
        pause:  function ()
        {
            if (this.__paused === undefined)
            {
                this.__paused = new Date().valueOf();
            }
            
            triggerEvent(this.__uid, 'pause');
        },
        
        /**
         *  Resume the clock.
         */
        resume: function ()
        {
            if (this.__paused === undefined)
            {
                return;
            }
            
            triggerEvent(this.__uid, 'resume');
            
            if (this.__tare)
            {
                this.__displacement += (this.__paused - new Date().valueOf());
            }
            
            this.__paused = undefined;
        },
        
        /**
         *	Toggle the pause status of the clock.
         */
        toggle: function ()
        {
            if (this.__paused === undefined)
            {
                this.pause();
            }
            else
            {
                this.resume();
            }
        },
        
        /**
         *	Destroy the clock.
         */
        destroy: function ()
        {
            this.__destroy = true;
            triggerEvent(this.__uid, 'destroy');
        },
        
        /**
         *	Reset the clock to use the current time as the start time for the clock.
         *
         *  @param displacement The new value for the displacement.
         */
        restart: function (displacement)
        {
            if (displacement !== undefined)
            {
                this.__displacement = displacement;
            }
            
            this.time = now.valueOf();
        },
        
        /**
         *  Print a string version of the clock's current time.
         *
         *  @param format If a special format other than the default is to be used.
         *
         *  @return The formatted string.
         */
        print: function (format)
        {
            var value = $.dateformat.format(this.format, printTick(this));
            
            if (this.displayOffset !== undefined)
            {
                return value
                    .replace('%displayOffset-days%', this.displayOffset.years)
                    .replace('%displayOffset-days%', this.displayOffset.days)
                    .replace('%displayOffset-days-pad%', $.dateformat.pad(this.displayOffset.days, 0));
            }
            
            return value;    
        }
    
    });
        
    //------------------------------
    //
    //  jQuery Hooks
    //
    //------------------------------
    
    $.epiclock = _;
    
    /**
     *	Element builder for epiclock.
     *
     *  @param properties   The properties for building the epiclock.
     */
    $.fn.epiclock = function (properties)
    {
        return this.each(function ()
        {
            var container = $(this),
                template;
            
            properties = properties || {};
            
            if (properties.template !== undefined)
            {
                template = properties.template;
                
                delete properties.template;
            } 
            else if (container.children().length > 0)
            {
                template = container.children().remove().eq(0);
            }
            
            $.epiclock.make(properties, container, template);
        });
    };
    
    //------------------------------
    //
    //  Event Bindings
    //
    //------------------------------
    
    //------------------------------
    //
    //  Startup Code
    //
    //------------------------------
        
}(jQuery));