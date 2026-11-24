<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\ORM\QueryBuilder;

/**
 * Class Answer
 * Allows to instantiate an object of type Answer
 * 5 arrays are created to receive the attributes of each answer belonging to a specified question.
 *
 * @author Olivier Brouckaert
 */
class Answer
{
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

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }

        $this->course = api_get_course_info_by_id($course_id);
        if (empty($exercise)) {
            // fills arrays
            $objExercise = new Exercise($course_id);
            $exerciseId = $_REQUEST['exerciseId'] ?? null;
            $objExercise->read($exerciseId, false);
        } else {
            $objExercise = $exercise;
        }
        $this->exercise = $objExercise;

        if ($readAnswer) {
            if ('1' == $objExercise->random_answers && CALCULATED_ANSWER != $this->getQuestionType()) {
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
        $rows = $this->answerRowsQueryBuilder()
            ->where('IDENTITY(a.question) = :questionId')
            ->orderBy('a.position', 'ASC')
            ->setParameter('questionId', (int) $this->questionId)
            ->getQuery()
            ->getArrayResult();
        $i = 1;

        // while a record is found
        foreach ($rows as $row) {
            $object = (object) $row;
            $this->id[$i] = $object->iid;
            $this->autoId[$i] = $object->iid;
            $this->iid[$i] = $object->iid;
            $this->answer[$i] = $object->answer;
            $this->correct[$i] = $object->correct;
            $this->comment[$i] = $object->comment;
            $this->weighting[$i] = $object->ponderation;
            $this->position[$i] = $object->position;
            $this->hotspot_coordinates[$i] = $object->hotspot_coordinates;
            $this->hotspot_type[$i] = $object->hotspot_type;
            $i++;
        }
        $this->nbrAnswers = $i - 1;
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
        if (empty($field)) {
            $field = 'position';
        }

        if ('ASC' != $order && 'DESC' != $order) {
            $order = 'ASC';
        }

        $questionId = (int) $this->questionId;

        $typeRows = Container::getEntityManager()->createQueryBuilder()
            ->select('q.type AS type')
            ->from(CQuizQuestion::class, 'q')
            ->where('q.iid = :questionId')
            ->setParameter('questionId', $questionId)
            ->getQuery()
            ->getArrayResult();
        $questionType = $typeRows[0] ?? [];

        if (DRAGGABLE == $questionType['type']) {
            // Random is done by submit.js.tpl
            $this->read();

            return true;
        }

        if ('rand()' === strtolower(trim((string) $field))) {
            // TODO(db-neutral): ORDER BY rand() has no DQL equivalent
            $TBL_ANSWER = Database::get_course_table(TABLE_QUIZ_ANSWER);
            $sql = "SELECT
                        answer,
                        correct,
                        comment,
                        ponderation,
                        position,
                        hotspot_coordinates,
                        hotspot_type,
                        iid
                    FROM $TBL_ANSWER
                    WHERE
                        question_id='".$questionId."'
                    ORDER BY rand() ";
            $result = Database::query($sql);
            $objects = [];
            while ($object = Database::fetch_object($result)) {
                $objects[] = $object;
            }
        } else {
            // Only these columns can be used to order the answers: anything else falls back to the position.
            $orderFields = [
                'answer' => 'a.answer',
                'correct' => 'a.correct',
                'comment' => 'a.comment',
                'ponderation' => 'a.ponderation',
                'position' => 'a.position',
                'hotspot_coordinates' => 'a.hotspotCoordinates',
                'hotspot_type' => 'a.hotspotType',
                'iid' => 'a.iid',
            ];
            $rows = $this->answerRowsQueryBuilder()
                ->where('IDENTITY(a.question) = :questionId')
                ->orderBy($orderFields[strtolower(trim((string) $field))] ?? 'a.position', $order)
                ->setParameter('questionId', $questionId)
                ->getQuery()
                ->getArrayResult();
            $objects = array_map(static fn (array $row): stdClass => (object) $row, $rows);
        }

        $i = 1;
        // while a record is found
        $doubt_data = null;
        foreach ($objects as $object) {
            if (UNIQUE_ANSWER_NO_OPTION == $questionType['type'] && 666 == $object->position) {
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
            $this->autoId[$i] = $object->iid;
            $this->iid[$i] = $object->iid;
            $i++;
        }

        if (UNIQUE_ANSWER_NO_OPTION == $questionType['type'] && !empty($doubt_data)) {
            $this->answer[$i] = $doubt_data->answer;
            $this->correct[$i] = $doubt_data->correct;
            $this->comment[$i] = $doubt_data->comment;
            $this->weighting[$i] = $doubt_data->ponderation;
            $this->position[$i] = $doubt_data->position;
            $this->hotspot_coordinates[$i] = isset($doubt_data->hotspot_coordinates) ? $doubt_data->hotspot_coordinates : 0;
            $this->hotspot_type[$i] = isset($doubt_data->hotspot_type) ? $doubt_data->hotspot_type : 0;
            $this->destination[$i] = isset($doubt_data->destination) ? $doubt_data->destination : 0;
            $this->autoId[$i] = $doubt_data->iid;
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
     * @return int - answer num
     */
    public function selectAutoId($id)
    {
        return isset($this->iid[$id]) ? (int) $this->iid[$id] : 0;
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
     */
    public function selectAnswerByAutoId($auto_id)
    {
        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('a.iid AS iid', 'a.answer AS answer')
            ->from(CQuizAnswer::class, 'a')
            ->where('a.iid = :iid')
            ->setParameter('iid', (int) $auto_id)
            ->getQuery()
            ->getArrayResult();

        return $rows[0] ?? false;
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
                    'id' => $i,
                    'answer' => $this->answer[$i],
                    'comment' => $this->comment[$i],
                    'grade' => $this->weighting[$i],
                    'hotspot_coord' => $this->hotspot_coordinates[$i],
                    'hotspot_type' => $this->hotspot_type[$i],
                    'correct' => $this->correct[$i],
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
        for ($i = 1; $i <= $this->nbrAnswers; $i++) {
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
     * @todo remove this function use CQuizQuestion
     *
     * @return int The type of the question this answer is bound to
     */
    public function getQuestionType()
    {
        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('q.type AS type')
            ->from(CQuizQuestion::class, 'q')
            ->where('q.iid = :questionId')
            ->setParameter('questionId', (int) $this->questionId)
            ->getQuery()
            ->getArrayResult();
        if ([] === $rows) {
            return null;
        }

        return (int) $rows[0]['type'];
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
        $quizAnswer = $em->find(CQuizAnswer::class, $iid);
        if ($quizAnswer) {
            $quizAnswer
                ->setAnswer((string) $answer)
                ->setComment((string) $comment)
                ->setCorrect((int) $correct)
                ->setPonderation($weighting)
                ->setPosition((int) $position)
                ->setHotspotCoordinates($hotSpotCoordinates)
                ->setHotspotType($hotSpotType)
            ;

            $em->persist($quizAnswer);
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
        $em = Database::getManager();
        $questionId = (int) $this->questionId;

        $courseId = $this->course['real_id'];
        $answerList = [];
        $questionRepo = Container::getQuestionRepository();
        /** @var CQuizQuestion $question */
        $question = $questionRepo->find($questionId);
        $questionType = $this->getQuestionType();

        for ($i = 1; $i <= $this->new_nbrAnswers; $i++) {
            $answer = (string) ($this->new_answer[$i] ?? '');
            $correct = isset($this->new_correct[$i]) ? (int) $this->new_correct[$i] : null;
            $comment = $this->new_comment[$i] ?? null;
            $weighting = isset($this->new_weighting[$i]) ? (float) $this->new_weighting[$i] : null;
            $position = isset($this->new_position[$i]) ? (int) $this->new_position[$i] : (int) $i;
            $hotspot_coordinates = $this->new_hotspot_coordinates[$i] ?? null;
            $hotspot_type = $this->new_hotspot_type[$i] ?? null;
            $iid = isset($this->iid[$i]) ? (int) $this->iid[$i] : 0;

            if (!isset($this->position[$i])) {
                $quizAnswer = new CQuizAnswer();
                $quizAnswer
                    ->setQuestion($question)
                    ->setAnswer($answer)
                    ->setCorrect($correct)
                    ->setComment($comment)
                    ->setPonderation(!is_null($weighting) ? $weighting : 0.0)
                    ->setPosition($position)
                    ->setHotspotCoordinates($hotspot_coordinates)
                    ->setHotspotType($hotspot_type);

                $em->persist($quizAnswer);
                $em->flush();

                $iid = (int) $quizAnswer->getIid();

                if ($iid) {
                    if (in_array(
                        $questionType,
                        [MATCHING, MATCHING_COMBINATION, MATCHING_DRAGGABLE, MATCHING_DRAGGABLE_COMBINATION],
                        true
                    )) {
                        $answerObj = new self($this->questionId, $courseId, $this->exercise, false);
                        $answerObj->read();
                        $correctAnswerId = $answerObj->selectAnswerIdByPosition($correct);

                        if (in_array($questionType, [MATCHING, MATCHING_COMBINATION], true) && !$correctAnswerId) {
                            continue;
                        }
                        $correctAnswerAutoId = $answerObj->selectAutoId($correct);
                        $quizAnswer->setCorrect($correctAnswerAutoId ?: 0);
                    }

                    $em->persist($quizAnswer);
                    $em->flush();
                }
            } else {
                // Update existing answer (note: updateAnswers will cast position to int now)
                $this->updateAnswers(
                    $iid,
                    $this->new_answer[$i],
                    $this->new_comment[$i],
                    $this->new_correct[$i],
                    $this->new_weighting[$i],
                    $this->new_position[$i],
                    null,
                    $this->new_hotspot_coordinates[$i],
                    $this->new_hotspot_type[$i]
                );
            }

            $answerList[$i] = $iid;
        }

        // Post-processing for draggable/matching types
        switch ($questionType) {
            case MATCHING_DRAGGABLE:
                foreach ($this->new_correct as $value => $status) {
                    if (!empty($status)) {
                        $correct = $answerList[$status] ?? $status;
                        $myAutoId = $answerList[$value];
                        $this->updateAnswerCorrect((int) $myAutoId, $correct);
                    }
                }
                break;

            case MATCHING_DRAGGABLE_COMBINATION:
                foreach ($this->new_correct as $value => $status) {
                    if (!empty($status)) {
                        $correct = $answerList[$status] ?? $status;
                        $myAutoId = $answerList[$value];
                        $this->updateAnswerCorrect((int) $myAutoId, $correct);
                    }
                }
                break;

            case DRAGGABLE:
                foreach ($this->new_correct as $value => $status) {
                    if (!empty($status)) {
                        $correct = $answerList[$status];
                        $myAutoId = $answerList[$value];
                        $this->updateAnswerCorrect((int) $myAutoId, $correct);
                    }
                }
                break;
        }

        // Delete trailing answers when we saved fewer than previously existed
        if (count($this->position) > $this->new_nbrAnswers) {
            $i = $this->new_nbrAnswers + 1;
            while (isset($this->position[$i])) {
                $toDeletePos = (int) $this->position[$i];
                Container::getEntityManager()->createQueryBuilder()
                    ->delete(CQuizAnswer::class, 'a')
                    ->where('IDENTITY(a.question) = :questionId')
                    ->andWhere('a.position = :position')
                    ->setParameter('questionId', $questionId)
                    ->setParameter('position', $toDeletePos)
                    ->getQuery()
                    ->execute();
                $i++;
            }
        }

        // Move new_* arrays into main arrays
        $this->answer               = $this->new_answer;
        $this->correct              = $this->new_correct;
        $this->comment              = $this->new_comment;
        $this->weighting            = $this->new_weighting;
        $this->position             = $this->new_position;
        $this->hotspot_coordinates  = $this->new_hotspot_coordinates;
        $this->hotspot_type         = $this->new_hotspot_type;
        $this->nbrAnswers           = $this->new_nbrAnswers;

        $this->cancel();
    }

    /**
     * Duplicates answers by copying them into another question.
     *
     * @author Olivier Brouckaert
     *
     * @param null $courseInfo destination course info (result of the function api_get_course_info() )
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function duplicate(Question $newQuestion, $courseInfo = null)
    {
        $newQuestionId = $newQuestion->id;

        $em = Database::getManager();
        $questionRepo = Container::getQuestionRepository();
        $answerRepo = $em->getRepository(CQuizAnswer::class);
        $question = $questionRepo->find($newQuestionId);

        if (empty($courseInfo)) {
            $courseInfo = $this->course;
        }

        $fixedList = [];

        if (MULTIPLE_ANSWER_TRUE_FALSE === self::getQuestionType() ||
            MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY === self::getQuestionType()
        ) {
            // Selecting origin options
            $originOptions = Question::readQuestionOption($this->selectQuestionId());

            if (!empty($originOptions)) {
                foreach ($originOptions as $item) {
                    $newOptionList[] = $item['iid'];
                }
            }

            $destinationOptions = Question::readQuestionOption($newQuestionId);
            $i = 0;
            if (!empty($destinationOptions)) {
                foreach ($destinationOptions as $item) {
                    $fixedList[$newOptionList[$i]] = $item['iid'];
                    $i++;
                }
            }
        }

        // if at least one answer
        if (empty($this->nbrAnswers)) {
            return;
        }
        // inserts new answers into data base
        $correctAnswers = [];
        $onlyAnswers = [];
        $allAnswers = [];

        if (in_array($newQuestion->type, [MATCHING, MATCHING_COMBINATION, MATCHING_DRAGGABLE, MATCHING_DRAGGABLE_COMBINATION], true)) {
            $temp = [];
            for ($i = 1; $i <= $this->nbrAnswers; $i++) {
                $answer = [
                    'id' => $this->id[$i],
                    'answer' => $this->answer[$i],
                    'correct' => $this->correct[$i],
                    'comment' => $this->comment[$i],
                    'weighting' => $this->weighting[$i],
                    'ponderation' => $this->weighting[$i],
                    'position' => $this->position[$i],
                    'hotspot_coordinates' => $this->hotspot_coordinates[$i],
                    'hotspot_type' => $this->hotspot_type[$i],
                ];
                $temp[$answer['position']] = $answer;
                $allAnswers[$this->id[$i]] = $this->answer[$i];
            }

            foreach ($temp as $answer) {
                if (!empty($this->course['id']) && ($this->course['id'] != $courseInfo['id'])) {
                    // check resources inside html from ckeditor tool and copy correct urls into recipient course
                    $answer['answer'] = DocumentManager::replaceUrlWithNewCourseCode(
                        $answer['answer'],
                        $this->course['id'],
                        $courseInfo['id']
                    );

                    $answer['comment'] = DocumentManager::replaceUrlWithNewCourseCode(
                        $answer['comment'],
                        $this->course['id'],
                        $courseInfo['id']
                    );
                }

                $quizAnswer = new CQuizAnswer();
                $quizAnswer
                    ->setQuestion($question)
                    ->setAnswer($answer['answer'])
                    ->setCorrect($answer['correct'])
                    ->setComment($answer['comment'])
                    ->setPonderation($answer['ponderation'])
                    ->setPosition($answer['position'])
                    ->setHotspotCoordinates($answer['hotspot_coordinates'])
                    ->setHotspotType($answer['hotspot_type'])
                ;

                $em->persist($quizAnswer);
                $em->flush();

                $answerId = $quizAnswer->getIid();

                if ($answerId) {
                    $em->persist($quizAnswer);
                    $em->flush();

                    $correctAnswers[$answerId] = $answer['correct'];
                    $onlyAnswers[$answerId] = $answer['answer'];
                }
            }
        } else {
            for ($i = 1; $i <= $this->nbrAnswers; $i++) {
                if (!empty($this->course['id']) && ($this->course['id'] != $courseInfo['id'])) {
                    $this->answer[$i] = DocumentManager::replaceUrlWithNewCourseCode(
                        $this->answer[$i],
                        $this->course['id'],
                        $courseInfo['id']
                    );
                    $this->comment[$i] = DocumentManager::replaceUrlWithNewCourseCode(
                        $this->comment[$i],
                        $this->course['id'],
                        $courseInfo['id']
                    );
                }

                $correct = $this->correct[$i];
                if (MULTIPLE_ANSWER_TRUE_FALSE == $newQuestion->type ||
                    MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == $newQuestion->type
                ) {
                    $correct = $fixedList[(int) $correct];
                }

                $quizAnswer = new CQuizAnswer();
                $quizAnswer
                    ->setQuestion($question)
                    ->setAnswer($this->answer[$i])
                    ->setCorrect($correct)
                    ->setComment($this->comment[$i])
                    ->setPonderation($this->weighting[$i])
                    ->setPosition($this->position[$i])
                    ->setHotspotCoordinates($this->hotspot_coordinates[$i])
                    ->setHotspotType($this->hotspot_type[$i]);

                $em->persist($quizAnswer);
                $em->flush();

                $answerId = $quizAnswer->getIid();

                $correctAnswers[$answerId] = $correct;
                $onlyAnswers[$answerId] = $this->answer[$i];
                $allAnswers[$this->id[$i]] = $this->answer[$i];
            }
        }

        // Fix correct answers
        if (in_array($newQuestion->type, [DRAGGABLE, MATCHING, MATCHING_COMBINATION, MATCHING_DRAGGABLE, MATCHING_DRAGGABLE_COMBINATION], true)) {
            $onlyAnswersFlip = array_flip($onlyAnswers);
            foreach ($correctAnswers as $answerIdItem => $correctAnswer) {
                if (!isset($allAnswers[$correctAnswer]) || !isset($onlyAnswersFlip[$allAnswers[$correctAnswer]])) {
                    continue;
                }
                $answer = $answerRepo->find($answerIdItem);
                $answer->setCorrect((int) $onlyAnswersFlip[$allAnswers[$correctAnswer]]);

                $em->persist($answer);
                $em->flush();
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
                            setTimeout(function() {
                                // Initialize MatchingDraggable
                                MatchingDraggable.init('{$this->questionId}');
                            }, 1000);
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

    /**
     * Get answers already added to question.
     *
     * @return array
     */
    public function getAnswers()
    {
        return $this->answerRowsQueryBuilder()
            ->where('IDENTITY(a.question) = :questionId')
            ->orderBy('a.position', 'ASC')
            ->setParameter('questionId', (int) $this->questionId)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Every column of the answer table, aliased to the column names the legacy callers read.
     */
    private function answerRowsQueryBuilder(): QueryBuilder
    {
        return Container::getEntityManager()->createQueryBuilder()
            ->select(
                'a.iid AS iid',
                'IDENTITY(a.question) AS question_id',
                'a.answer AS answer',
                'a.correct AS correct',
                'a.comment AS comment',
                'a.ponderation AS ponderation',
                'a.position AS position',
                'a.hotspotCoordinates AS hotspot_coordinates',
                'a.hotspotType AS hotspot_type',
                'a.answerCode AS answer_code'
            )
            ->from(CQuizAnswer::class, 'a');
    }

    /**
     * Points the "correct" column of one answer at another answer (draggable and matching types).
     */
    private function updateAnswerCorrect(int $iid, $correct): void
    {
        Container::getEntityManager()->createQueryBuilder()
            ->update(CQuizAnswer::class, 'a')
            ->set('a.correct', ':correct')
            ->where('a.iid = :iid')
            ->setParameter('correct', (int) $correct)
            ->setParameter('iid', $iid)
            ->getQuery()
            ->execute();
    }

}
