<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizAnswer;

/**
 * Class Answer
 * Allows to instantiate an object of type Answer, as a *list* of answers for one question
 * 5 arrays are created to receive the attributes of each answer belonging to a specified question.
 *
 * @author Olivier Brouckaert
 */
class Answer
{
    /* The question of which we want the possible answers */
    public $questionId;

    // these are arrays
    public $answer;
    public $correct;
    public $comment;
    public $weighting;
    public $position;
    public $hotspot_coordinates;
    public $hotspot_type;
    public $destination;
    // these arrays are used to save temporarily new answers
    // then they are moved into the arrays above or deleted in the event of cancellation
    public $new_answer;
    public $new_correct;
    public $new_comment;
    public $new_weighting;
    public $new_position;
    public $new_hotspot_coordinates;
    public $new_hotspot_type;
    /** @var int Incremental ID used in the past when PK was a mix of c_id+id */
    public $autoId;
    /** @var int Number of answers in the question */
    public $nbrAnswers;
    public $new_nbrAnswers;
    public $new_destination; // id of the next question if feedback option is set to Directfeedback
    public $course; //Course information
    public $iid;
    public $questionJSId;
    public $standalone;
    /** @var Exercise|null */
    private $exercise;

    /**
     * @author Olivier Brouckaert
     *
     * @param int      $questionId that answers belong to
     * @param int      $course_id
     * @param Exercise $exercise
     * @param bool     $readAnswer
     */
    public function __construct($questionId, $course_id = 0, $exercise = null, $readAnswer = true)
    {
        $this->questionId = (int) $questionId;
        $this->answer = [];
        $this->correct = [];
        $this->comment = [];
        $this->weighting = [];
        $this->position = [];
        $this->hotspot_coordinates = [];
        $this->hotspot_type = [];
        $this->destination = [];
        // clears $new_* arrays
        $this->cancel();

        if (!empty($course_id)) {
            $courseInfo = api_get_course_info_by_id($course_id);
        } else {
            $courseInfo = api_get_course_info();
        }

        $this->course = $courseInfo;
        $this->course_id = $courseInfo['real_id'];

        if (empty($exercise)) {
            // fills arrays
            $objExercise = new Exercise($this->course_id);
            $exerciseId = isset($_REQUEST['exerciseId']) ? $_REQUEST['exerciseId'] : null;
            $objExercise->read($exerciseId, false);
        } else {
            $objExercise = $exercise;
        }
        $this->exercise = $objExercise;

        if ($readAnswer) {
            if ($objExercise->random_answers == '1' && $this->getQuestionType() != CALCULATED_ANSWER) {
                $this->readOrderedBy('rand()', ''); // randomize answers
            } else {
                $this->read(); // natural order
            }
        }
    }

    /**
     * Clears $new_* arrays.
     *
     * @author Olivier Brouckaert
     */
    public function cancel()
    {
        $this->new_answer = [];
        $this->new_correct = [];
        $this->new_comment = [];
        $this->new_weighting = [];
        $this->new_position = [];
        $this->new_hotspot_coordinates = [];
        $this->new_hotspot_type = [];
        $this->new_nbrAnswers = 0;
        $this->new_destination = [];
    }

    /**
     * Reads answer information from the database.
     *
     * @author Olivier Brouckaert
     */
    public function read()
    {
        $table = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $questionId = $this->questionId;

        $sql = "SELECT * FROM $table
                WHERE question_id = $questionId
                ORDER BY position";

        $result = Database::query($sql);
        $i = 1;

        // while a record is found
        while ($object = Database::fetch_object($result)) {
            $this->id[$i] = $object->iid;
            $this->iid[$i] = $object->iid;
            $this->answer[$i] = $object->answer;
            $this->correct[$i] = $object->correct;
            $this->comment[$i] = $object->comment;
            $this->weighting[$i] = $object->ponderation;
            $this->position[$i] = $object->position;
            $this->hotspot_coordinates[$i] = $object->hotspot_coordinates;
            $this->hotspot_type[$i] = $object->hotspot_type;
            $this->destination[$i] = $object->destination;
            $this->autoId[$i] = $object->id_auto;
            $i++;
        }
        $this->nbrAnswers = $i - 1;
    }

    /**
     * Get answers already added to question.
     *
     * @return array
     */
    public function getAnswers()
    {
        $table = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $questionId = $this->questionId;

        $sql = "SELECT * FROM $table
                WHERE question_id = $questionId
                ORDER BY position";

        $result = Database::query($sql);

        $answers = [];

        // while a record is found
        while ($answer = Database::fetch_assoc($result)) {
            $answers[] = $answer;
        }

        return $answers;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getAnswerByAutoId($id)
    {
        foreach ($this->autoId as $key => $autoId) {
            if ($autoId == $id) {
                return [
                    'answer' => $this->answer[$key],
                    'correct' => $this->correct[$key],
                    'comment' => $this->comment[$key],
                ];
            }
        }

        return [];
    }

    /**
     * returns all answer ids from this question Id.
     *
     * @author Yoselyn Castillo
     *
     * @return array - $id (answer ids)
     */
    public function selectAnswerId()
    {
        $table = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $questionId = $this->questionId;

        $sql = "SELECT iid FROM $table WHERE question_id = $questionId";

        $result = Database::query($sql);
        $id = [];
        // while a record is found
        if (Database::num_rows($result) > 0) {
            while ($object = Database::fetch_array($result)) {
                $id[] = $object['iid'];
            }
        }

        return $id;
    }

    /**
     * Reads answer information from the data base ordered by parameter.
     *
     * @param string $field Field we want to order by
     * @param string $order DESC or ASC
     *
     * @return bool
     *
     * @author Frederic Vauthier
     */
    public function readOrderedBy($field, $order = 'ASC')
    {
        $field = Database::escape_string($field);
        if (empty($field)) {
            $field = 'position';
        }

        if ('ASC' != $order && 'DESC' != $order) {
            $order = 'ASC';
        }

        $TBL_ANSWER = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $TBL_QUESTION = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $questionId = (int) $this->questionId;

        $sql = "SELECT type FROM $TBL_QUESTION WHERE iid = $questionId";
        $result_question = Database::query($sql);
        $questionType = Database::fetch_array($result_question);

        if (DRAGGABLE == $questionType['type']) {
            // Random is done by submit.js.tpl
            $this->read();

            return true;
        }

        $sql = "SELECT
                    answer,
                    correct,
                    comment,
                    ponderation,
                    position,
                    hotspot_coordinates,
                    hotspot_type,
                    destination,
                    id_auto,
                    iid
                FROM $TBL_ANSWER
                WHERE
                    question_id='".$questionId."'
                ORDER BY $field $order";
        $result = Database::query($sql);

        $i = 1;
        // while a record is found
        $doubt_data = null;
        while ($object = Database::fetch_object($result)) {
            if ($questionType['type'] == UNIQUE_ANSWER_NO_OPTION && $object->position == 666) {
                $doubt_data = $object;
                continue;
            }
            $this->answer[$i] = $object->answer;
            $this->correct[$i] = $object->correct;
            $this->comment[$i] = $object->comment;
            $this->weighting[$i] = $object->ponderation;
            $this->position[$i] = $object->position;
            $this->hotspot_coordinates[$i] = $object->hotspot_coordinates;
            $this->hotspot_type[$i] = $object->hotspot_type;
            $this->destination[$i] = $object->destination;
            $this->autoId[$i] = $object->id_auto;
            $this->iid[$i] = $object->iid;
            $i++;
        }

        if ($questionType['type'] == UNIQUE_ANSWER_NO_OPTION && !empty($doubt_data)) {
            $this->answer[$i] = $doubt_data->answer;
            $this->correct[$i] = $doubt_data->correct;
            $this->comment[$i] = $doubt_data->comment;
            $this->weighting[$i] = $doubt_data->ponderation;
            $this->position[$i] = $doubt_data->position;
            $this->hotspot_coordinates[$i] = isset($object->hotspot_coordinates) ? $object->hotspot_coordinates : 0;
            $this->hotspot_type[$i] = isset($object->hotspot_type) ? $object->hotspot_type : 0;
            $this->destination[$i] = $doubt_data->destination;
            $this->autoId[$i] = $doubt_data->id_auto;
            $this->iid[$i] = $doubt_data->iid;
            $i++;
        }
        $this->nbrAnswers = $i - 1;

        return true;
    }

    /**
     * returns the autoincrement id.
     *
     * @author Juan Carlos Ra�a
     *
     * @return int Answer num
     */
    public function selectAutoId($id)
    {
        return isset($this->autoId[$id]) ? $this->autoId[$id] : 0;
    }

    /**
     * Returns the unique ID (iid field).
     *
     * @return int Answer ID
     */
    public function selectId($id)
    {
        return isset($this->iid[$id]) ? $this->iid[$id] : 0;
    }

    /**
     * returns the number of answers in this question.
     *
     * @author Olivier Brouckaert
     *
     * @return int - number of answers
     */
    public function selectNbrAnswers()
    {
        return $this->nbrAnswers;
    }

    /**
     * returns the question ID which the answers belong to.
     *
     * @author Olivier Brouckaert
     *
     * @return int - the question ID
     */
    public function selectQuestionId()
    {
        return $this->questionId;
    }

    /**
     * returns the question ID of the destination question.
     *
     * @author Julio Montoya
     *
     * @param int $id
     *
     * @return int - the question ID
     */
    public function selectDestination($id)
    {
        return isset($this->destination[$id]) ? $this->destination[$id] : null;
    }

    /**
     * returns the answer title.
     *
     * @author Olivier Brouckaert
     *
     * @param - integer $id - answer ID
     *
     * @return string - answer title
     */
    public function selectAnswer($id)
    {
        return isset($this->answer[$id]) ? $this->answer[$id] : null;
    }

    /**
     * return array answer by id else return a bool.
     *
     * @param int $auto_id
     *
     * @return array
     *
     * @todo Replace method by iid search
     */
    public function selectAnswerByAutoId($auto_id)
    {
        $table = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $auto_id = (int) $auto_id;
        $sql = "SELECT iid, answer FROM $table
                WHERE c_id = {$this->course_id} AND id_auto = $auto_id";
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0) {
            return Database::fetch_array($rs, 'ASSOC');
        }

        return false;
    }

    /**
     * return array answer by iid. Else return a bool.
     *
     * @param int $iid
     *
     * @return array
     */
    public function selectAnswerById($id)
    {
        if (empty($id)) {
            return false;
        }
        $table = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $id = (int) $id;
        $sql = "SELECT iid, answer, id_auto FROM $table
                WHERE iid = $id";
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0) {
            return Database::fetch_array($rs, 'ASSOC');
        }

        return false;
    }

    /**
     * returns the answer title from an answer's position.
     *
     * @author Yannick Warnier
     *
     * @param - integer $pos - answer ID
     *
     * @return bool - answer title
     */
    public function selectAnswerIdByPosition($pos)
    {
        foreach ($this->position as $k => $v) {
            if ($v != $pos) {
                continue;
            }

            return $k;
        }

        return false;
    }

    /**
     * Returns a list of answers.
     *
     * @author Yannick Warnier <ywarnier@beeznest.org>
     *
     * @param bool $decode
     *
     * @return array List of answers where each answer is an array
     *               of (id, answer, comment, grade) and grade=weighting
     */
    public function getAnswersList($decode = false)
    {
        $list = [];
        for ($i = 1; $i <= $this->nbrAnswers; $i++) {
            if (!empty($this->answer[$i])) {
                //Avoid problems when parsing elements with accents
                if ($decode) {
                    $this->answer[$i] = api_html_entity_decode(
                        $this->answer[$i],
                        ENT_QUOTES,
                        api_get_system_encoding()
                    );
                    $this->comment[$i] = api_html_entity_decode(
                        $this->comment[$i],
                        ENT_QUOTES,
                        api_get_system_encoding()
                    );
                }

                $list[] = [
                    'iid' => $i,
                    'answer' => $this->answer[$i],
                    'comment' => $this->comment[$i],
                    'grade' => $this->weighting[$i],
                    'hotspot_coord' => $this->hotspot_coordinates[$i],
                    'hotspot_type' => $this->hotspot_type[$i],
                    'correct' => $this->correct[$i],
                    'destination' => $this->destination[$i],
                ];
            }
        }

        return $list;
    }

    /**
     * Returns a list of grades.
     *
     * @author Yannick Warnier <ywarnier@beeznest.org>
     *
     * @return array List of grades where grade=weighting (?)
     */
    public function getGradesList()
    {
        $list = [];
        for ($i = 0; $i < $this->nbrAnswers; $i++) {
            if (!empty($this->answer[$i])) {
                $list[$i] = $this->weighting[$i];
            }
        }

        return $list;
    }

    /**
     * Returns the question type.
     *
     * @author    Yannick Warnier <ywarnier@beeznest.org>
     *
     * @return int The type of the question this answer is bound to
     */
    public function getQuestionType()
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $sql = "SELECT type FROM $table
                WHERE iid = {$this->questionId}";
        $res = Database::query($sql);
        if (Database::num_rows($res) <= 0) {
            return null;
        }
        $row = Database::fetch_array($res);

        return (int) $row['type'];
    }

    /**
     * tells if answer is correct or not.
     *
     * @author Olivier Brouckaert
     *
     * @param - integer $id - answer ID
     *
     * @return int - 0 if bad answer, not 0 if good answer
     */
    public function isCorrect($id)
    {
        return isset($this->correct[$id]) ? $this->correct[$id] : null;
    }

    /**
     * returns answer comment.
     *
     * @author Olivier Brouckaert
     *
     * @param - integer $id - answer ID
     *
     * @return string - answer comment
     */
    public function selectComment($id)
    {
        return isset($this->comment[$id]) ? $this->comment[$id] : null;
    }

    /**
     * returns answer weighting.
     *
     * @author Olivier Brouckaert
     *
     * @param - integer $id - answer ID
     *
     * @return int - answer weighting
     */
    public function selectWeighting($id)
    {
        return isset($this->weighting[$id]) ? $this->weighting[$id] : null;
    }

    /**
     * returns answer position.
     *
     * @author Olivier Brouckaert
     *
     * @param - integer $id - answer ID
     *
     * @return int - answer position
     */
    public function selectPosition($id)
    {
        return isset($this->position[$id]) ? $this->position[$id] : null;
    }

    /**
     * returns answer hotspot coordinates.
     *
     * @author Olivier Brouckaert
     *
     * @param int $id Answer ID
     *
     * @return int Answer position
     */
    public function selectHotspotCoordinates($id)
    {
        return isset($this->hotspot_coordinates[$id]) ? $this->hotspot_coordinates[$id] : null;
    }

    /**
     * returns answer hotspot type.
     *
     * @author Toon Keppens
     *
     * @param int $id Answer ID
     *
     * @return int Answer position
     */
    public function selectHotspotType($id)
    {
        return isset($this->hotspot_type[$id]) ? $this->hotspot_type[$id] : null;
    }

    /**
     * Creates a new answer.
     *
     * @author Olivier Brouckaert
     *
     * @param string $answer                  answer title
     * @param int    $correct                 0 if bad answer, not 0 if good answer
     * @param string $comment                 answer comment
     * @param int    $weighting               answer weighting
     * @param int    $position                answer position
     * @param array  $new_hotspot_coordinates Coordinates for hotspot exercises (optional)
     * @param int    $new_hotspot_type        Type for hotspot exercises (optional)
     * @param string $destination
     */
    public function createAnswer(
        $answer,
        $correct,
        $comment,
        $weighting,
        $position,
        $new_hotspot_coordinates = null,
        $new_hotspot_type = null,
        $destination = ''
    ) {
        $this->new_nbrAnswers++;
        $id = $this->new_nbrAnswers;
        $this->new_answer[$id] = $answer;
        $this->new_correct[$id] = $correct;
        $this->new_comment[$id] = $comment;
        $this->new_weighting[$id] = $weighting;
        $this->new_position[$id] = $position;
        $this->new_hotspot_coordinates[$id] = $new_hotspot_coordinates;
        $this->new_hotspot_type[$id] = $new_hotspot_type;
        $this->new_destination[$id] = $destination;
    }

    /**
     * Updates an answer.
     *
     * @author Toon Keppens
     *
     * @param int    $iid
     * @param string $answer
     * @param string $comment
     * @param string $correct
     * @param string $weighting
     * @param string $position
     * @param string $destination
     * @param string $hotSpotCoordinates
     * @param string $hotSpotType
     *
     * @return CQuizAnswer
     */
    public function updateAnswers(
        $iid,
        $answer,
        $comment,
        $correct,
        $weighting,
        $position,
        $destination,
        $hotSpotCoordinates,
        $hotSpotType
    ) {
        $em = Database::getManager();

        /** @var CQuizAnswer $quizAnswer */
        $quizAnswer = $em->find('ChamiloCourseBundle:CQuizAnswer', $iid);
        if ($quizAnswer) {
            $quizAnswer
                ->setAnswer($answer)
                ->setComment($comment)
                ->setCorrect($correct)
                ->setPonderation($weighting)
                ->setPosition($position)
                ->setDestination($destination)
                ->setHotspotCoordinates($hotSpotCoordinates)
                ->setHotspotType($hotSpotType);

            $em->merge($quizAnswer);
            $em->flush();

            return $quizAnswer;
        }

        return false;
    }

    /**
     * Records answers into the data base.
     *
     * @author Olivier Brouckaert
     */
    public function save()
    {
        $answerTable = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $em = Database::getManager();
        $questionId = (int) $this->questionId;

        $courseId = $this->course['real_id'];
        $answerList = [];

        for ($i = 1; $i <= $this->new_nbrAnswers; $i++) {
            $answer = $this->new_answer[$i];
            $correct = isset($this->new_correct[$i]) ? $this->new_correct[$i] : '';
            $comment = isset($this->new_comment[$i]) ? $this->new_comment[$i] : '';
            $weighting = isset($this->new_weighting[$i]) ? $this->new_weighting[$i] : '';
            $position = isset($this->new_position[$i]) ? $this->new_position[$i] : '';
            $hotspot_coordinates = isset($this->new_hotspot_coordinates[$i]) ? $this->new_hotspot_coordinates[$i] : '';
            $hotspot_type = isset($this->new_hotspot_type[$i]) ? $this->new_hotspot_type[$i] : '';
            $destination = isset($this->new_destination[$i]) ? $this->new_destination[$i] : '';
            $autoId = $this->selectAutoId($i);
            $iid = isset($this->iid[$i]) ? $this->iid[$i] : 0;

            if (!isset($this->position[$i])) {
                $quizAnswer = new CQuizAnswer();
                $quizAnswer
                    ->setIdAuto($autoId)
                    ->setCId($courseId)
                    ->setQuestionId($questionId)
                    ->setAnswer($answer)
                    ->setCorrect($correct)
                    ->setComment($comment)
                    ->setPonderation($weighting)
                    ->setPosition($position)
                    ->setHotspotCoordinates($hotspot_coordinates)
                    ->setHotspotType($hotspot_type)
                    ->setDestination($destination);

                $em->persist($quizAnswer);
                $em->flush();

                $iid = $quizAnswer->getId();

                if ($iid) {
                    $quizAnswer
                        ->setId($iid)
                        ->setIdAuto($iid);

                    $questionType = $this->getQuestionType();
                    if (in_array(
                        $questionType,
                        [MATCHING, MATCHING_DRAGGABLE, MATCHING_COMBINATION, MATCHING_DRAGGABLE_COMBINATION]
                    )) {
                        $answer = new Answer($this->questionId, $courseId, $this->exercise, false);
                        $answer->read();
                        $correctAnswerId = $answer->selectAnswerIdByPosition($correct);

                        // Continue to avoid matching question bug if $correctAnswerId returns false
                        // See : https://support.chamilo.org/issues/8334
                        if ($questionType == MATCHING && !$correctAnswerId) {
                            $em->merge($quizAnswer);
                            $em->flush();
                            continue;
                        }
                        $correctAnswerAutoId = $answer->selectAutoId($correct);
                        $quizAnswer->setCorrect($correctAnswerAutoId ? $correctAnswerAutoId : 0);
                    }

                    $em->merge($quizAnswer);
                    $em->flush();
                }
            } else {
                // https://support.chamilo.org/issues/6558
                // function updateAnswers already escape_string, error if we do it twice.
                // Feed function updateAnswers with none escaped strings
                $this->updateAnswers(
                    $iid,
                    $this->new_answer[$i],
                    $this->new_comment[$i],
                    $this->new_correct[$i],
                    $this->new_weighting[$i],
                    $this->new_position[$i],
                    $this->new_destination[$i],
                    $this->new_hotspot_coordinates[$i],
                    $this->new_hotspot_type[$i]
                );
            }

            $answerList[$i] = $iid;
        }

        $questionType = $this->getQuestionType();

        switch ($questionType) {
            case MATCHING_DRAGGABLE:
                foreach ($this->new_correct as $value => $status) {
                    if (!empty($status)) {
                        if (isset($answerList[$status])) {
                            $correct = $answerList[$status];
                        } else {
                            $correct = $status;
                        }
                        $myAutoId = $answerList[$value];
                        $sql = "UPDATE $answerTable
                            SET correct = '$correct'
                            WHERE
                                id_auto = $myAutoId
                            ";
                        Database::query($sql);
                    }
                }
                break;
            case DRAGGABLE:
                foreach ($this->new_correct as $value => $status) {
                    if (!empty($status)) {
                        $correct = $answerList[$status];
                        $myAutoId = $answerList[$value];
                        $sql = "UPDATE $answerTable
                            SET correct = '$correct'
                            WHERE
                                id_auto = $myAutoId
                            ";
                        Database::query($sql);
                    }
                }
                break;
        }

        if (count($this->position) > $this->new_nbrAnswers) {
            $i = $this->new_nbrAnswers + 1;
            while (isset($this->position[$i])) {
                $position = $this->position[$i];
                $sql = "DELETE FROM $answerTable
                        WHERE
                            c_id = {$this->course_id} AND
                            question_id = {$questionId} AND
                            position ='$position'";
                Database::query($sql);
                $i++;
            }
        }

        // moves $new_* arrays
        $this->answer = $this->new_answer;
        $this->correct = $this->new_correct;
        $this->comment = $this->new_comment;
        $this->weighting = $this->new_weighting;
        $this->position = $this->new_position;
        $this->hotspot_coordinates = $this->new_hotspot_coordinates;
        $this->hotspot_type = $this->new_hotspot_type;
        $this->nbrAnswers = $this->new_nbrAnswers;
        $this->destination = $this->new_destination;

        $this->cancel();
    }

    /**
     * Duplicates answers by copying them into another question.
     *
     * @author Olivier Brouckaert
     *
     * @param Question $newQuestion
     * @param array    $course_info destination course info (result of the function api_get_course_info() )
     */
    public function duplicate($newQuestion, $course_info = null)
    {
        $newQuestionId = $newQuestion->iid;

        if (empty($course_info)) {
            $course_info = $this->course;
        }

        $fixed_list = [];
        $tableAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);

        if (self::getQuestionType() == MULTIPLE_ANSWER_TRUE_FALSE ||
            self::getQuestionType() == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY
        ) {
            // Selecting origin options
            $origin_options = Question::readQuestionOption(
                $this->selectQuestionId(),
                $this->course['real_id']
            );

            if (!empty($origin_options)) {
                foreach ($origin_options as $item) {
                    $new_option_list[] = $item['iid'];
                }
            }

            $destination_options = Question::readQuestionOption(
                $newQuestionId,
                $course_info['real_id']
            );
            $i = 0;
            if (!empty($destination_options)) {
                foreach ($destination_options as $item) {
                    $fixed_list[$new_option_list[$i]] = $item['iid'];
                    $i++;
                }
            }
        }

        // if at least one answer
        if ($this->nbrAnswers) {
            // inserts new answers into data base
            $courseId = $course_info['real_id'];
            $correctAnswers = [];
            $onlyAnswers = [];
            $allAnswers = [];

            $em = Database::getManager();

            if (in_array($newQuestion->type, [MATCHING, MATCHING_DRAGGABLE])) {
                $temp = [];
                for ($i = 1; $i <= $this->nbrAnswers; $i++) {
                    $answer = [
                        'iid' => $this->iid[$i],
                        'answer' => $this->answer[$i],
                        'correct' => $this->correct[$i],
                        'comment' => $this->comment[$i],
                        'weighting' => $this->weighting[$i],
                        'ponderation' => $this->weighting[$i],
                        'position' => $this->position[$i],
                        'hotspot_coordinates' => $this->hotspot_coordinates[$i],
                        'hotspot_type' => $this->hotspot_type[$i],
                        'destination' => $this->destination[$i],
                    ];
                    $temp[$answer['position']] = $answer;
                    $allAnswers[$this->iid[$i]] = $this->answer[$i];
                }

                foreach ($temp as $answer) {
                    if ($this->course['id'] != $course_info['id']) {
                        // check resources inside html from ckeditor tool and copy correct urls into recipient course
                        $answer['answer'] = DocumentManager::replaceUrlWithNewCourseCode(
                            $answer['answer'],
                            $this->course['id'],
                            $course_info['id']
                        );

                        $answer['comment'] = DocumentManager::replaceUrlWithNewCourseCode(
                            $answer['comment'],
                            $this->course['id'],
                            $course_info['id']
                        );
                    }

                    $quizAnswer = new CQuizAnswer();
                    $quizAnswer
                        ->setCId($courseId)
                        ->setQuestionId($newQuestionId)
                        ->setAnswer($answer['answer'])
                        ->setCorrect($answer['correct'])
                        ->setComment($answer['comment'])
                        ->setPonderation($answer['ponderation'])
                        ->setPosition($answer['position'])
                        ->setHotspotCoordinates($answer['hotspot_coordinates'])
                        ->setHotspotType($answer['hotspot_type'])
                        ->setIdAuto(0);

                    $em->persist($quizAnswer);
                    $em->flush();

                    $answerId = $quizAnswer->getId();

                    if ($answerId) {
                        $quizAnswer
                            ->setId($answerId)
                            ->setIdAuto($answerId);

                        $em->merge($quizAnswer);
                        $em->flush();

                        $correctAnswers[$answerId] = $answer['correct'];
                        $onlyAnswers[$answerId] = $answer['answer'];
                    }
                }
            } else {
                for ($i = 1; $i <= $this->nbrAnswers; $i++) {
                    if ($this->course['id'] != $course_info['id']) {
                        $this->answer[$i] = DocumentManager::replaceUrlWithNewCourseCode(
                            $this->answer[$i],
                            $this->course['id'],
                            $course_info['id']
                        );
                        $this->comment[$i] = DocumentManager::replaceUrlWithNewCourseCode(
                            $this->comment[$i],
                            $this->course['id'],
                            $course_info['id']
                        );
                    }

                    $correct = $this->correct[$i];
                    if ($newQuestion->type == MULTIPLE_ANSWER_TRUE_FALSE ||
                        $newQuestion->type == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY
                    ) {
                        $correct = $fixed_list[intval($correct)];
                    }

                    $quizAnswer = new CQuizAnswer();
                    $quizAnswer
                        ->setCId($courseId)
                        ->setQuestionId($newQuestionId)
                        ->setAnswer($this->answer[$i])
                        ->setCorrect($correct)
                        ->setComment($this->comment[$i])
                        ->setPonderation($this->weighting[$i])
                        ->setPosition($this->position[$i])
                        ->setHotspotCoordinates($this->hotspot_coordinates[$i])
                        ->setHotspotType($this->hotspot_type[$i])
                        ->setDestination($this->destination[$i]);

                    $em->persist($quizAnswer);
                    $em->flush();

                    $answerId = $quizAnswer->getId();
                    $quizAnswer
                        ->setId($answerId)
                        ->setIdAuto($answerId);

                    $em->merge($quizAnswer);
                    $em->flush();

                    $correctAnswers[$answerId] = $correct;
                    $onlyAnswers[$answerId] = $this->answer[$i];
                    $allAnswers[$this->iid[$i]] = $this->answer[$i];
                }
            }

            // Fix correct answers
            if (in_array($newQuestion->type, [DRAGGABLE, MATCHING, MATCHING_DRAGGABLE])) {
                $onlyAnswersFlip = array_flip($onlyAnswers);
                foreach ($correctAnswers as $answer_id => $correct_answer) {
                    $params = [];
                    if (isset($allAnswers[$correct_answer]) &&
                        isset($onlyAnswersFlip[$allAnswers[$correct_answer]])
                    ) {
                        $params['correct'] = $onlyAnswersFlip[$allAnswers[$correct_answer]];
                        Database::update(
                            $tableAnswer,
                            $params,
                            [
                                'iid = ? ' => [
                                    $answer_id,
                                ],
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Get the necessary JavaScript for some answers.
     *
     * @return string
     */
    public function getJs()
    {
        return "<script>
                $(window).on('load', function() {
                    jsPlumb.ready(function() {
                        if ($('#drag{$this->questionId}_question').length > 0) {
                            MatchingDraggable.init('{$this->questionId}');
                        }
                    });
                });
            </script>";
    }

    /**
     * Check if a answer is correct by an answer auto id.
     *
     * @param int $needle The answer auto id
     *
     * @return bool
     */
    public function isCorrectByAutoId($needle)
    {
        $key = 0;
        if (is_array($this->autoId)) {
            foreach ($this->autoId as $autoIdKey => $autoId) {
                if ($autoId == $needle) {
                    $key = $autoIdKey;
                }
            }
        }

        if (!$key) {
            return false;
        }

        return $this->isCorrect($key) ? true : false;
    }
}
