var moment = require("moment");
require("moment/min/locales.min");
global.moment = moment;

require("webpack-jquery-ui");
require("webpack-jquery-ui/css");

// JS is equivalent to the normal "bootstrap" package
// no need to set this to a variable, just require it
require("bootstrap");
require("@coreui/coreui/dist/js/coreui.min.js");
require("chosen-js");
require("mediaelement");
require("pace-js-amd-fix");
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
require("sweetalert/dist/sweetalert.min");
require('bootstrap-select/dist/js/bootstrap-select.js');

require("bootstrap-daterangepicker");
require("fullcalendar/dist/fullcalendar.js");
require("fullcalendar/dist/gcal.js");
require("fullcalendar/dist/locale-all.js");
require("easy-pie-chart/dist/jquery.easypiechart.min");

//require("readmore-js");

// doesn't work with webpack added directly in /public/libs folder
/*
require("pwstrength-bootstrap");

require("js-cookie");
require("jquery-ui-timepicker-addon");
require("ckeditor");
*/
