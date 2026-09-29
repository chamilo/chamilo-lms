// import('multiselect-two-sides');

require("image-map-resizer/js/imageMapResizer.js")
require("cropper")
// require('jquery-ui-timepicker-addon/dist/jquery-ui-timepicker-addon');
import("select2")
require("timeago")
//require('sweetalert2');
//import('bootstrap-select');
require("easy-pie-chart/dist/jquery.easypiechart.min")
// require('jquery-ui-timepicker-addon');
require("@fancyapps/fancybox/dist/jquery.fancybox.js")
require("@fancyapps/fancybox/src/js/media.js")

// Lean build: TinyMCE's codesample plugin auto-detects a global "hljs" and uses it
// for highlighting, but only offers its own default language dropdown (mapped below)
// unless codesample_languages is overridden — the full "highlight.js" package bundles
// all ~190 languages, most of which are unreachable from that dropdown.
var hljs = require("highlight.js/lib/core")
hljs.registerLanguage("markup", require("highlight.js/lib/languages/xml"))
hljs.registerLanguage("javascript", require("highlight.js/lib/languages/javascript"))
hljs.registerLanguage("css", require("highlight.js/lib/languages/css"))
hljs.registerLanguage("php", require("highlight.js/lib/languages/php"))
hljs.registerLanguage("ruby", require("highlight.js/lib/languages/ruby"))
hljs.registerLanguage("python", require("highlight.js/lib/languages/python"))
hljs.registerLanguage("java", require("highlight.js/lib/languages/java"))
hljs.registerLanguage("c", require("highlight.js/lib/languages/c"))
hljs.registerLanguage("csharp", require("highlight.js/lib/languages/csharp"))
hljs.registerLanguage("cpp", require("highlight.js/lib/languages/cpp"))
global.hljs = hljs

var textcomplete = require("textcomplete")
global.textcomplete = textcomplete

//global.Chart = require("chart.js/dist/chart").Chart

import { Chart, registerables } from "chart.js"
import translateHtml from "../translatehtml.js"

Chart.register(...registerables)
global.Chart = Chart

require("./annotation.js")
require("../editor.js")

document.addEventListener("DOMContentLoaded", function () {
  translateHtml()
})
