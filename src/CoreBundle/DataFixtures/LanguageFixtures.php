<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\Language;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LanguageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $list = self::getLanguages();
        foreach ($list as $data) {
            $lang = (new Language())
                ->setOriginalName($data['original_name'])
                ->setEnglishName($data['english_name'])
                ->setIsocode($data['isocode'])
                ->setAvailable(1 === $data['available'])
            ;
            $manager->persist($lang);
        }

        $manager->flush();
    }

    /**
     * The following table contains two types of conventions concerning person names:.
     *
     * "format" - determines how a full person name to be formatted, i.e. in what order the title,
     * the first_name and the last_name to be placed.
     * You might need to correct the value for your language. The possible values are:
     * title first_name last_name  - Western order;
     * title last_name first_name  - Eastern order;
     * title last_name, first_name - Western libraries order.
     * Placing the title (Dr, Mr, Miss, etc) depends on the tradition in you country.
     *
     * @see http://en.wikipedia.org/wiki/Personal_name#Naming_convention
     *
     * "sort_by" - determines you preferable way of sorting person names. The possible values are:
     * first_name                  - sorting names with priority for the first name;
     * last_name                   - sorting names with priority for the last name.
     */
    public static function getLanguages(): array
    {
        return [
            [
                'original_name' => 'العربية',
                'english_name' => 'arabic',
                'isocode' => 'ar',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Asturianu',
                'english_name' => 'asturian',
                'isocode' => 'ast_ES',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Euskara',
                'english_name' => 'basque',
                'isocode' => 'eu_ES',
                'available' => 1,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'বাংলা',
                'english_name' => 'bengali',
                'isocode' => 'bn_BD',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Bosanski',
                'english_name' => 'bosnian',
                'isocode' => 'bs_BA',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Português do Brasil',
                'english_name' => 'brazilian',
                'isocode' => 'pt_PT',
                'available' => 1,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Български',
                'english_name' => 'bulgarian',
                'isocode' => 'bg',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Català',
                'english_name' => 'catalan',
                'isocode' => 'ca_ES',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Hrvatski',
                'english_name' => 'croatian',
                'isocode' => 'hr_HR',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Česky',
                'english_name' => 'czech',
                'isocode' => 'cs_CZ',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Dansk',
                'english_name' => 'danish',
                'isocode' => 'da',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'دری',
                'english_name' => 'dari',
                'isocode' => 'fa_AF',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Nederlands',
                'english_name' => 'dutch',
                'isocode' => 'nl',
                'available' => 1,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'English',
                'english_name' => 'english',
                'isocode' => 'en_US',
                'available' => 1,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            /*[
                'original_name' => 'Estonian',
                'english_name' => 'estonian',
                'isocode' => 'ety',
                'available' => 0,
            ],*/
            [
                'original_name' => 'Esperanto',
                'english_name' => 'esperanto',
                'isocode' => 'eo',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Føroyskt',
                'english_name' => 'faroese',
                'isocode' => 'fo_FO',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Suomi',
                'english_name' => 'finnish',
                'isocode' => 'fi_FI',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Français',
                'english_name' => 'french',
                'isocode' => 'fr_FR',
                'available' => 1,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Furlan',
                'english_name' => 'friulian',
                'isocode' => 'fur',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Galego',
                'english_name' => 'galician',
                'isocode' => 'gl',
                'available' => 0,
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            [
                'original_name' => 'ქართული',
                'english_name' => 'georgian',
                'isocode' => 'ka_GE',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Deutsch',
                'english_name' => 'german',
                'isocode' => 'de',
                'available' => 1,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Ελληνικά',
                'english_name' => 'greek',
                'isocode' => 'el',
                'available' => 1,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'עברית',
                'english_name' => 'hebrew',
                'isocode' => 'he_IL',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'हिन्दी',
                'english_name' => 'hindi',
                'isocode' => 'hi',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Magyar',
                'english_name' => 'hungarian',
                'isocode' => 'hu_HU',
                'available' => 0,
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            [
                'original_name' => 'Bahasa Indonesia',
                'english_name' => 'indonesian',
                'isocode' => 'id_ID',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Italiano',
                'english_name' => 'italian',
                'isocode' => 'it',
                'available' => 1,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => '日本語',
                'english_name' => 'japanese',
                'isocode' => 'ja',
                'available' => 0,
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            [
                'original_name' => '한국어',
                'english_name' => 'korean',
                'isocode' => 'ko_KR',
                'available' => 0,

                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            [
                'original_name' => 'Latviešu',
                'english_name' => 'latvian',
                'isocode' => 'lv_LV',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Lietuvių',
                'english_name' => 'lithuanian',
                'isocode' => 'lt_LT',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Македонски',
                'english_name' => 'macedonian',
                'isocode' => 'mk_MK',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Bahasa Melayu',
                'english_name' => 'malay',
                'isocode' => 'ms_MY',
                'available' => 0,
                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            [
                'original_name' => 'Norsk',
                'english_name' => 'norwegian',
                'isocode' => 'nn_NO',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Occitan',
                'english_name' => 'occitan',
                'isocode' => 'oc',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'پښتو',
                'english_name' => 'pashto',
                'isocode' => 'ps',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'فارسی',
                'english_name' => 'persian',
                'isocode' => 'fa_IR',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Polski',
                'english_name' => 'polish',
                'isocode' => 'pl_PL',
                'available' => 1,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Português europeu',
                'english_name' => 'portuguese',
                'isocode' => 'pt_PT',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Runasimi',
                'english_name' => 'quechua_cusco',
                'isocode' => 'quz_PE',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Română',
                'english_name' => 'romanian',
                'isocode' => 'ro_RO',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Русский',
                'english_name' => 'russian',
                'isocode' => 'ru_RU',
                'available' => 1,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Srpski',
                'english_name' => 'serbian',
                'isocode' => 'sr_RS',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => '中文（简体',
                'english_name' => 'simpl_chinese',
                'isocode' => 'zh_CN',
                'available' => 0,

                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            [
                'original_name' => 'Slovenčina',
                'english_name' => 'slovak',
                'isocode' => 'sk_SK',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Slovenščina',
                'english_name' => 'slovenian',
                'isocode' => 'sl_SI',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'الصومالية',
                'english_name' => 'somali',
                'isocode' => 'so_SO',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Español',
                'english_name' => 'spanish',
                'isocode' => 'es',
                'available' => 0,

                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            [
                'original_name' => 'Kiswahili',
                'english_name' => 'swahili',
                'isocode' => 'sw_KE',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Svenska',
                'english_name' => 'swedish',
                'isocode' => 'sv_SE',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Tagalog',
                'english_name' => 'tagalog',
                'isocode' => 'tl_PH',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'ไทย',
                'english_name' => 'thai',
                'isocode' => 'th',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Tibetan',
                'english_name' => 'tibetan',
                'isocode' => 'bo_CN',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => '繁體中文',
                'english_name' => 'trad_chinese',
                'isocode' => 'zh_TW',
                'available' => 0,

                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            [
                'original_name' => 'Türkçe',
                'english_name' => 'turkish',
                'isocode' => 'tr',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Українська',
                'english_name' => 'ukrainian',
                'isocode' => 'uk_UA',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Tiếng Việt',
                'english_name' => 'vietnamese',
                'isocode' => 'vi_VN',
                'available' => 0,

                'format' => 'title last_name first_name',
                'sort_by' => 'last_name',
            ],
            [
                'original_name' => 'isiXhosa',
                'english_name' => 'xhosa',
                'isocode' => 'xh_ZA',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
            [
                'original_name' => 'Yorùbá',
                'english_name' => 'yoruba',
                'isocode' => 'yo_NG',
                'available' => 0,
                'format' => 'title first_name last_name',
                'sort_by' => 'first_name',
            ],
        ];
    }
}
