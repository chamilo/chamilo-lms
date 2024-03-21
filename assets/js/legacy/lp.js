
/* For licensing terms, see /license.txt */

import $ from 'jquery';

window.jQuery = $;
window.$ = $;
global.jQuery = $;

import 'jquery-ui-dist/jquery-ui.js';

const frameReady = require('/public/main/inc/lib/javascript/jquery.frameready.js');

global.frameReady = frameReady;
window.frameReady = frameReady;

var hljs = require('highlight.js');
global.hljs = hljs;

document.addEventListener('DOMContentLoaded', (event) => {
  var tabLinks = document.querySelectorAll('.nav-item.nav-link');

  function removeActiveClasses() {
    tabLinks.forEach(function(link) {
      link.classList.remove('active');
      var tabPanel = document.getElementById(link.getAttribute('aria-controls'));
      if (tabPanel) {
        tabPanel.classList.remove('active');
      }
    });
  }

  tabLinks.forEach(function(link) {
    link.addEventListener('click', function() {
      removeActiveClasses();
      this.classList.add('active');
      var tabContentId = this.getAttribute('aria-controls');
      var tabContent = document.getElementById(tabContentId);
      if (tabContent) {
        tabContent.classList.add('active');
      }
    });
  });
});
