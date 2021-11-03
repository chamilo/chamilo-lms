// require('mediaelement');
// import('multiselect-two-sides');

require('image-map-resizer/js/imageMapResizer.js');
require('cropper');
// require('jquery-ui-timepicker-addon/dist/jquery-ui-timepicker-addon');
import('select2');
require('timeago');
//require('sweetalert2');
//import('bootstrap-select');
require('easy-pie-chart/dist/jquery.easypiechart.min');
// require('jquery-ui-timepicker-addon');
require('@fancyapps/fancybox/dist/jquery.fancybox.js');
require('@fancyapps/fancybox/src/js/media.js');

var hljs = require('highlight.js');
global.hljs = hljs;

var textcomplete = require('textcomplete');
global.textcomplete = textcomplete;

require('chart.js');
require('./annotation.js');
import translateHtml from './translatehtml.js';
document.addEventListener('DOMContentLoaded', function () {
  translateHtml();
});
