<?php
/* For licensing terms, see /license.txt */

/**
 * Index of the admin tools.
 */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;

// resetting the course id
$cidReset = true;

// including some necessary chamilo files
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

$nameTools = get_lang('Administration');

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => $nameTools];

// setting the name of the tool
$nameTools = get_lang('Data filler');

$output = [];
if (!empty($_GET['fill'])) {
    switch ($_GET['fill']) {
        case 'users':
            require __DIR__.'/../../../tests/datafiller/fill_users.php';
            $output = fill_users();
            break;
        case 'courses':
            require __DIR__.'/../../../tests/datafiller/fill_courses.php';
            $output = fill_courses();
            break;
        default:
            break;
    }
}

// Displaying the header
Display::display_header($nameTools);
?>
    <div class="w-full max-w-none px-6 py-6">

        <?php if (!empty($output)) : ?>
            <?php
            $reportTitle = $output[0]['title'] ?? get_lang('Report');
            ?>

            <div class="mb-4 rounded-xl border border-info/30 bg-info/10 px-4 py-3">
                <p class="font-medium text-gray-90"><?php echo htmlspecialchars($reportTitle); ?></p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
                <?php foreach ($output as $i => $line) :
                    if ($i === 0) { continue; }
                    $title      = $line['line-init'] ?? '';
                    $statusText = (string)($line['line-info'] ?? '');
                    $statusCode = $line['status'] ?? null;

                    if ($statusCode === null) {
                        $isOk  = preg_match('/(Ajout|Added|Añadid|Agregad|OK|Success)/i', $statusText);
                        $isErr = preg_match('/(Non|Not|Error|Erreur|No|Failed|Existe|already)/i', $statusText);
                        $statusCode = $isOk ? 'ok' : ($isErr ? 'error' : 'exists');
                    }
                    switch ($statusCode) {
                        case 'ok':
                            $badge = 'bg-success/10 text-success border-success/30';
                            $dot   = 'bg-success';
                            break;
                        case 'error':
                            $badge = 'bg-danger/10 text-danger border-danger/30';
                            $dot   = 'bg-danger';
                            break;
                        case 'exists':
                        default:
                            $badge = 'bg-warning/10 text-warning border-warning/30';
                            $dot   = 'bg-warning';
                            break;
                    }
                    ?>
                    <div class="flex items-center justify-between border-b border-gray-25 px-4 py-3 last:border-b-0">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-1 inline-flex h-2.5 w-2.5 flex-none rounded-full <?php echo $dot; ?>"></span>
                            <span class="truncate font-medium text-gray-90"><?php echo htmlspecialchars($title); ?></span>
                        </div>
                        <span class="ml-4 inline-flex items-center rounded-full border px-3 py-1 text-sm <?php echo $badge; ?>">
            <?php echo htmlspecialchars($statusText); ?>
        </span>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

        <section class="mt-8 rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-support-2">
                    <?php echo Display::getMdiIcon(ActionIcon::FILL, 'h-6 w-6 text-info', null, ICON_SIZE_SMALL, get_lang('Data filler')); ?>
                </div>
                <div>
                    <h4 class="text-lg font-semibold text-gray-90"><?php echo get_lang('Data filler'); ?></h4>
                    <p class="mt-1 text-sm text-gray-50">
                        <?php echo get_lang('This section is only visible on installations from source code, not in packaged versions of the platform. It will allow you to quickly populate your platform with test data. Use with care (data is really inserted) and only on development or testing installations.'); ?>
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <a href="filler.php?fill=users"
                   class="group flex items-center justify-between rounded-xl border border-gray-25 bg-white p-4 hover:bg-gray-10">
                    <div class="flex items-center gap-3">
                        <?php
                        echo Display::getMdiIcon(
                            ObjectIcon::USER,
                            'h-5 w-5 text-primary',
                            null,
                            ICON_SIZE_SMALL,
                            get_lang('Fill users')
                        );
                        ?>
                        <span class="font-medium text-gray-90"><?php echo get_lang('Fill users'); ?></span>
                    </div>
                    <span class="rounded-md bg-primary/10 px-2 py-1 text-xs font-medium text-primary">
          <?php echo get_lang('Run'); ?>
        </span>
                </a>

                <a href="filler.php?fill=courses"
                   class="group flex items-center justify-between rounded-xl border border-gray-25 bg-white p-4 hover:bg-gray-10">
                    <div class="flex items-center gap-3">
                        <?php
                        echo Display::getMdiIcon(
                            ObjectIcon::COURSE,
                            'h-5 w-5 text-secondary',
                            null,
                            ICON_SIZE_SMALL,
                            get_lang('Fill courses')
                        );
                        ?>
                        <span class="font-medium text-gray-90"><?php echo get_lang('Fill courses'); ?></span>
                    </div>
                    <span class="rounded-md bg-secondary/10 px-2 py-1 text-xs font-medium text-secondary">
          <?php echo get_lang('Run'); ?>
        </span>
                </a>
            </div>
        </section>
    </div>

<?php
Display::display_footer();
