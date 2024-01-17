// Script to be added in the exercises tool.
import 'jsplumb';
import 'jquery-ui-touch-punch';
// import 'xcolor/jquery.xcolor.js';
import 'signature_pad';
import '../../../public/main/inc/lib/javascript/epiclock/javascript/jquery.dateformat.min.js';
import '../../../public/main/inc/lib/javascript/epiclock/javascript/jquery.epiclock.js';
import '../../../public/main/inc/lib/javascript/epiclock/renderers/minute/epiclock.minute.js';
import './annotation'
import '../../../public/main/inc/lib/javascript/hotspot/js/hotspot.js';
import '../../../public/main/inc/lib/javascript/d3/jquery.xcolor.js';

document.addEventListener("DOMContentLoaded", function() {
  // Mapping French paths to their English equivalents
  var routeMapping = {
    "enregistrement-audio": "audio-recording-help"
  };

  var links = document.querySelectorAll('a[href*="web"]');
  links.forEach(function(link) {
    link.classList.add("ajax");
    var href = link.getAttribute("href");
    var pathSegments = href.split("/");
    if (pathSegments.length >= 3) {
      var frenchPath = pathSegments[2];
      var englishEquivalent = routeMapping[frenchPath] || frenchPath;
      link.setAttribute("href", "/main/inc/ajax/exercise.ajax.php?a=" + englishEquivalent);
      link.setAttribute("data-title", link.textContent.trim());
    }
  });
});
