<?php
/* For licensing terms, see /license.txt */

/**
 * This is a function library for the learning path.
 *
 * Due to the face that the learning path has been built upon the resoucelinker,
 * naming conventions have changed at least 2 times. You can see here in order the :
 * 1. name used in the first version of the resourcelinker
 * 2. name used in the first version of the LP
 * 3. name used in the second (current) version of the LP
 *
 *       1.       2.        3.
 *   Category = Chapter = Module
 *   Item (?) = Item    = Step
 *
 * @author  Denes Nagy <darkden@evk.bke.hu>, main author
 * @author  Roan Embrechts, some code cleaning
 * @author  Yannick Warnier <yannick.warnier@beeznest.com>, multi-level learnpath behaviour + new SCORM tool
 * @access  public
 * @package chamilo.learnpath
 * @todo rename functions to coding conventions: not deleteitem but delete_item, etc
 * @todo rewrite functions to comply with phpDocumentor
 * @todo remove code duplication
 */

use ChamiloSession as Session;

/**
 * This function returns false if there is at least one item in the path
 * @param	Learnpath ID
 * @return	boolean	True if nothing was found, false otherwise
 */
function is_empty($id) {
    $tbl_learnpath_item = Database :: get_course_table(TABLE_LEARNPATH_ITEM);
    $tbl_learnpath_chapter = Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);
    $course_id = api_get_course_int_id();

    $sql = "SELECT * FROM $tbl_learnpath_chapter WHERE c_id = $course_id AND lp_id=$id ORDER BY display_order ASC";
    $result = Database::query($sql);
    $num_modules = Database::num_rows($result);
    $empty = true;

    if ($num_modules != 0) {
        while ($row = Database::fetch_array($result)) {

            $num_items = 0;
            $parent_item_id = $row['id'];
            $sql2 = "SELECT * FROM $tbl_learnpath_item WHERE c_id = $course_id AND (parent_item_id=$parent_item_id) ORDER BY display_order ASC";
            $result2 = Database::query($sql2);
            $num_items = Database::num_rows($result2);
            if ($num_items > 0) {
                $empty = false;
            }
        }
    }

    return ($empty);
}

/**
 * This function writes $content to $filename
 * @param	string	Destination filename
 * @param	string	Learnpath name
 * @param	integer	Learnpath ID
 * @param	string	Content to write
 * @return	void
 */
function exporttofile($filename, $LPname, $LPid, $content) {

    global $circle1_files; // This keeps all the files which are exported [0]:filename [1]:LP name.
    // The $circle1_files variable is going to be used to a deep extent in the imsmanifest.xml.
    global $expdir;

    if (!$handle = fopen($expdir.'/'.$filename, 'w')) {
        echo "Cannot open file ($filename)";
    }
    if (fwrite($handle, $content) === false) {
        echo "Cannot write to file ($filename)";
        exit;
    }
    fclose($handle);

    $circle1_files[0][] = $filename;
    $circle1_files[1][] = $LPname;
    $circle1_files[2][] = $LPid;
}

/**
 * This function exports the given Chamilo test
 * @param	integer	Test ID
 * @return string 	The test itself as an HTML string
 */
function export_exercise($item_id) {

    global $expdir, $_course, $_configuration, $_SESSION, $_SERVER, $language_interface, $langExerciseNotFound, $langQuestion, $langOk, $origin, $questionNum;

    $exerciseId = $item_id;
    $TBL_EXERCISES = Database :: get_course_table(TABLE_QUIZ_TEST);

    /* Clears the exercise session */
    if (isset ($_SESSION['objExercise'])) {
        Session::erase('objExercise');
    }
    if (isset ($_SESSION['objQuestion'])) {
        Session::erase('objQuestion');
    }
    if (isset ($_SESSION['objAnswer'])) {
        Session::erase('objAnswer');
    }
    if (isset ($_SESSION['questionList'])) {
        Session::erase('questionList');
    }
    if (isset ($_SESSION['exerciseResult'])) {
        Session::erase('exerciseResult');
    }

    // If the object is not in the session:
    if (!isset ($_SESSION['objExercise'])) {
        // Construction of Exercise.
        $objExercise = new Exercise();

        $sql = "SELECT title,description,sound,type,random,active FROM $TBL_EXERCISES WHERE id='$exerciseId'";
        // If the specified exercise doesn't exist or is disabled:
        if (!$objExercise->read($exerciseId) || (!$objExercise->selectStatus() && !api_is_allowed_to_edit() && ($origin != 'learnpath'))) {
            die($langExerciseNotFound);
        }

        // Saves the object into the session.
        Session::write('objExercise',$objExercise);
    }

    $exerciseTitle = $objExercise->selectTitle();
    $exerciseDescription = $objExercise->selectDescription();
    $exerciseSound = $objExercise->selectSound();
    $randomQuestions = $objExercise->isRandom();
    $exerciseType = $objExercise->selectType();

    if (!isset ($_SESSION['questionList'])) {
        // Selects the list of question ID.
        $questionList = $randomQuestions ? $objExercise->selectRandomList() : $objExercise->selectQuestionList();

        // Saves the question list into the session.
        Session::write('questionList',$questionList);
    }

    $nbrQuestions = sizeof($questionList);

    // If questionNum comes from POST and not from GET:
    if (!$questionNum || $_POST['questionNum']) {
        // Only used for sequential exercises (see $exerciseType).
        if (!$questionNum) {
            $questionNum = 1;
        } else {
            $questionNum ++;
        }
    }

    $exerciseTitle = text_filter($exerciseTitle);

    $test .= "<h3>".$exerciseTitle."</h3>";

    if (!empty ($exerciseSound)) {
        $test .= "<a href=\"../document/download.php?doc_url=%2Faudio%2F".$exerciseSound."\"&SQMSESSID=36812c2dea7d8d6e708d5e6a2f09b0b9 target=\"_blank\"><img src=\"../img/sound.gif\" border=\"0\" align=\"absmiddle\" alt=".get_lang("Sound")."\" /></a>";
    }

    $exerciseDescription = text_filter($exerciseDescription);

    // Writing the .js file with to check the correct answers begin.
    $scriptfilename = "Exercice".$item_id.".js";
    $s = "<script type=\"text/javascript\" src='../js/".$scriptfilename."'></script>";
    $test .= $s;

    $content = "function evaluate() {
        alert('Test evaluated.');
        }
        ";

    if (!$handle = fopen($expdir.'/js/'.$scriptfilename, 'w')) {
        echo "Cannot open file ($scriptfilename)";
    }
    if (fwrite($handle, $content) === false) {
        echo "Cannot write to file ($filename)";
        exit;
    }
    fclose($handle);

    // Writing the .js file with to check the correct answers end.
    $s = "
        <p>$exerciseDescription</p>
        <table width='100%' border='0' cellpadding='1' cellspacing='0'>
         <form method='post' action=''><input type=\"hidden\" name=\"SQMSESSID\" value=\"36812c2dea7d8d6e708d5e6a2f09b0b9\" />
         <input type='hidden' name='formSent' value='1' />
         <input type='hidden' name='exerciseType' value='".$exerciseType."' />
         <input type='hidden' name='questionNum' value='".$questionNum."' />
         <input type='hidden' name='nbrQuestions' value='".$nbrQuestions."' />
         <tr>
          <td>
          <table width='100%' cellpadding='4' cellspacing='2' border='0'>";

    $test .= $s;

    $i = 0;

    foreach ($questionList as $questionId) {
        $i ++;

        echo $s = "<tr bgcolor='#e6e6e6'><td valign='top' colspan='2'>".get_lang('Question')." ";
        $test .= ExerciseLib::showQuestion($questionId, false, 'export', $i);

    } // end foreach()

    $s = "</table></td></tr><tr><td><br/><input type='button' value='".$langOk."' onclick=\"javascript: evaluate(); alert('Evaluated.');\">";
    $s .= "</td></tr></form></table>";
    $s .= "<script type='text/javascript'> loadPage(); </script>";
    $b = 2;
    $test .= $s;
    return ($test);
}

/**
 * This function exports the given item's description into a separate file
 * @param	integer	Item id
 * @param	string	Item type
 * @param	string	Description
 * @return void
 */
function exportdescription($id, $item_type, $description) {
    global $expdir;
    $filename = $item_type.$id.'.desc';
    $expcontent = $description;
    exporttofile($expdir.$filename, 'description_of_'.$item_type.$id, 'description_of_item_'.$id, $expcontent);
}

/**
 * This function deletes an entire directory
 * @param	string	The directory path
 * @return boolean	True on success, false on failure
 */
function deldir($dir) {
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if ($file != '.' && $file != '..') {
            $fullpath = $dir.'/'.$file;
            if (!is_dir($fullpath)) {
                unlink($fullpath);
            } else {
                deldir($fullpath);
            }
        }
    }

    closedir($dh);

    if (rmdir($dir)) {
        return true;
    }
    return false;
}

/**
 * This function returns an xml tag
 * $data behaves as the content in case of full tags
 * $data is an array of attributes in case of returning an opening tag
 * @param	string
 * @param	string
 * @param	array
 * @param	string
 * @return string
 */
function xmltagwrite($tagname, $which, $data, $linebreak = 'yes') {
    switch ($which) {
        case 'open':
            $tag = '<'.$tagname;
            $i = 0;
            while ($data[0][$i]) {
                $tag .= ' '.$data[0][$i]."=\"".$data[1][$i]."\"";
                $i ++;
            }
            if ($tagname == 'file') {
                $closing = '/';
            }
            $tag .= $closing.'>';
            if ($linebreak != 'no_linebreak') {
                $tag .= "\n";
            }
            break;
        case 'close':
            $tag = '</'.$tagname.'>';
            if ($linebreak != 'no_linebreak') {
                $tag .= "\n";
            }
            break;
        case 'full':
            $tag = '<'.$tagname;
            $tag .= '>'.$data.'</'.$tagname.'>';
            if ($linebreak != 'no_linebreak') {
                $tag .= "\n";
            }
            break;
    }
    return $tag;
}


/**
 * Gets the tags of the file given as parameter
 *
 * if $filename is not found, GetSRCTags(filename) will return FALSE
 * @param string		File path
 * @return mixed		Array of strings on success, false on failure
 * @author unknown
 * @author Included by imandak80
 */
function GetSRCTags($fileName) {
    if (!($fp = fopen($fileName, 'r'))) {
        // Iif file can't be opened, return false.
        return false;
    }
    // Read file contents.
    $contents = fread($fp, filesize($fileName));
    fclose($fp);

    $matches = array();
    $srcList = array();
    // Get all src tags contents in this file. Use multi-line search.
    preg_match_all('/src(\s)*=(\s)*[\'"]([^\'"]*)[\'"]/mi', $contents, $matches); // Get the img src as contained between " or '

    foreach ($matches[3] as $match) {
        if (!in_array($match, $srcList)) {
            $srcList[] = $match;
        }
    }
    if (count($srcList) == 0) {
        return false;
    }
    return $srcList;
}

function rcopy($source, $dest) {
    //error_log($source." -> ".$dest, 0);
    if (!file_exists($source)) {
        //error_log($source." does not exist", 0);
        return false;
    }

    if (is_dir($source)) {
        //error_log($source." is a dir", 0);
        // This is a directory.
        // Remove trailing '/'
        if (strrpos($source, '/') == sizeof($source) - 1) {
            $source = substr($source, 0, size_of($source) - 1);
        }
        if (strrpos($dest, '/') == sizeof($dest) - 1) {
            $dest = substr($dest, 0, size_of($dest) - 1);
        }

        if (!is_dir($dest)) {
            $res = @mkdir($dest, api_get_permissions_for_new_directories());
            if ($res !== false) {
                return true;
            } else {
                // Remove latest part of path and try creating that.
                if (rcopy(substr($source, 0, strrpos($source, '/')), substr($dest, 0, strrpos($dest, '/')))) {
                    return @mkdir($dest, api_get_permissions_for_new_directories());
                } else {
                    return false;
                }
            }
        }
        return true;
    } else {
        // This is presumably a file.
        //error_log($source." is a file", 0);
        if (!@ copy($source, $dest)) {
            //error_log("Could not simple-copy $source", 0);
            $res = rcopy(dirname($source), dirname($dest));
            if ($res === true) {
                //error_log("Welcome dir created", 0);
                return @ copy($source, $dest);
            } else {
                return false;
                //error_log("Error creating path", 0);
            }
        } else {
            //error_log("Could well simple-copy $source", 0);
            return true;
        }
    }
}
