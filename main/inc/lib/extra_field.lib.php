<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Entity\Tag;

/**
 * Class ExtraField.
 */
class ExtraField extends Model
{
    public const FIELD_TYPE_TEXT = 1;
    public const FIELD_TYPE_TEXTAREA = 2;
    public const FIELD_TYPE_RADIO = 3;
    public const FIELD_TYPE_SELECT = 4;
    public const FIELD_TYPE_SELECT_MULTIPLE = 5;
    public const FIELD_TYPE_DATE = 6;
    public const FIELD_TYPE_DATETIME = 7;
    public const FIELD_TYPE_DOUBLE_SELECT = 8;
    public const FIELD_TYPE_DIVIDER = 9;
    public const FIELD_TYPE_TAG = 10;
    public const FIELD_TYPE_TIMEZONE = 11;
    public const FIELD_TYPE_SOCIAL_PROFILE = 12;
    public const FIELD_TYPE_CHECKBOX = 13;
    public const FIELD_TYPE_MOBILE_PHONE_NUMBER = 14;
    public const FIELD_TYPE_INTEGER = 15;
    public const FIELD_TYPE_FILE_IMAGE = 16;
    public const FIELD_TYPE_FLOAT = 17;
    public const FIELD_TYPE_FILE = 18;
    public const FIELD_TYPE_VIDEO_URL = 19;
    public const FIELD_TYPE_LETTERS_ONLY = 20;
    public const FIELD_TYPE_ALPHANUMERIC = 21;
    public const FIELD_TYPE_LETTERS_SPACE = 22;
    public const FIELD_TYPE_ALPHANUMERIC_SPACE = 23;
    public const FIELD_TYPE_GEOLOCALIZATION = 24;
    public const FIELD_TYPE_GEOLOCALIZATION_COORDINATES = 25;
    public const FIELD_TYPE_SELECT_WITH_TEXT_FIELD = 26;
    public const FIELD_TYPE_TRIPLE_SELECT = 27;
    public $columns = [
        'id',
        'field_type',
        'variable',
        'display_text',
        'default_value',
        'field_order',
        'visible_to_self',
        'visible_to_others',
        'changeable',
        'filter',
        'extra_field_type',
        //Enable this when field_loggeable is introduced as a table field (2.0)
        //'field_loggeable',
        'created_at',
    ];

    public $ops = [
        'eq' => '=', //equal
        'ne' => '<>', //not equal
        'lt' => '<', //less than
        'le' => '<=', //less than or equal
        'gt' => '>', //greater than
        'ge' => '>=', //greater than or equal
        'bw' => 'LIKE', //begins with
        'bn' => 'NOT LIKE', //doesn't begin with
        'in' => 'LIKE', //is in
        'ni' => 'NOT LIKE', //is not in
        'ew' => 'LIKE', //ends with
        'en' => 'NOT LIKE', //doesn't end with
        'cn' => 'LIKE', //contains
        'nc' => 'NOT LIKE',  //doesn't contain
    ];

    public $type = 'user';
    public $pageName;
    public $pageUrl;
    public $extraFieldType = 0;

    public $table;
    public $table_field_options;
    public $table_field_values;
    public $table_field_tag;
    public $table_field_rel_tag;

    public $handler_id;
    public $primaryKey;

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        parent::__construct();

        $this->type = $type;
        $this->table = Database::get_main_table(TABLE_EXTRA_FIELD);
        $this->table_field_options = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
        $this->table_field_values = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $this->table_field_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $this->table_field_rel_tag = Database::get_main_table(TABLE_MAIN_EXTRA_FIELD_REL_TAG);

        $this->handler_id = 'item_id';

        switch ($this->type) {
            case 'calendar_event':
                $this->extraFieldType = EntityExtraField::CALENDAR_FIELD_TYPE;
                break;
            case 'course':
                $this->extraFieldType = EntityExtraField::COURSE_FIELD_TYPE;
                $this->primaryKey = 'id';
                break;
            case 'user':
                $this->extraFieldType = EntityExtraField::USER_FIELD_TYPE;
                $this->primaryKey = 'id';
                break;
            case 'session':
                $this->extraFieldType = EntityExtraField::SESSION_FIELD_TYPE;
                $this->primaryKey = 'id';
                break;
            case 'exercise':
                $this->extraFieldType = EntityExtraField::EXERCISE_FIELD_TYPE;
                break;
            case 'question':
                $this->extraFieldType = EntityExtraField::QUESTION_FIELD_TYPE;
                break;
            case 'lp':
                $this->extraFieldType = EntityExtraField::LP_FIELD_TYPE;
                break;
            case 'lp_item':
                $this->extraFieldType = EntityExtraField::LP_ITEM_FIELD_TYPE;
                break;
            case 'skill':
                $this->extraFieldType = EntityExtraField::SKILL_FIELD_TYPE;
                break;
            case 'work':
                $this->extraFieldType = EntityExtraField::WORK_FIELD_TYPE;
                break;
            case 'career':
                $this->extraFieldType = EntityExtraField::CAREER_FIELD_TYPE;
                break;
            case 'user_certificate':
                $this->extraFieldType = EntityExtraField::USER_CERTIFICATE;
                break;
            case 'survey':
                $this->extraFieldType = EntityExtraField::SURVEY_FIELD_TYPE;
                break;
            case 'scheduled_announcement':
                $this->extraFieldType = EntityExtraField::SCHEDULED_ANNOUNCEMENT;
                break;
            case 'terms_and_condition':
                $this->extraFieldType = EntityExtraField::TERMS_AND_CONDITION_TYPE;
                break;
            case 'forum_category':
                $this->extraFieldType = EntityExtraField::FORUM_CATEGORY_TYPE;
                break;
            case 'forum_post':
                $this->extraFieldType = EntityExtraField::FORUM_POST_TYPE;
                break;
            case 'track_exercise':
                $this->extraFieldType = EntityExtraField::TRACK_EXERCISE_FIELD_TYPE;
                break;
            case 'portfolio':
                $this->extraFieldType = EntityExtraField::PORTFOLIO_TYPE;
                break;
            case 'lp_view':
                $this->extraFieldType = EntityExtraField::LP_VIEW_TYPE;
                break;
            case 'course_announcement':
                $this->extraFieldType = EntityExtraField::COURSE_ANNOUNCEMENT;
                break;
            case 'message':
                $this->extraFieldType = EntityExtraField::MESSAGE_TYPE;
                break;
            case 'document':
                $this->extraFieldType = EntityExtraField::DOCUMENT_TYPE;
                break;
            case 'attendance_calendar':
                $this->extraFieldType = EntityExtraField::ATTENDANCE_CALENDAR_TYPE;
                break;
        }

        $this->pageUrl = 'extra_fields.php?type='.$this->type;
        // Example QuestionFields
        $this->pageName = get_lang(ucwords($this->type).'Fields');
    }

    /**
     * @return array
     */
    public static function getValidExtraFieldTypes()
    {
        $result = [
            'user',
            'course',
            'session',
            'question',
            'lp',
            'calendar_event',
            'lp_item',
            'skill',
            'work',
            'career',
            'user_certificate',
            'survey',
            'terms_and_condition',
            'forum_category',
            'forum_post',
            'exercise',
            'track_exercise',
            'lp_view',
            'course_announcement',
            'message',
            'document',
            'attendance_calendar',
        ];

        if (api_get_configuration_value('allow_scheduled_announcements')) {
            $result[] = 'scheduled_announcement';
        }

        if (api_get_configuration_value('allow_portfolio_tool')) {
            $result[] = 'portfolio';
        }
        sort($result);

        return $result;
    }

    /**
     * Converts a string like this:
     * France:Paris;Bretagne;Marseille;Lyon|Belgique:Bruxelles;Namur;Liège;Bruges|Peru:Lima;Piura;
     * into
     * array(
     *   'France' =>
     *      array('Paris', 'Bretagne', 'Marseille'),
     *   'Belgique' =>
     *      array('Namur', 'Liège')
     * ), etc.
     *
     * @param string $string
     *
     * @return array
     */
    public static function extra_field_double_select_convert_string_to_array($string)
    {
        $options = explode('|', $string);
        $options_parsed = [];
        $id = 0;

        if (!empty($options)) {
            foreach ($options as $sub_options) {
                $options = explode(':', $sub_options);
                $sub_sub_options = isset($options[1]) ? explode(';', $options[1]) : [];
                $options_parsed[$id] = [
                    'label' => $options[0],
                    'options' => $sub_sub_options,
                ];
                $id++;
            }
        }

        return $options_parsed;
    }

    /**
     * @param $string
     *
     * @return array
     */
    public static function tripleSelectConvertStringToArray($string)
    {
        $options = [];
        foreach (explode('|', $string) as $i => $item0) {
            $level1 = explode('\\', $item0);

            foreach ($level1 as $j => $item1) {
                if (0 === $j) {
                    $options[] = ['label' => $item1, 'options' => []];

                    continue;
                }

                foreach (explode(':', $item1) as $k => $item2) {
                    if (0 === $k) {
                        $options[$i]['options'][] = ['label' => $item2, 'options' => []];

                        continue;
                    }

                    $options[$i]['options'][$j - 1]['options'][] = explode(';', $item2);
                }
            }
        }

        array_walk_recursive(
            $options,
            function (&$item) {
                $item = trim($item);
            }
        );

        return $options;
    }

    /**
     * @param array $options the result of the get_field_options_by_field() array
     *
     * @return string
     */
    public static function extra_field_double_select_convert_array_to_string($options)
    {
        $string = null;
        $optionsParsed = self::extra_field_double_select_convert_array_to_ordered_array($options);

        if (!empty($optionsParsed)) {
            foreach ($optionsParsed as $option) {
                foreach ($option as $key => $item) {
                    $string .= $item['display_text'];
                    if (0 == $key) {
                        $string .= ':';
                    } else {
                        if (isset($option[$key + 1])) {
                            $string .= ';';
                        }
                    }
                }
                $string .= '|';
            }
        }

        if (!empty($string)) {
            $string = substr($string, 0, strlen($string) - 1);
        }

        return $string;
    }

    /**
     * @param array $options The result of the get_field_options_by_field() array
     *
     * @return string
     */
    public static function extraFieldSelectWithTextConvertArrayToString(array $options)
    {
        $parsedOptions = self::extra_field_double_select_convert_array_to_ordered_array($options);

        if (empty($parsedOptions)) {
            return '';
        }

        $string = '';
        foreach ($parsedOptions as $options) {
            $option = current($options);
            $string .= $option['display_text'];
            $string .= '|';
        }

        return rtrim($string, '|');
    }

    /**
     * @return string
     */
    public static function tripleSelectConvertArrayToString(array $options)
    {
        $parsedOptions = self::tripleSelectConvertArrayToOrderedArray($options);
        $string = '';
        foreach ($parsedOptions['level1'] as $item1) {
            $string .= $item1['display_text'];
            $level2 = self::getOptionsFromTripleSelect($parsedOptions['level2'], $item1['id']);

            foreach ($level2 as $item2) {
                $string .= '\\'.$item2['display_text'].':';
                $level3 = self::getOptionsFromTripleSelect($parsedOptions['level3'], $item2['id']);

                $string .= implode(';', array_column($level3, 'display_text'));
            }

            $string .= '|';
        }

        return trim($string, '\\|;');
    }

    /**
     * @param string $variable
     * @param string $dataValue
     *
     * @return string
     */
    public static function getLocalizationJavascript($variable, $dataValue)
    {
        $dataValue = addslashes($dataValue);
        $html = "<script>
            $(function() {
                if (typeof google === 'object') {
                    var address = '$dataValue';
                    initializeGeo{$variable}(address, false);

                    $('#geolocalization_extra_{$variable}').on('click', function() {
                        var address = $('#{$variable}').val();
                        initializeGeo{$variable}(address, false);
                        return false;
                    });

                    $('#myLocation_extra_{$variable}').on('click', function() {
                        myLocation{$variable}();
                        return false;
                    });

                    // When clicking enter
                    $('#{$variable}').keypress(function(event) {
                        if (event.which == 13) {
                            $('#geolocalization_extra_{$variable}').click();
                            return false;
                        }
                    });

                    // On focus out update city
                    $('#{$variable}').focusout(function() {
                        $('#geolocalization_extra_{$variable}').click();
                        return false;
                    });

                    return;
                }

                $('#map_extra_{$variable}')
                    .html('<div class=\"alert alert-info\">"
            .addslashes(get_lang('YouNeedToActivateTheGoogleMapsPluginInAdminPlatformToSeeTheMap'))
            ."</div>');
            });

            function myLocation{$variable}()
            {
                if (navigator.geolocation) {
                    var geoPosition = function(position) {
                        var lat = position.coords.latitude;
                        var lng = position.coords.longitude;
                        var latLng = new google.maps.LatLng(lat, lng);
                        initializeGeo{$variable}(false, latLng);
                    };

                    var geoError = function(error) {
                        alert('Geocode ".get_lang('Error').": ' + error);
                    };

                    var geoOptions = {
                        enableHighAccuracy: true
                    };
                    navigator.geolocation.getCurrentPosition(geoPosition, geoError, geoOptions);
                }
            }

            function initializeGeo{$variable}(address, latLng)
            {
                var geocoder = new google.maps.Geocoder();
                var latlng = new google.maps.LatLng(-34.397, 150.644);
                var myOptions = {
                    zoom: 15,
                    center: latlng,
                    mapTypeControl: true,
                    mapTypeControlOptions: {
                        style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
                    },
                    navigationControl: true,
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                };

                map_{$variable} = new google.maps.Map(
                    document.getElementById('map_extra_{$variable}'),
                    myOptions
                );

                var parameter = address ? {'address': address} : latLng ? {'latLng': latLng} : false;

                if (geocoder && parameter) {
                    geocoder.geocode(parameter, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (status != google.maps.GeocoderStatus.ZERO_RESULTS) {
                                map_{$variable}.setCenter(results[0].geometry.location);

                                // get city and country
                                var defaultAddress = results[0].formatted_address;
                                var city = '';
                                var country = '';

                                for (var i=0; i<results[0].address_components.length; i++) {
                                    if (results[0].address_components[i].types[0] == \"locality\") {
                                        //this is the object you are looking for City
                                        city = results[0].address_components[i];
                                    }
                                    /*if (results[j].address_components[i].types[0] == \"administrative_area_level_1\") {
                                        //this is the object you are looking for State
                                        region = results[0].address_components[i];
                                    }*/
                                    if (results[0].address_components[i].types[0] == \"country\") {
                                        //this is the object you are looking for
                                        country = results[0].address_components[i];
                                    }
                                }

                                if (city && city.long_name && country && country.long_name) {
                                    defaultAddress = city.long_name + ', ' + country.long_name;
                                }
                                $('#{$variable}').val(defaultAddress);
                                $('#{$variable}_coordinates').val(
                                    results[0].geometry.location.lat()+','+results[0].geometry.location.lng()
                                );

                                var infowindow = new google.maps.InfoWindow({
                                    content: '<b>' + $('#extra_{$variable}').val() + '</b>',
                                    size: new google.maps.Size(150, 50)
                                });

                                var marker = new google.maps.Marker({
                                    position: results[0].geometry.location,
                                    map: map_{$variable},
                                    title: $('#extra_{$variable}').val()
                                });
                                google.maps.event.addListener(marker, 'click', function() {
                                    infowindow.open(map_{$variable}, marker);
                                });
                            } else {
                                alert('".get_lang('NotFound')."');
                            }
                        } else {
                            alert('Geocode ".get_lang('Error').': '.get_lang('AddressField').' '.get_lang('NotFound')."');
                        }
                    });
                }
            }
            </script>";

        return $html;
    }

    /**
     * @param string $variable
     * @param string $text
     *
     * @return string
     */
    public static function getLocalizationInput($variable, $text)
    {
        $html = '
                <div class="form-group">
                    <label for="geolocalization_extra_'.$variable.'"
                        class="col-sm-2 control-label"></label>
                    <div class="col-sm-8">
                        <button class="btn btn-default"
                            id="geolocalization_extra_'.$variable.'"
                            name="geolocalization_extra_'.$variable.'"
                            type="submit">
                            <em class="fa fa-map-marker"></em> '.get_lang('SearchGeolocalization').'
                        </button>
                        <button class="btn btn-default" id="myLocation_extra_'.$variable.'"
                            name="myLocation_extra_'.$variable.'"
                            type="submit">
                            <em class="fa fa-crosshairs"></em> '.get_lang('MyLocation').'
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="map_extra_'.$variable.'" class="col-sm-2 control-label">
                        '.$text.' - '.get_lang('Map').'
                    </label>
                    <div class="col-sm-8">
                        <div name="map_extra_'.$variable.'"
                            id="map_extra_'.$variable.'" style="width:100%; height:300px;">
                        </div>
                    </div>
                </div>
            ';

        return $html;
    }

    /**
     * @return int
     */
    public function get_count()
    {
        $em = Database::getManager();
        $query = $em->getRepository('ChamiloCoreBundle:ExtraField')->createQueryBuilder('e');
        $query->select('count(e.id)');
        $query->where('e.extraFieldType = :type');
        $query->setParameter('type', $this->getExtraFieldType());

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function getExtraFieldType()
    {
        return (int) $this->extraFieldType;
    }

    /**
     * @param string $sidx
     * @param string $sord
     * @param int    $start
     * @param int    $limit
     *
     * @return array
     */
    public function getAllGrid($sidx, $sord, $start, $limit)
    {
        switch ($sidx) {
            case 'field_order':
                $sidx = 'e.fieldOrder';
                break;
            case 'variable':
                $sidx = 'e.variable';
                break;
            case 'display_text':
                $sidx = 'e.displayText';
                break;
            case 'changeable':
                $sidx = 'e.changeable';
                break;
            case 'visible_to_self':
                $sidx = 'e.visibleToSelf';
                break;
            case 'visible_to_others':
                $sidx = 'e.visibleToOthers';
                break;
            case 'filter':
                $sidx = 'e.filter';
                break;
        }
        $em = Database::getManager();
        $query = $em->getRepository('ChamiloCoreBundle:ExtraField')->createQueryBuilder('e');
        $query->select('e')
            ->where('e.extraFieldType = :type')
            ->setParameter('type', $this->getExtraFieldType())
            ->orderBy($sidx, $sord)
            ->setFirstResult($start)
            ->setMaxResults($limit);

        return $query->getQuery()->getArrayResult();
    }

    /**
     * Get all the field info for tags.
     *
     * @param string $variable
     *
     * @return array|bool
     */
    public function get_handler_field_info_by_tags($variable)
    {
        $variable = Database::escape_string($variable);
        $sql = "SELECT * FROM {$this->table}
                WHERE
                    variable = '$variable' AND
                    extra_field_type = $this->extraFieldType";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');
            $row['display_text'] = $this->translateDisplayName(
                $row['variable'],
                $row['display_text']
            );
            $row['options'] = [];

            // All the tags of the field
            $sql = "SELECT * FROM $this->table_field_tag
                    WHERE field_id='".intval($row['id'])."'
                    ORDER BY id ASC";
            $result = Database::query($sql);
            while ($option = Database::fetch_array($result, 'ASSOC')) {
                $row['options'][$option['id']] = $option;
            }

            return $row;
        } else {
            return false;
        }
    }

    /**
     * Translate the display text for a extra field.
     *
     * @param string $variable
     * @param string $defaultDisplayText
     *
     * @return string
     */
    public static function translateDisplayName($variable, $defaultDisplayText)
    {
        $camelCase = api_underscore_to_camel_case($variable);

        return isset($GLOBALS[$camelCase]) ? $GLOBALS[$camelCase] : $defaultDisplayText;
    }

    /**
     * @param int $fieldId
     *
     * @return array|bool
     */
    public function getFieldInfoByFieldId($fieldId)
    {
        $fieldId = (int) $fieldId;
        $sql = "SELECT * FROM {$this->table}
                WHERE
                    id = '$fieldId' AND
                    extra_field_type = $this->extraFieldType";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');

            // All the options of the field
            $sql = "SELECT * FROM $this->table_field_options
                    WHERE field_id='".$fieldId."'
                    ORDER BY option_order ASC";
            $result = Database::query($sql);
            while ($option = Database::fetch_array($result)) {
                $row['options'][$option['id']] = $option;
            }

            return $row;
        } else {
            return false;
        }
    }

    /**
     * Add elements to a form.
     *
     * @param FormValidator $form                            The form object to which to attach this element
     * @param int           $itemId                          The item (course, user, session, etc) this extra_field is linked to
     * @param array         $exclude                         Variables of extra field to exclude
     * @param bool          $filter                          Whether to get only the fields with the "filter" flag set to 1 (true)
     *                                                       or not (false)
     * @param bool          $useTagAsSelect                  Whether to show tag fields as select drop-down or not
     * @param array         $showOnlyTheseFields             Limit the extra fields shown to just the list given here
     * @param array         $orderFields                     An array containing the names of the fields shown, in the right order
     * @param array         $extraData
     * @param bool          $orderDependingDefaults
     * @param bool          $adminPermissions
     * @param array         $separateExtraMultipleSelect
     * @param array         $customLabelsExtraMultipleSelect
     * @param bool          $addEmptyOptionSelects
     * @param array         $introductionTextList
     * @param array         $requiredFields
     * @param bool          $hideGeoLocalizationDetails
     *
     * @throws Exception
     *
     * @return array|bool If relevant, returns a one-element array with JS code to be added to the page HTML headers.
     *                    Returns false if the form object was not given
     */
    public function addElements(
        $form,
        $itemId = 0,
        $exclude = [],
        $filter = false,
        $useTagAsSelect = false,
        $showOnlyTheseFields = [],
        $orderFields = [],
        $extraData = [],
        $orderDependingDefaults = false,
        $adminPermissions = false,
        $separateExtraMultipleSelect = [],
        $customLabelsExtraMultipleSelect = [],
        $addEmptyOptionSelects = false,
        $introductionTextList = [],
        $requiredFields = [],
        $hideGeoLocalizationDetails = false,
        $help = false
    ) {
        if (empty($form)) {
            return false;
        }

        $itemId = (int) $itemId;
        $form->addHidden('item_id', $itemId);
        $extraData = false;
        if (!empty($itemId)) {
            $extraData = $this->get_handler_extra_data($itemId);
            if (!empty($showOnlyTheseFields)) {
                $setData = [];
                foreach ($showOnlyTheseFields as $variable) {
                    $extraName = 'extra_'.$variable;
                    if (in_array($extraName, array_keys($extraData))) {
                        $setData[$extraName] = $extraData[$extraName];
                    }
                }
                $form->setDefaults($setData);
            } else {
                $form->setDefaults($extraData);
            }
        }

        $conditions = [];
        if ($filter) {
            $conditions = ['filter = ?' => 1];
        }

        $extraFields = $this->get_all($conditions, 'option_order');
        $extra = $this->set_extra_fields_in_form(
            $form,
            $extraData,
            $adminPermissions,
            $extraFields,
            $itemId,
            $exclude,
            $useTagAsSelect,
            $showOnlyTheseFields,
            $orderFields,
            $orderDependingDefaults,
            $separateExtraMultipleSelect,
            $customLabelsExtraMultipleSelect,
            $addEmptyOptionSelects,
            $introductionTextList,
            $hideGeoLocalizationDetails,
            $help
        );

        if (!empty($requiredFields)) {
            /** @var HTML_QuickForm_input $element */
            foreach ($form->getElements() as $element) {
                $name = str_replace('extra_', '', $element->getName());
                if (in_array($name, $requiredFields)) {
                    $form->setRequired($element);
                }
            }
        }

        return $extra;
    }

    /**
     * Return an array of all the extra fields available for this item.
     *
     * @param int $itemId (session_id, question_id, course id)
     *
     * @return array
     */
    public function get_handler_extra_data($itemId)
    {
        if (empty($itemId)) {
            return [];
        }

        $extra_data = [];
        $fields = $this->get_all();
        $field_values = new ExtraFieldValue($this->type);

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $field_value = $field_values->get_values_by_handler_and_field_id(
                    $itemId,
                    $field['id']
                );

                if (self::FIELD_TYPE_TAG == $field['field_type']) {
                    $tags = UserManager::get_user_tags_to_string(
                        $itemId,
                        $field['id'],
                        false
                    );
                    $extra_data['extra_'.$field['variable']] = $tags;

                    continue;
                }

                if ($field_value) {
                    $variable = $field['variable'];
                    $field_value = $field_value['value'];
                    switch ($field['field_type']) {
                        case self::FIELD_TYPE_TAG:
                            $tags = UserManager::get_user_tags_to_string(
                                $itemId,
                                $field['id'],
                                false
                            );

                            $extra_data['extra_'.$field['variable']] = $tags;
                            break;
                        case self::FIELD_TYPE_DOUBLE_SELECT:
                        case self::FIELD_TYPE_SELECT_WITH_TEXT_FIELD:
                            $selected_options = explode('::', $field_value);
                            $firstOption = isset($selected_options[0]) ? $selected_options[0] : '';
                            $secondOption = isset($selected_options[1]) ? $selected_options[1] : '';
                            $extra_data['extra_'.$field['variable']]['extra_'.$field['variable']] = $firstOption;
                            $extra_data['extra_'.$field['variable']]['extra_'.$field['variable'].'_second'] = $secondOption;

                            break;
                        case self::FIELD_TYPE_SELECT_MULTIPLE:
                            $field_value = explode(';', $field_value);
                            $extra_data['extra_'.$field['variable']] = $field_value;
                            break;
                        case self::FIELD_TYPE_RADIO:
                            $extra_data['extra_'.$field['variable']]['extra_'.$field['variable']] = $field_value;
                            break;
                        case self::FIELD_TYPE_TRIPLE_SELECT:
                            [$level1, $level2, $level3] = explode(';', $field_value);

                            $extra_data["extra_$variable"]["extra_$variable"] = $level1;
                            $extra_data["extra_$variable"]["extra_{$variable}_second"] = $level2;
                            $extra_data["extra_$variable"]["extra_{$variable}_third"] = $level3;
                            break;
                        default:
                            $extra_data['extra_'.$field['variable']] = $field_value;
                            break;
                    }
                } else {
                    // Set default values
                    if (isset($field['field_default_value']) &&
                        !empty($field['field_default_value'])
                    ) {
                        $extra_data['extra_'.$field['variable']] = $field['field_default_value'];
                    }
                }
            }
        }

        return $extra_data;
    }

    /**
     * Get an array of all the values from the extra_field and extra_field_options tables
     * based on the current object's type.
     *
     * @param array $conditions
     * @param null  $order_field_options_by
     *
     * @return array
     */
    public function get_all($conditions = [], $order_field_options_by = null)
    {
        $conditions = Database::parse_conditions(['where' => $conditions]);

        if (empty($conditions)) {
            $conditions .= ' WHERE extra_field_type = '.$this->extraFieldType;
        } else {
            $conditions .= ' AND extra_field_type = '.$this->extraFieldType;
        }

        $sql = "SELECT * FROM $this->table
                $conditions
                ORDER BY field_order ASC
        ";

        $result = Database::query($sql);
        $extraFields = Database::store_result($result, 'ASSOC');

        $option = new ExtraFieldOption($this->type);
        if (!empty($extraFields)) {
            foreach ($extraFields as &$extraField) {
                $extraField['display_text'] = $this->translateDisplayName(
                    $extraField['variable'],
                    $extraField['display_text']
                );
                $extraField['options'] = $option->get_field_options_by_field(
                    $extraField['id'],
                    false,
                    $order_field_options_by
                );
            }
        }

        return $extraFields;
    }

    /**
     * Add an element that matches the given extra field to the given $form object.
     *
     * @param FormValidator $form                The form these fields are to be attached to
     * @param array         $extraData
     * @param bool          $adminPermissions    Whether the display is considered without edition limits (true) or not
     *                                           (false)
     * @param array         $extra
     * @param int           $itemId              The item (course, user, session, etc) this extra_field is attached to
     * @param array         $exclude             Extra fields to be skipped, by textual ID
     * @param bool          $useTagAsSelect      Whether to show tag fields as select drop-down or not
     * @param array         $showOnlyTheseFields Limit the extra fields shown to just the list given here
     * @param array         $orderFields         An array containing the names of the fields shown, in the right order
     *
     * @throws Exception
     *
     * @return array If relevant, returns a one-element array with JS code to be added to the page HTML headers
     */
    public function set_extra_fields_in_form(
        $form,
        $extraData,
        $adminPermissions = false,
        $extra = [],
        $itemId = null,
        $exclude = [],
        $useTagAsSelect = false,
        $showOnlyTheseFields = [],
        $orderFields = [],
        $orderDependingDefaults = false,
        $separateExtraMultipleSelect = [],
        $customLabelsExtraMultipleSelect = [],
        $addEmptyOptionSelects = false,
        $introductionTextList = [],
        $hideGeoLocalizationDetails = false,
        $help = false
    ) {
        $jquery_ready_content = null;
        if (!empty($extra)) {
            $newOrder = [];
            if (!empty($orderFields)) {
                foreach ($orderFields as $order) {
                    foreach ($extra as $field_details) {
                        if ($order == $field_details['variable']) {
                            $newOrder[] = $field_details;
                        }
                    }
                }
                $extra = $newOrder;
            }

            foreach ($extra as $field_details) {
                if (!empty($showOnlyTheseFields)) {
                    if (!in_array($field_details['variable'], $showOnlyTheseFields)) {
                        continue;
                    }
                }

                // Getting default value id if is set
                $defaultValueId = null;
                if (isset($field_details['options']) && !empty($field_details['options'])) {
                    $valueToFind = null;
                    if (isset($field_details['field_default_value'])) {
                        $valueToFind = $field_details['field_default_value'];
                    }
                    // If a value is found we override the default value
                    if (isset($extraData['extra_'.$field_details['variable']])) {
                        $valueToFind = $extraData['extra_'.$field_details['variable']];
                    }

                    foreach ($field_details['options'] as $option) {
                        if ($option['option_value'] == $valueToFind) {
                            $defaultValueId = $option['id'];
                        }
                    }
                }

                if (!$adminPermissions) {
                    if (0 == $field_details['visible_to_self']) {
                        continue;
                    }

                    if (in_array($field_details['variable'], $exclude)) {
                        continue;
                    }
                }

                if (!empty($introductionTextList) &&
                    in_array($field_details['variable'], array_keys($introductionTextList))
                ) {
                    $form->addHtml($introductionTextList[$field_details['variable']]);
                }

                $freezeElement = false;
                if (!$adminPermissions) {
                    $freezeElement = 0 == $field_details['visible_to_self'] || 0 == $field_details['changeable'];
                }

                $translatedDisplayText = get_lang($field_details['display_text'], true);
                $translatedDisplayHelpText = '';
                if ($help) {
                    $translatedDisplayHelpText .= get_lang($field_details['display_text'].'Help');
                }
                if (!empty($translatedDisplayText)) {
                    if (!empty($translatedDisplayHelpText)) {
                        // In this case, exceptionally, display_text is an array
                        // which is then treated by display_form()
                        $field_details['display_text'] = [$translatedDisplayText, $translatedDisplayHelpText];
                    } else {
                        // We have an helper text, use it
                        $field_details['display_text'] = $translatedDisplayText;
                    }
                }

                switch ($field_details['field_type']) {
                    case self::FIELD_TYPE_TEXT:
                        $form->addElement(
                            'text',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            [
                                'id' => 'extra_'.$field_details['variable'],
                            ]
                        );
                        $form->applyFilter(
                            'extra_'.$field_details['variable'],
                            'stripslashes'
                        );
                        $form->applyFilter(
                            'extra_'.$field_details['variable'],
                            'trim'
                        );
                        $form->applyFilter(
                            'extra_'.$field_details['variable'],
                            'html_filter'
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_TEXTAREA:
                        $form->addHtmlEditor(
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            false,
                            false,
                            [
                                'ToolbarSet' => 'Profile',
                                'Width' => '100%',
                                'Height' => '130',
                                'id' => 'extra_'.$field_details['variable'],
                            ]
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_RADIO:
                        $group = [];
                        if (isset($field_details['options']) &&
                            !empty($field_details['options'])
                        ) {
                            foreach ($field_details['options'] as $option_details) {
                                $options[$option_details['option_value']] = $option_details['display_text'];
                                $group[] = $form->createElement(
                                    'radio',
                                    'extra_'.$field_details['variable'],
                                    $option_details['option_value'],
                                    $option_details['display_text'].'<br />',
                                    $option_details['option_value']
                                );
                            }
                        }
                        $form->addGroup(
                            $group,
                            'extra_'.$field_details['variable'],
                            $field_details['display_text']
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_CHECKBOX:
                        $group = [];
                        if (isset($field_details['options']) &&
                            !empty($field_details['options'])
                        ) {
                            foreach ($field_details['options'] as $option_details) {
                                $options[$option_details['option_value']] = $option_details['display_text'];
                                $group[] = $form->createElement(
                                    'checkbox',
                                    'extra_'.$field_details['variable'],
                                    $option_details['option_value'],
                                    $option_details['display_text'].'<br />',
                                    $option_details['option_value']
                                );
                            }
                        } else {
                            $fieldVariable = "extra_{$field_details['variable']}";
                            $checkboxAttributes = [];
                            if (is_array($extraData) &&
                                array_key_exists($fieldVariable, $extraData)
                            ) {
                                if (!empty($extraData[$fieldVariable])) {
                                    $checkboxAttributes['checked'] = 1;
                                }
                            }

                            if (empty($checkboxAttributes) &&
                                isset($field_details['default_value']) && empty($extraData)) {
                                if (1 == $field_details['default_value']) {
                                    $checkboxAttributes['checked'] = 1;
                                }
                            }

                            // We assume that is a switch on/off with 1 and 0 as values
                            $group[] = $form->createElement(
                                'checkbox',
                                'extra_'.$field_details['variable'],
                                null,
                                get_lang('Yes'),
                                $checkboxAttributes
                            );
                        }

                        $form->addGroup(
                            $group,
                            'extra_'.$field_details['variable'],
                            $field_details['display_text']
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_SELECT:
                        $this->addSelectElement($form, $field_details, $defaultValueId, $freezeElement);
                        break;
                    case self::FIELD_TYPE_SELECT_MULTIPLE:
                        $options = [];
                        if (empty($defaultValueId)) {
                            $options[''] = get_lang('SelectAnOption');
                        }

                        if (isset($field_details['options']) && !empty($field_details['options'])) {
                            foreach ($field_details['options'] as $optionDetails) {
                                $options[$optionDetails['option_value']] = $optionDetails['display_text'];
                            }
                        }

                        $form->addElement(
                            'select',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            $options,
                            [
                                'multiple' => 'multiple',
                                'id' => 'extra_'.$field_details['variable'],
                            ]
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_DATE:
                        $form->addDatePicker('extra_'.$field_details['variable'], $field_details['display_text']);
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_DATETIME:
                        $form->addDateTimePicker(
                            'extra_'.$field_details['variable'],
                            $field_details['display_text']
                        );

                        $defaults = [];
                        if (EntityExtraField::LP_ITEM_FIELD_TYPE !== (int) $field_details['extra_field_type']) {
                            $defaults['extra_'.$field_details['variable']] = api_get_local_time();
                        }
                        if (!isset($form->_defaultValues['extra_'.$field_details['variable']])) {
                            $form->setDefaults($defaults);
                        }
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_DOUBLE_SELECT:
                        $jquery_ready_content .= self::addDoubleSelectElement(
                            $form,
                            $field_details,
                            $extraData,
                            $freezeElement
                        );
                        break;
                    case self::FIELD_TYPE_DIVIDER:
                        $form->addHtml(
                            '
                            <div class="form-group ">
                                <div class="col-sm-12">
                                    <div class="panel-separator">
                                       <h4 id="'.$field_details['variable'].'" class="form-separator">'
                            .$field_details['display_text'].'
                                       </h4>
                                    </div>
                                </div>
                            </div>
                        '
                        );
                        break;
                    case self::FIELD_TYPE_TAG:
                        $variable = $field_details['variable'];
                        $field_id = $field_details['id'];
                        $separateValue = 0;
                        if (isset($separateExtraMultipleSelect[$field_details['variable']])) {
                            $separateValue = $separateExtraMultipleSelect[$field_details['variable']];
                        }

                        $selectedOptions = [];
                        if ($separateValue > 0) {
                            $em = Database::getManager();
                            $fieldTags = $em
                                ->getRepository('ChamiloCoreBundle:ExtraFieldRelTag')
                                ->findBy(
                                    [
                                        'fieldId' => $field_id,
                                        'itemId' => $itemId,
                                    ]
                                );
                            // ofaj

                            for ($i = 0; $i < $separateValue; $i++) {
                                $tagsSelect = $form->addElement(
                                    'select',
                                    'extra_'.$field_details['variable'].'['.$i.']',
                                    $customLabelsExtraMultipleSelect[$field_details['variable']][$i],
                                    null,
                                    ['id' => 'extra_'.$field_details['variable'].'_'.$i]
                                );

                                if ($addEmptyOptionSelects) {
                                    $tagsSelect->addOption(
                                        '',
                                        ''
                                    );
                                }

                                foreach ($fieldTags as $fieldTag) {
                                    $tag = $em->find('ChamiloCoreBundle:Tag', $fieldTag->getTagId());

                                    if (empty($tag)) {
                                        continue;
                                    }

                                    $tagsSelect->addOption(
                                        $tag->getTag(),
                                        $tag->getTag()
                                    );
                                }
                            }
                        } else {
                            $tagsSelect = $form->addSelect(
                                "extra_{$field_details['variable']}",
                                $field_details['display_text'],
                                [],
                                ['style' => 'width: 100%;']
                            );

                            if (false === $useTagAsSelect) {
                                $tagsSelect->setAttribute('class', null);
                            }

                            $tagsSelect->setAttribute(
                                'id',
                                "extra_{$field_details['variable']}"
                            );
                            $tagsSelect->setMultiple(true);

                            $selectedOptions = [];
                            if ('user' === $this->type) {
                                // The magic should be here
                                $user_tags = UserManager::get_user_tags(
                                    $itemId,
                                    $field_details['id']
                                );

                                if (is_array($user_tags) && count($user_tags) > 0) {
                                    foreach ($user_tags as $tag) {
                                        if (empty($tag['tag'])) {
                                            continue;
                                        }
                                        $tagsSelect->addOption(
                                            $tag['tag'],
                                            $tag['tag'],
                                            [
                                                'selected' => 'selected',
                                                'class' => 'selected',
                                            ]
                                        );
                                        $selectedOptions[] = $tag['tag'];
                                    }
                                }
                                $url = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php';
                            } else {
                                $em = Database::getManager();
                                $fieldTags = $em->getRepository(
                                    'ChamiloCoreBundle:ExtraFieldRelTag'
                                )
                                    ->findBy(
                                        [
                                            'fieldId' => $field_id,
                                            'itemId' => $itemId,
                                        ]
                                    );

                                /** @var ExtraFieldRelTag $fieldTag */
                                foreach ($fieldTags as $fieldTag) {
                                    /** @var Tag $tag */
                                    $tag = $em->find('ChamiloCoreBundle:Tag', $fieldTag->getTagId());

                                    if (empty($tag)) {
                                        continue;
                                    }
                                    $tagsSelect->addOption(
                                        $tag->getTag(),
                                        $tag->getTag()
                                    );
                                    $selectedOptions[] = $tag->getTag();
                                }

                                if (!empty($extraData) && isset($extraData['extra_'.$field_details['variable']])) {
                                    $data = $extraData['extra_'.$field_details['variable']];
                                    if (!empty($data)) {
                                        foreach ($data as $option) {
                                            $tagsSelect->addOption(
                                                $option,
                                                $option
                                            );
                                        }
                                    }
                                }

                                if ($useTagAsSelect) {
                                    $fieldTags = $em->getRepository('ChamiloCoreBundle:ExtraFieldRelTag')
                                        ->findBy(
                                            [
                                                'fieldId' => $field_id,
                                            ]
                                        );
                                    $tagsAdded = [];
                                    foreach ($fieldTags as $fieldTag) {
                                        $tag = $em->find('ChamiloCoreBundle:Tag', $fieldTag->getTagId());

                                        if (empty($tag)) {
                                            continue;
                                        }

                                        $tagText = $tag->getTag();

                                        if (in_array($tagText, $tagsAdded)) {
                                            continue;
                                        }

                                        $tagsSelect->addOption(
                                            $tag->getTag(),
                                            $tag->getTag(),
                                            []
                                        );

                                        $tagsAdded[] = $tagText;
                                    }
                                }
                                $url = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php';
                            }

                            $allowAsTags = 'true';

                            if ('portfolio' === $this->type) {
                                $allowAsTags = 'false';
                            }

                            $form->setDefaults(
                                [
                                    'extra_'.$field_details['variable'] => $selectedOptions,
                                ]
                            );

                            if (false == $useTagAsSelect) {
                                $jquery_ready_content .= "
                                $('#extra_$variable').select2({
                                    ajax: {
                                        url: '$url?a=search_tags&field_id=$field_id&type={$this->type}',
                                        processResults: function (data) {
                                            return {
                                                results: data.items
                                            }
                                        }
                                    },
                                    cache: false,
                                    tags: $allowAsTags,
                                    tokenSeparators: [','],
                                    placeholder: '".get_lang('StartToType')."'
                                });
                            ";
                            }
                        }

                        break;
                    case self::FIELD_TYPE_TIMEZONE:
                        $form->addElement(
                            'select',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            api_get_timezones(),
                            ''
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_SOCIAL_PROFILE:
                        // get the social network's favicon
                        $extra_data_variable = isset($extraData['extra_'.$field_details['variable']])
                            ? $extraData['extra_'.$field_details['variable']]
                            : null;
                        $field_default_value = isset($field_details['field_default_value'])
                            ? $field_details['field_default_value']
                            : null;
                        $icon_path = UserManager::get_favicon_from_url(
                            $extra_data_variable,
                            $field_default_value
                        );
                        // special hack for hi5
                        $leftpad = '1.7';
                        $top = '0.4';
                        $domain = parse_url($icon_path, PHP_URL_HOST);
                        if ('www.hi5.com' === $domain || 'hi5.com' === $domain) {
                            $leftpad = '3';
                            $top = '0';
                        }
                        // print the input field
                        $form->addElement(
                            'text',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            [
                                'size' => 60,
                                'size' => implode(
                                    '; ',
                                    [
                                        "background-image: url('$icon_path')",
                                        'background-repeat: no-repeat',
                                        "background-position: 0.4em {$top}em",
                                        "padding-left: {$leftpad}em",
                                    ]
                                ),
                            ]
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        $form->applyFilter('extra_'.$field_details['variable'], 'html_filter');
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_MOBILE_PHONE_NUMBER:
                        $form->addElement(
                            'text',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'].' ('.get_lang('CountryDialCode').')',
                            ['size' => 40, 'placeholder' => '(xx)xxxxxxxxx']
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        $form->applyFilter('extra_'.$field_details['variable'], 'mobile_phone_number_filter');
                        $form->applyFilter('extra_'.$field_details['variable'], 'html_filter');
                        $form->addRule(
                            'extra_'.$field_details['variable'],
                            get_lang('MobilePhoneNumberWrong'),
                            'mobile_phone_number'
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_INTEGER:
                        $form->addElement(
                            'number',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            ['class' => 'span1', 'step' => 1]
                        );

                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        $form->applyFilter('extra_'.$field_details['variable'], 'intval');

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_FILE_IMAGE:
                        $fieldVariable = "extra_{$field_details['variable']}";
                        $fieldTexts = [
                            $field_details['display_text'],
                        ];

                        if (is_array($extraData) && array_key_exists($fieldVariable, $extraData)) {
                            if (file_exists(api_get_path(SYS_UPLOAD_PATH).$extraData[$fieldVariable])) {
                                $fieldTexts[] = Display::img(
                                    api_get_path(WEB_UPLOAD_PATH).$extraData[$fieldVariable],
                                    $field_details['display_text'],
                                    ['width' => '300']
                                );
                            }
                        }

                        if ('Image' === $fieldTexts[0]) {
                            $fieldTexts[0] = get_lang($fieldTexts[0]);
                        }

                        $form->addFile(
                            $fieldVariable,
                            $fieldTexts,
                            ['accept' => 'image/*', 'id' => 'extra_image', 'crop_image' => 'true']
                        );

                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');

                        $allowedPictureTypes = ['jpg', 'jpeg', 'png', 'gif'];
                        $form->addRule(
                            'extra_'.$field_details['variable'],
                            get_lang('OnlyImagesAllowed').' ('.implode(',', $allowedPictureTypes).')',
                            'filetype',
                            $allowedPictureTypes
                        );

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_FLOAT:
                        $form->addElement(
                            'number',
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            ['class' => 'span1', 'step' => '0.01']
                        );

                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');
                        $form->applyFilter('extra_'.$field_details['variable'], 'floatval');

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_FILE:
                        $fieldVariable = "extra_{$field_details['variable']}";
                        $fieldTexts = [
                            $field_details['display_text'],
                        ];

                        if (is_array($extraData) &&
                            array_key_exists($fieldVariable, $extraData)
                        ) {
                            if (file_exists(api_get_path(SYS_UPLOAD_PATH).$extraData[$fieldVariable])) {
                                $linkToDelete = '';
                                $divItemId = $field_details['variable'];
                                if (api_is_platform_admin()) {
                                    $url = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?type='.$this->type;
                                    $url .= '&a=delete_file&field_id='.$field_details['id'].'&item_id='.$itemId;

                                    $deleteId = $field_details['variable'].'_delete';
                                    $form->addHtml(
                                        "
                                        <script>
                                            $(function() {
                                                $('#".$deleteId."').on('click', function() {
                                                    $.ajax({
                                                        type: 'GET',
                                                        url: '".$url."',
                                                        success: function(result) {
                                                            if (result == 1) {
                                                                $('#".$divItemId."').html('".get_lang('Deleted')."');
                                                            }
                                                        }
                                                    });
                                                });
                                            });
                                        </script>
                                    "
                                    );

                                    $linkToDelete = '&nbsp;'.Display::url(
                                            Display::return_icon('delete.png', get_lang('Delete')),
                                            'javascript:void(0)',
                                            ['id' => $deleteId]
                                        );
                                }
                                $fieldTexts[] = '<div id="'.$divItemId.'">'.Display::url(
                                        basename($extraData[$fieldVariable]),
                                        api_get_path(WEB_UPLOAD_PATH).$extraData[$fieldVariable],
                                        [
                                            'title' => $field_details['display_text'],
                                            'target' => '_blank',
                                        ]
                                    ).$linkToDelete.'</div>';
                            }
                        }

                        $form->addElement(
                            'file',
                            $fieldVariable,
                            $fieldTexts,
                            []
                        );

                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');
                        $form->applyFilter('extra_'.$field_details['variable'], 'trim');

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_VIDEO_URL:
                        $form->addUrl(
                            "extra_{$field_details['variable']}",
                            $field_details['display_text'],
                            false,
                            ['placeholder' => 'https://']
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_LETTERS_ONLY:
                        $form->addTextLettersOnly(
                            "extra_{$field_details['variable']}",
                            $field_details['display_text']
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_ALPHANUMERIC:
                        $form->addTextAlphanumeric(
                            "extra_{$field_details['variable']}",
                            $field_details['display_text']
                        );
                        $form->applyFilter(
                            'extra_'.$field_details['variable'],
                            'stripslashes'
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_LETTERS_SPACE:
                        $form->addTextLettersAndSpaces(
                            "extra_{$field_details['variable']}",
                            $field_details['display_text']
                        );
                        $form->applyFilter('extra_'.$field_details['variable'], 'stripslashes');

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_ALPHANUMERIC_SPACE:
                        $form->addTextAlphanumericAndSpaces(
                            "extra_{$field_details['variable']}",
                            $field_details['display_text']
                        );
                        $form->applyFilter(
                            'extra_'.$field_details['variable'],
                            'stripslashes'
                        );
                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                    case self::FIELD_TYPE_GEOLOCALIZATION:
                        $dataValue = isset($extraData['extra_'.$field_details['variable']])
                            ? $extraData['extra_'.$field_details['variable']]
                            : '';

                        $form->addGeoLocationMapField(
                            'extra_'.$field_details['variable'],
                            $field_details['display_text'],
                            $dataValue,
                            $hideGeoLocalizationDetails
                        );

                        if ($freezeElement) {
                            $form->freeze('extra_'.$field_details['variable']);
                        }
                        break;
                    case self::FIELD_TYPE_SELECT_WITH_TEXT_FIELD:
                        $jquery_ready_content .= $this->addSelectWithTextFieldElement(
                            $form,
                            $field_details,
                            $freezeElement
                        );
                        break;
                    case self::FIELD_TYPE_TRIPLE_SELECT:
                        $jquery_ready_content .= $this->addTripleSelectElement(
                            $form,
                            $field_details,
                            is_array($extraData) ? $extraData : [],
                            $freezeElement
                        );
                        break;
                }
            }
        }

        $return = [];
        $return['jquery_ready_content'] = $jquery_ready_content;

        return $return;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public static function extra_field_double_select_convert_array_to_ordered_array($options)
    {
        $optionsParsed = [];
        if (!empty($options)) {
            foreach ($options as $option) {
                if (0 == $option['option_value']) {
                    $optionsParsed[$option['id']][] = $option;
                } else {
                    $optionsParsed[$option['option_value']][] = $option;
                }
            }
        }

        return $optionsParsed;
    }

    /**
     * @return array
     */
    public static function tripleSelectConvertArrayToOrderedArray(array $options)
    {
        $level1 = self::getOptionsFromTripleSelect($options, 0);
        $level2 = [];
        $level3 = [];

        foreach ($level1 as $item1) {
            $level2 += self::getOptionsFromTripleSelect($options, $item1['id']);
        }

        foreach ($level2 as $item2) {
            $level3 += self::getOptionsFromTripleSelect($options, $item2['id']);
        }

        return ['level1' => $level1, 'level2' => $level2, 'level3' => $level3];
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function get_all_extra_field_by_type($type)
    {
        // all the information of the field
        $sql = "SELECT * FROM {$this->table}
                WHERE
                    field_type = '".Database::escape_string($type)."' AND
                    extra_field_type = $this->extraFieldType
                ";
        $result = Database::query($sql);

        $return = [];
        while ($row = Database::fetch_array($result)) {
            $return[] = $row['id'];
        }

        return $return;
    }

    /**
     * @param int $id
     */
    public function get_field_type_by_id($id)
    {
        $types = $this->get_field_types();
        if (isset($types[$id])) {
            return $types[$id];
        }

        return null;
    }

    /**
     * @return array
     */
    public function get_field_types()
    {
        return $this->get_extra_fields_by_handler($this->type);
    }

    /**
     * @param string $handler
     *
     * @return array
     */
    public static function get_extra_fields_by_handler($handler)
    {
        $types = [];
        $types[self::FIELD_TYPE_TEXT] = get_lang('FieldTypeText');
        $types[self::FIELD_TYPE_TEXTAREA] = get_lang('FieldTypeTextarea');
        $types[self::FIELD_TYPE_RADIO] = get_lang('FieldTypeRadio');
        $types[self::FIELD_TYPE_SELECT] = get_lang('FieldTypeSelect');
        $types[self::FIELD_TYPE_SELECT_MULTIPLE] = get_lang('FieldTypeSelectMultiple');
        $types[self::FIELD_TYPE_DATE] = get_lang('FieldTypeDate');
        $types[self::FIELD_TYPE_DATETIME] = get_lang('FieldTypeDatetime');
        $types[self::FIELD_TYPE_DOUBLE_SELECT] = get_lang('FieldTypeDoubleSelect');
        $types[self::FIELD_TYPE_DIVIDER] = get_lang('FieldTypeDivider');
        $types[self::FIELD_TYPE_TAG] = get_lang('FieldTypeTag');
        $types[self::FIELD_TYPE_TIMEZONE] = get_lang('FieldTypeTimezone');
        $types[self::FIELD_TYPE_SOCIAL_PROFILE] = get_lang('FieldTypeSocialProfile');
        $types[self::FIELD_TYPE_MOBILE_PHONE_NUMBER] = get_lang('FieldTypeMobilePhoneNumber');
        $types[self::FIELD_TYPE_CHECKBOX] = get_lang('FieldTypeCheckbox');
        $types[self::FIELD_TYPE_INTEGER] = get_lang('FieldTypeInteger');
        $types[self::FIELD_TYPE_FILE_IMAGE] = get_lang('FieldTypeFileImage');
        $types[self::FIELD_TYPE_FLOAT] = get_lang('FieldTypeFloat');
        $types[self::FIELD_TYPE_FILE] = get_lang('FieldTypeFile');
        $types[self::FIELD_TYPE_VIDEO_URL] = get_lang('FieldTypeVideoUrl');
        $types[self::FIELD_TYPE_LETTERS_ONLY] = get_lang('FieldTypeOnlyLetters');
        $types[self::FIELD_TYPE_ALPHANUMERIC] = get_lang('FieldTypeAlphanumeric');
        $types[self::FIELD_TYPE_LETTERS_SPACE] = get_lang('FieldTypeLettersSpaces');
        $types[self::FIELD_TYPE_ALPHANUMERIC_SPACE] = get_lang('FieldTypeAlphanumericSpaces');
        $types[self::FIELD_TYPE_GEOLOCALIZATION] = get_lang('Geolocalization');
        $types[self::FIELD_TYPE_GEOLOCALIZATION_COORDINATES] = get_lang('GeolocalizationCoordinates');
        $types[self::FIELD_TYPE_SELECT_WITH_TEXT_FIELD] = get_lang('FieldTypeSelectWithTextField');
        $types[self::FIELD_TYPE_TRIPLE_SELECT] = get_lang('FieldTypeTripleSelect');

        switch ($handler) {
            case 'course':
            case 'session':
            case 'user':
            case 'skill':
                break;
        }

        return $types;
    }

    /**
     * @param array $params
     * @param bool  $show_query
     *
     * @return int|bool
     */
    public function save($params, $show_query = false)
    {
        $fieldInfo = self::get_handler_field_info_by_field_variable($params['variable']);
        $params = $this->clean_parameters($params);
        $params['extra_field_type'] = $this->extraFieldType;

        if ($fieldInfo) {
            return $fieldInfo['id'];
        } else {
            $id = parent::save($params, $show_query);
            if ($id) {
                $fieldOption = new ExtraFieldOption($this->type);
                $params['field_id'] = $id;
                $fieldOption->save($params);
            }

            return $id;
        }
    }

    /**
     * Gets the set of values of an extra_field searching for the variable name.
     *
     * Example:
     * <code>
     * <?php
     * $extraField = new ExtraField('lp_item');
     * $extraFieldArray =  $extraField->get_handler_field_info_by_field_variable('authorlpitem');
     * echo "<pre>".var_export($extraFieldArray,true)."</pre>";
     * ?>
     * </code>
     *
     * @param string $variable
     *
     * @return array|bool
     */
    public function get_handler_field_info_by_field_variable($variable)
    {
        $variable = Database::escape_string($variable);
        $sql = "SELECT * FROM {$this->table}
                WHERE
                    variable = '$variable' AND
                    extra_field_type = $this->extraFieldType";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');
            if ($row) {
                $row['display_text'] = self::translateDisplayName($row['variable'], $row['display_text']);

                // All the options of the field
                $sql = "SELECT * FROM $this->table_field_options
                    WHERE field_id='".intval($row['id'])."'
                    ORDER BY option_order ASC";
                $result = Database::query($sql);
                while ($option = Database::fetch_array($result)) {
                    $row['options'][$option['id']] = $option;
                }

                return $row;
            }
        }

        return false;
    }

    public function getHandlerEntityByFieldVariable(string $variable)
    {
        return Database::getManager()
            ->getRepository('ChamiloCoreBundle:ExtraField')
            ->findOneBy(['variable' => $variable, 'extraFieldType' => $this->extraFieldType]);
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function clean_parameters($params)
    {
        if (!isset($params['variable']) || empty($params['variable'])) {
            $params['variable'] = $params['display_text'];
        }

        $params['variable'] = trim(strtolower(str_replace(' ', '_', $params['variable'])));

        if (!isset($params['field_order'])) {
            $max_order = self::get_max_field_order();
            $params['field_order'] = $max_order;
        } else {
            $params['field_order'] = (int) $params['field_order'];
        }

        return $params;
    }

    /**
     * @return int
     */
    public function get_max_field_order()
    {
        $sql = "SELECT MAX(field_order)
                FROM {$this->table}
                WHERE
                    extra_field_type = '.$this->extraFieldType.'";
        $res = Database::query($sql);

        $order = 0;
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_row($res);
            $order = $row[0] + 1;
        }

        return $order;
    }

    /**
     * {@inheritdoc}
     */
    public function update($params, $showQuery = false)
    {
        $params = $this->clean_parameters($params);
        if (isset($params['id'])) {
            $fieldOption = new ExtraFieldOption($this->type);
            $params['field_id'] = $params['id'];
            if (empty($params['field_type'])) {
                $params['field_type'] = $this->type;
            }
            $fieldOption->save($params, $showQuery);
        }

        return parent::update($params, $showQuery);
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function delete($id)
    {
        $em = Database::getManager();
        $items = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findBy(['field' => $id]);
        if ($items) {
            foreach ($items as $item) {
                $em->remove($item);
            }
            $em->flush();
        }
        $field_option = new ExtraFieldOption($this->type);
        $field_option->delete_all_options_by_field_id($id);

        $session_field_values = new ExtraFieldValue($this->type);
        $session_field_values->delete_all_values_by_field_id($id);

        return parent::delete($id);
    }

    /**
     * @param $breadcrumb
     * @param $action
     */
    public function setupBreadcrumb(&$breadcrumb, $action)
    {
        if ('add' === $action) {
            $breadcrumb[] = ['url' => $this->pageUrl, 'name' => $this->pageName];
            $breadcrumb[] = ['url' => '#', 'name' => get_lang('Add')];
        } elseif ('edit' === $action) {
            $breadcrumb[] = ['url' => $this->pageUrl, 'name' => $this->pageName];
            $breadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];
        } else {
            $breadcrumb[] = ['url' => '#', 'name' => $this->pageName];
        }
    }

    /**
     * Displays the title + grid.
     */
    public function display()
    {
        // action links
        echo '<div class="actions">';
        echo '<a href="../admin/index.php">';
        echo Display::return_icon(
            'back.png',
            get_lang('BackTo').' '.get_lang('PlatformAdmin'),
            '',
            ICON_SIZE_MEDIUM
        );
        echo '</a>';
        echo '<a href="'.api_get_self().'?action=add&type='.$this->type.'">';
        echo Display::return_icon(
            'add_user_fields.png',
            get_lang('Add'),
            '',
            ICON_SIZE_MEDIUM
        );
        echo '</a>';
        echo '</div>';
        echo Display::grid_html($this->type.'_fields');
    }

    /**
     * @return array
     */
    public function getJqgridColumnNames()
    {
        return [
            get_lang('Name'),
            get_lang('FieldLabel'),
            get_lang('Type'),
            get_lang('FieldChangeability'),
            get_lang('VisibleToSelf'),
            get_lang('VisibleToOthers'),
            get_lang('Filter'),
            get_lang('FieldOrder'),
            get_lang('Actions'),
        ];
    }

    /**
     * @return array
     */
    public function getJqgridColumnModel()
    {
        return [
            [
                'name' => 'display_text',
                'index' => 'display_text',
                'width' => '140',
                'align' => 'left',
            ],
            [
                'name' => 'variable',
                'index' => 'variable',
                'width' => '90',
                'align' => 'left',
                'sortable' => 'true',
            ],
            [
                'name' => 'field_type',
                'index' => 'field_type',
                'width' => '70',
                'align' => 'left',
                'sortable' => 'true',
            ],
            [
                'name' => 'changeable',
                'index' => 'changeable',
                'width' => '35',
                'align' => 'left',
                'sortable' => 'true',
            ],
            [
                'name' => 'visible_to_self',
                'index' => 'visible_to_self',
                'width' => '45',
                'align' => 'left',
                'sortable' => 'true',
            ],
            [
                'name' => 'visible_to_others',
                'index' => 'visible_to_others',
                'width' => '35',
                'align' => 'left',
                'sortable' => 'true',
            ],
            [
                'name' => 'filter',
                'index' => 'filter',
                'width' => '30',
                'align' => 'left',
                'sortable' => 'true',
            ],
            [
                'name' => 'field_order',
                'index' => 'field_order',
                'width' => '25',
                'align' => 'left',
                'sortable' => 'true',
            ],
            [
                'name' => 'actions',
                'index' => 'actions',
                'width' => '40',
                'align' => 'left',
                'formatter' => 'action_formatter',
                'sortable' => 'false',
            ],
        ];
    }

    /**
     * @param string $url
     * @param string $action
     *
     * @return FormValidator
     */
    public function return_form($url, $action)
    {
        $form = new FormValidator($this->type.'_field', 'post', $url);

        $form->addHidden('type', $this->type);
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $form->addHidden('id', $id);

        // Setting the form elements
        $header = get_lang('Add');
        $defaults = [];

        if ('edit' === $action) {
            $header = get_lang('Modify');
            // Setting the defaults
            $defaults = $this->get($id, false);
        }

        $form->addHeader($header);

        if ('edit' === $action) {
            $translateUrl = api_get_path(WEB_CODE_PATH).'extrafield/translate.php?'
                .http_build_query(['extra_field' => $id]);
            $translateButton = Display::toolbarButton(get_lang('TranslateThisTerm'), $translateUrl, 'language', 'link');

            $form->addText(
                'display_text',
                [get_lang('Title'), $translateButton]
            );
        } else {
            $form->addText('display_text', get_lang('Title'));
        }

        // Field type
        $types = self::get_field_types();

        $form->addElement(
            'select',
            'field_type',
            get_lang('FieldType'),
            $types,
            ['id' => 'field_type']
        );
        $form->addLabel(get_lang('Example'), '<div id="example">-</div>');
        $form->addElement(
            'text',
            'variable',
            [
                get_lang('SysId'),
                get_lang('ExtraFieldIdComment'),
            ]
        );
        $form->addElement(
            'text',
            'field_options',
            get_lang('FieldPossibleValues'),
            ['id' => 'field_options', 'class' => 'span6']
        );

        $fieldWithOptions = [
            self::FIELD_TYPE_RADIO,
            self::FIELD_TYPE_SELECT_MULTIPLE,
            self::FIELD_TYPE_SELECT,
            self::FIELD_TYPE_TAG,
            self::FIELD_TYPE_DOUBLE_SELECT,
            self::FIELD_TYPE_SELECT_WITH_TEXT_FIELD,
            self::FIELD_TYPE_TRIPLE_SELECT,
        ];

        if ('edit' == $action) {
            if (in_array($defaults['field_type'], $fieldWithOptions)) {
                $url = Display::url(
                    get_lang('EditExtraFieldOptions'),
                    'extra_field_options.php?type='.$this->type.'&field_id='.$id
                );
                $form->addLabel(null, $url);

                if (self::FIELD_TYPE_SELECT == $defaults['field_type']) {
                    $urlWorkFlow = Display::url(
                        get_lang('EditExtraFieldWorkFlow'),
                        'extra_field_workflow.php?type='.$this->type.'&field_id='.$id
                    );
                    $form->addLabel(null, $urlWorkFlow);
                }

                $form->freeze('field_options');
            }
        }
        $form->addText(
            'default_value',
            get_lang('FieldDefaultValue'),
            false,
            ['id' => 'default_value']
        );

        $group = [];
        $group[] = $form->createElement('radio', 'visible_to_self', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'visible_to_self', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('VisibleToSelf'), null, false);

        $group = [];
        $group[] = $form->createElement('radio', 'visible_to_others', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'visible_to_others', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('VisibleToOthers'), null, false);

        $group = [];
        $group[] = $form->createElement('radio', 'changeable', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'changeable', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldChangeability'), null, false);

        $group = [];
        $group[] = $form->createElement('radio', 'filter', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'filter', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldFilter'), null, false);

        /* Enable this when field_loggeable is introduced as a table field (2.0)
        $group   = array();
        $group[] = $form->createElement('radio', 'field_loggeable', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_loggeable', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldLoggeable'), '', false);
        */

        $form->addNumeric('field_order', get_lang('FieldOrder'), ['step' => 1, 'min' => 0]);

        if ('edit' == $action) {
            $option = new ExtraFieldOption($this->type);
            $defaults['field_options'] = $option->get_field_options_by_field_to_string($id);
            $form->addButtonUpdate(get_lang('Modify'));
        } else {
            $defaults['visible_to_self'] = 0;
            $defaults['visible_to_others'] = 0;
            $defaults['changeable'] = 0;
            $defaults['filter'] = 0;
            $form->addButtonCreate(get_lang('Add'));
        }

        /*if (!empty($defaults['created_at'])) {
            $defaults['created_at'] = api_convert_and_format_date($defaults['created_at']);
        }
        if (!empty($defaults['updated_at'])) {
            $defaults['updated_at'] = api_convert_and_format_date($defaults['updated_at']);
        }*/
        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('display_text', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('field_type', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    /**
     * Gets an element.
     *
     * @param int  $id
     * @param bool $translateDisplayText Optional
     *
     * @return array
     */
    public function get($id, $translateDisplayText = true)
    {
        $info = parent::get($id);

        if ($translateDisplayText) {
            $info['display_text'] = self::translateDisplayName($info['variable'], $info['display_text']);
        }

        return $info;
    }

    /**
     * @param $token
     *
     * @return string
     */
    public function getJqgridActionLinks($token)
    {
        //With this function we can add actions to the jgrid (edit, delete, etc)
        $editIcon = Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL);
        $deleteIcon = Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL);
        $confirmMessage = addslashes(
            api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)
        );

        $editButton = <<<JAVASCRIPT
            <a href="?action=edit&type={$this->type}&id=' + options.rowId + '" class="btn btn-link btn-xs">\
                $editIcon\
            </a>
JAVASCRIPT;
        $deleteButton = <<<JAVASCRIPT
            <a \
                onclick="if (!confirm(\'$confirmMessage\')) {return false;}" \
                href="?sec_token=$token&type={$this->type}&id=' + options.rowId + '&action=delete" \
                class="btn btn-link btn-xs">\
                $deleteIcon\
            </a>
JAVASCRIPT;

        return "function action_formatter(cellvalue, options, rowObject) {
            return '$editButton $deleteButton';
        }";
    }

    /**
     * @param array $columns
     * @param array $column_model
     * @param array $extraFields
     *
     * @return array
     */
    public function getRules(&$columns, &$column_model, $extraFields = [], $checkExtraFieldExistence = false)
    {
        $fields = $this->get_all(
            [
                'visible_to_self = ? AND filter = ?' => [1, 1],
            ],
            'display_text'
        );
        $extraFieldOption = new ExtraFieldOption($this->type);

        $rules = [];
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $search_options = [];
                $type = 'text';
                if (in_array($field['field_type'], [self::FIELD_TYPE_SELECT, self::FIELD_TYPE_DOUBLE_SELECT])) {
                    $type = 'select';
                    $search_options['sopt'] = ['eq', 'ne']; //equal not equal
                } else {
                    $search_options['sopt'] = ['cn', 'nc']; //contains not contains
                }

                $search_options['searchhidden'] = 'true';
                $search_options['defaultValue'] = isset($search_options['field_default_value'])
                    ? $search_options['field_default_value']
                    : null;

                if (self::FIELD_TYPE_DOUBLE_SELECT == $field['field_type']) {
                    // Add 2 selects
                    $options = $extraFieldOption->get_field_options_by_field($field['id']);
                    $options = self::extra_field_double_select_convert_array_to_ordered_array($options);

                    $first_options = [];
                    if (!empty($options)) {
                        foreach ($options as $option) {
                            foreach ($option as $sub_option) {
                                if (0 == $sub_option['option_value']) {
                                    $first_options[] = $sub_option['field_id'].'#'.$sub_option['id'].':'
                                        .$sub_option['display_text'];
                                }
                            }
                        }
                    }

                    $search_options['value'] = implode(';', $first_options);
                    $search_options['dataInit'] = 'fill_second_select';

                    // First
                    $column_model[] = [
                        'name' => 'extra_'.$field['variable'],
                        'index' => 'extra_'.$field['variable'],
                        'width' => '100',
                        'hidden' => 'true',
                        'search' => 'true',
                        'stype' => 'select',
                        'searchoptions' => $search_options,
                    ];
                    $columns[] = $field['display_text'].' (1)';
                    $rules[] = [
                        'field' => 'extra_'.$field['variable'],
                        'op' => 'cn',
                    ];

                    // Second
                    $search_options['value'] = $field['id'].':';
                    $search_options['dataInit'] = 'register_second_select';

                    $column_model[] = [
                        'name' => 'extra_'.$field['variable'].'_second',
                        'index' => 'extra_'.$field['variable'].'_second',
                        'width' => '100',
                        'hidden' => 'true',
                        'search' => 'true',
                        'stype' => 'select',
                        'searchoptions' => $search_options,
                    ];
                    $columns[] = $field['display_text'].' (2)';
                    $rules[] = ['field' => 'extra_'.$field['variable'].'_second', 'op' => 'cn'];
                    continue;
                } else {
                    $search_options['value'] = $extraFieldOption->getFieldOptionsToString(
                        $field['id'],
                        false,
                        'display_text'
                    );
                }
                $column_model[] = [
                    'name' => 'extra_'.$field['variable'],
                    'index' => 'extra_'.$field['variable'],
                    'width' => '100',
                    'hidden' => 'true',
                    'search' => 'true',
                    'stype' => $type,
                    'searchoptions' => $search_options,
                ];
                $columns[] = $field['display_text'];
                $rules[] = [
                    'field' => 'extra_'.$field['variable'],
                    'op' => 'cn',
                    'data' => '',
                ];
            }
        }

        return $rules;
    }

    public function processExtraFieldSearch($values, $form, $alias, $condition = 'OR')
    {
        // Parse params.
        $fields = [];
        foreach ($values as $key => $value) {
            if (substr($key, 0, 6) !== 'extra_' &&
                substr($key, 0, 7) !== '_extra_'
            ) {
                continue;
            }
            if (!empty($value)) {
                $fields[$key] = $value;
            }
        }

        $extraFieldsAll = $this->get_all(['visible_to_self = ? AND filter = ?' => [1, 1]], 'option_order');
        $extraFieldsType = array_column($extraFieldsAll, 'field_type', 'variable');
        $extraFields = array_column($extraFieldsAll, 'variable');
        $filter = new stdClass();
        $defaults = [];
        foreach ($fields as $variable => $col) {
            $variableNoExtra = str_replace('extra_', '', $variable);
            if (isset($values[$variable]) && !empty($values[$variable]) &&
                in_array($variableNoExtra, $extraFields)
            ) {
                $rule = new stdClass();
                $rule->field = $variable;
                $rule->op = 'in';
                $data = $col;
                if (is_array($data) && array_key_exists($variable, $data)) {
                    $data = $col;
                }
                $rule->data = $data;
                $filter->rules[] = $rule;
                $filter->groupOp = 'AND';

                if ($extraFieldsType[$variableNoExtra] == ExtraField::FIELD_TYPE_TAG) {
                    $tagElement = $form->getElement($variable);
                    $tags = [];
                    foreach ($values[$variable] as $tag) {
                        $tag = Security::remove_XSS($tag);
                        $tags[] = $tag;
                        $tagElement->addOption(
                            $tag,
                            $tag
                        );
                    }
                    $defaults[$variable] = $tags;
                } else {
                    if (is_array($data)) {
                        $defaults[$variable] = array_map(['Security', 'remove_XSS'], $data);
                    } else {
                        $defaults[$variable] = Security::remove_XSS($data);
                    }
                }
            }
        }

        $result = $this->getExtraFieldRules($filter, 'extra_', $condition);
        $conditionArray = $result['condition_array'];

        $whereCondition = '';
        $extraCondition = '';
        if (!empty($conditionArray)) {
            $extraCondition = ' ( ';
            $extraCondition .= implode(' AND ', $conditionArray);
            $extraCondition .= ' ) ';
        }
        $whereCondition .= $extraCondition;
        $conditions = $this->parseConditions(
            [
                'where' => $whereCondition,
                'extra' => $result['extra_fields'],
            ],
            $alias
        );

        return ['condition' => $conditions, 'fields' => $fields, 'defaults' => $defaults];
    }

    /**
     * @param        $filters
     * @param string $stringToSearch
     *
     * @return array
     */
    public function getExtraFieldRules($filters, $stringToSearch = 'extra_', $condition = 'OR')
    {
        $extraFields = [];
        $conditionArray = [];

        // Getting double select if exists
        $double_select = [];
        if (is_object($filters) &&
            property_exists($filters, 'rules') &&
            is_array($filters->rules) &&
            !empty($filters->rules)
        ) {
            foreach ($filters->rules as $rule) {
                if (empty($rule)) {
                    continue;
                }
                if (false === strpos($rule->field, '_second')) {
                } else {
                    $my_field = str_replace('_second', '', $rule->field);
                    $double_select[$my_field] = $rule->data;
                }
            }

            foreach ($filters->rules as $rule) {
                if (empty($rule)) {
                    continue;
                }
                if (false === strpos($rule->field, $stringToSearch)) {
                    // normal fields
                    $field = $rule->field;
                    if (isset($rule->data) && is_string($rule->data) && -1 != $rule->data) {
                        $conditionArray[] = $this->get_where_clause($field, $rule->op, $rule->data);
                    }
                } else {
                    // Extra fields
                    $ruleField = Database::escapeField($rule->field);
                    if (false === strpos($rule->field, '_second')) {
                        // No _second
                        $original_field = str_replace($stringToSearch, '', $rule->field);
                        $field_option = $this->get_handler_field_info_by_field_variable($original_field);

                        switch ($field_option['field_type']) {
                            case self::FIELD_TYPE_DOUBLE_SELECT:
                                if (isset($double_select[$rule->field])) {
                                    $data = explode('#', $rule->data);
                                    $rule->data = $data[1].'::'.$double_select[$rule->field];
                                } else {
                                    // only was sent 1 select
                                    if (is_string($rule->data)) {
                                        $data = explode('#', $rule->data);
                                        $rule->data = $data[1];
                                    }
                                }

                                if (!isset($rule->data)) {
                                    $conditionArray[] = ' ('
                                        .$this->get_where_clause($rule->field, $rule->op, $rule->data)
                                        .') ';
                                    $extraFields[] = ['field' => $ruleField, 'id' => $field_option['id']];
                                }
                                break;
                            case self::FIELD_TYPE_TAG:
                                if (isset($rule->data)) {
                                    if (is_int($rule->data) && -1 == $rule->data) {
                                        break;
                                    }
                                    // Where will be injected in the parseConditions()
                                    //$where = $this->get_where_clause($rule->field, $rule->op, $rule->data, 'OR');
                                    //$conditionArray[] = " ( $where ) ";
                                    $extraFields[] = [
                                        'field' => $ruleField,
                                        'id' => $field_option['id'],
                                        'data' => $rule->data,
                                    ];
                                }
                                break;
                            default:
                                if (isset($rule->data)) {
                                    if (is_int($rule->data) && -1 == $rule->data) {
                                        break;
                                    }
                                    $where = $this->get_where_clause($rule->field, $rule->op, $rule->data, 'OR');
                                    $conditionArray[] = " ( $where ) ";
                                    $extraFields[] = [
                                        'field' => $ruleField,
                                        'id' => $field_option['id'],
                                        'data' => $rule->data,
                                    ];
                                }
                                break;
                        }
                    } else {
                        $my_field = str_replace('_second', '', $rule->field);
                        $original_field = str_replace($stringToSearch, '', $my_field);
                        $field_option = $this->get_handler_field_info_by_field_variable($original_field);
                        $extraFields[] = [
                            'field' => $ruleField,
                            'id' => $field_option['id'],
                        ];
                    }
                }
            }
        }

        return ['extra_fields' => $extraFields, 'condition_array' => $conditionArray];
    }

    /**
     * @param $col
     * @param $oper
     * @param $val
     * @param $conditionBetweenOptions
     *
     * @return string
     */
    public function get_where_clause($col, $oper, $val, $conditionBetweenOptions = 'OR')
    {
        $col = Database::escapeField($col);

        if (empty($col)) {
            return '';
        }

        $conditionBetweenOptions = in_array($conditionBetweenOptions, ['OR', 'AND']) ? $conditionBetweenOptions : 'OR';
        if ('bw' === $oper || 'bn' === $oper) {
            $val .= '%';
        }
        if ('ew' === $oper || 'en' === $oper) {
            $val = '%'.$val;
        }
        if ('cn' === $oper || 'nc' === $oper || 'in' === $oper || 'ni' === $oper) {
            if (is_array($val)) {
                $result = '"%'.implode(';', $val).'%"';
                foreach ($val as $item) {
                    $item = trim($item);
                    $result .= ' '.$conditionBetweenOptions.' '.$col.' LIKE "%'.$item.'%"';
                }
                $val = $result;

                return " $col {$this->ops[$oper]} $val ";
            } else {
                if (is_string($val)) {
                    $val = '%'.$val.'%';
                } else {
                    $val = '';
                }
            }
        }
        $val = \Database::escape_string($val);

        return " $col {$this->ops[$oper]} '$val' ";
    }

    /**
     * @param array  $options
     * @param string $alias
     *
     * @return array
     */
    public function parseConditions($options, $alias = 's')
    {
        $inject_extra_fields = null;
        $extraFieldOption = new ExtraFieldOption($this->type);
        $double_fields = [];

        if (isset($options['extra'])) {
            $extra_fields = $options['extra'];
            if (!empty($extra_fields)) {
                $counter = 1;
                $extra_field_obj = new ExtraField($this->type);
                foreach ($extra_fields as &$extra) {
                    if (!isset($extra['id'])) {
                        continue;
                    }
                    $extra_field_info = $extra_field_obj->get($extra['id']);
                    if (empty($extra_field_info)) {
                        continue;
                    }
                    $extra['extra_field_info'] = $extra_field_info;

                    switch ($extra_field_info['field_type']) {
                        case self::FIELD_TYPE_SELECT:
                        case self::FIELD_TYPE_DOUBLE_SELECT:
                            $inject_extra_fields .= " fvo$counter.display_text as {$extra['field']}, ";
                            break;
                        case self::FIELD_TYPE_TAG:
                            // If using OR
                            // If using AND
                            $newCounter = 1;
                            $fields = [];
                            $tagAlias = $extra['field'];
                            foreach ($extra['data'] as $data) {
                                $fields[] = "tag$counter$newCounter.tag";
                                $newCounter++;
                            }

                            if (!empty($fields)) {
                                $tags = implode(' , " ", ', $fields);
                                $inject_extra_fields .= " CONCAT($tags) as $tagAlias, ";
                            }
                            break;
                        default:
                            $inject_extra_fields .= " fv$counter.value as {$extra['field']}, ";
                            break;
                    }

                    if (isset($extra_fields_info[$extra['id']])) {
                        $info = $extra_fields_info[$extra['id']];
                    } else {
                        $info = $this->get($extra['id']);
                        $extra_fields_info[$extra['id']] = $info;
                    }
                    if (isset($info['field_type']) && self::FIELD_TYPE_DOUBLE_SELECT == $info['field_type']) {
                        $double_fields[$info['id']] = $info;
                    }
                    $counter++;
                }
            }
        }

        $options_by_double = [];
        foreach ($double_fields as $double) {
            $my_options = $extraFieldOption->get_field_options_by_field($double['id'], true);
            $options_by_double['extra_'.$double['variable']] = $my_options;
        }

        $field_value_to_join = [];
        //filter can be all/any = and/or
        $inject_joins = null;
        $inject_where = null;
        $where = null;

        //if (!empty($options['where'])) {
        if (!empty($options['extra']) && !empty($extra_fields)) {
            // Removing double 1=1
            if (empty($options['where'])) {
                $options['where'] = ' 1 = 1 ';
            }
            $options['where'] = str_replace(' 1 = 1  AND', '', $options['where']);
            // Always OR
            $counter = 1;
            foreach ($extra_fields as $extra_info) {
                $extra_field_info = $extra_info['extra_field_info'];
                $inject_joins .= " INNER JOIN $this->table_field_values fv$counter
                                       ON ($alias.".$this->primaryKey." = fv$counter.".$this->handler_id.') ';
                // Add options
                switch ($extra_field_info['field_type']) {
                        case self::FIELD_TYPE_SELECT:
                        case self::FIELD_TYPE_DOUBLE_SELECT:
                            $options['where'] = str_replace(
                                $extra_info['field'],
                                'fv'.$counter.'.field_id = '.$extra_info['id'].' AND fvo'.$counter.'.option_value',
                                $options['where']
                            );
                            $inject_joins .= "
                                 INNER JOIN $this->table_field_options fvo$counter
                                 ON (
                                    fv$counter.field_id = fvo$counter.field_id AND
                                    fv$counter.value = fvo$counter.option_value
                                 )
                                ";
                            break;
                        case self::FIELD_TYPE_TAG:
                            $newCounter = 1;
                            if (isset($extra_info['data']) && !empty($extra_info['data'])) {
                                $whereTag = [];
                                foreach ($extra_info['data'] as $data) {
                                    $data = Database::escape_string($data);
                                    $key = $counter.$newCounter;
                                    $whereTag[] = ' tag'.$key.'.tag LIKE "%'.$data.'%" ';
                                    $inject_joins .= "
                                    INNER JOIN $this->table_field_rel_tag tag_rel$key
                                    ON (
                                        tag_rel$key.field_id = ".$extra_info['id']." AND
                                        tag_rel$key.item_id = $alias.".$this->primaryKey."
                                    )
                                    INNER JOIN $this->table_field_tag tag$key
                                    ON (tag$key.id = tag_rel$key.tag_id)
                                ";
                                    $newCounter++;
                                }
                                if (!empty($whereTag)) {
                                    $options['where'] .= ' AND  ('.implode(' AND ', $whereTag).') ';
                                }
                            }
                            break;
                        default:
                            // text, textarea, etc
                            $options['where'] = str_replace(
                                $extra_info['field'],
                                'fv'.$counter.'.field_id = '.$extra_info['id'].' AND fv'.$counter.'.value',
                                $options['where']
                            );
                            break;
                    }
                $field_value_to_join[] = " fv$counter.$this->handler_id ";
                $counter++;
            }
        }

        if (!empty($options['where'])) {
            $where .= ' AND '.$options['where'];
        }

        $order = '';
        if (!empty($options['order'])) {
            $order = " ORDER BY ".$options['order']." ";
        }
        $limit = '';
        if (!empty($options['limit'])) {
            $limit = ' LIMIT '.$options['limit'];
        }

        return [
            'order' => $order,
            'limit' => $limit,
            'where' => $where,
            'inject_where' => $inject_where,
            'inject_joins' => $inject_joins,
            'field_value_to_join' => $field_value_to_join,
            'inject_extra_fields' => $inject_extra_fields,
        ];
    }

    /**
     * Get the extra fields and their formatted values.
     *
     * @param int|string $itemId   The item ID (It could be a session_id, course_id or user_id)
     * @param bool       $filter
     * @param array      $onlyShow (list of extra fields variables to show)
     *
     * @return array The extra fields data
     */
    public function getDataAndFormattedValues($itemId, $filter = false, $onlyShow = [])
    {
        $valuesData = [];
        $fields = $this->get_all();
        $em = Database::getManager();

        $repoTag = $em->getRepository('ChamiloCoreBundle:ExtraFieldRelTag');

        foreach ($fields as $field) {
            if ('1' != $field['visible_to_self']) {
                continue;
            }

            if ($filter && $field['filter'] != 1) {
                continue;
            }

            if (!empty($onlyShow) && !in_array($field['variable'], $onlyShow)) {
                continue;
            }

            $valueAsArray = [];
            $fieldValue = new ExtraFieldValue($this->type);
            $valueData = $fieldValue->get_values_by_handler_and_field_id(
                $itemId,
                $field['id'],
                true
            );
            if (ExtraField::FIELD_TYPE_TAG == $field['field_type']) {
                $tags = $repoTag->findBy(['fieldId' => $field['id'], 'itemId' => $itemId]);
                if ($tags) {
                    /** @var ExtraFieldRelTag $tag */
                    $data = [];
                    foreach ($tags as $extraFieldTag) {
                        /** @var Tag $tag */
                        $tag = $em->find('ChamiloCoreBundle:Tag', $extraFieldTag->getTagId());
                        $data[] = $tag->getTag();
                    }
                    $valueData = implode(', ', $data);
                    $valueAsArray = $data;
                }
            }

            if (!$valueData) {
                continue;
            }
            $displayedValue = get_lang('None');

            switch ($field['field_type']) {
                case self::FIELD_TYPE_CHECKBOX:
                    if (false !== $valueData && '1' == $valueData['value']) {
                        $displayedValue = get_lang('Yes');
                    } else {
                        $displayedValue = get_lang('No');
                    }
                    break;
                case self::FIELD_TYPE_DATE:
                    if (false !== $valueData && !empty($valueData['value'])) {
                        $displayedValue = api_format_date($valueData['value'], DATE_FORMAT_LONG_NO_DAY);
                    }
                    break;
                case self::FIELD_TYPE_TAG:
                    if (!empty($valueData)) {
                        $displayedValue = $valueData;
                    }
                    break;
                case self::FIELD_TYPE_FILE_IMAGE:
                    if (false === $valueData || empty($valueData['value'])) {
                        break;
                    }

                    if (!file_exists(api_get_path(SYS_UPLOAD_PATH).$valueData['value'])) {
                        break;
                    }

                    $image = Display::img(
                        api_get_path(WEB_UPLOAD_PATH).$valueData['value'],
                        $field['display_text'],
                        ['width' => '300']
                    );

                    $displayedValue = Display::url(
                        $image,
                        api_get_path(WEB_UPLOAD_PATH).$valueData['value'],
                        ['target' => '_blank']
                    );
                    break;
                case self::FIELD_TYPE_FILE:
                    if (false === $valueData || empty($valueData['value'])) {
                        break;
                    }

                    if (!file_exists(api_get_path(SYS_UPLOAD_PATH).$valueData['value'])) {
                        break;
                    }

                    $displayedValue = Display::url(
                        get_lang('Download'),
                        api_get_path(WEB_UPLOAD_PATH).$valueData['value'],
                        [
                            'title' => $field['display_text'],
                            'target' => '_blank',
                            'class' => 'download_extra_field',
                        ]
                    );
                    break;
                default:
                    $displayedValue = $valueData['value'];
                    break;
            }

            $valuesData[] = [
                'variable' => $field['variable'],
                'text' => $field['display_text'],
                'value' => $displayedValue,
                'value_as_array' => $valueAsArray,
            ];
        }

        return $valuesData;
    }

    /**
     * @param int    $fieldId
     * @param string $tag
     *
     * @return array
     */
    public function getAllUserPerTag($fieldId, $tag)
    {
        $tagRelUserTable = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $tag = Database::escape_string($tag);
        $fieldId = (int) $fieldId;

        $sql = "SELECT user_id
                FROM {$this->table_field_tag} f INNER JOIN $tagRelUserTable ft
                ON tag_id = f.id
                WHERE tag = '$tag' AND f.field_id = $fieldId;
        ";

        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param int $fieldId
     * @param int $tagId
     *
     * @return array
     */
    public function getAllSkillPerTag($fieldId, $tagId)
    {
        $skillTable = Database::get_main_table(TABLE_MAIN_SKILL);
        $tagRelExtraTable = Database::get_main_table(TABLE_MAIN_EXTRA_FIELD_REL_TAG);
        $fieldId = (int) $fieldId;
        $tagId = (int) $tagId;

        $sql = "SELECT s.id
                FROM $skillTable s INNER JOIN $tagRelExtraTable t
                ON t.item_id = s.id
                WHERE tag_id = $tagId AND t.field_id = $fieldId;
        ";

        $result = Database::query($sql);
        $result = Database::store_result($result, 'ASSOC');

        $skillList = [];
        foreach ($result as $index => $value) {
            $skillList[$value['id']] = $value['id'];
        }

        return $skillList;
    }

    /**
     * @param string $from
     * @param string $search
     * @param array  $options
     *
     * @return array
     */
    public function searchOptionsFromTags($from, $search, $options)
    {
        $extraFieldInfo = $this->get_handler_field_info_by_field_variable(
            str_replace('extra_', '', $from)
        );
        $extraFieldInfoTag = $this->get_handler_field_info_by_field_variable(
            str_replace('extra_', '', $search)
        );

        if (empty($extraFieldInfo) || empty($extraFieldInfoTag)) {
            return [];
        }

        $id = $extraFieldInfo['id'];
        $tagId = $extraFieldInfoTag['id'];

        $table = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $tagRelExtraTable = Database::get_main_table(TABLE_MAIN_EXTRA_FIELD_REL_TAG);
        $tagTable = Database::get_main_table(TABLE_MAIN_TAG);
        $optionsTable = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);

        $cleanOptions = [];
        foreach ($options as $option) {
            $cleanOptions[] = Database::escape_string($option);
        }
        $cleanOptions = array_filter($cleanOptions);

        if (empty($cleanOptions)) {
            return [];
        }

        $value = implode("','", $cleanOptions);

        $sql = "SELECT DISTINCT t.*, v.value, o.display_text
                FROM $tagRelExtraTable te
                INNER JOIN $tagTable t
                ON (t.id = te.tag_id AND te.field_id = t.field_id AND te.field_id = $tagId)
                INNER JOIN $table v
                ON (te.item_id = v.item_id AND v.field_id = $id)
                INNER JOIN $optionsTable o
                ON (o.option_value = v.value)
                WHERE v.value IN ('".$value."')
                ORDER BY o.option_order, t.tag
               ";

        $result = Database::query($sql);
        $result = Database::store_result($result);

        return $result;
    }

    /**
     * For one given field ID, get all the item_id + value.
     *
     * @return array
     */
    public function getAllValuesByFieldId(int $fieldId)
    {
        $type = $this->get_field_type_by_id($fieldId);
        $sql = "SELECT item_id, value FROM ".$this->table_field_values." WHERE field_id = $fieldId";
        $res = Database::query($sql);
        $values = [];
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                if (is_null($row['value'])) {
                    // If the entry exists but is NULL, consider it an empty string (to reproduce the behaviour of UserManager::get_extra_user_data()
                    $values[$row['item_id']] = '';
                } else {
                    if ($type == self::FIELD_TYPE_SELECT_MULTIPLE) {
                        $values[$row['item_id']] = explode(';', $row['value']);
                    } elseif (empty($row['value'])) {
                        // Avoid "0" values when no value should be set
                        $values[$row['item_id']] = null;
                    } else {
                        $values[$row['item_id']] = $row['value'];
                    }
                }
            }
        }

        return $values;
    }

    /**
     * Gets the default value for one specific field.
     *
     * @param int $fieldId Field ID
     *
     * @return mixed Default value for the field (could be null, or usually a string)
     */
    public function getDefaultValueByFieldId(int $fieldId)
    {
        $sql = "SELECT default_value FROM $this->table WHERE id = $fieldId";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);

            return $row['default_value'];
        }

        return null;
    }

    /**
     * @param \FormValidator $form
     * @param int            $defaultValueId
     * @param bool           $freezeElement
     */
    private function addSelectElement(FormValidator $form, array $fieldDetails, $defaultValueId, $freezeElement = false)
    {
        $get_lang_variables = false;
        if (in_array(
            $fieldDetails['variable'],
            ['mail_notify_message', 'mail_notify_invitation', 'mail_notify_group_message']
        )) {
            $get_lang_variables = true;
        }

        // Get extra field workflow
        $addOptions = [];
        $optionsExists = false;
        $options = [];

        $optionList = [];
        if (!empty($fieldDetails['options'])) {
            foreach ($fieldDetails['options'] as $option_details) {
                $optionList[$option_details['id']] = $option_details;
                if ($get_lang_variables) {
                    $options[$option_details['option_value']] = $option_details['display_text'];
                } else {
                    if ($optionsExists) {
                        // Adding always the default value
                        if ($option_details['id'] == $defaultValueId) {
                            $options[$option_details['option_value']] = $option_details['display_text'];
                        } else {
                            if (isset($addOptions) && !empty($addOptions)) {
                                // Parsing filters
                                if (in_array($option_details['id'], $addOptions)) {
                                    $options[$option_details['option_value']] = $option_details['display_text'];
                                }
                            }
                        }
                    } else {
                        // Normal behaviour
                        $options[$option_details['option_value']] = $option_details['display_text'];
                    }
                }
            }

            // Setting priority message
            if (isset($optionList[$defaultValueId])
                && isset($optionList[$defaultValueId]['priority'])
            ) {
                if (!empty($optionList[$defaultValueId]['priority'])) {
                    $priorityId = $optionList[$defaultValueId]['priority'];
                    $option = new ExtraFieldOption($this->type);
                    $messageType = $option->getPriorityMessageType($priorityId);
                    $form->addElement(
                        'label',
                        null,
                        Display::return_message(
                            $optionList[$defaultValueId]['priority_message'],
                            $messageType
                        )
                    );
                }
            }
        }

        /** @var \HTML_QuickForm_select $slct */
        $slct = $form->addElement(
            'select',
            'extra_'.$fieldDetails['variable'],
            $fieldDetails['display_text'],
            [],
            ['id' => 'extra_'.$fieldDetails['variable']]
        );

        if (empty($defaultValueId)) {
            $slct->addOption(get_lang('SelectAnOption'), '');
        }

        foreach ($options as $value => $text) {
            if (empty($value)) {
                $slct->addOption($text, $value);
                continue;
            }

            $valueParts = explode('#', $text);
            $dataValue = count($valueParts) > 1 ? array_shift($valueParts) : '';

            $slct->addOption(implode('', $valueParts), $value, ['data-value' => $dataValue]);
        }

        if ($freezeElement) {
            $form->freeze('extra_'.$fieldDetails['variable']);
        }
    }

    /**
     * @param \FormValidator $form
     * @param array          $fieldDetails
     * @param array          $extraData
     * @param bool           $freezeElement
     *
     * @return string JavaScript code
     */
    private function addDoubleSelectElement(FormValidator $form, $fieldDetails, $extraData, $freezeElement = false)
    {
        $firstSelectId = 'first_extra_'.$fieldDetails['variable'];
        $secondSelectId = 'second_extra_'.$fieldDetails['variable'];

        $jqueryReadyContent = "
            $('#$firstSelectId').on('change', function() {
                var id = $(this).val();

                if (!id) {
                    $('#$secondSelectId').empty().selectpicker('refresh');

                    return;
                }

                $.getJSON(_p.web_ajax + 'extra_field.ajax.php?1=1&a=get_second_select_options', {
                    'type': '{$this->type}',
                    'field_id': {$fieldDetails['id']},
                    'option_value_id': id
                })
                    .done(function(data) {
                        $('#$secondSelectId').empty();
                        $.each(data, function(index, value) {
                            $('#second_extra_{$fieldDetails['variable']}').append(
                                $('<option>', {value: index, text: value})
                            );
                        });
                        $('#$secondSelectId').selectpicker('refresh');
                    });
            });
        ";

        $firstId = null;
        if (!empty($extraData)) {
            if (isset($extraData['extra_'.$fieldDetails['variable']])) {
                $firstId = $extraData['extra_'.$fieldDetails['variable']]['extra_'.$fieldDetails['variable']];
            }
        }

        $options = $this->extra_field_double_select_convert_array_to_ordered_array($fieldDetails['options']);
        $values = ['' => get_lang('Select')];

        $second_values = [];
        if (!empty($options)) {
            foreach ($options as $option) {
                foreach ($option as $sub_option) {
                    if ('0' == $sub_option['option_value']) {
                        $values[$sub_option['id']] = $sub_option['display_text'];

                        continue;
                    }

                    if ($firstId === $sub_option['option_value']) {
                        $second_values[$sub_option['id']] = $sub_option['display_text'];
                    }
                }
            }
        }

        $form
            ->defaultRenderer()
            ->setGroupElementTemplate('<p>{element}</p>', 'extra_'.$fieldDetails['variable']);
        $group = [];
        $group[] = $form->createElement(
            'select',
            'extra_'.$fieldDetails['variable'],
            null,
            $values,
            ['id' => $firstSelectId]
        );
        $group[] = $form->createElement(
            'select',
            'extra_'.$fieldDetails['variable'].'_second',
            null,
            $second_values,
            ['id' => $secondSelectId]
        );
        $form->addGroup(
            $group,
            'extra_'.$fieldDetails['variable'],
            $fieldDetails['display_text']
        );

        if ($freezeElement) {
            $form->freeze('extra_'.$fieldDetails['variable']);
        }

        return $jqueryReadyContent;
    }

    /**
     * @param \FormValidator $form
     * @param bool           $freezeElement Optional
     *
     * @return string JavaScript code
     */
    private function addSelectWithTextFieldElement(
        FormValidator $form,
        array $fieldDetails,
        $freezeElement = false
    ) {
        $firstSelectId = 'slct_extra_'.$fieldDetails['variable'];
        $txtSelectId = 'txt_extra_'.$fieldDetails['variable'];

        $jqueryReadyContent = "
            $('#$firstSelectId').on('change', function() {
                var id = $(this).val();

                if (!id) {
                    $('#$txtSelectId').val('');
                }
            });
        ";

        $options = $this->extra_field_double_select_convert_array_to_ordered_array($fieldDetails['options']);
        $values = ['' => get_lang('Select')];

        if (!empty($options)) {
            foreach ($options as $option) {
                foreach ($option as $sub_option) {
                    if ('0' == $sub_option['option_value']) {
                        continue;
                    }

                    $values[$sub_option['id']] = $sub_option['display_text'];
                }
            }
        }

        $form
            ->defaultRenderer()
            ->setGroupElementTemplate('<p>{element}</p>', 'extra_'.$fieldDetails['variable']);
        $group = [];
        $group[] = $form->createElement(
            'select',
            'extra_'.$fieldDetails['variable'],
            null,
            $values,
            ['id' => $firstSelectId]
        );
        $group[] = $form->createElement(
            'text',
            'extra_'.$fieldDetails['variable'].'_second',
            null,
            ['id' => $txtSelectId]
        );
        $form->addGroup(
            $group,
            'extra_'.$fieldDetails['variable'],
            $fieldDetails['display_text']
        );

        if ($freezeElement) {
            $form->freeze('extra_'.$fieldDetails['variable']);
        }

        return $jqueryReadyContent;
    }

    /**
     * @param \FormValidator $form
     * @param bool           $freezeElement
     *
     * @return string
     */
    private function addTripleSelectElement(
        FormValidator $form,
        array $fieldDetails,
        array $extraData,
        $freezeElement
    ) {
        $variable = $fieldDetails['variable'];
        $id = $fieldDetails['id'];
        $slctFirstId = "first_extra$variable";
        $slctSecondId = "second_extra$variable";
        $slctThirdId = "third_extra$variable";
        $langSelect = get_lang('Select');

        $js = "
            (function () {
                var slctFirst = $('#$slctFirstId'),
                    slctSecond = $('#$slctSecondId'),
                    slctThird = $('#$slctThirdId');

                slctFirst.on('change', function () {
                    slctSecond.empty().selectpicker('refresh');
                    slctThird.empty().selectpicker('refresh');

                    var level = $(this).val();

                    if (!level) {
                        return;
                    }

                    $.getJSON(_p.web_ajax + 'extra_field.ajax.php', {
                        'a': 'get_second_select_options',
                        'type': '$this->type',
                        'field_id': $id,
                        'option_value_id': level
                    })
                        .done(function (data) {
                            slctSecond.append(
                                $('<option>', {value: '', text: '$langSelect'})
                            );

                            $.each(data, function (index, value) {
                                var valueParts = value.split('#'),
                                    dataValue = valueParts.length > 1 ? valueParts.shift() : '';

                                slctSecond.append(
                                    $('<option>', {value: index, text: valueParts.join(''), 'data-value': dataValue})
                                );
                            });

                            slctSecond.selectpicker('refresh');
                        });
                });
                slctSecond.on('change', function () {
                    slctThird.empty().selectpicker('refresh');

                    var level = $(this).val();

                    if (!level) {
                        return;
                    }

                    $.getJSON(_p.web_ajax + 'extra_field.ajax.php', {
                        'a': 'get_second_select_options',
                        'type': '$this->type',
                        'field_id': $id,
                        'option_value_id': level
                    })
                        .done(function (data) {
                            slctThird.append(
                                $('<option>', {value: '', text: '$langSelect'})
                            );

                            $.each(data, function (index, value) {
                                var valueParts = value.split('#'),
                                    dataValue = valueParts.length > 1 ? valueParts.shift() : '';

                                slctThird.append(
                                    $('<option>', {value: index, text: valueParts.join(''), 'data-value': dataValue})
                                );
                            });

                            slctThird.selectpicker('refresh');
                        });
                });
            })();
        ";

        $firstId = isset($extraData["extra_$variable"]["extra_$variable"])
            ? $extraData["extra_$variable"]["extra_$variable"]
            : '';
        $secondId = isset($extraData["extra_$variable"]["extra_{$variable}_second"])
            ? $extraData["extra_$variable"]["extra_{$variable}_second"]
            : '';

        $options = $this->tripleSelectConvertArrayToOrderedArray($fieldDetails['options']);
        $values1 = ['' => $langSelect];
        $values2 = ['' => $langSelect];
        $values3 = ['' => $langSelect];
        $level1 = $this->getOptionsFromTripleSelect($options['level1'], 0);
        $level2 = $this->getOptionsFromTripleSelect($options['level2'], $firstId);
        $level3 = $this->getOptionsFromTripleSelect($options['level3'], $secondId);
        /** @var \HTML_QuickForm_select $slctFirst */
        $slctFirst = $form->createElement('select', "extra_$variable", null, $values1, ['id' => $slctFirstId]);
        /** @var \HTML_QuickForm_select $slctFirst */
        $slctSecond = $form->createElement(
            'select',
            "extra_{$variable}_second",
            null,
            $values2,
            ['id' => $slctSecondId]
        );
        /** @var \HTML_QuickForm_select $slctFirst */
        $slctThird = $form->createElement('select', "extra_{$variable}_third", null, $values3, ['id' => $slctThirdId]);

        foreach ($level1 as $item1) {
            $valueParts = explode('#', $item1['display_text']);
            $dataValue = count($valueParts) > 1 ? array_shift($valueParts) : '';
            $slctFirst->addOption(implode('', $valueParts), $item1['id'], ['data-value' => $dataValue]);
        }

        foreach ($level2 as $item2) {
            $valueParts = explode('#', $item2['display_text']);
            $dataValue = count($valueParts) > 1 ? array_shift($valueParts) : '';
            $slctSecond->addOption(implode('', $valueParts), $item2['id'], ['data-value' => $dataValue]);
        }

        foreach ($level3 as $item3) {
            $valueParts = explode('#', $item3['display_text']);
            $dataValue = count($valueParts) > 1 ? array_shift($valueParts) : '';
            $slctThird->addOption(implode('', $valueParts), $item3['id'], ['data-value' => $dataValue]);
        }

        $form
            ->defaultRenderer()
            ->setGroupElementTemplate('<p>{element}</p>', "extra_$variable");
        $form->addGroup([$slctFirst, $slctSecond, $slctThird], "extra_$variable", $fieldDetails['display_text']);

        if ($freezeElement) {
            $form->freeze('extra_'.$fieldDetails['variable']);
        }

        return $js;
    }

    /**
     * @param int $parentId
     *
     * @return array
     */
    private static function getOptionsFromTripleSelect(array $options, $parentId)
    {
        return array_filter(
            $options,
            function ($option) use ($parentId) {
                return $option['option_value'] == $parentId;
            }
        );
    }
}
