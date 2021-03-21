
/* For licensing terms, see /license.txt */

import $ from 'jquery';

window.jQuery = $;
window.$ = $;
global.jQuery = $;

import('webpack-jquery-ui');
import('webpack-jquery-ui/css');

const frameReady = require('/public/main/inc/lib/javascript/jquery.frameready.js');

global.frameReady = frameReady;
window.frameReady = frameReady;

var hljs = require('highlight.js');
global.hljs = hljs;
