<?php

/* For licensing terms, see /license.txt */

use Symfony\Component\HttpFoundation\Request as HttpRequest;

$questionId = (int) $_GET['mad_admin'];

$exerciseId = $exerciseId ?? 0;
/** @var Exercise $objExercise */
$objExercise = $objExercise ?? null;
/** @var Question $objQuestion */
$objQuestion = $objQuestion ?? null;

if (!is_object($objQuestion)) {
    $objQuestion = Question::read($questionId);
}

$isGlobal = MULTIPLE_ANSWER_DROPDOWN_COMBINATION === (int) $objQuestion->type;

$objAnswer = new Answer($objQuestion->iid, 0, $objExercise);
$options = [];

for ($i = 1; $i <= $objAnswer->nbrAnswers; $i++) {
    $options[$objAnswer->iid[$i]] = $objAnswer->answer[$i];
}

$webCodePath = api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.api_get_cidreq().'&';
$adminUrl = $webCodePath.http_build_query(['exerciseId' => $exerciseId]);

$httpRequest = HttpRequest::createFromGlobals();
$submitAnswers = $httpRequest->request->has('submitAnswers');

if ($submitAnswers) {
    $questionAnswers = array_map(
        'intval',
        (array) $httpRequest->request->get('answer', [])
    );

    $tblQuizAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);

    Database::query(
        "UPDATE $tblQuizAnswer SET correct = 0, ponderation = 0 WHERE question_id = ".$objQuestion->iid
    );
    Database::query(
        "UPDATE $tblQuizAnswer SET correct = 1
             WHERE question_id = {$objQuestion->iid} AND iid IN (".implode(', ', $questionAnswers).")"
    );

    if ($isGlobal) {
        $questionWeighting = (float) $httpRequest->request->get('weighting', 0);
    } else {
        $questionWeighting = 0;
        $choiceWeighting = array_map(
            'intval',
            (array) $httpRequest->request->get('c_weighting', [])
        );

        foreach ($questionAnswers as $key => $questionAnswer) {
            if (empty($choiceWeighting[$key])) {
                continue;
            }

            $questionWeighting += $choiceWeighting[$key];

            Database::query(
                "UPDATE $tblQuizAnswer SET ponderation = {$choiceWeighting[$key]}
                    WHERE question_id = {$objQuestion->iid} AND iid = $questionAnswer"
            );
        }
    }

    $objQuestion->updateWeighting($questionWeighting);
    $objQuestion->save($objExercise);

    echo '<script type="text/javascript">window.location.href="'.$adminUrl.'&message=ItemUpdated"</script>';
    exit;
}

if ($questionId) {
    $answers = [];

    for ($i = 1; $i <= $objAnswer->nbrAnswers; $i++) {
        if (false === (bool) $objAnswer->correct[$i]) {
            continue;
        }

        $answers[] = [
            $objAnswer->iid[$i],
            $objAnswer->answer[$i],
            $objAnswer->weighting[$i],
        ];
    }

    $selfUrl = $webCodePath.http_build_query(['mad_admin' => $questionId, 'exerciseId' => $exerciseId]);

    echo Display::page_header(
        get_lang('Question').': '.$objQuestion->selectTitle()
    ); ?>
    <form action="<?php echo $selfUrl; ?>" class="form-horizontal" method="post">
        <div class="form-group">
            <label for="option" class="col-sm-2 control-label"><?php echo get_lang('Answer'); ?></label>
            <div class="col-sm-8">
                <?php echo Display::select(
                    'option',
                    $options,
                    -1,
                    ['data-live-search' => 'true', 'class' => 'form-control selectpicker']
                ); ?>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="button" class="btn btn-default" name="add_answers">
                    <em class="fa fa-plus fa-fw" aria-hidden="true"></em>
                    <?php echo get_lang('Add'); ?>
                </button>
            </div>
        </div>
        <fieldset>
            <legend><?php echo get_lang('Answers'); ?></legend>
            <table class="table table-striped table-hover table-condensed">
                <thead>
                <tr>
                    <th class="text-right"><?php echo get_lang('Number'); ?></th>
                    <th style="width: 85%;"><?php echo get_lang('Answer'); ?></th>
                    <?php if (!$isGlobal) { ?>
                        <th class="text-right"><?php echo get_lang('Weighting'); ?></th>
                    <?php } ?>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </fieldset>
        <?php if ($isGlobal) { ?>
            <div class="form-group">
                <label for="weighting" class="control-label col-sm-2"><?php echo get_lang('Weighting'); ?></label>
                <div class="col-sm-8">
                    <input type="number" required min="0" class="form-control" step="any" id="weighting" name="weighting"
                        value="<?php echo $objQuestion->weighting; ?>">
                </div>
            </div>
        <?php } ?>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-primary" name="submitAnswers" value="submitAnswers">
                    <em class="fa fa-save fa-fw" aria-hidden="true"></em>
                    <?php echo get_lang('AddQuestionToExercise'); ?>
                </button>
            </div>
        </div>
    </form>
    <script>
        $(function () {
            var lines = <?php echo json_encode($options); ?>;
            var answers = <?php echo json_encode($answers); ?>;

            var $txtOption = $('#option');
            var $tBody = $('table tbody');

            $('[name="add_answers"]').on('click', function (e) {
                e.preventDefault();

                var selected = $txtOption.val();

                if ($txtOption.val() < 0) {
                    return;
                }

                answers.push([selected, lines[selected], 0]);

                $txtOption.val(-1).selectpicker('refresh');

                renderList();
            });

            $tBody.on('click', '.btn-remove', function (e) {
                e.preventDefault();

                var index = $(this).data('index');

                answers.splice(index, 1);

                renderList();
            });

            function renderList () {
                var html = '';

                $.each(answers, function (key, line) {
                    var counter = key + 1;

                    html += '<tr><td class="text-right">'
                        + counter + "\n"
                        + '<input type="hidden" name="counter[]" value="' + counter + '">'
                        + '</td><td>'
                        + line[1] + "\n"
                        + '<input type="hidden" name="answer[]" value="' + line[0] + '">'
                        + '</td><td class="text-right">'
                        <?php if (!$isGlobal) { ?>
                            + '<input type="number" required min="0" class="form-control" step="any" name="c_weighting[]" value="' + line[2] + '">'
                            + '</td><td class="text-right">'
                        <?php } ?>
                        + '<button type="button" class="btn btn-default btn-remove" data-index="' + key + '" aria-label="<?php echo get_lang('Remove'); ?>">'
                        + '<?php echo Display::returnFontAwesomeIcon('minus', '', true); ?>'
                        + '</button>'
                        + '</td></tr>';
                });

                $tBody.html(html);
            }

            renderList();
        })
    </script>
    <?php
}
