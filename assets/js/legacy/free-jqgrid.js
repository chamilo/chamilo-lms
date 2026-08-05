import 'free-jqgrid';
import 'free-jqgrid/css/ui.jqgrid.bootstrap4.css';

const parts = document.documentElement.dataset.lang.split(/[-_]/)
const base = parts[0]
const region = (parts[1] || '').toLowerCase()

const langs = [
  'ar', 'bg', 'bs', 'ca', 'cn', 'cs', 'da', 'de', 'el', 'en', 'es', 'fa', 'fi', 'fr', 'gl', 'he', 'hr', 'hu', 'id',
  'is', 'it', 'ja', 'kr', 'lt', 'me', 'nl', 'no', 'pl', 'pt-br', 'pt', 'ro', 'ru', 'sk', 'sl', 'sr', 'sv', 'th', 'tr',
  'tw', 'ua', 'vi',
];

// Try full regional tag first (e.g. pt_BR → "pt-br"), then fall back to base language.
const full = region ? base + '-' + region : ''
let locale = (full && langs.indexOf(full) !== -1) ? full : (langs.indexOf(base) !== -1 ? base : 'en')

import(
  /* webpackChunkName: "../build/free-jqgrid/i18n/" */
  'free-jqgrid/js/i18n/grid.locale-' + locale
);
