<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use ChamiloSession as Session;

/**
 * @author Olivier Brouckaert & Julio Montoya & Hubert Borderiou 21-10-2011 (Question by category)
 *    QUESTION LIST ADMINISTRATION
 *
 *    This script allows to manage the question list
 *    It is included from the script admin.php
 */
$limitTeacherAccess = ('true' === api_get_setting('exercise.limit_exercise_teacher_access'));

// deletes a question from the exercise (not from the data base)
if ($deleteQuestion) {
    if ($limitTeacherAccess && !api_is_platform_admin()) {
        exit;
    }

    // if the question exists
    if ($objQuestionTmp = Question::read($deleteQuestion)) {
        $objQuestionTmp->delete($exerciseId);

        // if the question has been removed from the exercise
        if ($objExercise->removeFromList($deleteQuestion)) {
            $nbrQuestions--;
        }
    }
    // destruction of the Question object
    unset($objQuestionTmp);
}
$ajax_url = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&exercise_id='.(int) $exerciseId;
?>
<script>
    $(function () {
        $("#dialog:ui-dialog").dialog("destroy");

        var stop = false;
        $("#question_list h3").click(function (event) {
            if (stop) {
                event.stopImmediatePropagation();
                event.preventDefault();
                stop = false;
            }
        });

        /* We can add links in the accordion header */
        $(".btn-actions .edition a.btn").click(function () {
            //Avoid the redirecto when selecting the delete button
            if (this.id.indexOf('delete') == -1) {
                newWind = window.open(this.href, "_self");
                newWind.focus();
                return false;
            }
        });

        var isDragging = false;
        $("#question_list").accordion({
            icons: null,
            heightStyle: "content",
            active: false, // all items closed by default
            collapsible: true,
            header: ".header_operations",
            beforeActivate: function (e, ui) {
                if (isDragging) {
                    e.preventDefault();
                    isDragging = false;
                }
                var data = ui.newHeader.data();
                if (typeof data === 'undefined') {
                    return;
                }

                var exerciseId = data.exercise || 0,
                    questionId = data.question || 0;

                if (!questionId || !exerciseId) {
                    return;
                }

                var $pnlQuestion = $('#pnl-question-' + questionId);

                if ($pnlQuestion.html().trim().length) {
                    return;
                }

                $pnlQuestion.html('<span class="fa fa-spinner fa-spin fa-3x fa-fw" aria-hidden="true"></span>');

                $.get('<?php echo api_get_path(WEB_AJAX_PATH); ?>exercise.ajax.php?<?php echo api_get_cidreq(); ?>', {
                    a: 'show_question',
                    exercise: exerciseId,
                    question: questionId
                }, function (response) {
                    $pnlQuestion.html(response)
                });
            }
        })
        .sortable({
            cursor: "move", // works?
            axis: "y",
            placeholder: "ui-state-highlight", //defines the yellow highlight
            handle: ".moved", //only the class "moved"
            start: function(event, ui) {
                isDragging = true;
            },
            stop: function(event, ui) {
                stop = true;
                setTimeout(function() {
                    isDragging = false;
                }, 50);
            },
            update: function (event, ui) {
                var order = $(this).sortable("serialize") + "&a=update_question_order&exercise_id=<?php echo $exerciseId; ?>";
                $.post("<?php echo $ajax_url; ?>", order, function (result) {
                    $("#message").html(result);
                });
            }
        });

        $(".moved").on('click', function(event) {
            event.stopImmediatePropagation();
        });
    });
</script>
<?php

if ($objExercise->selectType() === ONE_PER_PAGE
    && $objExercise->hasQuestionWithType(PAGE_BREAK)
) {
    echo Display::return_message(
        get_lang("The test contains page-break questions, which will only take effect if the test is set to 'All questions on one page'."),
        'warning'
    );
}

// Filter the type of questions we can add
Question::displayTypeMenu($objExercise);

echo '<div id="message"></div>';
$token = Security::get_token();
//deletes a session when using don't know question type (ugly fix)
Session::erase('less_answer');

if (isset($exerciseId) && $exerciseId > 0) {
    if ($nbrQuestions) {
        // In the building exercise mode show question list ordered as is.
        $objExercise->setCategoriesGrouping(false);

        $originalQuestionSelectType = $objExercise->questionSelectionType;
        // In building mode show all questions not render by teacher order.
        $objExercise->questionSelectionType = EX_Q_SELECTION_ORDERED;
        $allowQuestionOrdering = true;
        $showPagination = 'true' === api_get_setting('exercise.show_question_pagination');
        $length = (int) api_get_setting('exercise.question_pagination_length') ?: 30;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        if ($showPagination && $nbrQuestions > $length) {
            $allowQuestionOrdering = false;
            $start = ($page - 1) * $length;
            $questionList = $objExercise->selectQuestionList(true, true);
            $questionList = array_slice($questionList, $start, $length);
        } else {
            // Classic order
            $questionList = $objExercise->selectQuestionList(true, true);
        }
        $objExercise->questionSelectionType = $originalQuestionSelectType;

        echo '
            <div class="row gt-xs my-4 question-header">
                <div class="col-sm-5"><strong>'.get_lang('Questions').'</strong></div>
                <div class="col-sm-1 text-center"><strong>'.get_lang('Type').'</strong></div>
                <div class="col-sm-2"><strong>'.get_lang('Category').'</strong></div>
                <div class="col-sm-1 text-right"><strong>'.get_lang('Difficulty').'</strong></div>
                <div class="col-sm-1 text-right"><strong>'.get_lang('Score').'</strong></div>
                <div class="col-sm-2 text-right"><strong>'.get_lang('Detail').'</strong></div>
            </div>
            <div id="question_list">
        ';

        $category_list = TestCategory::getListOfCategoriesNameForTest($objExercise->id);

        if (is_array($questionList)) {
            foreach ($questionList as $id) {
                // To avoid warning messages.
                if (!is_numeric($id)) {
                    continue;
                }
                /** @var Question $objQuestionTmp */
                $objQuestionTmp = Question::read($id);

                if (empty($objQuestionTmp)) {
                    continue;
                }

                $baseUrl = api_get_self().'?'.api_get_cidreq().'&exerciseId='.(int) $exerciseId;

                $clone_link = Display::url(
                    Display::getMdiIcon(ActionIcon::COPY_CONTENT, 'ch-tool-icon-button', 'margin-bottom: 5px;', ICON_SIZE_TINY, get_lang('Copy')),
                    $baseUrl.'&'.http_build_query([
                        'clone_question' => $id,
                        'page' => $page,
                    ]),
                    ['class' => 'btn btn--warning btn-sm']
                );

                $edit_link = CALCULATED_ANSWER == $objQuestionTmp->selectType() && $objQuestionTmp->isAnswered()
                    ? Display::span(
                        Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon-disabled', 'margin-bottom: 5px;', ICON_SIZE_TINY, get_lang('Question edition is not available because the question has been already answered. However, you can copy and modify it.')),
                        ['class' => 'btn btn--plain btn-sm']
                    )
                    : Display::url(
                        Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon-button', 'margin-bottom: 5px;', ICON_SIZE_TINY, get_lang('Edit')),
                        $baseUrl.'&'.http_build_query([
                            'type' => $objQuestionTmp->selectType(),
                            'editQuestion' => $id,
                            'page' => $page,
                        ]),
                        ['class' => 'btn btn--warning btn-sm']
                    );
                $delete_link = null;
                if ($objExercise->edit_exercise_in_lp) {
                    $delete_link = Display::url(
                        Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon-button', 'margin-bottom: 5px;', ICON_SIZE_TINY, get_lang('Remove from test')),
                        $baseUrl.'&'.http_build_query([
                                'deleteQuestion' => $id,
                                'page' => $page,
                            ]),
                        [
                            'id' => "delete_$id",
                            'class' => 'delete-swal btn btn--danger btn-sm',
                            'data-title' => get_lang('Are you sure you want to delete'),
                            'data-confirm-text' => get_lang('Yes'),
                            'data-cancel-text'  => get_lang('Cancel'),
                            'title' => get_lang('Delete'),
                        ]
                    );
                }

                if ($limitTeacherAccess && !api_is_platform_admin()) {
                    $delete_link = '';
                }

                $btnActions = implode(
                    PHP_EOL,
                    [$edit_link, $clone_link, $delete_link]
                );

                $title = Security::remove_XSS($objQuestionTmp->selectTitle());
                $title = strip_tags($title);
                $move = '&nbsp;';
                if ($allowQuestionOrdering) {
                    $move = Display::getMdiIcon('cursor-move', 'moved', null, ICON_SIZE_MEDIUM);
                }

                // Question name
                $questionName =
                    '<a href="#" title = "'.Security::remove_XSS($title).'">
                        '.$move.' '.cut($title, 42).'
                    </a>';

                $questionType = Display::return_icon(
                    $objQuestionTmp->getTypePicture(),
                    $objQuestionTmp->getExplanation(),
                    ['class' => 'm-auto']
                );

                // Question category
                $questionCategory = Security::remove_XSS(
                    TestCategory::getCategoryNameForQuestion($objQuestionTmp->getId())
                );
                if (empty($questionCategory)) {
                    $questionCategory = '-';
                }

                // Question level
                $txtQuestionLevel = $objQuestionTmp->getLevel();
                if (empty($objQuestionTmp->level)) {
                    $txtQuestionLevel = '-';
                }
                $questionLevel = $txtQuestionLevel;

                // Question score
                $questionScore = $objQuestionTmp->selectWeighting();

                echo '<div id="question_id_list_'.$id.'">
                        <div class="header_operations" data-exercise="'.$objExercise->getId().'"
                            data-question="'.$id.'">
                            <div class="row">
                                <div class="question col-sm-5 col-xs-12">'
                                    .$questionName.'
                                </div>
                                <div class="type text-center col-sm-1 col-xs-12">
                                    <span class="xs">'.get_lang('Type').' </span>'
                                    .$questionType.'
                                </div>
                                <div class="category col-sm-2 col-xs-12" title="'.$questionCategory.'">
                                    <span class="xs">'.get_lang('Category').' </span>'
                                    .cut($questionCategory, 42).'
                                </div>
                                <div class="level text-right col-sm-1 col-xs-6">
                                    <span class="xs">'.get_lang('Difficulty').' </span>'
                                    .$questionLevel.'
                                </div>
                                <div class="score text-right col-sm-1 col-xs-6">
                                    <span class="xs">'.get_lang('Score').' </span>'
                                    .$questionScore.'
                                </div>
                                <div class="btn-actions text-right col-sm-2 col-xs-6">
                                    <div class="edition">'.$btnActions.'</div>
                                </div>
                            </div>
                        </div>
                        <div class="question-list-description-block" id="pnl-question-'.$id.'">
                        </div>
                    </div>
                ';
                unset($objQuestionTmp);
            }
        }

        echo '</div>'; //question list div
        // Pagination navigation
        if ($showPagination && $nbrQuestions > $length) {
            $totalPages = ceil($nbrQuestions / $length);
            echo '<div class="pagination flex justify-center mt-4">';
            for ($i = 1; $i <= $totalPages; $i++) {
                $isActive = ($i == $page) ? 'bg-primary text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-200';
                echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '" class="mx-1 px-4 py-2 border ' . $isActive . ' rounded">' . $i . '</a>';
            }
            echo '</div>';
        }
    } else {
        echo Display::return_message(get_lang('Questions list (there is no question so far).'), 'warning');
    }
} else {
    echo Display::return_message(get_lang('Choose question type'), 'warning');
}
