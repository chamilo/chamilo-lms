/* For licensing terms, see /license.txt */

document.addEventListener('DOMContentLoaded', function () {
  if (
      window.user && window.user.locale &&
      window.config &&
      window.config['editor.translate_html'] &&
      'true' === window.config['editor.translate_html']
  ) {
    var isoCode = window.user.locale;
    const translateElement = document.querySelector('.mce-translatehtml');
    if (translateElement) {
      document.querySelectorAll('.mce-translatehtml').forEach(function (el) {
        el.style.display = 'none';
      });
      const selectedLang = document.querySelectorAll('[lang="' + isoCode + '"]');
      if (selectedLang.length > 0) {
        selectedLang.forEach(function (userLang) {
          userLang.classList.remove('hidden')
          userLang.style.display = 'block';
        });
      }
    }

    // it checks content from old version
    const langs = document.querySelectorAll('span[lang]:not(.mce-translatehtml)');
    if (langs.length > 0) {
      // it hides all contents with lang
      langs.forEach(function (el) {
        el.style.display = 'none';
      });

      // To show only the content by user language.
      if (isoCode == 'pl_PL') {
        isoCode = 'pl';
      }
      if (isoCode == 'fr_FR') {
        isoCode = 'fr';
      }
      if (isoCode == 'en_US') {
        isoCode = 'en';
      }
      const selectedLang = document.querySelectorAll('span[lang="' + isoCode + '"]');
      if (selectedLang.length > 0) {
        selectedLang.forEach(function (userLang) {
          userLang.classList.remove('hidden')
          userLang.style.display = 'block';
        });
      }
    }
  }
});
