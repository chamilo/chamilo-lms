<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Form element to select a date.
 */
class DatePicker extends HTML_QuickForm_text
{
    /**
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
    public function toHtml(): string
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }

        $id = $this->getAttribute('id');
        $value = $this->getValue();

        if (!empty($value)) {
            $value = api_format_date($value, DATE_FORMAT_LONG_NO_DAY);
        }

        $label = $this->getLabel();
        $settingRequiredFields = api_get_setting('registration.required_extra_fields_in_inscription', true);
        $requiredFields = 'false' !== $settingRequiredFields ? $settingRequiredFields : [];

        if (!empty($requiredFields) && $requiredFields['options']) {
            $requiredFields = $requiredFields['options'];
        }
        $variable = str_replace('extra_', '',$id);
        $requiredSymbol = '';
        if (!empty($requiredFields) && in_array($variable, $requiredFields)) {
            $requiredSymbol = '<span class="form_required">*</span>';
        }

        $this->setAttribute('placeholder', get_lang('Select date'));

        return '
        <label>'.$requiredSymbol.$label.'</label>
        <div id="'.$id.'_container" class="flex items-center mt-1 flatpickr-wrapper" data-wrap="true">
            <input '.$this->_getAttrString($this->_attributes).' value="'.$value.'" data-input>
            <div class="flex space-x-1 ml-2" id="button-addon3">
                <button class="btn btn--secondary-outline mr-2" type="button" data-toggle>
                  '.Display::getMdiIcon(ObjectIcon::AGENDA).'
                </button>
                <button class="btn btn--secondary-outline" type="button" data-clear>
                  '.Display::getMdiIcon(ActionIcon::CLOSE).'
                </button>
            </div>
        </div>
    '.$this->getElementJS();
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        if (empty($value)) {
            return;
        }

        $value = substr($value, 0, 16);
        $this->updateAttributes(
            [
                'value' => $value,
            ]
        );
    }

    /**
     * Get the necessary javascript for this datepicker.
     */
    private function getElementJS(): string
    {
        $localeCode = $this->getLocaleCode();
        $id = $this->getAttribute('id');

        $baseLang = strtolower(explode('-', $localeCode)[0] ?? $localeCode);
        $altFormat = ($baseLang === 'en') ? 'F d, Y' : 'd F, Y';

        return "<script>
            window.addEventListener('load', function () {
              var container = document.getElementById('{$id}_container');
              if (!container) return;

              function cap(s){ return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }

              // Build a flatpickr locale object using Intl (no external l10n files)
              function buildFlatpickrLocale(loc) {
                try {
                  var fmtWeekLong  = new Intl.DateTimeFormat(loc, { weekday: 'long' });
                  var fmtWeekShort = new Intl.DateTimeFormat(loc, { weekday: 'short' });
                  var fmtMonthLong = new Intl.DateTimeFormat(loc, { month: 'long' });
                  var fmtMonthShort= new Intl.DateTimeFormat(loc, { month: 'short' });

                  // Weekdays 0..6 starting on Sunday (as flatpickr expects)
                  var sun = new Date(Date.UTC(2020, 0, 5));
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

                  // First day of week (fallback to Monday)
                  var firstDay = 1;
                  try {
                    if (window.Intl && Intl.Locale) {
                      var inf = new Intl.Locale(loc);
                      if (inf.weekInfo && inf.weekInfo.firstDay) {
                        firstDay = (inf.weekInfo.firstDay === 7) ? 0 : inf.weekInfo.firstDay; // 0=Sun
                      }
                    }
                  } catch(e){}

                  return {
                    weekdays: { shorthand: weekdaysShort, longhand: weekdaysLong },
                    months:   { shorthand: monthsShort,  longhand: monthsLong  },
                    firstDayOfWeek: firstDay,
                    weekAbbreviation: 'Wk',
                    rangeSeparator: ' \u2013 ',
                    time_24hr: true
                  };
                } catch(e) {
                  return 'en';
                }
              }

              function initialize() {
                try {
                  if (!window.flatpickr) return;

                  // If already initialized, destroy before re-init (in case something set EN earlier)
                  var input = container.querySelector('[data-input]');
                  if (input && input._flatpickr) { input._flatpickr.destroy(); }

                  var loc = buildFlatpickrLocale('{$localeCode}');

                  // Set as global default when possible
                  if (typeof flatpickr.localize === 'function' && typeof loc === 'object') {
                    flatpickr.localize(loc);
                  }
                  if (flatpickr.l10ns && typeof loc === 'object') {
                    flatpickr.l10ns['{$localeCode}'] = loc;
                  }

                  var instance = flatpickr('#{$id}_container', {
                    locale: loc,
                    altInput: true,
                    altFormat: '{$altFormat}',
                    enableTime: false,
                    dateFormat: 'Y-m-d',
                    time_24hr: true,
                    wrap: true
                  });

                  try {
                    if (instance && instance.l10n && typeof loc === 'object') {
                      Object.assign(instance.l10n, loc);
                      instance.redraw();
                    }
                  } catch(e){}
                } catch(e) {
                  console.error('[DatePicker] flatpickr init error', e);
                }
              }

              initialize();

              // Hide original label if present (kept from your original code)
              try {
                var lbl = document.querySelector('label[for=\"{$id}\"]');
                if (lbl) { lbl.style.display = 'none'; lbl.classList.add('datepicker-label'); }
              } catch(e){}
            });
            </script>";
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
