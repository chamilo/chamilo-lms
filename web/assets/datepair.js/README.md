# Datepair.js

[See a demo and examples here](http://jonthornton.github.com/Datepair.js)

Datepair.js is a lightweight, modular javascript plugin for intelligently selecting date and time ranges, inspired by Google Calendar. It will keep the start and end date/times in sync and can set default values based on user action. There are no external dependencies, however it can easily be used with jQuery or Zepto. The plugin does not provide any UI widgets; it's preconfigured to work with [jquery-timepicker](https://github.com/jonthornton/jquery-timepicker) and [Bootstrap Datepicker](https://github.com/eternicode/bootstrap-datepicker), but you can use it with any datepicker or timepicker (or none at all).

*Looking for [jquery-datepair](#jquery-plugin)? Scroll down.*

## Requirements

* [jquery-timepicker](https://github.com/jonthornton/jquery-timepicker) (>= 1.3) (this dependency can be overridden)
* [Bootstrap Datepicker](https://github.com/eternicode/bootstrap-datepicker) (>= 1.3) (this dependency can be overridden)

## Usage

Include `dist/datepair.js` or `dist/jquery.datepair.min.js` in your app.

```javascript
var container = document.getElementById('container')
var datepair = new Datepair(container, options);
```

Where `#container` contains time/date `<input />` elements with the appropriate class names. `options` is an optional javascript object with parameters explained below.

Note that Datepair is triggered by `change` events and won't work unless the container has some `<input />` elements.

## Options

- **anchor**  
The input that will control the other input. One of `"start"`, `"end"`, or `null`. See [demo page](http://jonthornton.github.io/Datepair.js/) for more information.
*default: "start"*

- **dateClass**  
Class name of the date inputs, if any.  
*default: "date"*

- **defaultDateDelta**  
Fill in the second date value with the specified range when the users selects the first date. Value is in days. Set this to ```null``` to disable automatically setting the second date.  
*default: 0*

- **defaultTimeDelta**  
Fill in the second time value with the specified range when the users selects the first time. Value is in milliseconds; set this to ```7200000``` for a 2 hour range, for example. Set this to ```null``` to disable automatically setting the second time.  
*default: 0*

- **endClass**  
Class name of the range end input(s).  
*default: "end"*

- **parseDate**  
A function that takes a jQuery element for a date input and returns a local time ```Date``` object representing the date input value.  
*default: function for [Bootstrap Datepicker](https://github.com/eternicode/bootstrap-datepicker)*

- **parseTime**  
A function that takes a jQuery element for a time input and returns a local time ```Date``` object representing the time input value. See [example page](http://jonthornton.github.com/Datepair.js) for more info.  
*default: function for [jquery-timepicker](https://github.com/jonthornton/jquery-timepicker)*

- **setMinTime**  
A function that takes a jQuery element for a time input and a local time ```Date``` object representing the time, and sets the timepicker minTime value.  
*default: function for [jquery-timepicker](https://github.com/jonthornton/jquery-timepicker)*

- **startClass**  
Class name of the range start input(s).  
*default: "start"*

- **timeClass**  
Class name of the time inputs, if any.  
*default: "time"*

- **updateDate**  
A function that takes a jQuery element for a date input and a local time ```Date``` object representing the date, and sets the input value.  
*default: function for [Bootstrap Datepicker](https://github.com/eternicode/bootstrap-datepicker)*

- **updateTime**  
A function that takes a jQuery element for a time input and a local time ```Date``` object representing the time, and sets the input value.  
*default: function for [jquery-timepicker](https://github.com/jonthornton/jquery-timepicker)*


## Methods

- **getTimeDiff**  
Get the date/time range size, in milliseconds.

	```javascript
	var milliseconds = datepair.getTimeDiff();
	```

- **refresh**  
Call this method if you programmatically update the date/time fields after first initialization of datepair.  

	```javascript
	$('#someInput').val(someValue)
	datepair.refresh();
	```

- **remove**  
Unbind the datepair functionality from a set of inputs.  

	```javascript
	datepair.remove();
	``` 

## Events

- **rangeError**  
Fired after the user interacts with the datepair inputs but selects an invalid range, where the end time/date is before the start.

- **rangeIncomplete**  
Fired after the user interacts with the datepair inputs but one or more empty inputs remain. Unpaired inputs (such as a start date with no corresponding end date) will not be taken into account.

- **rangeSelected**  
Fired after the user interacts with the datepair inputs and all paired inputs have valid values.

## jQuery Plugin

Datepair.js includes an optional jQuery interface that can simplify usage when working with jQuery or Zepto. To activate, include both `datepair.js` and `jquery.datepair.js`, or just `jquery.datepair.min.js`. (The minified version includes both scripts.)

### Usage

```javascript
$('#container').datepair(options);
var milliseconds = $('#container').datepair('getTimeDiff');
$('#container').datepair('remove');
$('#container').datepair('refresh');
```

## jQuery-UI Datepicker

By default, Datepair.js is configured to work with [Bootstrap Datepicker](https://github.com/eternicode/bootstrap-datepicker). This is different from [jQuery UI Datepicker](http://jqueryui.com/datepicker/). To use jQuery UI Datepicker, override the `parseDate` and `updateDate` methods:

```javascript
$('#some-container').datepair({
    parseDate: function (el) {
        var val = $(el).datepicker('getDate');
        if (!val) {
            return null;
        }
        var utc = new Date(val);
        return utc && new Date(utc.getTime() + (utc.getTimezoneOffset() * 60000));
    },
    updateDate: function (el, v) {
        $(el).datepicker('setDate', new Date(v.getTime() - (v.getTimezoneOffset() * 60000)));
    }
});
```

## Packaging

https://www.npmjs.com/package/datepair.js  
`npm install --save datepair.js`

## Help

Submit a [GitHub Issues request](https://github.com/jonthornton/Datepair.js/issues/new).

## Development Guidelines

* Install dependencies (grunt) `npm install`
* To build and minify, run `grunt`
* Use `grunt watch` to continuously build while developing

Datepair.js follows [semantic versioning](http://semver.org/).

- - -

This software is made available under the open source MIT License. &copy; 2014 [Jon Thornton](http://www.jonthornton.com).
