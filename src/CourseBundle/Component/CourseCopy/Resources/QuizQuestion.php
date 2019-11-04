<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;

/**
 * Exercises questions backup script
 * Class QuizQuestion.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
class QuizQuestion extends Resource
{
    /**
     * The question.
     */
    public $question;

    /**
     * The description.
     */
    public $description;

    /**
     * Ponderation.
     */
    public $ponderation;

    /**
     * Type.
     */
    public $quiz_type;

    /**
     * Position.
     */
    public $position;

    /**
     * Level.
     */
    public $level;

    /**
     * Answers.
     */
    public $answers;

    /**
     * Picture.
     */
    public $picture;
    public $extra;

    /**
     * @var int the question category if any, 0 by default
     */
    public $question_category;

    /**
     * QuizQuestion constructor.
     *
     * @param int    $id
     * @param string $question
     * @param string $description
     * @param int    $ponderation
     * @param        $type
     * @param        $position
     * @param string $picture
     * @param        $level
     * @param        $extra
     * @param int    $question_category
     */
    public function __construct(
        $id,
        $question,
        $description,
        $ponderation,
        $type,
        $position,
        $picture,
        $level,
        $extra,
        $question_category = 0
    ) {
        parent::__construct($id, RESOURCE_QUIZQUESTION);
        $this->question = $question;
        $this->description = $description;
        $this->ponderation = $ponderation;
        $this->quiz_type = $type;
        $this->position = $position;
        $this->level = $level;
        $this->answers = [];
        $this->extra = $extra;
        $this->question_category = $question_category;
        $this->picture = $picture;
    }

    public function addPicture(CourseBuilder $courseBuilder)
    {
        if (!empty($this->picture)) {
            $courseInfo = $courseBuilder->course->info;
            $courseId = $courseInfo['real_id'];
            $courseCode = $courseInfo['code'];
            $questionId = $this->source_id;
            $question = \Question::read($questionId, $courseInfo);
            $pictureId = $question->getPictureId();
            // Add the picture document in the builder
            if (!empty($pictureId)) {
                $itemsToAdd[] = $pictureId;
                // Add the "images" folder needed for correct restore
                $documentData = \DocumentManager::get_document_data_by_id($pictureId, $courseCode, true);
                if ($documentData) {
                    if (isset($documentData['parents'])) {
                        foreach ($documentData['parents'] as $parent) {
                            $itemsToAdd[] = $parent['id'];
                        }
                    }
                }

                // Add the picture
                $courseBuilder->build_documents(api_get_session_id(), $courseId, false, $itemsToAdd);
            }
        }
    }

    /**
     * Add an answer to this QuizQuestion.
     *
     * @param int    $answer_id
     * @param string $answer_text
     * @param string $correct
     * @param string $comment
     * @param string $ponderation
     * @param string $position
     * @param string $hotspot_coordinates
     * @param string $hotspot_type
     */
    public function add_answer(
        $answer_id,
        $answer_text,
        $correct,
        $comment,
        $ponderation,
        $position,
        $hotspot_coordinates,
        $hotspot_type
    ) {
        $answer = [];
        $answer['id'] = $answer_id;
        $answer['answer'] = $answer_text;
        $answer['correct'] = $correct;
        $answer['comment'] = $comment;
        $answer['ponderation'] = $ponderation;
        $answer['position'] = $position;
        $answer['hotspot_coordinates'] = $hotspot_coordinates;
        $answer['hotspot_type'] = $hotspot_type;
        $this->answers[] = $answer;
    }

    /**
     * @param QuizQuestionOption $option
     */
    public function add_option($option)
    {
        $this->question_options[$option->obj->id] = $option;
    }

    /**
     * Show this question.
     */
    public function show()
    {
        parent::show();
        echo $this->question;
    }
}
