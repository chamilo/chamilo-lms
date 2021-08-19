/* For licensing terms, see /license.txt */

document.addEventListener('DOMContentLoaded', function () {
  if (window.user) {
    const isoCode = window.user.locale;
    const translateElement = document.querySelector('.mce-translatehtml');
    if (translateElement) {
      document.querySelectorAll('.mce-translatehtml').forEach(function (el) {
        el.style.display = 'none';
      });
      const selectedLang = document.querySelectorAll('[lang="' + isoCode + '"]');
      selectedLang.forEach(function (userLang) {
        userLang.classList.remove('hidden')
        userLang.style.display = 'block';
      });
    }
  }
});