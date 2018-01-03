var moment = require('moment');
require("moment/min/locales.min");
global.moment = moment;

const $ = require('jquery');
// create global $ and jQuery variables
global.$ = global.jQuery = $;

// JS is equivalent to the normal "bootstrap" package
// no need to set this to a variable, just require it
require('bootstrap-sass');
require('chosen-js');
require('webpack-jquery-ui');
require('webpack-jquery-ui/css');
require('font-awesome-webpack');

/*

require('jquery-ui-timepicker-addon');
require('chosen-js');*/
//require('bootstrap-daterangepicker');
