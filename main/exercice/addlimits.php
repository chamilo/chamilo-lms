<?php
/*
-----------------------------------------------------------
	including the global file
-----------------------------------------------------------
*/
include('../inc/global.inc.php');

/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
include('exercise.class.php');
include('question.class.php');
include('answer.class.php');
include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

/*
-----------------------------------------------------------
	Answer types
-----------------------------------------------------------
*/
define('UNIQUE_ANSWER',	1);
define('MULTIPLE_ANSWER',	2);
define('FILL_IN_BLANKS',	3);
define('MATCHING',		4);
define('FREE_ANSWER', 5);

/*
-----------------------------------------------------------
	Language
-----------------------------------------------------------
*/
$langFile='exercice';

/*
-----------------------------------------------------------
	section (for the tabs)
-----------------------------------------------------------
*/
$this_section=SECTION_COURSES;

api_protect_course_script();

/*
-----------------------------------------------------------
	Table definitions 
	@todo: use the Database :: get_course_table functions
-----------------------------------------------------------
*/
$TBL_EXERCICE_QUESTION = $_course['dbNameGlu'].'quiz_rel_question';
$TBL_EXERCICES         = $_course['dbNameGlu'].'quiz';
$TBL_QUESTIONS         = $_course['dbNameGlu'].'quiz_question';
$TBL_REPONSES          = $_course['dbNameGlu'].'quiz_answer';
$main_user_table = Database :: get_main_table(MAIN_USER_TABLE);
$main_course_user_table = Database :: get_main_table(MAIN_COURSE_USER_TABLE);
$statsdb = $statsDbName;
$TABLETRACK_ATTEMPT = $statsDbName."`.`track_e_attempt"; 
$TABLETRACK_EXERCICES = $statsDbName."`.`track_e_exercices";

$dsp_percent = false; 
$debug=0;
if($debug>0){echo str_repeat('&nbsp;',0).'Entered exercise_result.php'."<br />\n";var_dump($_POST);}
// general parameters passed via POST/GET
if ( empty ( $origin ) ) {
    $origin = $_REQUEST['origin'];
}
if ( empty ( $learnpath_id ) ) {
    $learnpath_id       = mysql_real_escape_string($_REQUEST['learnpath_id']);
}
if ( empty ( $learnpath_item_id ) ) {
    $learnpath_item_id  = mysql_real_escape_string($_REQUEST['learnpath_item_id']);
}
if ( empty ( $formSent ) ) {
    $formSent= $_REQUEST['formSent'];
}
if ( empty ( $exerciseResult ) ) {
    $exerciseResult = $_SESSION['exerciseResult'];
}
if ( empty ( $questionId ) ) {
    $questionId = $_REQUEST['questionId'];
}
if ( empty ( $choice ) ) {
    $choice = $_REQUEST['choice'];
}
if ( empty ( $questionNum ) ) {
    $questionNum    = mysql_real_escape_string($_REQUEST['questionNum']);
} 
if ( empty ( $nbrQuestions ) ) {
    $nbrQuestions   = mysql_real_escape_string($_REQUEST['nbrQuestions']);
}
if ( empty ( $questionList ) ) {
    $questionList = $_SESSION['questionList'];
}
if ( empty ( $objExercise ) ) {
    $objExercise = $_SESSION['objExercise'];
}
$exercise_id = $_GET['exercise_id'];
$is_allowedToEdit=$is_courseAdmin;	


$nameTools=get_lang('Exercice');

$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));

Display::display_header($nameTools,"Exercise");

/*
-----------------------------------------------------------
	Action handling
-----------------------------------------------------------
*/
include('../inc/global.inc.php');
if (isset($_POST['ok']))
{
	$message = "Test limits added";
	Display::display_normal_message($message);
}
?>



  <script type="text/javascript">
  function selectlimited()
  {
  document.getElementById('limited').checked="checked";
  }
   function selectattempts()
  {
  document.getElementById('attemptlimited').checked="checked";
  }
  </script>
  <h3>
  <?php 
  echo "AddLimits"; ?>
</h3>
<br>
<form action="addlimits.php" name="frmlimit" method="post">
<h4>
Time :
</h4>
<input type="hidden" name="exe_id" value="<?php echo $exercise_id; ?>" />
<input type="radio" name="limit" checked="checked" value="0" id="unlimit"> Unlimited
<br>
<input type="radio" name="limit" value="1" id="limited"> Limited: Each question of the test should be answered in
<select name="minutes" onfocus="selectlimited();" >
  <option selected="selected">1</option>
  <option>2</option>
  <option>3</option>
  <option>4</option>
  <option>5</option>
  <option>6</option>
  <option>7</option>
  <option>8</option>
  <option>9</option>
  <option>10</option>
  <option>15</option>
  <option>20</option>
  <option>25</option>
  <option>30</option>
  <option>40</option>
  <option>50</option>
  <option>60</option>
</select> minute(s).
<h4>
Attempts :
</h4>
<input type="radio" name="attempt" checked="checked" value="0" id="attemptunlimited"> Unlimited
<br>
<input type="radio" name="attempt" value="1" id="attemptlimited"> Limited: The test can be taken only
<select name="attempts" onfocus="selectattempts();">
  <option selected="selected">1</option>
  <option>2</option>
  <option>3</option>
  <option>4</option>
  <option>5</option>
  <option>6</option>
  <option>7</option>
  <option>8</option>
  <option>9</option>
  <option>10</option>
  </select> time(s).

<br> <br>
<input type="submit" name="ok" value="Ok">
</form>
<?php
/**
 * @todo shouldn't this be moved to the part above (around line 111: action handling)
 */
if (isset($_POST['ok']))
{
	$exercise_id = $_POST['exe_id'];
	if ($_POST['limit']==1)
	{
		$minutes = $_POST['minutes'];
		$query = "update `".$TBL_EXERCICES."` set ques_time_limit= $minutes where id= $exercise_id";
		api_sql_query($query,__FILE__,__LINE__);
	}
	else
	{
		$query = "update `".$TBL_EXERCICES."` set ques_time_limit= 0 where id= $exercise_id";
		api_sql_query($query,__FILE__,__LINE__);
	}

	if ($_POST['attempt']==1)
	{
		$attempts = $_POST['attempts'];
		$query = "update `".$TBL_EXERCICES."` set num_attempts = $attempts where id= $exercise_id";
		api_sql_query($query,__FILE__,__LINE__);
	}
	else
	{
		$query = "update`".$TBL_EXERCICES."` set num_attempts = 0 where id= $exercise_id";
		api_sql_query($query,__FILE__,__LINE__);
	}
}
?>

