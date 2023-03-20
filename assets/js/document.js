/** This JS  will be included when loading an HTML in the Document tool */

import translateHtml from './translatehtml.js';
document.addEventListener('DOMContentLoaded', function () {
  translateHtml();
});
