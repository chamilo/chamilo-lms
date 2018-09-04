var moment = require("moment");
require("moment/min/locales.min");
global.moment = moment;

require("webpack-jquery-ui");
require("webpack-jquery-ui/css");

// JS is equivalent to the normal "bootstrap" package
// no need to set this to a variable, just require it
require("bootstrap");
require("chosen-js");
require("mediaelement");
require("multiselect-two-sides");
require('@fortawesome/fontawesome-free');

require("qtip2");
require("image-map-resizer");
require("cropper");
require("jquery-ui-timepicker-addon/dist/jquery-ui-timepicker-addon");
require("jquery.scrollbar");
require("blueimp-file-upload");
require("select2");
require("timeago");
require('bootstrap-select/dist/js/bootstrap-select.js');

require("bootstrap-daterangepicker");
require("fullcalendar/dist/fullcalendar.js");
require("fullcalendar/dist/gcal.js");
require("fullcalendar/dist/locale-all.js");

//require("readmore-js");

// doesn't work with webpack added directly in /public/libs folder
/*
require("pwstrength-bootstrap");

require("js-cookie");
require("jquery-ui-timepicker-addon");
require("ckeditor");
*/
