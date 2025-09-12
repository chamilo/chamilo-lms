<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\HttpFoundation\Request as HttpRequest;

$questionId = (int) ($_GET['mad_admin'] ?? 0);
if (!$questionId) {
    api_not_allowed(true);
}

$exerciseId  = $exerciseId  ?? 0;
/** @var Exercise|null $objExercise */
$objExercise = $objExercise ?? null;
/** @var Question|null $objQuestion */
$objQuestion = $objQuestion ?? Question::read($questionId);
if (!$objQuestion) {
    api_not_allowed(true);
}

$isGlobal = ((int) $objQuestion->type === MULTIPLE_ANSWER_DROPDOWN_COMBINATION);

// URLs
$webCodePath         = api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.api_get_cidreq().'&';
$questionsListUrl    = $webCodePath.http_build_query(['exerciseId' => $exerciseId]); // back to question list
$selfUrl             = $webCodePath.http_build_query(['mad_admin' => $questionId, 'exerciseId' => $exerciseId]);
$adminUrlAfterSubmit = $questionsListUrl;

// Load options from Answer with proper course context
$courseId  = api_get_course_int_id();
$objAnswer = new Answer($objQuestion->iid, $courseId, $objExercise);
$objAnswer->read();

// Build <option> list
$options = [];
if ((int) $objAnswer->nbrAnswers > 0) {
    for ($i = 1; $i <= (int) $objAnswer->nbrAnswers; $i++) {
        // Strip markup so Select2 text looks clean
        $label = trim(strip_tags(html_entity_decode((string) $objAnswer->answer[$i], ENT_QUOTES, 'UTF-8')));
        $options[(int) $objAnswer->iid[$i]] = $label !== '' ? $label : get_lang('Untitled');
    }
}

// Fallback: read directly from DB if needed
if (empty($options)) {
    $tblQuizAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);
    $sql = "SELECT iid, answer
        FROM $tblQuizAnswer
        WHERE question_id = {$objQuestion->iid}
        ORDER BY position ASC";
    $res = Database::query($sql);
    while ($row = Database::fetch_array($res)) {
        $label = trim(strip_tags(html_entity_decode((string) $row['answer'], ENT_QUOTES, 'UTF-8')));
        $options[(int) $row['iid']] = $label !== '' ? $label : get_lang('Untitled');
    }
}

// Handle POST
$httpRequest   = HttpRequest::createFromGlobals();
$submitAnswers = $httpRequest->request->has('submitAnswers');

if ($submitAnswers) {
    // Read POST safely: never call ->get() for array params
    $post            = $httpRequest->request->all();
    $questionAnswers = array_map('intval', (array) ($post['answer'] ?? []));
    $tblQuizAnswer   = Database::get_course_table(TABLE_QUIZ_ANSWER);

    // Reset flags/weights
    Database::query(
        "UPDATE $tblQuizAnswer
         SET correct = 0, ponderation = 0
         WHERE question_id = {$objQuestion->iid}"
    );

    if (!empty($questionAnswers)) {
        $in = implode(', ', $questionAnswers);
        Database::query(
            "UPDATE $tblQuizAnswer
             SET correct = 1
             WHERE question_id = {$objQuestion->iid} AND iid IN ($in)"
        );
    }

    if ($isGlobal) {
        // Scalar is fine to read directly with a default
        $questionWeighting = (float) ($post['weighting'] ?? 0);
    } else {
        $questionWeighting = 0.0;
        $choiceWeighting   = array_map('floatval', (array) ($post['c_weighting'] ?? []));
        foreach ($questionAnswers as $k => $answerIid) {
            $w = $choiceWeighting[$k] ?? 0.0;
            if ($w <= 0) { continue; }
            $questionWeighting += $w;

            Database::query(
                "UPDATE $tblQuizAnswer
                 SET ponderation = {$w}
                 WHERE question_id = {$objQuestion->iid} AND iid = {$answerIid}"
            );
        }
    }

    $objQuestion->updateWeighting($questionWeighting);
    $objQuestion->save($objExercise);

    $redirect = $adminUrlAfterSubmit.'&message=ItemUpdated';
    if (!headers_sent()) {
        header('Location: '.$redirect);
        exit;
    }
    echo '<meta http-equiv="refresh" content="0;url='.htmlspecialchars($redirect, ENT_QUOTES).'">';
    echo '<script>location.replace("'.addslashes($redirect).'");</script>';
    exit;
}

// Preload current correct answers
$answers = [];
if ((int) $objAnswer->nbrAnswers > 0) {
    for ($i = 1; $i <= (int) $objAnswer->nbrAnswers; $i++) {
        if (empty($objAnswer->correct[$i])) { continue; }
        $label = trim(strip_tags(html_entity_decode((string) $objAnswer->answer[$i], ENT_QUOTES, 'UTF-8')));
        $answers[] = [
            (int) $objAnswer->iid[$i],
            $label !== '' ? $label : get_lang('Untitled'),
            (float) $objAnswer->weighting[$i],
        ];
    }
} else {
    $tblQuizAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);
    $sql = "SELECT iid, answer, ponderation
            FROM $tblQuizAnswer
            WHERE question_id = {$objQuestion->iid} AND correct = 1
            ORDER BY position ASC";
    $res = Database::query($sql);
    while ($row = Database::fetch_array($res)) {
        $label = trim(strip_tags(html_entity_decode((string) $row['answer'], ENT_QUOTES, 'UTF-8')));
        $answers[] = [
            (int) $row['iid'],
            $label !== '' ? $label : get_lang('Untitled'),
            (float) $row['ponderation'],
        ];
    }
}

// Header
echo Display::page_header(get_lang('Question').': '.$objQuestion->selectTitle());
?>

<!-- Shell -->
<div class="bg-white border border-gray-20 rounded-lg shadow-sm p-5 md:p-6 space-y-5">

    <!-- Top bar -->
    <div class="flex items-center justify-between">
        <a href="<?php echo $questionsListUrl; ?>"
           class="inline-flex items-center gap-2 rounded-md border border-gray-20 bg-white px-3 py-2 text-sm font-medium text-gray-90 hover:bg-gray-15">
            <i class="fa fa-arrow-left" aria-hidden="true"></i>
            <span><?php echo get_lang('Back'); ?></span>
        </a>

        <div class="text-sm text-gray-50">
            <?php echo $isGlobal ? get_lang('Global weighting mode') : get_lang('Per-answer weighting mode'); ?>
        </div>
    </div>

    <form action="<?php echo $selfUrl; ?>" method="post" class="space-y-6">
        <!-- Selector -->
        <div class="grid grid-cols-12 items-end gap-4">
            <label for="option" class="col-span-12 sm:col-span-2 text-sm font-medium text-gray-90">
                <?php echo get_lang('Answer'); ?>
            </label>

            <div class="col-span-12 sm:col-span-10">
                <?php
                echo Display::select(
                    'option',
                    $options,
                    null,
                    [
                        // Select2 enhanced below
                        'data-placeholder' => get_lang('Please select an option'),
                        'class'            => 'ch-select2 w-full',
                    ]
                );
                ?>
                <p class="mt-2 text-tiny text-gray-50">
                    <?php echo get_lang('Selecting an option will add it to the Responses table below.'); ?>
                </p>
            </div>
        </div>

        <!-- Answers table -->
        <fieldset class="space-y-3">
            <legend class="text-sm font-semibold text-gray-90"><?php echo get_lang('Answers'); ?></legend>

            <div class="overflow-x-auto border border-gray-20 rounded-md">
                <table class="min-w-full table-auto text-sm">
                    <thead class="bg-gray-15 text-gray-90">
                    <tr>
                        <th class="px-3 py-2 text-right w-24"><?php echo get_lang('ID'); ?></th>
                        <th class="px-3 py-2 text-left" style="width: <?php echo $isGlobal ? '90%' : '70%'; ?>;">
                            <?php echo get_lang('Answer'); ?>
                        </th>
                        <?php if (!$isGlobal) { ?>
                            <th class="px-3 py-2 text-right w-40"><?php echo get_lang('Score'); ?></th>
                        <?php } ?>
                        <th class="px-3 py-2 text-right w-24">&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-20"></tbody>
                </table>
            </div>
        </fieldset>

        <?php if ($isGlobal) { ?>
            <div class="grid grid-cols-12 items-center gap-4">
                <label for="weighting" class="col-span-12 sm:col-span-2 text-sm font-medium text-gray-90">
                    <?php echo get_lang('Score'); ?>
                </label>
                <div class="col-span-12 sm:col-span-8">
                    <input
                        type="number"
                        required
                        min="0"
                        step="any"
                        id="weighting"
                        name="weighting"
                        value="<?php echo (float) $objQuestion->weighting; ?>"
                        class="w-40 sm:w-56 rounded-md border border-gray-20 px-3 py-2 text-right text-gray-90 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                    />
                </div>
            </div>
        <?php } ?>

        <!-- Actions -->
        <div class="flex items-center gap-3">
            <a href="<?php echo $questionsListUrl; ?>"
               class="inline-flex items-center gap-2 rounded-md border border-gray-20 bg-white px-4 py-2 text-sm font-medium text-gray-90 hover:bg-gray-15">
                <i class="fa fa-times" aria-hidden="true"></i>
                <span><?php echo get_lang('Cancel'); ?></span>
            </a>

            <button type="submit" name="submitAnswers" value="submitAnswers"
                    class="inline-flex items-center gap-2 rounded-md bg-primary text-white hover:bg-primary/90 px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <i class="fa fa-save" aria-hidden="true"></i>
                <span><?php echo get_lang('Add question to exercise'); ?></span>
            </button>
        </div>
    </form>
</div>

<script>
    $(function () {
        // Initialize Select2 and add-on-select behavior
        var $txtOption = $('#option');

        $('.ch-select2').select2({
            width: '100%',
            placeholder: '<?php echo addslashes(get_lang('Please select an option')); ?>',
            allowClear: true
        });

        var lines   = <?php echo json_encode($options,  JSON_UNESCAPED_UNICODE); ?>;
        var answers = <?php echo json_encode($answers,  JSON_UNESCAPED_UNICODE); ?>;

        var $tBody = $('table tbody');

        // Disable options already present in answers at load time
        function syncDisabledOptions() {
            // Enable all first
            $txtOption.find('option').prop('disabled', false);
            // Disable those already chosen
            for (var i = 0; i < answers.length; i++) {
                $txtOption.find('option[value="' + answers[i][0] + '"]').prop('disabled', true);
            }
            // Refresh Select2
            $txtOption.trigger('change.select2');
        }

        // Auto-add on select
        $txtOption.on('select2:select', function (e) {
            var selected = e.params.data.id;
            if (!selected) { return; }

            // Prevent duplicates
            for (var i = 0; i < answers.length; i++) {
                if (String(answers[i][0]) === String(selected)) {
                    $txtOption.val(null).trigger('change');
                    return;
                }
            }

            var label = lines[selected] || $txtOption.find('option[value="' + selected + '"]').text();
            answers.push([selected, label, 0]);

            // Disable selected option and clear
            $txtOption.find('option[value="' + selected + '"]').prop('disabled', true);
            $txtOption.val(null).trigger('change');

            renderList();
        });

        // Remove row handler
        $tBody.on('click', '.btn-remove', function (e) {
            e.preventDefault();
            var index = $(this).data('index');
            var removedId = answers[index][0];
            answers.splice(index, 1);

            // Re-enable the option in Select2
            $txtOption.find('option[value="' + removedId + '"]').prop('disabled', false);
            $txtOption.trigger('change.select2');

            renderList();
        });

        function weightInput(value) {
            return '<input type="number" required min="0" step="any" ' +
                'class="w-28 rounded-md border border-gray-20 px-2 py-1 text-right text-gray-90 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" ' +
                'name="c_weighting[]" value="' + (value || 0) + '">';
        }

        function removeBtn(idx) {
            return '<button type="button" ' +
                'class="btn-remove inline-flex items-center justify-center rounded-md border border-gray-20 px-2 py-1 text-gray-90 hover:bg-danger/10 hover:text-danger" ' +
                'data-index="' + idx + '" aria-label="<?php echo get_lang('Remove'); ?>">' +
                '<?php echo Display::returnFontAwesomeIcon('minus', '', true); ?>' +
                '</button>';
        }

        function renderList () {
            var html = '';
            $.each(answers, function (key, line) {
                var counter = key + 1;
                html += '<tr class="hover:bg-support-2">'
                    + '<td class="px-3 py-2 text-right">' + counter
                    +   '<input type="hidden" name="counter[]" value="' + counter + '"></td>'
                    + '<td class="px-3 py-2">' + (line[1] || '')
                    +   '<input type="hidden" name="answer[]" value="' + line[0] + '"></td>';
                <?php if (!$isGlobal) { ?>
                html += '<td class="px-3 py-2 text-right">' + weightInput(line[2]) + '</td>';
                <?php } ?>
                html += '<td class="px-3 py-2 text-right">' + removeBtn(key) + '</td></tr>';
            });
            $tBody.html(html);
            syncDisabledOptions();
        }

        // Initial paint
        renderList();
    });
</script>
<?php
Display::display_footer();
