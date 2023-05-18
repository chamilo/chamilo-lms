import 'free-jqgrid';
import 'free-jqgrid/css/ui.jqgrid.bootstrap4.css';

let locale = document.querySelector('html').lang;

const langs = [
  'ar', 'bg', 'bs', 'ca', 'cn', 'cs', 'da', 'de', 'el', 'en', 'es', 'fa', 'fi', 'fr', 'gl', 'he', 'hr', 'hu', 'id',
  'is', 'it', 'ja', 'kr', 'lt', 'me', 'nl', 'no', 'pl', 'pt-br', 'pt', 'ro', 'ru', 'sk', 'sl', 'sr', 'sv', 'th', 'tr',
  'tw', 'ua', 'vi',
];

locale = langs.indexOf(locale) !== -1 ? locale : 'en';

import(
  /* webpackChunkName: "../build/free-jqgrid/i18n/" */
  'free-jqgrid/js/i18n/grid.locale-' + locale
);
