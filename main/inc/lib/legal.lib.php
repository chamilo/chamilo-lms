<?php
/* For licensing terms, see /license.txt */

/**
 * Class LegalManager
 *
 * @package chamilo.legal
 */
class LegalManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

	/**
	 * Add a new Term and Condition
	 * @param int $language language id
	 * @param string $content content
	 * @param int $type term and condition type (0 or 1)
	 * @param string $changes explain changes
	 * @return boolean success
	 */
	public static function add($language, $content, $type, $changes)
    {
        $legal_table = Database::get_main_table(TABLE_MAIN_LEGAL);
        $last = self::get_last_condition($language);
        $type = intval($type);
        $time = time();

        if ($last['content'] != $content) {
            $version = intval(LegalManager::get_last_condition_version($language));
            $version++;
            $params = [
                'language_id' => $language,
                'content' => $content,
                'changes' => $changes,
                'type' => $type,
                'version' => intval($version),
                'date' => $time
            ];
            Database::insert($legal_table, $params);

            return true;
        } elseif ($last['type'] != $type && $language == $last['language_id']) {
            // Update
            $id = $last['id'];
            $params = [
                'changes' => $changes,
                'type' => $type,
                'date' => $time
            ];
            Database::update($legal_table, $params, ['id => ?' => $id]);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $id
     */
    public static function delete($id)
    {
        /*
        $legal_table = Database::get_main_table(TABLE_MAIN_LEGAL);
        $id = intval($id);
        $sql = "DELETE FROM $legal_table WHERE id = '".$id."'";
        */
    }

    /**
     * Gets the last version of a Term and condition by language
     * @param int $language language id
     * @return array all the info of a Term and condition
     */
    public static function get_last_condition_version($language)
    {
        $legal_conditions_table = Database::get_main_table(TABLE_MAIN_LEGAL);
        $language= Database::escape_string($language);
        $sql = "SELECT version FROM $legal_conditions_table
                WHERE language_id = '".$language."'
                ORDER BY id DESC LIMIT 1 ";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        if (Database::num_rows($result) > 0) {
            return $row['version'];
        } else {
            return 0;
        }
    }

	/**
	 * Gets the data of a Term and condition by language
	 * @param int $language language id
	 * @return array all the info of a Term and condition
	 */
	public static function get_last_condition($language)
    {
		$legal_conditions_table = Database::get_main_table(TABLE_MAIN_LEGAL);
		$language= Database::escape_string($language);
		$sql = "SELECT * FROM $legal_conditions_table
                WHERE language_id = '".$language."'
                ORDER BY version DESC
                LIMIT 1 ";
		$result = Database::query($sql);
		$result = Database::fetch_array($result, 'ASSOC');

        if (isset($result['content'])) {
            $result['content'] = self::replaceTags($result['content']);
        }
        return $result;
	}

    /**
     * @param string $content
     * @return string
     */
    public static function replaceTags($content)
    {
        if (strpos($content, '{{sessions}}')) {
            $sessionListToString = '';
            $sessionList = SessionManager::get_sessions_by_user(api_get_user_id());
            if ($sessionList) {
                $sessionListToString = get_lang('SessionList').'<ul>';
                foreach ($sessionList as $session) {
                    $sessionListToString .= '<li>'.$session['session_name'].'</li>';
                }
                $sessionListToString .= '<ul>';
            }
            $content = str_replace('{{sessions}}', $sessionListToString, $content);
        }

        return $content;
    }

	/**
	 * Gets the last version of a Term and condition by language
	 * @param int $language language id
	 * @return boolean | int the version or false if does not exist
	 */
	public static function get_last_version($language)
    {
        $legal_conditions_table = Database::get_main_table(TABLE_MAIN_LEGAL);
        $language = intval($language);
        $sql = "SELECT version FROM $legal_conditions_table
                WHERE language_id = '".$language."'
                ORDER BY version DESC
                LIMIT 1 ";
        $result = Database::query($sql);
        if (Database::num_rows($result)>0){
            $version = Database::fetch_array($result);
            $version = explode(':',$version[0]);

            return $version[0];
        } else {

            return false;
        }
	}

	/**
	 * Show the last condition
	 * @param array $term_preview with type and content i.e array('type'=>'1', 'content'=>'hola');
     *
	 * @return string html preview
	 */
	public static function show_last_condition($term_preview)
    {
        $preview = '';
        switch ($term_preview['type']) {
            case 0:
                if (!empty($term_preview['content'])) {
                    $preview = '<div class="legal-terms">'.$term_preview['content'].'</div><br />';
                }
                $preview .= get_lang('ByClickingRegisterYouAgreeTermsAndConditions');
                $courseInfo = api_get_course_info();
                if (api_get_setting('load_term_conditions_section') === 'course' && empty($courseInfo)) {
                    $preview = '';
                }
                break;
                // Page link
            case 1:
                $preview ='<fieldset>
                             <legend>'.get_lang('TermsAndConditions').'</legend>';
                $preview .= '<div id="legal-accept-wrapper" class="form-item">
                <label class="option" for="legal-accept">
                <input id="legal-accept" type="checkbox" value="1" name="legal_accept"/>
                '.get_lang('IHaveReadAndAgree').'
                <a href="#">'.get_lang('TermsAndConditions').'</a>
                </label>
                </div>
                </fieldset>';
                break;
            default:
                break;
        }

		return $preview;
	}

	/**
	 * Get the terms and condition table (only for maintenance)
	 * @param int $from
	 * @param int $number_of_items
	 * @param int $column
	 * @return array
	 */
	public static function get_legal_data($from, $number_of_items, $column)
    {
		$legal_conditions_table = Database::get_main_table(TABLE_MAIN_LEGAL);
		$lang_table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
		$from = intval($from);
		$number_of_items = intval($number_of_items);
		$column = intval($column);

 		$sql  = "SELECT version, original_name as language, content, changes, type, FROM_UNIXTIME(date)
				FROM $legal_conditions_table inner join $lang_table l on(language_id = l.id) ";
		$sql .= "ORDER BY language, version ASC ";
		$sql .= "LIMIT $from, $number_of_items ";

		$result = Database::query($sql);
		$legals = array();
		while ($legal = Database::fetch_array($result)) {
			// max 2000 chars
			$languages[] = $legal[1];
            if (strlen($legal[2]) > 2000) {
                $legal[2] = substr($legal[2], 0, 2000).' ... ';
            }
            if ($legal[4] == 0) {
                $legal[4] = get_lang('HTMLText');
            } elseif ($legal[4] == 1) {
                $legal[4] = get_lang('PageLink');
            }
			$legals[] = $legal;
		}

		return $legals;
	}

	/**
	 * Gets the number of terms and conditions available
	 * @return int
	 */
	public static function count()
    {
		$legal_conditions_table = Database::get_main_table(TABLE_MAIN_LEGAL);
		$sql = "SELECT count(*) as count_result
		        FROM $legal_conditions_table
		        ORDER BY id DESC ";
		$result = Database::query($sql);
		$url = Database::fetch_array($result,'ASSOC');
		$result = $url['count_result'];

		return $result;
	}

	/**
	 * Get type of terms and conditions
	 * @param int $legal_id
	 * @param int $language_id
	 * @return int The current type of terms and conditions
	 */
	public static function get_type_of_terms_and_conditions($legal_id, $language_id)
    {
		$legal_conditions_table = Database::get_main_table(TABLE_MAIN_LEGAL);
		$legal_id = intval($legal_id);
		$language_id = intval($language_id);
		$sql = 'SELECT type FROM '.$legal_conditions_table.'
		        WHERE id =  "'.$legal_id.'" AND language_id="'.$language_id.'"';
		$rs = Database::query($sql);

		return Database::result($rs,0,'type');
	}

    /**
     * @param int $userId
     */
	public static function sendLegal($userId)
    {
        $subject = get_lang('SendTermsSubject');
        $content = sprintf(
            get_lang('SendTermsDescriptionToUrlX'),
            api_get_path(WEB_PATH)
        );
        MessageManager::send_message_simple($userId, $subject, $content);
        Display::addFlash(Display::return_message(get_lang('Sent')));

        $extraFieldValue = new ExtraFieldValue('user');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($userId, 'termactivated');
        if ($value === false) {
            $extraFieldInfo = $extraFieldValue->getExtraField()->get_handler_field_info_by_field_variable('termactivated');
            if ($extraFieldInfo) {
                $newParams = array(
                    'item_id' => $userId,
                    'field_id' => $extraFieldInfo['id'],
                    'value' => 1,
                    'comment' => ''
                );
                $extraFieldValue->save($newParams);
            }
        }
    }

    /**
     * @param int $userId
     */
    public static function deleteLegal($userId)
    {
        $extraFieldValue = new ExtraFieldValue('user');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($userId, 'legal_accept');
        $result = $extraFieldValue->delete($value['id']);
        if ($result) {
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }

        $value = $extraFieldValue->get_values_by_handler_and_field_variable($userId, 'termactivated');
        if ($value) {
            $extraFieldValue->delete($value['id']);
        }
    }
}
