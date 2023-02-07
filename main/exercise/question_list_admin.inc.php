<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @author Olivier Brouckaert & Julio Montoya & Hubert Borderiou 21-10-2011 (Question by category)
 *    QUESTION LIST ADMINISTRATION
 *
 *    This script allows to manage the question list
 *    It is included from the script admin.php
 */
$limitTeacherAccess = api_get_configuration_value('limit_exercise_teacher_access');
$allowInterCourseLinking = api_get_configuration_value('quiz_question_allow_inter_course_linking');

// deletes a question from the exercise (not from the data base)
if ($deleteQuestion) {
    if ($limitTeacherAccess && !api_is_platform_admin()) {
        exit;
    }

    // if the question exists
    if ($objQuestionTmp = Question::read($deleteQuestion)) {
        if ($allowInterCourseLinking === true) {
            $masterExerciseId = Question::getMasterQuizForQuestion($objQuestionTmp->iid);

            if ($masterExerciseId != $exerciseId) {
                $objQuestionTmp->delete($exerciseId);
            } else {
                $objQuestionTmp->delete();
            }
        } else {
            $objQuestionTmp->delete($exerciseId);
        }

        // if the question has been removed from the exercise
        if ($objExercise->removeFromList($deleteQuestion)) {
            $nbrQuestions--;
        }
    }
    // destruction of the Question object
    unset($objQuestionTmp);
}
$ajax_url = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&exercise_id='.intval($exerciseId);
$addImageUrl = api_get_path(WEB_CODE_PATH).'inc/lib/elfinder/filemanager.php?add_image=1&'.api_get_cidreq();
?>
<div id="dialog-confirm"
     title="<?php echo get_lang('ConfirmYourChoice'); ?>"
     style="display:none;">
    <p>
        <?php echo get_lang('AreYouSureToDelete'); ?>
    </p>
</div>
<script>
    function addImageToQuestion(image, questionId) {
        let url = '<?php echo $ajax_url; ?>' + '&a=save_question_description&question_id=' + questionId;
        let params = {
            image: image
        }
        $.ajax({
            url: url,
            data: params,
            success: function (data) {
                window.alert('<?php echo addslashes(get_lang('Updated')); ?>');
            }
        });
    }

    function loadEditor(button, questionId) {
        event.preventDefault();
        let url = '<?php echo $addImageUrl; ?>' + '&question_id=' + questionId;
        let w = 850;
        let h = 500;
        let left = (screen.width / 2) - (w / 2);
        let top = (screen.height / 2) - (h / 2);
        let params = 'height=' + h + ',width=' + w + ',resizable=0,top=' + top + ',left=' + left;

        setTimeout(() => window.open(url, 'editor', params), 1000);
        return;
    }

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
            var otherQuizs = $(this).data("otherquizs");

            if (otherQuizs == 1) {
                $("#dialog-confirm p").text("<?php echo get_lang('QuestionInOtherExercises'); ?>")
            }

            $("#dialog-confirm").dialog({
                modal: true,
                buttons: {
                    "<?php echo get_lang('Yes'); ?>": function () {
                        location.href = targetUrl;
                        $(this).dialog("close");

                    },
                    "<?php echo get_lang('No'); ?>": function () {
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

// Filter the type of questions we can add
Question::displayTypeMenu($objExercise);
echo '<div id="message"></div>';
$token = Security::get_token();
//deletes a session when using don't know question type (ugly fix)
Session::erase('less_answer');

// If we are in a test
$inATest = isset($exerciseId) && $exerciseId > 0;
if (!$inATest) {
    echo Display::return_message(get_lang('ChoiceQuestionType'), 'warning');
} else {
    if ($nbrQuestions) {
        // In the building exercise mode show question list ordered as is.
        $objExercise->setCategoriesGrouping(false);
        $originalQuestionSelectType = $objExercise->questionSelectionType;
        // In building mode show all questions not render by teacher order.
        $objExercise->questionSelectionType = EX_Q_SELECTION_ORDERED;
        $allowQuestionOrdering = true;
        $showPagination = api_get_configuration_value('show_question_pagination');
        if (!empty($showPagination) && $nbrQuestions > $showPagination) {
            $length = api_get_configuration_value('question_pagination_length');
            $url = api_get_self().'?'.api_get_cidreq();
            // Use pagination for exercise with more than 200 questions.
            $allowQuestionOrdering = false;
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
            // Teacher view, see all questions.
            //$questionList = $objExercise->selectQuestionList(true, true);
            $questionList = $objExercise->getQuestionOrderedList(true);
        }

        // Restore original value
        $objExercise->questionSelectionType = $originalQuestionSelectType;

        echo '
            <div class="row hidden-xs">
                <div class="col-sm-5"><strong>'.get_lang('Questions').'</strong></div>
                <div class="col-sm-1 text-center"><strong>'.get_lang('Type').'</strong></div>
                <div class="col-sm-2"><strong>'.get_lang('Category').'</strong></div>
                <div class="col-sm-1 text-right"><strong>'.get_lang('Difficulty').'</strong></div>
                <div class="col-sm-1 text-right"><strong>'.get_lang('MaximumScore').'</strong></div>
                <div class="col-sm-2 text-right"><strong>'.get_lang('Actions').'</strong></div>
            </div>
            <div id="question_list">
        ';

        $category_list = TestCategory::getListOfCategoriesNameForTest($objExercise->iid, false);

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

                $addImageLink = '';
                if (api_get_configuration_value('allow_quick_question_description_popup')) {
                    $addImageLink = Display::url(
                        Display::return_icon(
                            'image.png',
                            get_lang('AddImage'),
                            [],
                            ICON_SIZE_TINY
                        ),
                        'javascript:void(0);',
                        ['class' => 'btn btn-default btn-sm ajax', 'onclick' => 'loadEditor(this, '.$id.')']
                    );
                }

                $clone_link = null;
                if ($objExercise->edit_exercise_in_lp == true) {
                    $clone_link = Display::url(
                        Display::return_icon(
                            'cd.png',
                            get_lang('Copy'),
                            [],
                            ICON_SIZE_TINY
                        ),
                        api_get_self().'?'.api_get_cidreq().'&clone_question='.$id.'&page='.$page,
                        ['class' => 'btn btn-default btn-sm']
                    );
                }

                $edit_link = $objQuestionTmp->selectType() == CALCULATED_ANSWER && $objQuestionTmp->isAnswered()
                    ? Display::span(
                        Display::return_icon(
                            'edit_na.png',
                            get_lang(
                                'QuestionEditionNotAvailableBecauseItIsAlreadyAnsweredHoweverYouCanCopyItAndModifyTheCopy'
                            ),
                            [],
                            ICON_SIZE_TINY
                        ),
                        ['class' => 'btn btn-default btn-sm']
                    )
                    : Display::url(
                        Display::return_icon(
                            'edit.png',
                            get_lang('Modify'),
                            [],
                            ICON_SIZE_TINY
                        ),
                        api_get_self().'?'.api_get_cidreq().'&'
                            .http_build_query([
                                'type' => $objQuestionTmp->selectType(),
                                'editQuestion' => $id,
                                'page' => $page,
                            ]),
                        ['class' => 'btn btn-default btn-sm']
                    );
                $delete_link = null;
                if (!$limitTeacherAccess && api_is_allowed_to_edit() && $objExercise->edit_exercise_in_lp) {
                    $questionInOtherQuizs = 0;
                    $results = Question::countQuizzesUsingQuestion($id);

                    if ($results > 1) {
                        $masterExerciseId = Question::getMasterQuizForQuestion($id);
                        if ($masterExerciseId !== $exerciseId) {
                            $questionInOtherQuizs = 1;
                        }
                    }

                    $delete_link = Display::url(
                        Display::return_icon(
                            'delete.png',
                            get_lang('RemoveFromTest'),
                            [],
                            ICON_SIZE_TINY
                        ),
                        api_get_self().'?'.api_get_cidreq().'&'
                            .http_build_query([
                                'exerciseId' => $exerciseId,
                                'deleteQuestion' => $id,
                                'page' => $page,
                            ]),
                        [
                            'id' => "delete_$id",
                            'class' => 'opener btn btn-default btn-sm',
                            'data-otherquizs' => $questionInOtherQuizs,
                        ]
                    );
                }

                $btnActions = implode(
                    PHP_EOL,
                    [$edit_link, $clone_link, $addImageLink, $delete_link]
                );

                $title = Security::remove_XSS(strip_tags($objQuestionTmp->selectTitle()));
                $move = '&nbsp;';
                if ($allowQuestionOrdering) {
                    $move = Display::returnFontAwesomeIcon('arrows moved', 1, true);
                }

                // Question name
                $questionName =
                    '<a href="#" title = "'.$title.'">
                        '.$move.' '.cut($title, 42).'
                    </a>';

                // Question type
                $typeImg = $objQuestionTmp->getTypePicture();
                $typeExpl = $objQuestionTmp->getExplanation();
                $questionType = Display::return_icon($typeImg, $typeExpl);

                // Question category
                $txtQuestionCat = Security::remove_XSS(
                    TestCategory::getCategoryNameForQuestion($objQuestionTmp->iid)
                );
                if (empty($txtQuestionCat)) {
                    $txtQuestionCat = '-';
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
                        <div class="header_operations" data-exercise="'.$objExercise->selectId().'"
                            data-question="'.$id.'">
                            <div class="row">
                                <div class="question col-sm-5 col-xs-12">'
                                    .$questionName.'
                                </div>
                                <div class="type text-center col-sm-1 col-xs-12">
                                    <span class="visible-xs-inline">'.get_lang('Type').' </span>'
                                    .$questionType.'
                                </div>
                                <div class="category col-sm-2 col-xs-12" title="'.$txtQuestionCat.'">
                                    <span class="visible-xs-inline">'.get_lang('Category').' </span>'
                                    .cut($txtQuestionCat, 42).'
                                </div>
                                <div class="level text-right col-sm-1 col-xs-6">
                                    <span class="visible-xs-inline">'.get_lang('Difficulty').' </span>'
                                    .$questionLevel.'
                                </div>
                                <div class="score text-right col-sm-1 col-xs-6">
                                    <span class="visible-xs-inline">'.get_lang('Score').' </span>'
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
    } else {
        echo Display::return_message(get_lang('NoQuestion'), 'warning');
    }
}
