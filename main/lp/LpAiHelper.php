<?php
/* For licensing terms, see /license.txt */

class LpAiHelper
{
    /**
     * AiHelper constructor.
     * Requires plugin ai_helper to connect to the api.
     */
    public function __construct()
    {
        if (!('true' === api_get_plugin_setting('ai_helper', 'tool_enable') && 'true' === api_get_plugin_setting('ai_helper', 'tool_lp_enable'))) {
            return false;
        }
    }

    /**
     * Get the form to generate Lp items using Ai Helper.
     */
    public function aiHelperForm()
    {
        $form = new FormValidator(
            'lp_ai_generate',
            'post',
            api_get_self()."?".api_get_cidreq(),
            null
        );
        $form->addElement('header', get_lang('LpAiGenerator'));
        $form->addElement('text', 'lp_name', [get_lang('LpAiTopic'), get_lang('LpAiTopicHelp')]);
        $form->addRule('lp_name', get_lang('ThisFieldIsRequired'), 'required');
        $form->addElement('number', 'nro_items', [get_lang('LpAiNumberOfItems'), get_lang('LpAiNumberOfItemsHelper')]);
        $form->addRule('nro_items', get_lang('ThisFieldIsRequired'), 'required');
        $form->addElement('number', 'words_count', [get_lang('LpAiWordsCount'), get_lang('LpAiWordsCountHelper')]);
        $form->addRule('words_count', get_lang('ThisFieldIsRequired'), 'required');

        $form->addElement('checkbox', 'add_lp_quiz', null, get_lang('AddTestAfterEachPage'), ['id' => 'add-lp-quiz']);
        $form->addHtml('<div id="lp-quiz-area">');
        $form->addElement('number', 'nro_questions', [get_lang('NumberOfQuestions'), get_lang('AIQuestionsGeneratorNumberHelper')]);
        $form->addRule('nro_questions', get_lang('ThisFieldIsRequired'), 'required');
        $form->addHtml('</div>');
        $form->setDefaults(['nro_questions' => 2]);

        $generateUrl = api_get_path(WEB_PLUGIN_PATH).'ai_helper/tool/learnpath.php';
        $language = api_get_interface_language();
        $courseCode = api_get_course_id();
        $sessionId = api_get_session_id();
        $redirectSuccess = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=add_item&type=step&isStudentView=false&lp_id=';
        $form->addHtml('<script>
                $(function () {
                    $("#lp-quiz-area").hide();
                    $("#add-lp-quiz").change(function() {
                        if ($(this).is(":checked")) {
                            $("#lp-quiz-area").show();
                        } else {
                            $("#lp-quiz-area").hide();
                        }
                    });

                    $("#create-lp-ai").on("click", function (e) {
                      e.preventDefault();
                      e.stopPropagation();

                      var btnGenerate = $(this);
                      var lpName = $("[name=\'lp_name\']").val();
                      var nroItems = parseInt($("[name=\'nro_items\']").val());
                      var wordsCount = parseInt($("[name=\'words_count\']").val());
                      var valid = (lpName != \'\' && nroItems > 0 && wordsCount > 0);
                      var addTests = $("#add-lp-quiz").is(":checked");
                      var nroQuestions = parseInt($("[name=\'nro_questions\']").val());

                      if (valid) {
                        if (addTests) {
                            var quizValid = (nroQuestions > 0 && nroQuestions <= 5);
                            if (!quizValid) {
                                alert("'.sprintf(get_lang('NumberOfQuestionsLimitedFromXToY'), 1, 5).'");
                                return false;
                            }
                        }
                        btnGenerate.attr("disabled", true);
                        btnGenerate.text("'.get_lang('PleaseWaitThisCouldTakeAWhile').'");
                        $.getJSON("'.$generateUrl.'", {
                            "lp_name": lpName,
                            "nro_items": nroItems,
                            "words_count": wordsCount,
                            "language": "'.$language.'",
                            "course_code": "'.$courseCode.'",
                            "session_id": "'.$sessionId.'",
                            "add_tests": addTests,
                            "nro_questions": nroQuestions
                        }).done(function (data) {
                          btnGenerate.attr("disabled", false);
                          btnGenerate.text("'.get_lang('Generate').'");
                          if (data.success && data.success == true) {
                            location.href = "'.$redirectSuccess.'" + data.lp_id;
                          } else {
                            var errorMessage = "'.get_lang('NoSearchResults').'. '.get_lang('PleaseTryAgain').'";
                            if (data.text) {
                                errorMessage = data.text;
                            }
                            alert(errorMessage);
                          }
                        });
                      }
                    });
                });
            </script>');

        $form->addButton(
            'create_lp_button',
            get_lang('LearnpathAddLearnpath'),
            '',
            'default',
            'default',
            null,
            ['id' => 'create-lp-ai']
        );

        echo $form->returnForm();
    }
}
