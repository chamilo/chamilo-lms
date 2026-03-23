<?php

/* For license terms, see /license.txt */

/**
 * Exercise listing page for Test2Pdf.
 */
require_once '../config.php';

api_protect_course_script(true);

$plugin = Test2pdfPlugin::create();

if (!test2pdf_is_plugin_active()) {
    Display::addFlash(
        Display::return_message(
            $plugin->get_lang('PluginDisabledFromAdminPanel'),
            'warning'
        )
    );

    header('Location: '.test2pdf_get_course_home_url());
    exit;
}

$templateName = $plugin->get_lang('ViewExercises');
$tpl = new Template($templateName);

$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$cidreq = api_get_cidreq();

$infoExercise = showExerciseCourse($courseId, $sessionId);

$toolName = $plugin->get_lang('ViewExercises');
$backLabel = get_lang('Back');
$exerciseLabel = $plugin->get_lang('Exercise');
$downloadQuestionsLabel = $plugin->get_lang('DownloadOnlyQuestion');
$downloadAnswersLabel = $plugin->get_lang('DownloadOnlyAnswer');
$downloadAllLabel = $plugin->get_lang('DownloadAll');
$noExerciseLabel = $plugin->get_lang('NoExercise');
$courseHomeUrl = test2pdf_get_course_home_url();

ob_start();
?>
    <div class="w-full max-w-none space-y-6">
        <div class="w-full rounded-2xl border border-gray-25 bg-white shadow-xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-gray-25 bg-gray-10 text-primary">
                        <em class="mdi mdi-file-pdf-box text-3xl" aria-hidden="true"></em>
                    </div>

                    <div class="min-w-0 space-y-1">
                        <h1 class="text-2xl font-semibold text-gray-90">
                            <?php echo htmlspecialchars($toolName, ENT_QUOTES, 'UTF-8'); ?>
                        </h1>
                        <p class="text-body-2 text-fontdisabled">
                            <?php echo htmlspecialchars($exerciseLabel, ENT_QUOTES, 'UTF-8'); ?>:
                            <span class="font-semibold text-gray-90"><?php echo count($infoExercise); ?></span>
                        </p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <a
                        href="<?php echo htmlspecialchars($courseHomeUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-medium text-gray-90 transition hover:bg-gray-10"
                    >
                        <em class="mdi mdi-arrow-left text-base" aria-hidden="true"></em>
                        <span><?php echo htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                </div>
            </div>
        </div>

        <div class="w-full rounded-2xl border border-primary/20 bg-support-2 p-4 text-body-2 text-primary">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="font-semibold">PDF</span>
                <span>—</span>
                <span><?php echo htmlspecialchars($downloadQuestionsLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                <span>,</span>
                <span><?php echo htmlspecialchars($downloadAnswersLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                <span>,</span>
                <span><?php echo htmlspecialchars($downloadAllLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>

        <div class="w-full overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-xl">
            <?php if (!empty($infoExercise)) { ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto divide-y divide-gray-25">
                        <thead class="bg-gray-10">
                        <tr>
                            <th class="px-6 py-4 text-left text-body-2 font-semibold text-gray-90">
                                <?php echo htmlspecialchars($exerciseLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </th>
                            <th class="px-6 py-4 text-center text-body-2 font-semibold text-gray-90">
                                <?php echo htmlspecialchars($downloadQuestionsLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </th>
                            <th class="px-6 py-4 text-center text-body-2 font-semibold text-gray-90">
                                <?php echo htmlspecialchars($downloadAnswersLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </th>
                            <th class="px-6 py-4 text-center text-body-2 font-semibold text-gray-90">
                                <?php echo htmlspecialchars($downloadAllLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-25 bg-white">
                        <?php foreach ($infoExercise as $item) {
                            $quizId = (int) ($item['iid'] ?? 0);
                            $title = htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8');

                            $questionUrl = 'download-pdf.php?'.$cidreq.'&type=question&id_quiz='.$quizId;
                            $answerUrl = 'download-pdf.php?'.$cidreq.'&type=answer&id_quiz='.$quizId;
                            $allUrl = 'download-pdf.php?'.$cidreq.'&type=all&id_quiz='.$quizId;
                            ?>
                            <tr class="hover:bg-gray-15">
                                <td class="px-6 py-4 align-middle">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-10 text-primary">
                                            <em class="mdi mdi-clipboard-text-outline text-lg" aria-hidden="true"></em>
                                        </span>
                                        <span class="text-body-2 font-medium text-gray-90"><?php echo $title; ?></span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center align-middle">
                                    <a
                                        target="_blank"
                                        href="<?php echo htmlspecialchars($questionUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                        title="<?php echo htmlspecialchars($downloadQuestionsLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                        class="inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-medium text-gray-90 transition hover:bg-gray-10"
                                    >
                                        <em class="mdi mdi-file-pdf-box text-base text-danger" aria-hidden="true"></em>
                                        <span>PDF</span>
                                    </a>
                                </td>

                                <td class="px-6 py-4 text-center align-middle">
                                    <a
                                        target="_blank"
                                        href="<?php echo htmlspecialchars($answerUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                        title="<?php echo htmlspecialchars($downloadAnswersLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                        class="inline-flex items-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2 text-body-2 font-medium text-gray-90 transition hover:bg-gray-10"
                                    >
                                        <em class="mdi mdi-file-pdf-box text-base text-danger" aria-hidden="true"></em>
                                        <span>PDF</span>
                                    </a>
                                </td>

                                <td class="px-6 py-4 text-center align-middle">
                                    <a
                                        target="_blank"
                                        href="<?php echo htmlspecialchars($allUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                        title="<?php echo htmlspecialchars($downloadAllLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-body-2 font-medium text-white transition hover:bg-secondary"
                                    >
                                        <em class="mdi mdi-download text-base" aria-hidden="true"></em>
                                        <span>PDF</span>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="p-6">
                    <div class="rounded-2xl border border-warning/20 bg-support-6 px-4 py-3 text-body-2 text-gray-90">
                        <?php echo htmlspecialchars($noExerciseLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
<?php
$content = ob_get_clean();

$tpl->assign('content', $content);
$tpl->display_one_col_template();
