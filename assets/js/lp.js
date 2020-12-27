
/* For licensing terms, see /license.txt */

import $ from 'jquery';

window.jQuery = $;
window.$ = $;
global.jQuery = $;

import('webpack-jquery-ui');
import('webpack-jquery-ui/css');


var hljs = require('highlight.js');
global.hljs = hljs;
