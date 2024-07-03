<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/fe/exportgradebook.php';

api_block_anonymous_users();
$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
    api_get_user_id(),
    api_get_course_info()
);

if (!$isDrhOfCourse) {
    GradebookUtils::block_students();
}

$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Gradebook'),
];

//load the evaluation & category
$select_eval = (int) $_GET['selecteval'];
if (empty($select_eval)) {
    api_not_allowed();
}

$displayscore = ScoreDisplay::instance();
$eval = Evaluation::load($select_eval);
$overwritescore = 0;
if ($eval[0]->get_category_id() < 0) {
    // if category id is negative, then the evaluation's origin is a link
    $link = LinkFactory::get_evaluation_link($eval[0]->get_id());
    $currentcat = Category::load($link->get_category_id());
} else {
    $currentcat = Category::load($eval[0]->get_category_id());
}

//load the result with the evaluation id
if (isset($_GET['delete_mark'])) {
    $result = Result::load($_GET['delete_mark']);
    if (!empty($result[0])) {
        $result[0]->delete();
    }
}

if (isset($_GET['selecteval'])) {
    $allresults = Result::load(null, null, $select_eval);
    $iscourse = !empty(api_get_course_id());
}

$allowMultipleAttempts = api_get_configuration_value('gradebook_multiple_evaluation_attempts');

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'delete_attempt':
            $result = Result::load($_GET['editres']);
            if ($allowMultipleAttempts && !empty($result) && isset($result[0]) && api_is_allowed_to_edit()) {
                /** @var Result $result */
                $result = $result[0];
                $url = api_get_self().'?selecteval='.$select_eval.'&'.api_get_cidreq().'&editres='.$result->get_id();
                $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT_ATTEMPT);
                if (isset($_GET['result_attempt_id'])) {
                    $attemptId = (int) $_GET['result_attempt_id'];
                    $sql = "DELETE FROM $table WHERE result_id = ".$result->get_id()." AND id = $attemptId";
                    Database::query($sql);
                    Display::addFlash(Display::return_message(get_lang('Deleted')));
                }

                header('Location: '.$url.'&action=add_attempt');
                exit;
            }
            break;
        case 'add_attempt':
            $result = Result::load($_GET['editres']);
            if ($allowMultipleAttempts && !empty($result) && isset($result[0]) && api_is_allowed_to_edit()) {
                /** @var Result $result */
                $result = $result[0];
                $backUrl = api_get_self().'?selecteval='.$select_eval.'&'.api_get_cidreq();
                $interbreadcrumb[] = [
                    'url' => $backUrl,
                    'name' => get_lang('Details'),
                ];

                /** @var Evaluation $evaluation */
                $evaluation = $eval[0];

                $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT_ATTEMPT);
                $now = api_get_utc_datetime();

                $url = api_get_self().'?selecteval='.$select_eval.'&'.api_get_cidreq().'&editres='.$result->get_id();
                $form = new FormValidator('attempt', 'post', $url.'&action=add_attempt');
                $form->addHeader(get_lang('AddResult'));
                $form->addLabel(get_lang('CurrentScore'), $result->get_score());

                $form->addFloat(
                    'score',
                    [
                        get_lang('Score'),
                        null,
                        '/ '.$evaluation->get_max(),
                    ],
                    true,
                    [
                        'size' => '4',
                        'maxlength' => '5',
                    ],
                    false,
                    0,
                    $evaluation->get_max()
                );

                $form->addTextarea('comment', get_lang('Comment'));
                $form->addButtonSave(get_lang('Save'));
                $attemptList = ResultTable::getResultAttemptTable($result, $url);
                $form->addLabel(get_lang('Attempts'), $attemptList);

                if ($form->validate()) {
                    $values = $form->getSubmitValues();
                    $newScore = $values['score'];
                    $newScore = api_number_format(
                        $newScore,
                        api_get_setting('gradebook_number_decimals')
                    );

                    $params = [
                        'result_id' => $result->get_id(),
                        'score' => $newScore,
                        'comment' => $values['comment'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    Database::insert($table, $params);
                    if ($newScore > $result->get_score()) {
                        $result->set_score($newScore);
                        $result->save();
                    }
                    Display::addFlash(Display::return_message(get_lang('Saved')));
                    header('Location: '.api_get_self().'?selecteval='.$select_eval.'&'.api_get_cidreq());
                    exit;
                }

                Display::display_header();
                $items[] = [
                    'url' => $backUrl,
                    'content' => Display::return_icon(
                        'back.png',
                        get_lang('Back'),
                        [],
                        ICON_SIZE_MEDIUM
                    ),
                ];
                echo Display::actions($items);
                $form->display();
                Display::display_footer();
                exit;
            }
            break;
    }
}

if (isset($_GET['editres'])) {
    $resultedit = Result::load($_GET['editres']);
    $edit_res_form = new EvalForm(
        EvalForm::TYPE_RESULT_EDIT,
        $eval[0],
        $resultedit[0],
        'edit_result_form',
        null,
        api_get_self().'?editres='.$resultedit[0]->get_id().'&selecteval='.$select_eval.'&'.api_get_cidreq()
    );

    if ($edit_res_form->validate()) {
        $values = $edit_res_form->exportValues();
        $result = new Result();
        $resultlog = new Result();
        $resultlog->addResultLog($values['hid_user_id'], $select_eval);
        $result->set_id($_GET['editres']);
        $result->set_user_id($values['hid_user_id']);
        $result->set_evaluation_id($select_eval);
        $row_value = isset($values['score']) ? $values['score'] : 0;
        if (!empty($row_value) || $row_value == 0) {
            $row_value = api_number_format(
                $row_value,
                api_get_setting('gradebook_number_decimals')
            );
            $result->set_score($row_value);
        }
        $result->save();
        if ($allowMultipleAttempts && !empty($result->get_id())) {
            $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT_ATTEMPT);
            $now = api_get_utc_datetime();
            $params = [
                'result_id' => $result->get_id(),
                'score' => $row_value,
                'comment' => $values['comment'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            Database::insert($table, $params);
        }

        Display::addFlash(Display::return_message(get_lang('ResultEdited'), 'normal', false));
        header('Location: gradebook_view_result.php?selecteval='.$select_eval.'&editresmessage=&'.api_get_cidreq());
        exit;
    }
}
$file_type = null;
if (isset($_GET['import'])) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'gradebook/gradebook_view_result.php?selecteval='.$select_eval.'&'.api_get_cidreq(),
        'name' => get_lang('ViewResult'),
    ];
    $import_result_form = new DataForm(
        DataForm::TYPE_IMPORT,
        'import_result_form',
        null,
        api_get_self().'?import=&selecteval='.$select_eval,
        '_blank',
        ''
    );
    if (!$import_result_form->validate()) {
        Display::display_header(get_lang('Import'));
    }
    $eval[0]->check_lock_permissions();
    if (isset($_POST['formSent']) && $_POST['formSent']) {
        if (!empty($_FILES['import_file']['name'])) {
            $values = $import_result_form->exportValues();
            $file_type = $_POST['file_type'];
            $file_name = $_FILES['import_file']['tmp_name'];
            $results = Import::csvToArray($file_name);
            $nr_results_added = 0;
            foreach ($results as $index => $importedresult) {
                //check username & score
                $importedresult['user_id'] = UserManager::get_user_id_from_username($importedresult['username']);
                $added = '0';
                foreach ($allresults as $allresult) {
                    if (($importedresult['user_id'] == $allresult->get_user_id())) {
                        if ($importedresult['score'] != $allresult->get_score()) {
                            if (!isset($values['overwrite'])) {
                                header('Location: '.api_get_path(WEB_CODE_PATH).'gradebook/gradebook_view_result.php?selecteval='.$select_eval.'&import_score_error='.$importedresult['user_id']);
                                exit;
                                break;
                            } else {
                                GradebookUtils::overwritescore(
                                    $allresult->get_id(),
                                    $importedresult['score'],
                                    $eval[0]->get_max()
                                );
                                $overwritescore++;
                                $added = '1';
                            }
                        } else {
                            $added = '1';
                        }
                    }
                }
                if ($importedresult['user_id'] == null) {
                    header('Location: gradebook_view_result.php?selecteval='.$select_eval.'&incorrectdata=');
                    exit;
                }
                $userinfo = api_get_user_info($importedresult['user_id']);
                if ($userinfo['lastname'] != $importedresult['lastname'] ||
                    $userinfo['firstname'] != $importedresult['firstname'] ||
                    $userinfo['official_code'] != $importedresult['official_code']
                ) {
                    if (!isset($values['ignoreerrors'])) {
                        header('Location: gradebook_view_result.php?selecteval='.$select_eval.'&import_user_error='.$importedresult['user_id']);
                        exit;
                    }
                }
                if ($added != '1') {
                    if ($importedresult['score'] > $eval[0]->get_max()) {
                        header('Location: gradebook_view_result.php?selecteval='.$select_eval.'&overwritemax=');
                        exit;
                    }
                    $result = new Result();
                    $result->set_user_id($importedresult['user_id']);
                    if (!empty($importedresult['score'])) {
                        $result->set_score(
                            api_number_format($importedresult['score'], api_get_setting('gradebook_number_decimals'))
                        );
                    }
                    if (!empty($importedresult['date'])) {
                        $result->set_date(api_get_utc_datetime($importedresult['date']));
                    } else {
                        $result->set_date(api_get_utc_datetime());
                    }
                    $result->set_evaluation_id($select_eval);
                    $result->add();
                    $nr_results_added++;
                }
            }
        } else {
            Display::addFlash(
                Display::return_message(
                    get_lang('ImportNoFile'),
                    'warning',
                    false
                )
            );
            header('Location: '.api_get_self().'?import=&selecteval='.$select_eval.'&importnofile=');
            exit;
        }
        if ($overwritescore != 0) {
            Display::addFlash(
                Display::return_message(
                    get_lang('ImportOverWriteScore').' '.$overwritescore
                )
            );
            header('Location: '.api_get_self().'?selecteval='.$select_eval.'&importoverwritescore='.$overwritescore);
            exit;
        }
        if ($nr_results_added == 0) {
            Display::addFlash(
                Display::return_message(
                    get_lang('ProblemUploadingFile'),
                    'warning',
                    false
                )
            );
            header('Location: '.api_get_self().'?selecteval='.$select_eval.'&nothingadded=');
            exit;
        }
        Display::addFlash(
            Display::return_message(
                get_lang('FileUploadComplete'),
                'success',
                false
            )
        );
        header('Location: '.api_get_self().'?selecteval='.$select_eval.'&importok=');
        exit;
    }
}

if (isset($_GET['export'])) {
    $interbreadcrumb[] = [
        'url' => 'gradebook_view_result.php?selecteval='.$select_eval.'&'.api_get_cidreq(),
        'name' => get_lang('ViewResult'),
    ];
    $locked_status = $eval[0]->get_locked();
    $export_result_form = new DataForm(
        DataForm::TYPE_EXPORT,
        'export_result_form',
        null,
        api_get_self().'?export=&selecteval='.$select_eval.'&'.api_get_cidreq(),
        '_blank',
        $locked_status
    );
    if (!$export_result_form->validate()) {
        Display::display_header(get_lang('Export'));
    }

    if ($export_result_form->validate()) {
        $export = $export_result_form->exportValues();
        $file_type = $export['file_type'];
        $filename = 'export_results_'.gmdate('Y-m-d_H-i-s');
        $results = Result::load(null, null, $select_eval);
        $data = []; //when file type is csv, add a header to the output file
        if ($file_type == 'csv') {
            $alldata[] = [
                'username',
                'official_code',
                'lastname',
                'firstname',
                'score',
                'date',
            ];
        }

        // export results to pdf file
        if ($file_type == 'pdf') {
            $number_decimals = api_get_setting('gradebook_number_decimals');
            $datagen = new ResultsDataGenerator($eval[0], $allresults);

            // set headers pdf
            !empty($_user['official_code']) ? $officialcode = $_user['official_code'].' - ' : '';

            $h1 = [get_lang('Teacher'), $officialcode.$_user['firstName'].', '.$_user['lastName']];
            $h2 = [get_lang('Score'), $eval[0]->get_max()];
            $h3 = [get_lang('Course'), $_course['name']];
            $h4 = [get_lang('Weight'), $eval[0]->get_weight()];
            $h5 = [get_lang('Session'), api_get_session_name(api_get_session_id())];
            $date = date('d-m-Y H:i:s', time());
            $h6 = [get_lang('DateTime'), api_convert_and_format_date($date, "%d/%m/%Y %H:%M")];
            $header_pdf = [$h1, $h2, $h3, $h4, $h5, $h6];

            // set footer pdf
            $f1 = '<hr />'.get_lang('Drh');
            $f2 = '<hr />'.get_lang('Teacher');
            $f3 = '<hr />'.get_lang('Date');
            $footer_pdf = [$f1, $f2, $f3];

            // set title pdf
            $title_pdf = $eval[0]->get_name();

            // set headers data table
            $head_ape_name = '';
            if (api_is_western_name_order()) {
                $head_ape_name = get_lang('FirstName').', '.get_lang('LastName');
            } else {
                $head_ape_name = get_lang('LastName').', '.get_lang('FirstName');
            }

            $head_table = [
                ['#', 3],
                [get_lang('Code'), 12],
                [$head_ape_name, 40],
                [get_lang('Score'), 12],
            ];
            if ($number_decimals == null) {
                $head_table[] = [get_lang('Letters'), 15];
            }
            $head_display_score = '';
            $scoredisplay = ScoreDisplay::instance();
            $customdisplays = $scoredisplay->get_custom_score_display_settings();

            if (!empty($customdisplays) && $scoredisplay->is_custom()) {
                $head_display_score = get_lang('Display');
                $head_table[] = [$head_display_score, 15];
            }

            // get data table
            if (api_sort_by_first_name()) {
                $data_array = $datagen->get_data(
                    ResultsDataGenerator::RDG_SORT_FIRSTNAME,
                    0,
                    null,
                    false,
                    true
                );
            } else {
                $data_array = $datagen->get_data(
                    ResultsDataGenerator::RDG_SORT_LASTNAME,
                    0,
                    null,
                    false,
                    true
                );
            }
            $data_table = [];
            foreach ($data_array as $data) {
                $result = [];
                $user_info = api_get_user_info($data['id']);
                $result[] = $user_info['username'];

                if (api_is_western_name_order()) {
                    $result[] = $user_info['firstname'].', '.$user_info['lastname'];
                } else {
                    $result[] = $user_info['lastname'].', '.$user_info['firstname'];
                }
                if ($number_decimals == null) {
                    if (empty($data['scoreletter']) && !is_numeric($data['score'])) {
                        $result[] = get_lang('DidNotTakeTheExam');
                    } else {
                        $result[] = api_strtoupper(get_lang('Literal'.$data['scoreletter']));
                    }
                } else {
                    if (empty($data['score']) && !is_numeric($data['score'])) {
                        $result[] = get_lang('DidNotTakeTheExamAcronym');
                    } else {
                        $result[] = $data['score'];
                    }
                }
                if ($scoredisplay->is_custom()) {
                    $result[] = $data['display'];
                }
                $data_table[] = $result;
            }

            export_pdf_with_html(
                $head_table,
                $data_table,
                $header_pdf,
                $footer_pdf,
                $title_pdf
            );
        }

        // export results to xml or csv file
        foreach ($results as $result) {
            $userinfo = api_get_user_info($result->get_user_id());
            $data['username'] = $userinfo['username']; //$result->get_user_id();
            $data['official_code'] = $userinfo['official_code'];
            $data['lastname'] = $userinfo['lastname'];
            $data['firstname'] = $userinfo['firstname'];
            $data['score'] = $result->get_score();
            $data['date'] = api_format_date($result->get_date(), "%d/%m/%Y %R");
            $alldata[] = $data;
        }

        switch ($file_type) {
            case 'xml':
                Export::arrayToXml(
                    $alldata,
                    $filename,
                    'Result',
                    'XMLResults'
                );
                exit;
                break;
            case 'csv':
                Export::arrayToCsv($alldata, $filename);
                exit;
                break;
        }
    }
}

if (isset($_GET['resultdelete'])) {
    $result = Result::load($_GET['resultdelete']);
    $result[0]->delete();
    Display::addFlash(Display::return_message(get_lang('ResultDeleted')));
    header('Location: gradebook_view_result.php?selecteval='.$select_eval.'&'.api_get_cidreq());
    exit;
}

if (isset($_POST['action'])) {
    $number_of_selected_items = count($_POST['id']);
    if ($number_of_selected_items == '0') {
        Display::addFlash(
            Display::return_message(
                get_lang('NoItemsSelected'),
                'warning',
                false
            )
        );
    } else {
        switch ($_POST['action']) {
            case 'delete':
                $number_of_deleted_results = 0;
                foreach ($_POST['id'] as $indexstr) {
                    $result = Result::load($indexstr);
                    $result[0]->delete();
                    $number_of_deleted_results++;
                }
                Display::addFlash(Display::return_message(get_lang('ResultsDeleted'), 'confirmation', false));
                header('Location: gradebook_view_result.php?massdelete=&selecteval='.$select_eval.'&'.api_get_cidreq());
                exit;
                break;
        }
    }
} // TODO - what if selecteval not set ?

$addparams = ['selecteval' => $eval[0]->get_id()];
if (isset($_GET['print'])) {
    $datagen = new ResultsDataGenerator($eval[0], $allresults);
    if (api_sort_by_first_name()) {
        $data_array = $datagen->get_data(
            ResultsDataGenerator::RDG_SORT_FIRSTNAME,
            0,
            null,
            true
        );
    } else {
        $data_array = $datagen->get_data(
            ResultsDataGenerator::RDG_SORT_LASTNAME,
            0,
            null,
            true
        );
    }
    if ($displayscore->is_custom()) {
        if (api_is_western_name_order()) {
            $header_names = [
                get_lang('FirstName'),
                get_lang('LastName'),
                get_lang('Score'),
                get_lang('Display'),
            ];
        } else {
            $header_names = [
                get_lang('LastName'),
                get_lang('FirstName'),
                get_lang('Score'),
                get_lang('Display'),
            ];
        }
    } else {
        if (api_is_western_name_order()) {
            $header_names = [get_lang('FirstName'), get_lang('LastName'), get_lang('Score')];
        } else {
            $header_names = [get_lang('LastName'), get_lang('FirstName'), get_lang('Score')];
        }
    }
    $newarray = [];
    foreach ($data_array as $data) {
        $newarray[] = array_slice($data, 3);
    }

    echo print_table(
        $newarray,
        $header_names,
        get_lang('ViewResult'),
        $eval[0]->get_name()
    );
    exit;
} else {
    $resultTable = new ResultTable($eval[0], $allresults, $iscourse, $addparams);
}

$htmlHeadXtra[] = '<script>
function confirmationuser() {
    if (confirm("'.get_lang('DeleteUser').'?"))
    	{return true;}
    else
    	{return false;}
}

function confirmationall () {
    if (confirm("'.get_lang('DeleteAll').'?"))
    	{return true;}
    else
    	{return false;}
    }
</script>';
if (isset($_GET['deleteall'])) {
    $eval[0]->delete_results();
    Display::addFlash(Display::return_message(get_lang('AllResultDeleted')));
    header('Location: '.api_get_path(WEB_CODE_PATH).'gradebook/gradebook_view_result.php?allresdeleted=&selecteval='.$select_eval.'&'.api_get_cidreq());
    exit;
}
if (!isset($_GET['export']) && (!isset($_GET['import']))) {
    if (!isset($_GET['selectcat'])) {
        $interbreadcrumb[] = [
            'url' => Category::getUrl().'selectcat='.$currentcat[0]->get_id(),
            'name' => get_lang('Details'),
        ];
    }
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'gradebook/gradebook_view_result.php?selecteval='.$select_eval.'&'.api_get_cidreq(),
        'name' => get_lang('ViewResult'),
    ];
    Display::display_header();
}

if (isset($_GET['adduser'])) {
    echo Display::return_message(get_lang('UserAdded'), 'confirmation', false);
}

if (isset($_GET['incorrectdata'])) {
    echo Display::return_message(get_lang('IncorrectData'), 'warning', false);
}

if (isset($_GET['nouser'])) {
    echo Display::return_message(get_lang('NoUser'), 'warning', false);
}
if (isset($_GET['overwritemax'])) {
    echo Display::return_message(get_lang('OverWriteMax'), 'warning', false);
}

if (isset($_GET['import_user_error'])) {
    $userinfo = api_get_user_info($_GET['import_user_error']);
    echo Display::return_message(
        get_lang('UserInfoDoesNotMatch').' '.
        api_get_person_name($userinfo['firstname'], $userinfo['lastname']),
        'warning'
    );
}
if (isset($_GET['import_score_error'])) {
    $userinfo = api_get_user_info($_GET['import_score_error']);
    echo Display::return_message(
        get_lang('ScoreDoesNotMatch').' '.
        api_get_person_name($userinfo['firstname'], $userinfo['lastname']),
        'warning'
    );
}

if ($file_type == null) {
    //show the result header
    if (isset($export_result_form) && !(isset($edit_res_form))) {
        echo $export_result_form->display();
        DisplayGradebook::display_header_result($eval[0], $currentcat[0]->get_id(), 1);
    } else {
        if (isset($import_result_form)) {
            echo $import_result_form->display();
        }
        if (isset($edit_res_form)) {
            echo $edit_res_form->toHtml();
        }
        DisplayGradebook::display_header_result($eval[0], $currentcat[0]->get_id(), 1);
    }
    // Letter-based scores are built from lib/results_data_generator.class.php::get_score_display()
    $resultTable->display();
    Display::display_footer();
}
