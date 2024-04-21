/* For licensing terms, see /license.txt */

import { usePlatformConfig } from "../vue/store/platformConfig";

function normalizeLocale(locale) {
  return locale.split('_')[0];
}

export default function translateHtml() {
  const platformConfigStore = usePlatformConfig();

  if (
    window.user &&
    window.user.locale &&
    "true" === platformConfigStore.getSetting("editor.translate_html")
  ) {
    var isoCode = normalizeLocale(window.user.locale);
    const translateElement = document.querySelector(".mce-translatehtml");
    if (translateElement) {
      document.querySelectorAll(".mce-translatehtml").forEach(function (el) {
        el.style.display = "none";
      });
      const selectedLang = document.querySelectorAll(`[lang="${isoCode}"]`);
      if (selectedLang.length > 0) {
        selectedLang.forEach(function (userLang) {
          userLang.classList.remove("hidden");
          userLang.style.display = "block";
        });
      }
    }

    // it checks content from old version
    const langSpans = document.querySelectorAll('span[lang]');
    const langs = [...langSpans].filter(span => !span.classList.contains('mce-translatehtml'));

    if (langs.length > 0) {
      // it hides all contents with lang
      langs.forEach(function (el) {
        el.style.display = "none";
      });

      const selectedLang = document.querySelectorAll(`span[lang="${isoCode}"]`);
      if (selectedLang.length > 0) {
        selectedLang.forEach(function (userLang) {
          userLang.classList.remove("hidden");
          userLang.style.display = "block";
        });
      }
    }
  }
}
