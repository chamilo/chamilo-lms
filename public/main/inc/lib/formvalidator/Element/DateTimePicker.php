<?php

/* For licensing terms, see /license.txt */

/**
 * Form element to select a date and hour.
 */
class DateTimePicker extends HTML_QuickForm_text
{
    /**
     * DateTimePicker constructor.
     *
     * @param string       $elementName
     * @param string|array $elementLabel
     * @param array        $attributes
     */
    public function __construct($elementName, $elementLabel = null, $attributes = null)
    {
        if (!isset($attributes['id'])) {
            $attributes['id'] = $elementName;
        }
        $attributes['class'] = 'p-component p-inputtext p-filled';
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_appendName = true;
    }

    /**
     * HTML code to display this datepicker.
     *
     * @return string
     */
    public function toHtml()
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }

        $id = $this->getAttribute('id');
        $value = $this->getValue();

        $formattedValue = '';
        if (!empty($value)) {
            $formattedValue = api_format_date($value, DATE_TIME_FORMAT_LONG_24H);
        }

        $label = $this->getLabel();
        if (is_array($label) && isset($label[0])) {
            $label = $label[0];
        }

        //$resetFieldX = sprintf(get_lang('Reset %s'), $label);

        return '<input '.$this->_getAttrString($this->_attributes).' />'.$this->getElementJS();
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $value = substr($value, 0, 16);
        $this->updateAttributes(['value' => $value]);
    }

    /**
     * Injects the JS for initializing flatpickr with Intl-driven localization.
     * All comments/console messages are in English.
     */
    private function getElementJS(): string
    {
        $localeCode = $this->getLocaleCode();
        $id = $this->getAttribute('id');

        $altFormat = ($localeCode === 'en') ? 'F d, Y - H:i' : 'd F, Y - H:i';

        $js = "<script>
            document.addEventListener('DOMContentLoaded', function () {
              var input = document.getElementById('{$id}');
              if (!input) return;

              function cap(s){ return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }

              // Build a flatpickr locale object using Intl (no external l10n files)
              function buildFlatpickrLocale(loc) {
                try {
                  var fmtWeekLong  = new Intl.DateTimeFormat(loc, { weekday: 'long' });
                  var fmtWeekShort = new Intl.DateTimeFormat(loc, { weekday: 'short' });
                  var fmtMonthLong = new Intl.DateTimeFormat(loc, { month: 'long' });
                  var fmtMonthShort= new Intl.DateTimeFormat(loc, { month: 'short' });

                  // Weekdays: flatpickr expects 0..6 starting on Sunday
                  var sun = new Date(Date.UTC(2020, 0, 5)); // a Sunday
                  var weekdaysLong = [], weekdaysShort = [];
                  for (var i=0;i<7;i++){
                    var d = new Date(sun); d.setUTCDate(sun.getUTCDate()+i);
                    weekdaysLong.push(cap(fmtWeekLong.format(d)));
                    weekdaysShort.push(cap(fmtWeekShort.format(d)));
                  }

                  // Months 0..11
                  var monthsLong = [], monthsShort = [];
                  for (var m=0;m<12;m++){
                    var dm = new Date(Date.UTC(2020, m, 1));
                    monthsLong.push(cap(fmtMonthLong.format(dm)));
                    monthsShort.push(cap(fmtMonthShort.format(dm)));
                  }

                  // First day of week (fallback to Monday if not available)
                  var firstDay = 1; // 0=Sun, 1=Mon
                  try {
                    if (window.Intl && Intl.Locale) {
                      var inf = new Intl.Locale(loc);
                      if (inf.weekInfo && inf.weekInfo.firstDay) {
                        firstDay = (inf.weekInfo.firstDay === 7) ? 0 : inf.weekInfo.firstDay;
                      }
                    }
                  } catch(e){}

                  return {
                    weekdays: { shorthand: weekdaysShort, longhand: weekdaysLong },
                    months:   { shorthand: monthsShort,  longhand: monthsLong  },
                    firstDayOfWeek: firstDay,
                    weekAbbreviation: 'Wk',
                    rangeSeparator: ' – ',
                    time_24hr: true
                  };
                } catch(e) {
                  return 'en';
                }
              }

              function initialize() {
                try {
                  if (!window.flatpickr) return;
                  if (input._flatpickr) { input._flatpickr.destroy(); }

                  var loc = buildFlatpickrLocale('{$localeCode}');

                  // Set as global default when possible
                  if (typeof flatpickr.localize === 'function' && typeof loc === 'object') {
                    flatpickr.localize(loc);
                  }

                  // Also register under the key for string-based locale usage
                  if (flatpickr.l10ns && typeof loc === 'object') {
                    flatpickr.l10ns['{$localeCode}'] = loc;
                  }

                  var instance = flatpickr('#{$id}', {
                    locale: loc,
                    altInput: true,
                    altFormat: '{$altFormat}',
                    enableTime: true,
                    dateFormat: 'Y-m-d H:i',
                    time_24hr: true,
                    wrap: false,
                    onReady: function(selectedDates, dateStr, fp) {
                      const btn = document.createElement('button');
                      btn.textContent = '".get_lang('Validate')."';
                      btn.className = 'flatpickr-validate-btn';
                      btn.type = 'button';
                      btn.onclick = function(){ fp.close(); };
                      fp.calendarContainer.appendChild(btn);

                      try { fp.redraw(); } catch(e){}
                    }
                  });

                  // Ensure l10n is applied and redraw if needed
                  try {
                    if (instance && instance.l10n && typeof loc === 'object') {
                      Object.assign(instance.l10n, loc);
                      instance.redraw();
                    }
                  } catch(e){}

                  // Debug (optional):
                  // console.log('[DateTimePicker]', '{$localeCode}', instance && instance.l10n);
                } catch(e) {
                  console.error('[DateTimePicker] flatpickr init error', e);
                }
              }

              initialize();
            });
            </script>";

        return $js;
    }

    /**
     * Returns a normalized 2-letter locale to be used by JS/Intl.
     * Priority: course > user > platform.
     */
    private function getLocaleCode(): string
    {
        // 1) platform default
        $raw = (string) api_get_language_isocode();

        // 2) user (if not anonymous)
        $user = api_get_user_info();
        if (is_array($user) && !empty($user['language']) && ANONYMOUS != $user['status']) {
            $raw = (string) $user['language'];
        }

        // 3) course (highest priority)
        $course = api_get_course_info();
        if (!empty($course) && !empty($course['language'])) {
            $raw = (string) $course['language'];
        }

        return $this->normalizeIsoKey($raw);
    }

    /**
     * Normalizes any ISO/custom-ISO to a base language code that Intl can handle safely.
     * Rules:
     *  - 'xx'                -> 'xx'
     *  - 'xx_YY' / 'xx-YY'   -> 'xx'   (e.g., pt_PT -> pt, nn_NO -> nn, zh_TW -> zh)
     *  - 'xx_suffix*'        -> 'xx'   (e.g., de_german2 -> de, es_spanish -> es, fr_french2 -> fr)
     *  - 'longtag_ES'        -> 'es'   (e.g., ast_ES, eu_ES -> es, instead of falling back to en)
     *  - otherwise           -> 'en'
     */
    private function normalizeIsoKey(string $raw): string
    {
        $s = strtolower(trim($raw));
        if ($s === '') {
            return 'en';
        }

        // unify separator
        $s = str_replace('-', '_', $s);

        // direct 2-letter language (es, en, fr, de, ...)
        if (preg_match('/^[a-z]{2}$/', $s)) {
            return $s;
        }

        // 'xx_YY' or 'xx_anything' -> keep base 'xx'
        if (preg_match('/^([a-z]{2})_[a-z0-9]+$/', $s, $m)) {
            return $m[1];
        }

        // 'xx_suffix' with digits (custom like es_spanish, de_german2, ...)
        if (preg_match('/^([a-z]{2})_[a-z]+[0-9]*$/', $s, $m)) {
            return $m[1];
        }

        // long language tag followed by region, prefer 'es' if region is ES
        if (preg_match('/^[a-z]{3,}_(..)$/', $s, $m)) {
            return ($m[1] === 'es') ? 'es' : 'en';
        }

        // fallback: extract first 2 letters if available
        if (preg_match('/^([a-z]{2})/', $s, $m)) {
            return $m[1];
        }

        return 'en';
    }
}
