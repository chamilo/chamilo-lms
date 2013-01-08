<?php
/* For licensing terms, see /license.txt */
/** @author hubert.borderiou  **/

if (!class_exists('Testcategory')):

class Testcategory {
	public $id;
	public $name;
	public $description;

	/**
	 * Constructor of the class Category
	 * @author - Hubert Borderiou
	 If you give an in_id and no in_name, you get info concerning the category of id=in_id
	 otherwise, you've got an category objet avec your in_id, in_name, in_descr
	 */
	function Testcategory($in_id=0, $in_name = '', $in_description="") {
		if ($in_id != 0 && $in_name == "") {
			$tmpobj = new Testcategory();
			$tmpobj->getCategory($in_id);
			$this->id = $tmpobj->id;
			$this->name = $tmpobj->name;
			$this->description = $tmpobj->description;
		}
		else {
			$this->id = $in_id;
			$this->name = $in_name;
			$this->description = $in_description;
		}
	}

	/** return the Testcategory object with id=in_id
	 */
	function getCategory($in_id) {
		$t_cattable = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
		$in_id = Database::escape_string($in_id);
		$sql = "SELECT * FROM $t_cattable WHERE id=$in_id AND c_id=".api_get_course_int_id();
		$res = Database::query($sql);
		$numrows = Database::num_rows($res);
		if ($numrows > 0) {
			$row = Database::fetch_array($res);
			$this->id = $row['id'];
			$this->name = $row['title'];
			$this->description  = $row['description'];
		}
	}

	/** add Testcategory in the database if name doesn't already exists
		*/
	function addCategoryInBDD() {
		$t_cattable = Database :: get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
		$v_name = $this->name;
		$v_name = Database::escape_string($v_name);
		$v_description = $this->description;
		$v_description = Database::escape_string($v_description);
		// check if name already exists
		$sql_verif = "SELECT count(*) AS nb FROM $t_cattable WHERE title = '$v_name' AND c_id=".api_get_course_int_id();
		$result_verif = Database::query($sql_verif);
		$data_verif = Database::fetch_array($result_verif);
		// lets add in BDD if not the same name
		if ($data_verif['nb'] <= 0) {
			$c_id = api_get_course_int_id();
			$sql = "INSERT INTO $t_cattable VALUES ('$c_id', '', '$v_name', '$v_description')";
			$res = Database::query($sql);
			return true;
		}
		else {
			return false;
		}
	}

	/**
     * Removes the category with id=in_id from the database if no question use this category
     * @todo I'm removing the $in_id parameter because it seems that you're using $this->id instead of $in_id after confirmation delete this
     * jmontoya
	 */
	//function removeCategory($in_id) {
    function removeCategory() {
		$t_cattable = Database :: get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
		$v_id = Database::escape_string($this->id);
		$sql = "DELETE FROM $t_cattable WHERE id=$v_id AND c_id=".api_get_course_int_id();
		$res = Database::query($sql);
		if (Database::affected_rows() <= 0) {
			return false;
		} else {
			return true;
		}
	}


	/** modify category name or description of category with id=in_id
	 */
	//function modifyCategory($in_id, $in_name, $in_description) {
    function modifyCategory() {
		$t_cattable = Database :: get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
		$v_id = Database::escape_string($this->id);
		$v_name = Database::escape_string($this->name);
		$v_description = Database::escape_string($this->description);
		$sql = "UPDATE $t_cattable SET title='$v_name', description='$v_description' WHERE id='$v_id' AND c_id=".api_get_course_int_id();
		$res = Database::query($sql);
		if (Database::affected_rows() <= 0) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
     * Gets the number of question of category id=in_id
     * @todo I'm removing the $in_id parameter because it seems that you're using $this->id instead of $in_id after confirmation delete this
     * jmontoya
	 */
	//function getCategoryQuestionsNumber($in_id) {
	function getCategoryQuestionsNumber() {
		$t_reltable = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
		$in_id = Database::escape_string($this->id);
		$sql = "SELECT count(*) AS nb FROM $t_reltable WHERE category_id=$in_id AND c_id=".api_get_course_int_id();
		$res = Database::query($sql);
		$row = Database::fetch_array($res);
		return $row['nb'];
	}

	function display($in_color="#E0EBF5") {
		echo "<textarea style='background-color:$in_color; width:60%; height:100px;'>";
		print_r($this);
		echo "</textarea>";
	}



	/** return an array of all Category objects in the database
	If in_field=="" Return an array of all category objects in the database
	Otherwise, return an array of all in_field value in the database (in_field = id or name or description)
	 */
	public static function getCategoryListInfo($in_field="", $in_courseid="") {
		if (empty($in_courseid) || $in_courseid=="") {
			$in_courseid = api_get_course_int_id();
		}
		$t_cattable = Database :: get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
		$in_field = Database::escape_string($in_field);
		$tabres = array();
		if ($in_field=="") {
			$sql = "SELECT * FROM $t_cattable WHERE c_id=$in_courseid ORDER BY title ASC";
			$res = Database::query($sql);
			while ($row = Database::fetch_array($res)) {
				$tmpcat = new Testcategory($row['id'], $row['title'], $row['description']);
				$tabres[] = $tmpcat;
			}
		}
		else {
			$sql = "SELECT $in_field FROM $t_cattable WHERE c_id=$in_courseid ORDER BY $in_field ASC";
			$res = Database::query($sql);
			while ($row = Database::fetch_array($res)) {
				$tabres[] = $row[$in_field];
			}
		}
		return $tabres;
	}


	/**
	 Return the testcategory id for question with question_id = $in_questionid
	 In this version, a question has only 1 testcategory.
	 Return the testcategory id, 0 if none
	 */
	public static function getCategoryForQuestion($in_questionid, $in_courseid = null) {
		$result = array();	// result
		if (empty($in_courseid) || $in_courseid=="") {
			$in_courseid = api_get_course_int_id();
		}
		$t_cattable = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
		$question_id = Database::escape_string($in_questionid);
		$sql = "SELECT category_id FROM $t_cattable WHERE question_id = '$question_id' AND c_id = $in_courseid";
		$res = Database::query($sql);
		if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $result[] = $row['category_id'];
            }
		}
		return $result;
	}

    public static function getCategoryNamesForQuestion($in_questionid, $in_courseid = null, $display_into_labels = true) {
		$result = array();	// result
		if (empty($in_courseid) || $in_courseid=="") {
			$in_courseid = api_get_course_int_id();
		}
		$t_cattable = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $table_category = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
		$question_id = Database::escape_string($in_questionid);
		$sql = "SELECT c.title FROM $t_cattable qc INNER JOIN $table_category c
                        ON (qc.category_id = c.id AND qc.c_id = $in_courseid AND c.c_id = $in_courseid)
                     WHERE question_id = '$question_id' ";
		$res = Database::query($sql);
		if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $result[] = $row['title'];
            }
		}

        if ($display_into_labels) {
            $html = self::draw_category_label($result, 'header');
            return $html;
        }
		return $result;
	}

	/**
	 * true if question id has a category
	 */
	public static function isQuestionHasCategory($in_questionid) {
        $category_list = Testcategory::getCategoryForQuestion($in_questionid);
		if (!empty($category_list)) {
			return true;
		}
		return false;
	}


	/**
     * @todo fix this
	 Return the category name for question with question_id = $in_questionid
	 In this version, a question has only 1 category.
	 Return the category id, "" if none
	 */
	public static function getCategoryNameForQuestion($question_id, $course_id = null) {
		if (empty($course_id) || $course_id == "") {
			$course_id = api_get_course_int_id();
		}
        $course_id = intval($course_id);

		$category_list = Testcategory::getCategoryForQuestion($question_id, $course_id);

		$result = array();	// result
		$t_cattable = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
		$catid = Database::escape_string($catid);
		$sql = "SELECT title FROM $t_cattable WHERE id='$catid' AND c_id = $course_id";
		$res = Database::query($sql);
		$data = Database::fetch_array($res);
		if (Database::num_rows($res) > 0) {
			$result = $data['title'];
		}
		return $result;
	}

	/**
	 * return the list of differents categories ID for a test
	 * input : test_id
	 * return : array of category id (integer)
	 * @author hubert.borderiou 07-04-2011, Julio Montoya
	 */
	public static function getListOfCategoriesIDForTest($exercise_id) {
		// parcourir les questions d'un test, recup les categories uniques dans un tableau
		$categories_in_exercise = array();
		$quiz = new Exercise();
		$quiz->read($exercise_id);
		$question_list = $quiz->selectQuestionList();
		// the array given by selectQuestionList start at indice 1 and not at indice 0 !!! ???
		for ($i=1; $i <= count($question_list); $i++) {
            $category_list = Testcategory::getCategoryForQuestion($question_list[$i]);
            if (!empty($category_list)) {
				$categories_in_exercise = array_merge($categories_in_exercise, $category_list);
            }
        }
        if (!empty($categories_in_exercise)) {
            $categories_in_exercise = array_unique(array_filter($categories_in_exercise));
        }
		return $categories_in_exercise;
	}

    /**
	 * return the list of differents categories NAME for a test
	 * input : test_id
	 * return : array of string
	 * hubert.borderiou 07-04-2011
     * @author function rewrote by jmontoya
	 */
	public static function getListOfCategoriesNameForTest($in_testid) {
		$tabcatName = array();
		$tabcatID = self::getListOfCategoriesIDForTest($in_testid);
		for ($i=0; $i < count($tabcatID); $i++) {
			$cat = new Testcategory($tabcatID[$i]);
			$tabcatName[$cat->id] = $cat->name;
		}
		return $tabcatName;
	}

	/**
	 * return the number of differents categories for a test
	 * input : test_id
	 * return : integer
	 * hubert.borderiou 07-04-2011
	 */
	public static function getNumberOfCategoriesForTest($in_testid) {
		return count(Testcategory::getListOfCategoriesIDForTest($in_testid));
	}

	/**
	 * return the number of question of a category id in a test
	 * input : test_id, category_id
	 * return : integer
	 * hubert.borderiou 07-04-2011
	 */
	public static function getNumberOfQuestionsInCategoryForTest($exercise_id, $category_id) {
		$number_questions_in_category = 0;
		$exercise = new Exercise();
		$exercise->read($exercise_id);
		$question_list = $exercise->selectQuestionList();
		// the array given by selectQuestionList start at indice 1 and not at indice 0 !!! ? ? ?
		for ($i=1; $i <= count($question_list); $i++) {
            $category_in_question = Testcategory::getCategoryForQuestion($question_list[$i]);
			if (in_array($category_id, $category_in_question)) {
				$number_questions_in_category++;
			}
		}
		return $number_questions_in_category;
	}

	/**
	 * return the number of question for a test using random by category
	 * input  : test_id, number of random question (min 1)
	 * hubert.borderiou 07-04-2011
	 * question witout categories are not counted
	 */
	public static function getNumberOfQuestionRandomByCategory($exercise_id, $in_nbrandom) {
		$nbquestionresult = 0;
		$list_categories = Testcategory::getListOfCategoriesIDForTest($exercise_id);

        if (!empty($list_categories)) {
            for ($i=0; $i < count($list_categories); $i++) {
                if ($list_categories[$i] > 0) {	// 0 = no category for this question
                    $nbQuestionInThisCat = Testcategory::getNumberOfQuestionsInCategoryForTest($exercise_id, $list_categories[$i]);

                    if ($nbQuestionInThisCat > $in_nbrandom) {
                        $nbquestionresult += $in_nbrandom;
                    } else {
                        $nbquestionresult += $nbQuestionInThisCat;
                    }
                }
            }
        }
		return $nbquestionresult;
	}


	/**
	 * Return an array (id=>name)
	 * tabresult[0] = get_lang('NoCategory');
	 *
	 */
	static function getCategoriesIdAndName($in_courseid = "") {
		if (empty($in_courseid) || $in_courseid=="") {
			$in_courseid = api_get_course_int_id();
		}
	 	$tabcatobject = Testcategory::getCategoryListInfo("", $in_courseid);
	 	$tabresult = array("0"=>get_lang('NoCategorySelected'));
	 	for ($i=0; $i < count($tabcatobject); $i++) {
	 		$tabresult[$tabcatobject[$i]->id] = $tabcatobject[$i]->name;
	 	}
	 	return $tabresult;
	}

	/**
		* return an array of question_id for each category
		* tabres[0] = array of question id with category id = 0 (i.e. no category)
		* tabres[24] = array of question id with category id = 24
		* In this version, a question has 0 or 1 category
	 */
	static function getQuestionsByCat($in_exerciceId) {
		$tabres = array();
		$TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
		$TBL_QUESTION_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $in_exerciceId = intval($in_exerciceId);
		$sql = "SELECT qrc.question_id, qrc.category_id FROM $TBL_QUESTION_REL_CATEGORY qrc, $TBL_EXERCICE_QUESTION eq
                WHERE exercice_id=$in_exerciceId AND eq.question_id=qrc.question_id AND eq.c_id=".api_get_course_int_id()." AND eq.c_id=qrc.c_id ORDER BY category_id, question_id";
		$res = Database::query($sql);
		while ($data = Database::fetch_array($res)) {
			if (!is_array($tabres[$data['category_id']])) {
				$tabres[$data['category_id']] = array();
			}
			$tabres[$data['category_id']][] = $data['question_id'];
		}
		return $tabres;
	}


	/**
	 * return a tab of $in_number random elements of $in_tab
	 */
	static function getNElementsFromArray($in_tab, $in_number) {
		$tabres = $in_tab;
		shuffle($tabres);
		if ($in_number < count($tabres)) {
			$tabres = array_slice($tabres, 0, $in_number);
		}
		return $tabres;
	}

	/**
		* Display signs [+] and/or (>0) after question title if question has options
		* scoreAlwaysPositive and/or uncheckedMayScore
		*/
	function displayQuestionOption($in_objQuestion) {
		if ($in_objQuestion->type == MULTIPLE_ANSWER && $in_objQuestion->scoreAlwaysPositive) {
			echo "<span style='font-size:75%'> (>0)</span>";
		}
		if ($in_objQuestion->type == MULTIPLE_ANSWER && $in_objQuestion->uncheckedMayScore) {
			echo "<span style='font-size:75%'> [+]</span>";
		}
	}

	/**
	 * sortTabByBracketLabel ($tabCategoryQuestions)
	 * key of $tabCategoryQuestions are the categopy id (0 for not in a category)
	 * value is the array of question id of this category
	 * Sort question by Category
	*/
	static function sortTabByBracketLabel($in_tab) {
		$tabResult = array();
		$tabCatName = array();	// tab of category name
		while (list($cat_id, $tabquestion) = each($in_tab)) {
			$catTitle = new Testcategory($cat_id);
			$tabCatName[$cat_id] = $catTitle->name;
		}
		reset($in_tab);
		// sort table by value, keeping keys as they are
		asort($tabCatName);
		// keys of $tabCatName are keys order for $in_tab
		while (list($key, $val) = each($tabCatName)) {
			$tabResult[$key] = $in_tab[$key];
		}
		return $tabResult;
	}

	/**
		* return total score for test exe_id for all question in the category $in_cat_id for user
		* If no question for this category, return ""
	*/
	public static function getCatScoreForExeidForUserid($in_cat_id, $in_exe_id, $in_user_id) {
		$tbl_track_attempt		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
		$tbl_question_rel_category = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $in_cat_id = intval($in_cat_id);
        $in_exe_id = intval($in_exe_id);
        $in_user_id = intval($in_user_id);

		$query = "SELECT DISTINCT marks, exe_id, user_id, ta.question_id, category_id FROM $tbl_track_attempt ta , $tbl_question_rel_category qrc
                  WHERE ta.question_id=qrc.question_id AND qrc.category_id=$in_cat_id AND exe_id=$in_exe_id AND user_id=$in_user_id";
		$res = Database::query($query);
		$totalcatscore = "";
		while ($data = Database::fetch_array($res)) {
			$totalcatscore += $data['marks'];
		}
		return $totalcatscore;
	}


    /**
     * return the number max of question in a category
     * count the number of questions in all categories, and return the max
     * @author - hubert borderiou
    */
    public static function getNumberMaxQuestionByCat($in_testid) {
        $res_num_max = 0;
        // foreach question
		$tabcatid = Testcategory::getListOfCategoriesIDForTest($in_testid);
		for ($i=0; $i < count($tabcatid); $i++) {
			if ($tabcatid[$i] > 0) {	// 0 = no category for this question
				$nbQuestionInThisCat = Testcategory::getNumberOfQuestionsInCategoryForTest($in_testid, $tabcatid[$i]);
                if ($nbQuestionInThisCat > $res_num_max) {
                    $res_num_max = $nbQuestionInThisCat;
                }
			}
		}
        return $res_num_max;
    }

    public static function getCategoryListName($course_id = null) {
        $category_list = self::getCategoryListInfo(null, $course_id);
        $category_name_list = array();
        if (!empty($category_list)) {
            foreach($category_list as $category) {
                $category_name_list[$category->id] = $category->name;
            }
        }
        return $category_name_list;
    }

    public static function return_category_labels($category_list, $all_categories) {
        $category_list_to_render = array();
        foreach ($category_list as $category_id) {
            $category_name = null;
            if (!isset($all_categories[$category_id])) {
                $category_name = get_lang('Untitled');
            } else {
                $category_name = cut($all_categories[$category_id], 15);
            }
            $category_list_to_render[] = $category_name;
        }
        $html = self::draw_category_label($category_list_to_render, 'label');
        return $html;
    }

    static function draw_category_label($category_list, $type = 'label') {
        $new_category_list = array();
        foreach ($category_list as $category_name) {
            switch ($type) {
                case 'label':
                    $new_category_list[] = Display::label($category_name, 'info');
                    break;
                case 'header':
                    $new_category_list[] = $category_name;
                    break;
            }
        }

        $html = null;
        if (!empty($new_category_list)) {
            switch ($type) {
            case 'label':
                $html = implode(' ', $new_category_list);
                break;
            case 'header':
                $html = Display::page_subheader3(get_lang('Category').': '.implode(', ', $new_category_list));
            }
        }
        return $html;
    }

    /**
     * Returns a category summary report
     * @params int exercise id
     * @params array prefilled array with the category_id, score, and weight example: array(1 => array('score' => '10', 'total' => 20));
     */
    public static function get_stats_table_by_attempt($exercise_id, $category_list = array()) {
        if (empty($category_list)) {
            return null;
        }
        $category_name_list = Testcategory::getListOfCategoriesNameForTest($exercise_id);

        $table = new HTML_Table(array('class' => 'data_table'));
        $table->setHeaderContents(0, 0, get_lang('Categories'));
        $table->setHeaderContents(0, 1, get_lang('AbsoluteScore'));
        $table->setHeaderContents(0, 2, get_lang('RelativeScore'));
        $row = 1;

        $none_category = array();
        if (isset($category_list['none'])) {
            $none_category = $category_list['none'];
            unset($category_list['none']);
        }

        $total = array();
        if (isset($category_list['total'])) {
            $total = $category_list['total'];
            unset($category_list['total']);
        }
        if (count($category_list) > 1) {
            foreach ($category_list as $category_id => $category_item) {
                $table->setCellContents($row, 0, $category_name_list[$category_id]);
                $table->setCellContents($row, 1, show_score($category_item['score'], $category_item['total'], false));
                $table->setCellContents($row, 2, show_score($category_item['score'], $category_item['total'], true, false, true));
                $row++;
            }

            if (!empty($none_category)) {
                $table->setCellContents($row, 0, get_lang('None'));
                $table->setCellContents($row, 1, show_score($none_category['score'], $none_category['total'], false));
                $table->setCellContents($row, 2, show_score($none_category['score'], $none_category['total'], true, false, true));
                $row++;
            }
            if (!empty($total)) {
                $table->setCellContents($row, 0, get_lang('Total'));
                $table->setCellContents($row, 1, show_score($total['score'], $total['total'], false));
                $table->setCellContents($row, 2, show_score($total['score'], $total['total'], true, false, true));
            }
            return $table->toHtml();
        }
        return null;
    }



}
endif;