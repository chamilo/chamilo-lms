<?php
/* For licensing terms, see /license.txt */
/**
 * @author hubert.borderiou & jmontoya
 */
use ChamiloLMS\CoreBundle\CQuizCategory;

class Testcategory
{
    public $id;
    public $name; //why using name?? Use title as in the db!
    public $title;
    public $description;
    public $parent_id;
    public $parent_path;
    public $category_array_tree;
    public $type;
    public $course_id;
    public $c_id; // from db
    public $root;
    public $visibility;

    /**
     * Constructor of the class Category
     * @author - Hubert Borderiou
     * If you give an in_id and no in_name, you get info concerning the category of id=in_id
     * otherwise, you've got an category objet avec your in_id, in_name, in_descr
     * @todo fix this function
     *
     * @param int $in_id
     * @param string $in_name
     * @param string $in_description
     * @param int $parent_id
     * @param string $type Posible values: all, simple, global
     * @param int $course_id
     * @param int visibility
     */
    public function Testcategory($in_id = 0, $in_name = '', $in_description = "", $parent_id = 0, $type = 'simple', $course_id = null, $visibility = 1)
    {
        if ($in_id != 0 && $in_name == "") {
            $tmpobj = new Testcategory();
            $tmpobj->getCategory($in_id);
            $this->id = $tmpobj->id;
            $this->name = $tmpobj->name;
            $this->title = $tmpobj->name;
            $this->description = $tmpobj->description;
            $this->parent_id = $tmpobj->parent_id;
            $this->parent_path = $this->name;
            $this->c_id = $tmpobj->c_id;
            $this->root = $tmpobj->root;
            $this->visibility = $tmpobj->visibility;

            if (!empty($tmpobj->parent_id)) {
                $category = new Testcategory($tmpobj->parent_id);
                $this->parent_path = $category->parent_path.' > '.$this->name;
            }
        } else {
            $this->id = $in_id;
            $this->name = $in_name;
            $this->description = $in_description;
            $this->parent_id = $parent_id;
            $this->visibility = $visibility;
        }
        $this->type = $type;

        if (!empty($course_id)) {
            $this->course_id = $course_id;
        } else {
            $this->course_id = api_get_course_int_id();
        }
    }

    /**
    * Return the Testcategory object with id=in_id
    * @param int $id
    * @return bool
    * @assert () === false
    */
    public function getCategory($id)
    {
        if (empty($id)) {
            return false;
        }

        $t_cattable = Database::get_course_table(TABLE_QUIZ_CATEGORY);
        $id = intval($id);
        $sql = "SELECT * FROM $t_cattable WHERE iid = $id ";
        $res = Database::query($sql);
        $numRows = Database::num_rows($res);
        if ($numRows > 0) {
            $row = Database::fetch_array($res);
            $this->id = $row['iid'];
            $this->title = $this->name = $row['title'];
            $this->description = $row['description'];
            $this->parent_id = $row['parent_id'];
            $this->c_id = $row['c_id'];
            $this->root = $row['root'];
            $this->visibility = $row['visibility'];
        } else {
            return false;
        }
    }

    /**
    * Add Testcategory in the database if name doesn't already exists
    *
    */
    public function addCategoryInBDD()
    {
        $t_cattable = Database :: get_course_table(TABLE_QUIZ_CATEGORY);
        $v_name = Database::escape_string($this->name);
        $parent_id = intval($this->parent_id);
        $course_id = $this->course_id;
        $courseCondition = " AND c_id = $course_id ";
        if ($this->type == 'global') {
            $course_id = '';
            $courseCondition = null;
        }

        // Only admins can add global categories
        if ($this->type == 'global' && empty($course_id) && (!api_is_platform_admin() && !api_is_question_manager())) {
            return false;
        }

        // Check if name already exists.
        $sql = "SELECT count(*) AS nb FROM $t_cattable WHERE title = '$v_name' $courseCondition";
        $result = Database::query($sql);
        $data = Database::fetch_array($result);

        // lets add in BD if not the same name
        if ($data['nb'] <= 0) {
            $category = new CQuizCategory();
            $category->setTitle($this->name);
            $category->setDescription($this->description);

            if (!empty($parent_id)) {
                $parent = Database::getManager()->find('\Entity\CQuizCategory', $parent_id);
                if ($parent) {
                    $category->setParent($parent);
                }
            }
            $category->setCId($course_id);
            Database::getManager()->persist($category);
            Database::getManager()->flush();

            if ($category->getIid()) {
                return $category->getIid();
            } else {
                return false;
            }
        } else {
            return false;
		}
    }

    /**
     * Modify category name or description of category with id=in_id
     */
    public function modifyCategory()
    {
        $category = Database::getManager()->find('\Entity\CQuizCategory', $this->id);
        if (!$category) {
            return false;
        }
        $courseId = $category->getCId();

        //Only admins can delete global categories
        if (empty($courseId) && !api_is_platform_admin()) {
            return false;
        }

        $category->setTitle($this->name);
        $category->setDescription($this->description);
        $category->setVisibility($this->visibility);

        if (!empty($this->parent_id)) {
            foreach ($this->parent_id as $parentId) {
                if ($this->id == $parentId) {
                    continue;
                }
                $parent = Database::getManager()->find('\Entity\CQuizCategory', $parentId);
                if ($parent) {
                    $category->setParent($parent);
                }
                break;
            }
        } else {
            $category->setParent(null);
        }

        Database::getManager()->persist($category);
        Database::getManager()->flush();

        if ($category->getIid()) {
            return $category->getIid();
        } else {
            return false;
        }
    }


    /**
     * Removes the category with id=in_id from the database if no question use this category
     * @todo I'm removing the $in_id parameter because it seems that you're using $this->id instead of $in_id after confirmation delete this
     * jmontoya
     */
    public function removeCategory()
    {
        $category = Database::getManager()->find('ChamiloLMS\CoreBundle\CQuizCategory', $this->id);
        if (!$category) {
            return false;
        }

        //Only admins can delete global categories
        $courseId = $category->getCId();
        //Only admins can delete global categories
        if (empty($courseId) && !api_is_platform_admin() || api_is_question_manager()) {
            return false;
        }

        $repo = Database::getManager()->getRepository('ChamiloLMSCoreBundle:CQuizCategory');
        $repo->removeFromTree($category);
        // clear cached nodes
        Database::getManager()->clear();
        return true;
    }


    /**
     * @param string $title
     * @param int $course_id
     * @return bool
     */
    public function get_category_by_title($title , $course_id = 0)
    {
        $table = Database::get_course_table(TABLE_QUIZ_CATEGORY);
        $course_id = intval($course_id);
        $title = Database::escape_string($title);

        $sql = "SELECT * FROM $table WHERE title = '$title' AND c_id IN ('0', '$course_id')LIMIT 1";

        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $result = Database::store_result($result, 'ASSOC');
            return $result[0];
        }
		return false;
	}

    /**
     *
     * Get categories by title for json calls
     * @param string $tag
     * @return array
     * @assert() === false
     */
    public function get_categories_by_keyword($tag)
    {
        if (empty($tag)) {
            return false;
        }
        $table = Database::get_course_table(TABLE_QUIZ_CATEGORY);
        $sql = "SELECT iid, title, c_id FROM $table WHERE 1=1 ";
        $tag = Database::escape_string($tag);

        $where_condition = array();
        if (!empty($tag)) {
            $condition = ' LIKE "%'.$tag.'%"';
            $where_condition = array(
                "title $condition",
            );
            $where_condition = ' AND  ('.implode(' OR ', $where_condition).') ';
        }

        switch ($this->type) {
            case 'simple':
                $course_condition = " AND c_id = '".api_get_course_int_id()."' ";
                break;
            case 'global':
                $course_condition = " AND c_id = '0' ";
                break;
            case 'all':
                $course_condition = " AND c_id IN ('0', '".api_get_course_int_id()."')";
                break;
        }

        $where_condition .= $course_condition;
        $order_clause = " ORDER BY title";
        $sql .= $where_condition.$order_clause;
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return Database::store_result($result, 'ASSOC');
        }
        return false;
	}

	/**
     * Gets the number of question of category id=in_id
     * @todo I'm removing the $in_id parameter because it seems that you're using $this->id instead of $in_id after confirmation delete this
     * jmontoya
	 */
	//public function getCategoryQuestionsNumber($in_id) {
	public function getCategoryQuestionsNumber()
    {
		$t_reltable = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
		$in_id = Database::escape_string($this->id);
        $sql = "SELECT count(*) AS nb FROM $t_reltable WHERE category_id = $in_id";
		$res = Database::query($sql);
        if (Database::num_rows($res)) {
		    $row = Database::fetch_array($res);
            return $row['nb'];
        }
		return 0;
	}

	public function display($in_color="#E0EBF5")
    {
		echo "<textarea style='background-color:$in_color; width:60%; height:100px;'>";
		print_r($this);
		echo "</textarea>";
	}

	/**
     * return an array of all Category objects in the database
	 * If in_field=="" Return an array of all category objects in the database
	 * Otherwise, return an array of all in_field value in the database (in_field = id or name or description)
	 */
	public static function getCategoryListInfo($in_field = "", $courseId = null)
    {
        $courseId = intval($courseId);
		$t_cattable = Database :: get_course_table(TABLE_QUIZ_CATEGORY);
		$in_field = Database::escape_string($in_field);
		$tabres = array();
		if ($in_field=="") {
			$sql = "SELECT * FROM $t_cattable WHERE c_id = $courseId ORDER BY title ASC";
			$res = Database::query($sql);
			while ($row = Database::fetch_array($res)) {
                $tmpcat = new Testcategory($row['iid'], $row['title'], $row['description'], $row['parent_id']);
				$tabres[] = $tmpcat;
			}
        } else {
			$sql = "SELECT $in_field FROM $t_cattable WHERE c_id = $courseId
			        ORDER BY $in_field ASC";

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
     * @assert () === false
	 */
    public static function getCategoryForQuestion($question_id, $courseId = null)
    {
		$result = array();
		if (empty($courseId)) {
            $courseId = api_get_course_int_id();
		}
        $courseId = intval($courseId);
		$categoryTable = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $question_id = Database::escape_string($question_id);
		$sql = "SELECT category_id FROM $categoryTable WHERE question_id = '$question_id' AND c_id = $courseId";
		$res = Database::query($sql);
		if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                $result[] = $row['category_id'];
            }
		}
		return $result;
	}

    public static function getCategoryForQuestionWithCategoryData($question_id, $courseId = null) {
		$result = array();	// result
		if (empty($courseId)) {
            $courseId = api_get_course_int_id();
		}
        $courseId = intval($courseId);

		$t_cattable = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $table_category = Database::get_course_table(TABLE_QUIZ_CATEGORY);
        $question_id = Database::escape_string($question_id);
        $sql = "SELECT * FROM $t_cattable qc INNER JOIN $table_category c ON (category_id = c.iid) WHERE question_id = '$question_id' AND qc.c_id = $courseId";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     *
     * @param int $question_id
     * @param int $course_id
     * @param bool $display_into_labels
     * @param bool $categoryMinusOne shows category - 1 see BT#6540
     * @return string
     */
    public static function getCategoryNamesForQuestion($question_id, $course_id = null, $display_into_labels = true, $categoryMinusOne = false)
    {
        if (empty($course_id) || $course_id == "") {
            $course_id = api_get_course_int_id();
        }
        $t_cattable = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $table_category = Database::get_course_table(TABLE_QUIZ_CATEGORY);
        $question_id = intval($question_id);
        $course_id = intval($course_id);

        $sql = "SELECT c.title, c.iid FROM $t_cattable qc INNER JOIN $table_category c
                ON (qc.category_id = c.iid AND qc.c_id = $course_id)
                WHERE question_id = '$question_id' ";
		$res = Database::query($sql);
        $result = array();
		if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $cat = new Testcategory($row['iid']);
                if ($categoryMinusOne) {
                    $catParts = explode('>', $cat->parent_path);
                    if (isset($catParts[0])) {
                        unset($catParts[0]);
                        $cat->parent_path = implode('>', $catParts);
                    }
                }
                $result[] = array('title' => $cat->parent_path);
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
     * @assert () === false
     * @assert (null) === false
     * @assert (-1) === false
	 */
    public static function isQuestionHasCategory($question_id)
    {
        $category_list = Testcategory::getCategoryForQuestion($question_id);
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
    public static function getCategoryNameForQuestion($catid, $course_id = null)
    {
		if (empty($course_id) || $course_id == "") {
			$course_id = api_get_course_int_id();
		}
        $course_id = intval($course_id);

        $result = array();
		$t_cattable = Database::get_course_table(TABLE_QUIZ_CATEGORY);
		$catid = Database::escape_string($catid);
        $sql = "SELECT title FROM $t_cattable WHERE iid = '$catid' AND c_id = $course_id";
		$res = Database::query($sql);
		$data = Database::fetch_array($res);
		if (Database::num_rows($res) > 0) {
			$result = $data['title'];
		}
		return $result;
	}


	/**
	 * return the list of different categories ID for a test
	 * @param int exercise id
     * @param bool group category
	 * @return array of category id (integer)
	 * @author hubert.borderiou 07-04-2011, Julio Montoya
	 */
    public static function getListOfCategoriesIDForTest($exercise_id, $grouped_by_category = true)
    {
		// parcourir les questions d'un test, recup les categories uniques dans un tableau
		$categories_in_exercise = array();
        $exercise = new Exercise();
        $exercise->read($exercise_id, false);
        $categories_in_exercise = $exercise->getQuestionWithCategories();
        $categories = array();
        if (!empty($categories_in_exercise)) {
            foreach($categories_in_exercise as $category) {
                $category['id'] = $category['iid'];
                $categories[$category['iid']] = $category;
            }
        }
        return $categories;

        /*
		// the array given by selectQuestionList start at indice 1 and not at indice 0 !!! ???
        foreach ($question_list as $question_id) {
            $category_list = Testcategory::getCategoryForQuestion($question_id);
            if (!empty($category_list)) {
                $categories_in_exercise = array_merge($categories_in_exercise, $category_list);
            }
        }
        if (!empty($categories_in_exercise)) {
            $categories_in_exercise = array_unique(array_filter($categories_in_exercise));
        }
        return $categories_in_exercise;*/
    }

    public static function getListOfCategoriesIDForTestObject(Exercise $exercise_obj)
    {
        // parcourir les questions d'un test, recup les categories uniques dans un tableau
        $categories_in_exercise = array();
        // $question_list = $exercise_obj->getQuestionList();
        $question_list = $exercise_obj->getQuestionOrderedListByName();

        // the array given by selectQuestionList start at indice 1 and not at indice 0 !!! ???
        foreach ($question_list as $questionInfo) {
            $question_id = $questionInfo['question_id'];
            $category_list = Testcategory::getCategoryForQuestion($question_id);
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
	 * Return the list of differents categories NAME for a test
	 * @param int exercise id
     * @param bool
	 * @return array of string
	 *
     * @author function rewrote by jmontoya
	 */
    public static function getListOfCategoriesNameForTest($exercise_id, $grouped_by_category = true)
    {
        $result = array();
        $categories = self::getListOfCategoriesIDForTest($exercise_id, $grouped_by_category);

        foreach ($categories as $catInfo) {
            $categoryId = $catInfo['iid'];
            ///$cat = new Testcategory($cat_id);
            if (!empty($categoryId)) {
                $result[$categoryId] = array(
                    'title' => $catInfo['title'],
                    'parent_id' =>  $catInfo['parent_id'],
                    'c_id' => $catInfo['c_id']
                );
		    }
        }
        return $result;
    }

    /**
     * @param Exercise $exercise_obj
     * @return array
     */
    public static function getListOfCategoriesForTest(Exercise $exercise_obj) {
        $result = array();
        $categories = self::getListOfCategoriesIDForTestObject($exercise_obj);
        foreach ($categories as $cat_id) {
            $cat = new Testcategory($cat_id);
            $cat = (array)$cat;
            $cat['iid'] = $cat['id'];
            $cat['title'] = $cat['name'];
            $result[$cat['id']] = $cat;
        }
        return $result;
	}

	/**
	 * return the number of differents categories for a test
	 * input : test_id
	 * return : integer
	 * hubert.borderiou 07-04-2011
	 */
    public static function getNumberOfCategoriesForTest($exercise_id) {
        return count(Testcategory::getListOfCategoriesIDForTest($exercise_id));
	}

	/**
	 * return the number of question of a category id in a test
	 * input : test_id, category_id
	 * return : integer
	 * hubert.borderiou 07-04-2011
	 */
	public static function getNumberOfQuestionsInCategoryForTest($exercise_id, $category_id)
    {
		$number_questions_in_category = 0;
		$exercise = new Exercise();
		$exercise->read($exercise_id);
		$question_list = $exercise->getQuestionList();
		// the array given by selectQuestionList start at indice 1 and not at indice 0 !!! ? ? ?
        foreach ($question_list as $question_id) {
            $category_in_question = Testcategory::getCategoryForQuestion($question_id);
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
	public static function getNumberOfQuestionRandomByCategory($exercise_id, $in_nbrandom)
    {
		$nbquestionresult = 0;
		$list_categories = Testcategory::getListOfCategoriesIDForTest($exercise_id);

        if (!empty($list_categories)) {
            foreach ($list_categories as $category_item) {
                if ($category_item > 0) {
                    // 0 = no category for this question
                    $nbQuestionInThisCat = Testcategory::getNumberOfQuestionsInCategoryForTest($exercise_id, $category_item);

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
	static function getCategoriesIdAndName($in_courseid = 0)
    {
	 	$tabcatobject = Testcategory::getCategoryListInfo("", $in_courseid);
	 	$tabresult = array("0"=>get_lang('NoCategorySelected'));
	 	for ($i=0; $i < count($tabcatobject); $i++) {
	 		$tabresult[$tabcatobject[$i]->id] = $tabcatobject[$i]->name;
	 	}
	 	return $tabresult;
	}

	/**
     * Returns an array of question ids for each category
     * $categories[1][30] = 10, array with category id = 1 and question_id = 10
     * A question has "n" categories
     * @param int exercise
     * @param array check question list
     * @param string order by
     * @return array
	 */
    static function getQuestionsByCat(
        $exerciseId,
        $check_in_question_list = array(),
        $categoriesAddedInExercise = array()
    ) {
        $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
		$TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
		$TBL_QUESTION_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $categoryTable = Database::get_course_table(TABLE_QUIZ_CATEGORY);
        $exerciseId = intval($exerciseId);

        $sql = "SELECT DISTINCT qrc.question_id, qrc.category_id
                FROM $TBL_QUESTION_REL_CATEGORY qrc INNER JOIN $TBL_EXERCICE_QUESTION eq
                ON (eq.question_id = qrc.question_id)
                INNER JOIN $categoryTable c
                ON (c.iid = qrc.category_id)
                INNER JOIN $tableQuestion q
                ON (q.iid = qrc.question_id )
                WHERE   exercice_id = $exerciseId AND
                        qrc.c_id = ".api_get_course_int_id()."
                ";

		$res = Database::query($sql);
        $categories = array();

		while ($data = Database::fetch_array($res)) {
            if (!empty($check_in_question_list)) {
                if (!in_array($data['question_id'], $check_in_question_list)) {
                    continue;
			    }
		    }

            if (!isset($categories[$data['category_id']]) OR !is_array($categories[$data['category_id']])) {
                $categories[$data['category_id']] = array();
            }

            $categories[$data['category_id']][] = $data['question_id'];
        }

        $newCategoryList = array();
        foreach ($categoriesAddedInExercise as $category) {
            $categoryId = $category['category_id'];
            if (isset($categories[$categoryId])) {
                $newCategoryList[$categoryId] = $categories[$categoryId];
            }
        }
        return $newCategoryList;
    }

    /**
     * Return an array of X elements of an array
     * @param array $array
     * @param int $numberOfElements
     * @param bool $randomize
     * @return array
     */
    public static function getNElementsFromArray($array, $numberOfElements, $randomize)
    {
        if (empty($numberOfElements)) {
            return array();
        }

        if (!empty($array)) {
            if ($randomize) {
                shuffle($array);
            }
            if ($numberOfElements < count($array)) {
                $array = array_slice($array, 0, $numberOfElements);
            }
        }
        return $array;
	}

	/**
		* Display signs [+] and/or (>0) after question title if question has options
		* scoreAlwaysPositive and/or uncheckedMayScore
		*/
	public function displayQuestionOption($in_objQuestion) {
		if ($in_objQuestion->type == MULTIPLE_ANSWER && $in_objQuestion->scoreAlwaysPositive) {
			echo "<span style='font-size:75%'> (>0)</span>";
		}
		if ($in_objQuestion->type == MULTIPLE_ANSWER && $in_objQuestion->uncheckedMayScore) {
			echo "<span style='font-size:75%'> [+]</span>";
		}
	}

	/**
     * key of $array are the categopy id (0 for not in a category)
	 * value is the array of question id of this category
	 * Sort question by Category
	*/
    public static function sortCategoriesQuestionByName($array) {
		$tabResult = array();
        $category_array = array();
        // tab of category name
        while (list($cat_id, $tabquestion) = each($array)) {
            $cat = new Testcategory($cat_id);
            $category_array[$cat_id] = $cat->title;
		}
        reset($array);
		// sort table by value, keeping keys as they are
        asort($category_array);
		// keys of $tabCatName are keys order for $in_tab
        while (list($key, $val) = each($category_array)) {
            $tabResult[$key] = $array[$key];
		}
		return $tabResult;
	}

	/**
		* return total score for test exe_id for all question in the category $in_cat_id for user
		* If no question for this category, return ""
	*/
	public static function getCatScoreForExeidForUserid($in_cat_id, $in_exe_id, $in_user_id) {
		$tbl_track_attempt		= Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
		$tbl_question_rel_category = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $in_cat_id = intval($in_cat_id);
        $in_exe_id = intval($in_exe_id);
        $in_user_id = intval($in_user_id);

		$query = "SELECT DISTINCT marks, exe_id, user_id, ta.question_id, category_id
                  FROM $tbl_track_attempt ta , $tbl_question_rel_category qrc
                  WHERE ta.question_id = qrc.question_id AND qrc.category_id=$in_cat_id AND exe_id = $in_exe_id AND user_id = $in_user_id";
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
    public static function getNumberMaxQuestionByCat($in_testid)
    {
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

    /**
     * @param int $course_id
     * @return array
     */
    public static function getCategoryListName($course_id = null)
    {
        $category_list = self::getCategoryListInfo(null, $course_id);
        $category_name_list = array();
        if (!empty($category_list)) {
            foreach ($category_list as $category) {
                $category_name_list[$category->id] = $category->name;
            }
        }
        return $category_name_list;
    }

    /**
     * @param array $category_list
     * @param array $all_categories
     * @return null|string
     */
    public static function return_category_labels($category_list, $all_categories)
    {
        $category_list_to_render = array();
        foreach ($category_list as $category_id) {
            $category_name = null;
            if (!isset($all_categories[$category_id])) {
                $category_name = get_lang('Untitled');
                $parentId = null;
                $cId = null;
            } else {
                $parentId = $all_categories[$category_id]['parent_id'];
                $cId = $all_categories[$category_id]['c_id'];
                $category_name = $all_categories[$category_id]['title'];
            }
            $category_list_to_render[] = array(
                'title' => $category_name,
                'parent_id' => $parentId,
                'c_id' => $cId
            );
        }
        $html = self::draw_category_label($category_list_to_render, 'label');
        return $html;
    }

    /**
     * @param array $category_list
     * @param string $type
     * @return null|string
     */
    public static function draw_category_label($category_list, $type = 'label') {

        $new_category_list = array();
        foreach ($category_list as $category) {
            $category_name = $category['title'];
            $category_name_cut = Text::cut($category['title'], 20);

            switch ($type) {
                case 'label':
                    // Global cat
                    /*
                    $parentId = isset($category['parent_id']) && !empty($category['parent_id']) ? $category['parent_id'] : null;
                    if (empty($parentId)) {
                        $new_category_list[] = Display::label($category_name, 'info');
                    } else {
                        // Local cat
                        $new_category_list[] = Display::label($category_name, 'success');
                    }*/
                    $courseId = isset($category['c_id']) && !empty($category['c_id']) ? $category['c_id'] : null;
                    if (empty($courseId)) {
                        $new_category_list[] = Display::label($category_name_cut, 'info', $category_name);
                    } else {
                        // Local cat
                        $new_category_list[] = Display::label($category_name_cut, 'success', $category_name);
                    }
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
                    break;
            }
        }
        return $html;
    }

    /**
     * Returns a category summary report
     *
     * @param int exercise id
     * @param array prefilled array with the category_id, score, and weight example: array(1 => array('score' => '10', 'total' => 20));
     * @param bool $categoryMinusOne shows category - 1 see BT#6540
     * @return string
     */
    public static function get_stats_table_by_attempt($exercise_id, $category_list = array(), $categoryMinusOne = false)
    {
        if (empty($category_list)) {
            return null;
        }

        $category_name_list = Testcategory::getListOfCategoriesNameForTest($exercise_id, false);

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
        $em = Database::getManager();
        $repo = $em->getRepository('ChamiloLMSCoreBundle:CQuizCategory');

        $redefineCategoryList = array();

        if (!empty($category_list) && count($category_list) > 1) {
            $globalCategoryScore = array();

            foreach ($category_list as $category_id => $category_item) {
                $cat = $em->find('ChamiloLMSCoreBundle:CQuizCategory', $category_id);
                $path = $repo->getPath($cat);

                $categoryName = $category_name_list[$category_id];

                $index = 0;
                if ($categoryMinusOne) {
                    $index = 1;
                }
                if (isset($path[$index])) {
                    $category_id = $path[$index]->getIid();
                    $categoryName = $path[$index]->getTitle();
                }
                if (!isset($globalCategoryScore[$category_id])) {
                    $globalCategoryScore[$category_id] = array();
                    $globalCategoryScore[$category_id]['score'] = 0;
                    $globalCategoryScore[$category_id]['total'] = 0;
                    $globalCategoryScore[$category_id]['title'] = '';
                }
                $globalCategoryScore[$category_id]['score'] += $category_item['score'];
                $globalCategoryScore[$category_id]['total'] += $category_item['total'];
                $globalCategoryScore[$category_id]['title'] = $categoryName;
            }

            foreach ($globalCategoryScore as $category_item) {
                $table->setCellContents($row, 0, $category_item['title']);
                $table->setCellContents($row, 1, ExerciseLib::show_score($category_item['score'], $category_item['total'], false));
                $table->setCellContents($row, 2, ExerciseLib::show_score($category_item['score'], $category_item['total'], true, false, true));

                $class = 'class="row_odd"';
                if ($row % 2) {
                    $class = 'class="row_even"';
                }
                $table->setRowAttributes($row, $class, true);
                $row++;

            }

            if (!empty($none_category)) {
                $table->setCellContents($row, 0, get_lang('None'));
                $table->setCellContents($row, 1, ExerciseLib::show_score($none_category['score'], $none_category['total'], false));
                $table->setCellContents($row, 2, ExerciseLib::show_score($none_category['score'], $none_category['total'], true, false, true));
                $row++;
            }

            if (!empty($total)) {
                $table->setCellContents($row, 0, get_lang('Total'));
                $table->setCellContents($row, 1, ExerciseLib::show_score($total['score'], $total['total'], false));
                $table->setCellContents($row, 2, ExerciseLib::show_score($total['score'], $total['total'], true, false, true));
                $table->setRowAttributes($row, 'class="row_total"', true);
            }

            return $table->toHtml();
        }

        return null;
    }

    /**
     * @return array
     */
    function get_all_categories() {
        $table = Database::get_course_table(TABLE_QUIZ_CATEGORY);
        $sql = "SELECT * FROM $table ORDER BY title ASC";
        $res = Database::query($sql);
        while ($row = Database::fetch_array($res,'ASSOC')) {
            $array[] = $row;
        }
        return $array;
    }

    /**
     * @param int $exercise_id
     * @param int $course_id
     * @param string $order
     * @return array
     */
    public function getCategoryExerciseTree(
        $exercise_id,
        $course_id,
        $order = null,
        $shuffle = false,
        $excludeCategoryWithNoQuestions = true
    ) {
        $table = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
        $table_category = Database::get_course_table(TABLE_QUIZ_CATEGORY);
        $sql = "SELECT * FROM $table qc INNER JOIN $table_category c ON (category_id = c.iid)
                WHERE exercise_id = {$exercise_id} ";

        if (!empty($order)) {
            $sql .= "ORDER BY $order";
        }
        $categories = array();

        $result = Database::query($sql);
        if (Database::num_rows($result)) {
             while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($excludeCategoryWithNoQuestions) {
                    if ($row['count_questions'] == 0) {
                       continue;
                    }
                }
                $categories[$row['category_id']] = $row;
            }
            }

        if ($shuffle) {
            ArrayClass::shuffle_assoc($categories);
        }
        return $categories;
    }

    /**
     * Returns the category form.
     * @param Exercise $exercise_obj
     * @return string
     */
    public function returnCategoryForm(Exercise $exercise_obj)
    {
        $categories = $this->getListOfCategoriesForTest($exercise_obj);

        $saved_categories = $exercise_obj->get_categories_in_exercise();
        $return = null;

        if (!empty($categories)) {
            $nbQuestionsTotal = $exercise_obj->getNumberQuestionExerciseCategory();
            $exercise_obj->setCategoriesGrouping(true);
            $real_question_count = count($exercise_obj->getQuestionList());

            $warning = null;
            if ($nbQuestionsTotal != $real_question_count) {
                $warning = Display::return_message(get_lang('CheckThatYouHaveEnoughQuestionsInYourCategories'), 'warning');
            }

            $return .= $warning;
            $return .= '<table class="data_table">';
            $return .= '<tr>';
            $return .= '<th height="24">' . get_lang('Categories') . '</th>';
            $return .= '<th width="70" height="24">' . get_lang('Number') . '</th></tr>';

            foreach($categories as $category) {
                $cat_id = $category['iid'];
                $return .= '<tr>';
                $return .= '<td>';
                $return .= Display::div($category['parent_path']);
                $return .= '</td>';
                $return .= '<td>';
                $value = isset($saved_categories) && isset($saved_categories[$cat_id]) ? $saved_categories[$cat_id]['count_questions'] : -1;
                $return .= '<input name="category['.$cat_id.']" value="' .$value.'" />';
                $return .= '</td>';
                $return .= '</tr>';
            }
            $return .= '</table>';
            $return .= get_lang('ZeroMeansNoQuestionWillBeSelectedMinusOneMeansThatAllQuestionsWillBeSelected');
            return $return;
        }
    }

    /**
     * Sorts an array
     * @param $array
     * @return mixed
     */
    public function sort_tree_array($array)
    {
      foreach ($array as $key => $row) {
            $parent[$key] = $row['parent_id'];
        }
        if (count($array) > 0) {
            array_multisort($parent, SORT_ASC, $array);
        }
        return $array;
    }

    public function getForm(& $form, $action = 'new') {

        switch($action) {
            case 'new':
                $header = get_lang('AddACategory');
                $submit = get_lang('AddTestCategory');
                break;
            case 'edit':
                $header = get_lang('EditCategory');
                $submit = get_lang('ModifyCategory');
                break;
        }

         // settting the form elements
        $form->addElement('header', $header);
        $form->addElement('hidden', 'category_id');
        $form->addElement('text', 'category_name', get_lang('CategoryName'), array('class' => 'span6'));
        $form->add_html_editor('category_description', get_lang('CategoryDescription'), false, false, array('ToolbarSet' => 'test_category', 'Width' => '90%', 'Height' => '200'));
        $category_parent_list = array();

        $options = array(
            '1' => get_lang('Visible'),
            '0' => get_lang('Hidden')
        );
        $form->addElement('select', 'visibility', get_lang('Visibility'), $options);
        $script = null;
        if (!empty($this->parent_id)) {
            $parent_cat = new Testcategory($this->parent_id);
            $category_parent_list = array($parent_cat->id => $parent_cat->name);
            $script .= '<script>$(function() { $("#parent_id").trigger("addItem",[{"title": "'.$parent_cat->name.'", "value": "'.$parent_cat->id.'"}]); });</script>';
        }
        $form->addElement('html', $script);

        $form->addElement('select', 'parent_id', get_lang('Parent'), $category_parent_list, array('id' => 'parent_id'));
        $form->addElement('style_submit_button', 'SubmitNote', $submit, 'class="add"');

        // setting the defaults
        $defaults = array();
        $defaults["category_id"] = $this->id;
        $defaults["category_name"] = $this->name;
        $defaults["category_description"] = $this->description;
        $defaults["parent_id"] = $this->parent_id;
        $defaults["visibility"] = $this->visibility;
        $form->setDefaults($defaults);

        // setting the rules
        $form->addRule('category_name', get_lang('ThisFieldIsRequired'), 'required');
    }
}
