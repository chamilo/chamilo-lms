/*!
 *  minute countdown renderer for epiclock
 *
 *  Copyright (c) Eric Garside
 *  Copyright (c) Chamilo team
 *  Dual licensed under:
 *      MIT: http://www.opensource.org/licenses/mit-license.php
 *      GPLv3: http://www.opensource.org/licenses/gpl-3.0.html
 */
"use strict";

/*global window, jQuery */
/*jslint white: true, browser: true, onevar: true, undef: true, eqeqeq: true, bitwise: true, regexp: true, strict: true, newcap: true, immed: true, maxerr: 50, indent: 4 */

(function ($) {
    //constants
    var epClock;   // clock object

    //  Setup
    $.epiclock.addRenderer('minute', function (element, value)
    {
        var currentTime = new Date().valueOf();
        var dist = epClock.time+epClock.__offset - currentTime;
       
        //Sets the value to the clock very important!
        element.text(value);
        
        var div_clock = $('#exercise_clock_warning');
        
        // 60000 = 60 seconds
        if (dist <= 180000) {  //3min
            if (!(div_clock.hasClass('time_warning_two'))) {
                div_clock.addClass('time_warning_two');
            }
        }
        
        if (dist <= 60000) { //1min
            div_clock.removeClass('time_warning_two');
            if (!(div_clock.hasClass('time_warning_one'))) {                
                div_clock.addClass('time_warning_one');
            }            
        }
    },
    function ()
    {
       epClock = this;
    });	
}(jQuery));