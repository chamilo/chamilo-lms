<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

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

        $baseLang = strtolower(explode('-', $localeCode)[0] ?? $localeCode);
        $altFormat = ($baseLang === 'en') ? 'F d, Y - H:i' : 'd F, Y - H:i';

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
        $raw = '';

        if (class_exists('\Chamilo\CoreBundle\Framework\Container')) {
            $req = Container::getRequest();
            if ($req && $req->getLocale()) {
                $raw = $req->getLocale();
            }
        }

        if ($raw === '' && !empty($_SESSION['_locale']) && is_string($_SESSION['_locale'])) {
            $raw = $_SESSION['_locale'];
        }

        if ($raw === '') {
            $raw = (string) api_get_language_isocode();
        }

        $s = str_replace('_', '-', trim($raw));
        if ($s === '') {
            return 'en-US';
        }

        if (preg_match('/^([A-Za-z]{2,3})-([A-Za-z]{2})$/', $s, $m)) {
            return strtolower($m[1]).'-'.strtoupper($m[2]);
        }

        if (preg_match('/^([A-Za-z]{2,3})$/', $s, $m)) {
            return strtolower($m[1]);
        }

        if (preg_match('/^([A-Za-z]{2,3})[-_].+$/', $s, $m)) {
            return strtolower($m[1]);
        }

        return 'en-US';
    }
}
