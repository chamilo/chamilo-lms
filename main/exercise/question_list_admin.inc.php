<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 *    Code library for HotPotatoes integration.
 *
 * @package chamilo.exercise
 *
 * @author Olivier Brouckaert & Julio Montoya & Hubert Borderiou 21-10-2011 (Question by category)
 *    QUESTION LIST ADMINISTRATION
 *
 *    This script allows to manage the question list
 *    It is included from the script admin.php
 */
$limitTeacherAccess = api_get_configuration_value('limit_exercise_teacher_access');

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
$ajax_url = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&exercise_id='.intval($exerciseId);
?>
<div id="dialog-confirm"
     title="<?php echo get_lang('ConfirmYourChoice'); ?>"
     style="display:none;">
    <p>
        <?php echo get_lang('AreYouSureToDelete'); ?>
    </p>
</div>

<script>
    $(function () {
        $("#dialog:ui-dialog").dialog("destroy");
        $("#dialog-confirm").dialog({
            autoOpen: false,
            show: "blind",
            resizable: false,
            height: 150,
            modal: false
        });

        $(".opener").click(function () {
            var targetUrl = $(this).attr("href");
            $("#dialog-confirm").dialog({
                modal: true,
                buttons: {
                    "<?php echo get_lang("Yes"); ?>": function () {
                        location.href = targetUrl;
                        $(this).dialog("close");

                    },
                    "<?php echo get_lang("No"); ?>": function () {
                        $(this).dialog("close");
                    }
                }
            });
            $("#dialog-confirm").dialog("open");
            return false;
        });

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

        $("#question_list").accordion({
            icons: null,
            heightStyle: "content",
            active: false, // all items closed by default
            collapsible: true,
            header: ".header_operations",
            beforeActivate: function (e, ui) {
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
            update: function (event, ui) {
                var order = $(this).sortable("serialize") + "&a=update_question_order&exercise_id=<?php echo $exerciseId; ?>";
                $.post("<?php echo $ajax_url; ?>", order, function (result) {
                    $("#message").html(result);
                });
            },
            axis: "y",
            placeholder: "ui-state-highlight", //defines the yellow highlight
            handle: ".moved", //only the class "moved"
            stop: function () {
                stop = true;
            }
        });
    });
</script>
<?php

//we filter the type of questions we can add
Question::displayTypeMenu($objExercise);

echo '<div id="message"></div>';
$token = Security::get_token();
//deletes a session when using don't know question type (ugly fix)
Session::erase('less_answer');

// If we are in a test
$inATest = isset($exerciseId) && $exerciseId > 0;
if (!$inATest) {
    echo "<div class='alert alert-warning'>".get_lang('ChoiceQuestionType')."</div>";
} else {
    if ($nbrQuestions) {
        // In the building exercise mode show question list ordered as is.
        $objExercise->setCategoriesGrouping(false);

        // In building mode show all questions not render by teacher order.
        $objExercise->questionSelectionType = EX_Q_SELECTION_ORDERED;
        $alloQuestionOrdering = true;
        $showPagination = api_get_configuration_value('show_question_pagination');
        if (!empty($showPagination) && $nbrQuestions > $showPagination) {
            // $page is declare in admin.php
            //$page = isset($_GET['page']) && !empty($_GET['page']) ? (int) $_GET['page'] : 1;
            $length = api_get_configuration_value('question_pagination_length');
            $url = api_get_self().'?'.api_get_cidreq();
            // Use pagination for exercise with more than 200 questions.
            $alloQuestionOrdering = false;
            $start = ($page - 1) * $length;
            $questionList = $objExercise->getQuestionForTeacher($start, $length);
            $paginator = new Knp\Component\Pager\Paginator();
            $pagination = $paginator->paginate([]);

            $pagination->setTotalItemCount($nbrQuestions);
            $pagination->setItemNumberPerPage($length);
            $pagination->setCurrentPageNumber($page);
            $pagination->renderer = function ($data) use ($url) {
                $render = '<ul class="pagination">';
                for ($i = 1; $i <= $data['pageCount']; $i++) {
                    $pageContent = '<li><a href="'.$url.'&page='.$i.'">'.$i.'</a></li>';
                    if ($data['current'] == $i) {
                        $pageContent = '<li class="active"><a href="#" >'.$i.'</a></li>';
                    }
                    $render .= $pageContent;
                }
                $render .= '</ul>';

                return $render;
            };
            echo $pagination;
        } else {
            // Classic order
            $questionList = $objExercise->selectQuestionList(true, true);
        }

        echo '
        <div class="list-header-question">
            <h2>'.get_lang('List Questions').'</h2>
        </div>
        <div id="question_list">
    ';

        $category_list = TestCategory::getListOfCategoriesNameForTest($objExercise->id, false);

        if (is_array($questionList)) {
            foreach ($questionList as $id) {
                //To avoid warning messages
                if (!is_numeric($id)) {
                    continue;
                }
                /** @var Question $objQuestionTmp */
                $objQuestionTmp = Question::read($id);

                if (empty($objQuestionTmp)) {
                    continue;
                }

                $clone_link = Display::url(
                    Display::returnFontAwesomeIcon('copy'),
                    api_get_self().'?'.api_get_cidreq().'&clone_question='.$id,
                    [
                        'class' => 'btn btn-outline-secondary btn-sm',
                        'title' => get_lang('Copy'),
                    ]
                );
                $edit_link = $objQuestionTmp->type == CALCULATED_ANSWER && $objQuestionTmp->isAnswered()
                    ? Display::span(
                        Display::returnFontAwesomeIcon('pencil-alt'),
                        [
                           'class' => 'btn btn-outline-secondary btn-sm',
                           'title' => get_lang('QuestionEditionNotAvailableBecauseItIsAlreadyAnsweredHoweverYouCanCopyItAndModifyTheCopy'),
                           'disabled',
                        ]
                    )
                    : Display::url(
                        Display::returnFontAwesomeIcon('pencil-alt'),
                        api_get_self().'?'.api_get_cidreq().'&'
                            .http_build_query([
                                'type' => $objQuestionTmp->selectType(),
                                'myid' => 1,
                                'editQuestion' => $id,
                            ]),
                        [
                            'class' => 'btn btn-outline-secondary btn-sm',
                            'title' => get_lang('Modify'),
                        ]
                    );
                $delete_link = null;
                if ($objExercise->edit_exercise_in_lp == true) {
                    $delete_link = Display::url(
                        Display::returnFontAwesomeIcon('trash-alt'),
                        api_get_self().'?'.api_get_cidreq().'&'
                            .http_build_query([
                                'exerciseId' => $exerciseId,
                                'deleteQuestion' => $id,
                            ]),
                        [
                            'id' => "delete_$id",
                            'title' => get_lang('RemoveFromTest'),
                            'class' => 'btn btn-outline-secondary btn-sm delete-swal',
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
                $move = null;
                if ($alloQuestionOrdering) {
                    $move .= Display::tag(
                        'div',
                        Display::returnFontAwesomeIcon(
                            'arrows-alt ',
                            1,
                            true),
                        [
                            'class' => 'btn-moved moved',
                        ]
                    );
                }

                // Question name
                $questionName =
                    '<a href="#" title = "'.Security::remove_XSS($title).'">
                        '.cut($title, 42).'
                    </a>';

                // Question type
                list($typeImg, $typeExpl) = $objQuestionTmp->get_type_icon_html();
                $questionType = Display::return_icon($typeImg, $typeExpl, [], ICON_SIZE_MEDIUM);

                // Question category
                $txtQuestionCat = Security::remove_XSS(
                    TestCategory::getCategoryNameForQuestion($objQuestionTmp->id)
                );
                if (empty($txtQuestionCat)) {
                    $txtQuestionCat = "-";
                }

                // Question level
                $txtQuestionLevel = $objQuestionTmp->level;
                if (empty($objQuestionTmp->level)) {
                    $txtQuestionLevel = '-';
                }
                $questionLevel = $txtQuestionLevel;

                // Question score
                $questionScore = $objQuestionTmp->selectWeighting();

                echo '<div id="question_id_list_'.$id.'">
                        <div class="header_operations" data-exercise="'.$objExercise->selectId().'"
                            data-question="'.$id.'">
                            <div class="card">
                                <div class="card-body">
                                     <div class="row">
                                        <div class="col-md-1">
                                            '.$move.'
                                        </div>
                                        <div class="col-md-5">
                                            <div class="question-title">
                                                '.$questionType.$questionName.'
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex flex-row bd-highlight mt-2 mb-2">
                                                <div class="p-2 bd-highlight">'.get_lang('Category').': '.cut($txtQuestionCat, 42).'</div>
                                                <div class="p-2 bd-highlight">'.get_lang('Difficulty').': '.$questionLevel.'</div>
                                                <div class="p-2 bd-highlight">'.get_lang('Score').': '.$questionScore.'</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="text-right mt-2 mb-2 mr-2">'.$btnActions.'</div>
                                        </div>
                                     </div>
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
    } else {
        echo Display::return_message(get_lang('NoQuestion'), 'warning');
    }
    echo '</div>'; //question list div
}
