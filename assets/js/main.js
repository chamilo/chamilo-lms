var moment = require("moment");
require("moment/min/locales.min");
global.moment = moment;

var $ = require("jquery");
// create global $ and jQuery variables
window.jQuery = window.$ = global.$ = global.jQuery = $;

require("webpack-jquery-ui");
require("webpack-jquery-ui/css");

// JS is equivalent to the normal "bootstrap" package
// no need to set this to a variable, just require it
require("bootstrap-sass");
require("chosen-js");
require("mediaelement");
// require("font-awesome-webpack"); already added manually in main.scss

require("qtip2");
require("image-map-resizer");
require("cropper");
require("jquery-ui-timepicker-addon/dist/jquery-ui-timepicker-addon");
require("jquery.scrollbar");
require("blueimp-file-upload");
require("select2");
require("timeago");
require("select2/dist/css/select2.css");
require('bootstrap-select/dist/js/bootstrap-select.js');
require('bootstrap-select/dist/css/bootstrap-select.css');
require('flag-icon-css/css/flag-icon.css');

// doesn't work with webpack added directly in /public/libs folder
/*
require("fullcalendar");
require("pwstrength-bootstrap");
require ("readmore-js");
require("js-cookie");
require("jquery-ui-timepicker-addon");
//require("bootstrap-daterangepicker");
require("ckeditor");
*/
