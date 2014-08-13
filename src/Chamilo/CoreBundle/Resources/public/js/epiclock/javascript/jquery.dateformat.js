/*!
 *  Date formatting plugin
 *
 *  Copyright (c) Eric Garside
 *  Dual licensed under:
 *      MIT: http://www.opensource.org/licenses/mit-license.php
 *      GPLv3: http://www.opensource.org/licenses/gpl-3.0.html
 */

"use strict";

/*global jQuery */

/*jslint white: true, browser: true, onevar: true, undef: true, eqeqeq: true, bitwise: true, regexp: true, strict: true, newcap: true, immed: true, maxerr: 50, indent: 4 */

(function ($) {

    //------------------------------
    //
    //  Property Declaration
    //
    //------------------------------

        /**
         *  String formatting for each month, with January at index "0" and December at "11".
         */
    var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    
        /**
         *  String formatting for each day, with Sunday at index "0" and Saturday at index "6"
         */
        days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        
        /**
         *	The number of days in each month.
         */
        counts = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
        
        /**
         *  English ordinal suffix for corresponding days of the week.
         */
        suffix = [null, 'st', 'nd', 'rd'],
        
        /**
         *  Define the object which will hold reference to the actual formatting functions. By not directly prototyping these
         *  into the date function, we vastly reduce the amount of bloat adding these options causes.
         */
        _;
        
    //------------------------------
    //
    //  Internal Methods
    //
    //------------------------------
    
    /**
     *	Left-pad the string with the provided string up to the provided length.
     *
     *  @param format   The string to pad.
     *
     *  @param string   The string to pad with.
     *
     *  @param length   The length to make the string (default is "0" if undefined).
     *
     *  @return The padded string.
     */
    function pad(format, string, length)
    {
        format = format + '';
        length = length || 2;
    
        return format.length < length ? new Array(1 + length - format.length).join(string) + format : format;
    }
    
    /**
     *	Right-pad the string with the provided string up to the provided length.
     *
     *  @param format   The string to pad.
     *
     *  @param string   The string to pad with.
     *
     *  @param length   The length to make the string (default is "0" if undefined).
     *
     *  @return The padded string.
     */
    function rpad(format, string, length)
    {
        format = format + '';
        length = length || 2;
        
        return format.length < length ? format + new Array(1 + length - format.length).join(string) : format;
    }

    /**
     *  Perform a modulus calculation on a date object to extract the desired value.
     *
     *  @param date The date object to perform the calculation on.
     *
     *  @param mod1 The value to divide the date value seconds by.
     *
     *  @param mod2 The modulus value.
     *
     *  @return The computed value.
     */
    function modCalc(date, mod1, mod2)
    {
        return (Math.floor(Math.floor(date.valueOf() / 1e3) / mod1) % mod2);
    }
    
    /**
     *  Given a string, return a properly formatted date string.
     *
     *  @param date     The date object being formatted.
     *
     *  @param format   The formatting string.
     *
     *  @return The formatted date string
     */
    function formatDate(date, format)
    {
        format = format.split('');
        
        var output = '',
            
            /**
             *  The characters '{' and '}' are the start and end characters of an escape. Anything between these
             *  characters will not be treated as a formatting commands, and will merely be appended to the output
             *  string. When the buffering property here is true, we are in the midst of appending escaped characters
             *  to the output, and the formatting check should therefore be skipped.
             */
            buffering = false,
     
            char = '',
            
            index = 0;
            
        for (; index < format.length; index++)
        {
            char = format[index] + '';
            
            switch (char)
            {

            case ' ':
                output += char;
                break;
                
            case '{':
            case '}':
                buffering = char === '{';
                break;
            
            default:
                if (!buffering && _[char])
                {
                    output += _[char].apply(date);
                }
                else
                {
                    output += char;
                }
                break;

            }
        }
     
        return output;
    }
    
    //------------------------------
    //
    //  Class Definition
    //
    //------------------------------
    
    /**
     *  The formatting object holds all the actual formatting commands which should be accessible
     *  for date formatting.
     *
     *  Each method should reference the date function via its "this" context, which will be set
     *  by the formatter.
     *
     *  This function makes heavy use of the exponent notation for large numbers, to save space. In
     *  javascript, any number with a set of trailing zeros can be expressed in exponent notation.
     *
     *  Ex. 15,000,000,000 === 15e9, where the number after "e" represents the number of zeros.
     */
    _ = 
    {
        //------------------------------
        //  Timer Formatting
        //------------------------------
        
        /**
         *  This is intended to be used for delta computation when subtracting one date object from another.
         *
         *  @return The number of days since the epoch.
         */
        V: function ()
        {
            return modCalc(this, 864e2, 1e5);
        },
        
        /**
         *  This is intended to be used for delta computation when subtracting one date object from another.
         *
         *  @return The number of days since the epoch, padded to 2 digits.
         */
        v: function ()
        {
            return pad(_.V.apply(this), 0);
        },

        /**
         *  This is intended to be used for delta computation when subtracting one date object from another.
         *
         *  @return The number of days since the epoch, offset for years.
         */
        K: function ()
        {
            return _.V.apply(this) % 365;
        },
        
        /**
         *  This is intended to be used for delta computation when subtracting one date object from another.
         *
         *  @return The number of days since the epoch, offset for years, padded to 2 digits.
         */
        k: function ()
        {
            return pad(_.K.apply(this), 0);
        },
        
        /**
         *  This is intended to be used for delta computation when subtracting one date object from another.
         *
         *  @return The number of hours since the epoch.
         */
        X: function ()
        {
            return modCalc(this, 36e2, 24);
        },
        
        /**
         *  This is intended to be used for delta computation when subtracting one date object from another.
         *
         *  @return The number of hours since the epoch, padded to two digits.
         */
        x: function ()
        {
            return pad(_.X.apply(this), 0);
        },
        
        /**
         *  This is intended to be used for delta computation when subtracting one date object from another.
         *
         *  @return The number of minutes since the epoch.
         */
        p: function ()
        {
            return modCalc(this, 60, 60);
        },
        
        /**
         *  This is intended to be used for delta computation when subtracting one date object from another.
         *
         *  @return The number of minutes since the epoch, padded to two digits.
         */
        C: function ()
        {
            return pad(_.p.apply(this), 0);
        },
        
        /**
         *	This is intended to be used for delta computation when subtracting one date object from another.
         *
         *  @return The number of minutes since the epoch, uncapped. (1min 30seconds would be 90s)
         */
        E: function ()
        {
            return (_.X.apply(this) * 60) + _.p.apply(this);
        },
        
        /**
         *	This is intended to be used for delta computation when subtracting one date object from another.
         *
         *  @return The number of minutes since the epoch, uncapped and padded to two digits. (1min 30seconds would be 90s)
         */
        e: function ()
        {
            return pad(_.e.apply(this), 0);
        },
        
        //------------------------------
        //  Day Formatting
        //------------------------------
        
        /**
         *  @return The day of the month, padded to two digits.
         */
        d: function ()
        {
            return pad(this.getDate(), 0);
        },
        
        /**
         *  @return A textual representation of the day, three letters.
         */
        D: function ()
        {
            return days[this.getDay()].substring(0, 3);
        },
        
        /**
         *  @return Day of the month without leading zeros.
         */
        j: function ()
        {
            return this.getDate();
        },
        
        /**
         *  @return A full textual representation of the day of the week.
         */
        l: function ()
        {
            return days[this.getDay()];
        },
        
        /**
         *  @return ISO-8601 numeric representation of the day of the week.
         */
        N: function ()
        {
            return this.getDay() + 1;
        },
        
        /**
         *  @return English ordinal suffix for the day of the month, two characters.
         */
        S: function ()
        {
            return suffix[this.getDate()] || 'th';
        },
        
        /**
         *  @return Numeric representation of the day of the week.
         */
        w: function ()
        {
            return this.getDay();
        },
        
        /**
         *  @return The day of the year (starting from 0).
         */
        z: function ()
        {
            return Math.round((this - _.f.apply(this)) / 864e5);
        },
        
        //------------------------------
        //  Week
        //------------------------------
        
        /**
         *  @return ISO-8601 week number of year, weeks starting on Monday 
         */
        W: function ()
        {
            return Math.ceil(((((this - _.f.apply(this)) / 864e5) + _.w.apply(_.f.apply(this))) / 7));
        },
        
        //------------------------------
        //  Month
        //------------------------------
        
        /**
         *  @return A full textual representation of a month, such as January.
         */
        F: function ()
        {
            return months[this.getMonth()];
        },
        
        /**
         *  @return Numeric representation of a month, padded to two digits.
         */
        m: function ()
        {
            return pad((this.getMonth() + 1), 0);
        },
        
        /**
         *  @return A short textual representation of a month, three letters.
         */
        M: function ()
        {
            return months[this.getMonth()].substring(0, 3);
        },
        
        /**
         *  @return Numeric representation of a month, without leading zeros.
         */
        n: function ()
        {
            return this.getMonth() + 1;
        },
        
        /**
         *  @return Number of days in the given month.
         */
        t: function ()
        {
            //  For February on leap years, we must return 29.
            if (this.getMonth() === 1 && _.L.apply(this) === 1)
            {
                return 29;
            }
            
            return counts[this.getMonth()];
        },
        
        
        //------------------------------
        //  Year
        //------------------------------
        
        /**
         *  @return Whether it's a leap year. 1 if it is a leap year, 0 otherwise.
         */
        L: function ()
        {
            var Y = _.Y.apply(this);
        
            return Y % 4 ? 0 : Y % 100 ? 1 : Y % 400 ? 0 : 1;
        },
        
        /**
         *  @return A Date object representing the first day of the current year.
         */
        f: function ()
        {
            return new Date(this.getFullYear(), 0, 1);
        },
        
        /**
         *  @return A full numeric representation of the year, 4 digits.
         */
        Y: function ()
        {
            return this.getFullYear();
        },
        
        /**
         *  @return A two digit representation of the year.
         */
        y: function ()
        {
            return ('' + this.getFullYear()).substr(2);
        },
        
        //------------------------------
        //  Time
        //------------------------------
        
        /**
         *  @return Lowercase Ante/Post Meridiem values.
         */
        a: function ()
        {
            return this.getHours() < 12 ? 'am' : 'pm';
        },
        
        /**
         *  @return Uppercase Ante/Post Meridiem values.
         */
        A: function ()
        {
            return _.a.apply(this).toUpperCase();
        },
        
        /**
         *  If you ever use this for anything, email <eric@knewton.com>, cause he'd like to know how you found this nonsense useful.
         *
         *  @return Swatch internet time. 
         */
        B: function ()
        {
            return pad(Math.floor((((this.getHours()) * 36e5) + (this.getMinutes() * 6e4) + (this.getSeconds() * 1e3)) / 864e2), 0, 3);
        },
        
        /**
         *  @return 12-hour format of an hour.
         */
        g: function ()
        {
            return this.getHours() % 12 || 12;
        },
        
        /**
         *  @return 24-hour format of an hour.
         */
        G: function ()
        {
            return this.getHours();
        },
        
        /**
         *  @return 12-hour format of an hour, padded to two digits.
         */
        h: function ()
        {
            return pad(_.g.apply(this), 0);
        },
        
        /**
         *  @return 24-hour format of an hour, padded to two digits.
         */
        H: function ()
        {
            return pad(this.getHours(), 0);
        },
        
        /**
         *  @return Minutes, padded to two digits.
         */
        i: function ()
        {
            return pad(this.getMinutes(), 0);
        },
        
        /**
         *  @return Seconds, padded to two digits.
         */
        s: function ()
        {
            return pad(this.getSeconds(), 0);
        },
        
        /**
         *  @return Microseconds
         */
        u: function ()
        {
            return this.getTime() % 1e3;
        },
        
        //------------------------------
        //  Timezone
        //------------------------------
        
        /**
         *  @return Difference to GMT in hours.
         */
        O: function ()
        {
            var t = this.getTimezoneOffset() / 60;
            
            return rpad(pad((t >= 0 ? '+' : '-') + Math.abs(t), 0), 0, 4);
        },
        
        /**
         *  @return Difference to GMT in hours, with colon between hours and minutes
         */
        P: function ()
        {
            var t = _.O.apply(this);
            
            return t.subst(0, 3) + ':' + t.substr(3);
        },
        
        /**
         *  @return Timezone offset in seconds.
         */
        Z: function ()
        {
            return this.getTimezoneOffset() * 60;
        },
        
        //------------------------------
        //  Full Date/Time
        //------------------------------
        
        /**
         *  @return ISO 8601 date
         */
        c: function ()
        {
            return _.Y.apply(this) + '-' + _.m.apply(this) + '-' + _.d.apply(this) + 'T' + _.H.apply(this) + ':' + _.i.apply(this) + ':' +  _.s.apply(this) + _.P.apply(this);
        },
        
        /**
         *  @return RFC 2822 formatted date
         */
        r: function ()
        {
            return this.toString();
        },
        
        /**
         *  @return The number of seconds since the epoch.
         */
        U: function ()
        {
            return this.getTime() / 1e3;
        }
    };
    
    //------------------------------
    //
    //  Native Prototype
    //
    //------------------------------
    
    $.extend(Date.prototype, {
        
        /**
         *	Given a string of formatting commands, return the date object as a formatted string.
         *
         *  @param format   The formatting string.
         *
         *  @return The formatted date string
         */
        format: function (format)
        {
            return formatDate(this, format);
        }
    });
    
    //------------------------------
    //
    //  Expose to jQuery
    //
    //------------------------------
    
    $.dateformat = 
    {
        /**
         *	Get a reference to the formatting rules, or set custom rules.
         *
         *  @param custom   The custom rules to set for formatting.
         *
         *  @return The formatting rules.
         */
        rules: function (custom)
        {
            if (custom !== undefined)
            {
                _ = $.extend(_, custom);
            }
            
            return _;
        },
        
        /**
         *	Determine if the dateformat plugin has the requested formatting rule.
         *
         *  @param rule The formatting rule to check.
         *
         *  @return True if the rule exists, false otherwise.
         */
        hasRule: function (rule)
        {
            return _[rule] !== undefined;
        },
    
        /**
         *	Get a formatting value for a given date.
         *
         *  @param type The formatting character type to get the value of.
         *
         *  @param date The date to extract the value from. Defaults to current.
         */
        get: function (type, date)
        {
            return _[type].apply(date || new Date());
        },
        
        /**
         *	Given a string of formatting commands, return the date object as a formatted string.
         *
         *  @param format   The formatting string.
         *
         *  @param date The date to extract the value from. Defaults to current.
         *
         *  @return The formatted date string
         */
        format: function (format, date)
        {
            return formatDate(date || new Date(), format);
        },
        
        /**
         *	@inheritDoc
         */
        pad: pad,
        
        /**
         *	@inheritDoc
         */
        rpad: rpad
    };
    
}(jQuery));
