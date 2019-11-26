/*!
 *  Retro countdown renderer for epiclock
 *
 *  Copyright (c) Eric Garside
 *  Dual licensed under:
 *      MIT: http://www.opensource.org/licenses/mit-license.php
 *      GPLv3: http://www.opensource.org/licenses/gpl-3.0.html
 */

"use strict";

/*global window, jQuery */

/*jslint white: true, browser: true, onevar: true, undef: true, eqeqeq: true, bitwise: true, regexp: true, strict: true, newcap: true, immed: true, maxerr: 50, indent: 4 */

(function ($) {

    //------------------------------
    //
    //  Constants
    //
    //------------------------------
    
        /**
         *	Because epiclock returns values as 2 digits in one number, we need an "inner template" to contain
         *  the actual image objects.
         */
    var innerTemplate = '<span class="epiclock-img"><span class="epiclock-animation"></span></span>';

    //------------------------------
    //
    //  Animation
    //
    //------------------------------
    
    /**
     *	Animate a given element. The animation for the retro clock has four stages:
     *      :a1 - First stage of the animation
     *      :a2 - Second stage of the animation
     *      :a3 - Third stage of the animation
     *      :s  - Static image, end of animation.
     * 
     *  @param element  The element being animated.
     */
    function animate()
    {
        var clock = this;
    
        setTimeout(function ()
        {
            $('.a1', clock.container).removeClass('a1').addClass('a2');
            
            setTimeout(function ()
            {
                $('.a2', clock.container).removeClass('a2').addClass('s');
            }, 150);
        }, 150);
    }
    
    //------------------------------
    //
    //  Setup
    //
    //------------------------------

    $.epiclock.addRenderer('retro-countdown', function (element, value)
    {
            /**
             *	Determine if this is a collection of digits, or the am/pm string, and parser
             *  the value accordingly.
             */
        var digits = value.substring(1) === 'm' ? [value] : value.split('').reverse(),
            
            /**
             *	The last value of this element.
             */
            last = element.data('epiclock-last'),
            
            /**
             *	Comparison values for the last array as compared to this one.
             */
            compare = last ? last.split('').reverse() : [],
            
            /**
             *	The image instances for this block. If these don't yet exist, they will be created in the digit for...each callback.
             */
            image = $.makeArray($('.epiclock-img', element)).reverse();
            
        $.each(digits, function (index, digit)
        {
            /**
             *	We don't want to change the image part if it hasn't been updated.
             */
            if (digit === compare[index])
            {
                return;
            }
            
            /**
             *	Animate the number after the clock has changed.
             */
            $('.epiclock-animation', $(image[index] || $(innerTemplate).prependTo(element)).removeClass('d' + compare[index]).addClass('d' + digit)).removeClass('s').addClass('a1');
        });
    }, 
    
    function ()
    {
        this.bind('rendered', animate);
    });
	
}(jQuery));
