<?php
/* For licensing terms, see /license.txt */

/**
 * @todo use formvalidator for the form
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$xajax = new xajax();
$xajax->registerFunction('search_coachs');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

$formSent = 0;
$errorMsg = '';
$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('Session list'),
];
$interbreadcrumb[] = [
    'url' => 'session_category_list.php',
    'name' => get_lang('Sessions categories list'),
];

// Database Table Definitions
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

if (isset($_POST['formSent']) && $_POST['formSent']) {
    $formSent = 1;
    $name = $_POST['name'];
    $year_start = $_POST['year_start'];
    $month_start = $_POST['month_start'];
    $day_start = $_POST['day_start'];
    $year_end = $_POST['year_end'];
    $month_end = $_POST['month_end'];
    $day_end = $_POST['day_end'];

    $return = SessionManager::create_category_session(
        $name,
        $year_start,
        $month_start,
        $day_start,
        $year_end,
        $month_end,
        $day_end
    );

    if ($return == strval(intval($return))) {
        Display::addFlash(Display::return_message(get_lang('The category has been added')));
        header('Location: session_category_list.php');
        exit();
    }
}

$thisYear = date('Y');
$thisMonth = date('m');
$thisDay = date('d');
$tool_name = get_lang('Add category');

//display the header
Display::display_header($tool_name);
if (!empty($return)) {
    echo Display::return_message($return, 'error', false);
}
?>

    <div class="w-full space-y-4">

        <?php
        // Top navigation icons using mdiIcon (same style as other session pages)
        echo '<div class="flex items-center gap-4">';
        echo Display::url(
            Display::getMdiIcon('file-tree-outline', 'ch-tool-icon-gradient', null, 32, get_lang('Sessions categories list')),
            api_get_path(WEB_CODE_PATH).'session/session_category_list.php'
        );
        echo Display::url(
            Display::getMdiIcon('google-classroom', 'ch-tool-icon-gradient', null, 32, get_lang('Training sessions list')),
            api_get_path(WEB_CODE_PATH).'session/session_list.php'
        );
        echo '</div>';
        ?>

        <div class="bg-white border border-gray-20 rounded-lg shadow-sm p-6 md:p-8">

            <div class="max-w-xl">

                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-lg font-semibold text-gray-90">
                        <?php echo $tool_name; ?>
                    </h1>
                    <p class="mt-1 text-caption text-gray-50">
                        <?php echo get_lang('Create a logical group of sessions to keep your training catalogue organized.'); ?>
                    </p>
                </div>

                <form
                    method="post"
                    name="form"
                    action="<?php echo api_get_self(); ?>"
                    class="space-y-6"
                >
                    <input type="hidden" name="formSent" value="1">

                    <!-- Category name -->
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-90">
                            <?php echo get_lang('Category name'); ?>
                        </label>
                        <input
                            type="text"
                            name="name"
                            placeholder="<?php echo get_lang('Category'); ?>"
                            size="50"
                            maxlength="50"
                            value="<?php
                            if ($formSent) {
                                echo api_htmlentities($name, ENT_QUOTES);
                            }
                            ?>"
                            class="block w-full rounded-md border border-gray-25 bg-white px-3 py-2 text-body-2 text-gray-90 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                        >
                    </div>

                    <!-- Info text + toggle -->
                    <div class="space-y-1">
                        <p class="text-caption text-gray-50">
                            <?php echo get_lang('The time limit of a category is referential, will not affect the boundaries of a training session'); ?>
                        </p>
                        <button
                            type="button"
                            class="text-body-2 text-primary hover:underline"
                            onclick="var el = document.getElementById('options'); el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';"
                        >
                            <?php echo get_lang('Add time limit'); ?>
                        </button>
                    </div>

                    <!-- Time limit options -->
                    <div
                        id="options"
                        style="display: none;"
                        class="space-y-4 pt-4 border-t border-gray-20"
                    >
                        <!-- Start date -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-90">
                                <?php echo get_lang('Start date'); ?>
                            </label>
                            <div class="flex flex-wrap items-center gap-2">
                                <select
                                    name="day_start"
                                    class="rounded-md border border-gray-25 bg-white px-2 py-1 text-caption focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                >
                                    <option value="1">01</option>
                                    <option value="2" <?php if ((!$formSent && 2 == $thisDay) || ($formSent && 2 == $day_start)) { echo 'selected="selected"'; } ?> >02</option>
                                    <option value="3" <?php if ((!$formSent && 3 == $thisDay) || ($formSent && 3 == $day_start)) { echo 'selected="selected"'; } ?> >03</option>
                                    <option value="4" <?php if ((!$formSent && 4 == $thisDay) || ($formSent && 4 == $day_start)) { echo 'selected="selected"'; } ?> >04</option>
                                    <option value="5" <?php if ((!$formSent && 5 == $thisDay) || ($formSent && 5 == $day_start)) { echo 'selected="selected"'; } ?> >05</option>
                                    <option value="6" <?php if ((!$formSent && 6 == $thisDay) || ($formSent && 6 == $day_start)) { echo 'selected="selected"'; } ?> >06</option>
                                    <option value="7" <?php if ((!$formSent && 7 == $thisDay) || ($formSent && 7 == $day_start)) { echo 'selected="selected"'; } ?> >07</option>
                                    <option value="8" <?php if ((!$formSent && 8 == $thisDay) || ($formSent && 8 == $day_start)) { echo 'selected="selected"'; } ?> >08</option>
                                    <option value="9" <?php if ((!$formSent && 9 == $thisDay) || ($formSent && 9 == $day_start)) { echo 'selected="selected"'; } ?> >09</option>
                                    <option value="10" <?php if ((!$formSent && 10 == $thisDay) || ($formSent && 10 == $day_start)) { echo 'selected="selected"'; } ?> >10</option>
                                    <option value="11" <?php if ((!$formSent && 11 == $thisDay) || ($formSent && 11 == $day_start)) { echo 'selected="selected"'; } ?> >11</option>
                                    <option value="12" <?php if ((!$formSent && 12 == $thisDay) || ($formSent && 12 == $day_start)) { echo 'selected="selected"'; } ?> >12</option>
                                    <option value="13" <?php if ((!$formSent && 13 == $thisDay) || ($formSent && 13 == $day_start)) { echo 'selected="selected"'; } ?> >13</option>
                                    <option value="14" <?php if ((!$formSent && 14 == $thisDay) || ($formSent && 14 == $day_start)) { echo 'selected="selected"'; } ?> >14</option>
                                    <option value="15" <?php if ((!$formSent && 15 == $thisDay) || ($formSent && 15 == $day_start)) { echo 'selected="selected"'; } ?> >15</option>
                                    <option value="16" <?php if ((!$formSent && 16 == $thisDay) || ($formSent && 16 == $day_start)) { echo 'selected="selected"'; } ?> >16</option>
                                    <option value="17" <?php if ((!$formSent && 17 == $thisDay) || ($formSent && 17 == $day_start)) { echo 'selected="selected"'; } ?> >17</option>
                                    <option value="18" <?php if ((!$formSent && 18 == $thisDay) || ($formSent && 18 == $day_start)) { echo 'selected="selected"'; } ?> >18</option>
                                    <option value="19" <?php if ((!$formSent && 19 == $thisDay) || ($formSent && 19 == $day_start)) { echo 'selected="selected"'; } ?> >19</option>
                                    <option value="20" <?php if ((!$formSent && 20 == $thisDay) || ($formSent && 20 == $day_start)) { echo 'selected="selected"'; } ?> >20</option>
                                    <option value="21" <?php if ((!$formSent && 21 == $thisDay) || ($formSent && 21 == $day_start)) { echo 'selected="selected"'; } ?> >21</option>
                                    <option value="22" <?php if ((!$formSent && 22 == $thisDay) || ($formSent && 22 == $day_start)) { echo 'selected="selected"'; } ?> >22</option>
                                    <option value="23" <?php if ((!$formSent && 23 == $thisDay) || ($formSent && 23 == $day_start)) { echo 'selected="selected"'; } ?> >23</option>
                                    <option value="24" <?php if ((!$formSent && 24 == $thisDay) || ($formSent && 24 == $day_start)) { echo 'selected="selected"'; } ?> >24</option>
                                    <option value="25" <?php if ((!$formSent && 25 == $thisDay) || ($formSent && 25 == $day_start)) { echo 'selected="selected"'; } ?> >25</option>
                                    <option value="26" <?php if ((!$formSent && 26 == $thisDay) || ($formSent && 26 == $day_start)) { echo 'selected="selected"'; } ?> >26</option>
                                    <option value="27" <?php if ((!$formSent && 27 == $thisDay) || ($formSent && 27 == $day_start)) { echo 'selected="selected"'; } ?> >27</option>
                                    <option value="28" <?php if ((!$formSent && 28 == $thisDay) || ($formSent && 28 == $day_start)) { echo 'selected="selected"'; } ?> >28</option>
                                    <option value="29" <?php if ((!$formSent && 29 == $thisDay) || ($formSent && 29 == $day_start)) { echo 'selected="selected"'; } ?> >29</option>
                                    <option value="30" <?php if ((!$formSent && 30 == $thisDay) || ($formSent && 30 == $day_start)) { echo 'selected="selected"'; } ?> >30</option>
                                    <option value="31" <?php if ((!$formSent && 31 == $thisDay) || ($formSent && 31 == $day_start)) { echo 'selected="selected"'; } ?> >31</option>
                                </select>
                                <span>/</span>
                                <select
                                    name="month_start"
                                    class="rounded-md border border-gray-25 bg-white px-2 py-1 text-caption focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                >
                                    <option value="1">01</option>
                                    <option value="2" <?php if ((!$formSent && 2 == $thisMonth) || ($formSent && 2 == $month_start)) { echo 'selected="selected"'; } ?> >02</option>
                                    <option value="3" <?php if ((!$formSent && 3 == $thisMonth) || ($formSent && 3 == $month_start)) { echo 'selected="selected"'; } ?> >03</option>
                                    <option value="4" <?php if ((!$formSent && 4 == $thisMonth) || ($formSent && 4 == $month_start)) { echo 'selected="selected"'; } ?> >04</option>
                                    <option value="5" <?php if ((!$formSent && 5 == $thisMonth) || ($formSent && 5 == $month_start)) { echo 'selected="selected"'; } ?> >05</option>
                                    <option value="6" <?php if ((!$formSent && 6 == $thisMonth) || ($formSent && 6 == $month_start)) { echo 'selected="selected"'; } ?> >06</option>
                                    <option value="7" <?php if ((!$formSent && 7 == $thisMonth) || ($formSent && 7 == $month_start)) { echo 'selected="selected"'; } ?> >07</option>
                                    <option value="8" <?php if ((!$formSent && 8 == $thisMonth) || ($formSent && 8 == $month_start)) { echo 'selected="selected"'; } ?> >08</option>
                                    <option value="9" <?php if ((!$formSent && 9 == $thisMonth) || ($formSent && 9 == $month_start)) { echo 'selected="selected"'; } ?> >09</option>
                                    <option value="10" <?php if ((!$formSent && 10 == $thisMonth) || ($formSent && 10 == $month_start)) { echo 'selected="selected"'; } ?> >10</option>
                                    <option value="11" <?php if ((!$formSent && 11 == $thisMonth) || ($formSent && 11 == $month_start)) { echo 'selected="selected"'; } ?> >11</option>
                                    <option value="12" <?php if ((!$formSent && 12 == $thisMonth) || ($formSent && 12 == $month_start)) { echo 'selected="selected"'; } ?> >12</option>
                                </select>
                                <span>/</span>
                                <select
                                    name="year_start"
                                    class="rounded-md border border-gray-25 bg-white px-2 py-1 text-caption focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                >
                                    <?php
                                    for ($i = $thisYear - 5; $i <= ($thisYear + 5); $i++) {
                                        ?>
                                        <option value="<?php echo $i; ?>" <?php if ((!$formSent && $thisYear == $i) || ($formSent && $year_start == $i)) { echo 'selected="selected"'; } ?> >
                                            <?php echo $i; ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- End date -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-90">
                                <?php echo get_lang('End date'); ?>
                            </label>
                            <div class="flex flex-wrap items-center gap-2">
                                <select
                                    name="day_end"
                                    class="rounded-md border border-gray-25 bg-white px-2 py-1 text-caption focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                >
                                    <option value="0">--</option>
                                    <option value="1">01</option>
                                    <option value="2">02</option>
                                    <option value="3">03</option>
                                    <option value="4">04</option>
                                    <option value="5">05</option>
                                    <option value="6">06</option>
                                    <option value="7">07</option>
                                    <option value="8">08</option>
                                    <option value="9">09</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                    <option value="13">13</option>
                                    <option value="14">14</option>
                                    <option value="15">15</option>
                                    <option value="16">16</option>
                                    <option value="17">17</option>
                                    <option value="18">18</option>
                                    <option value="19">19</option>
                                    <option value="20">20</option>
                                    <option value="21">21</option>
                                    <option value="22">22</option>
                                    <option value="23">23</option>
                                    <option value="24">24</option>
                                    <option value="25">25</option>
                                    <option value="26">26</option>
                                    <option value="27">27</option>
                                    <option value="28">28</option>
                                    <option value="29">29</option>
                                    <option value="30">30</option>
                                    <option value="31">31</option>
                                </select>
                                <span>/</span>
                                <select
                                    name="month_end"
                                    class="rounded-md border border-gray-25 bg-white px-2 py-1 text-caption focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                >
                                    <option value="0">--</option>
                                    <option value="1">01</option>
                                    <option value="2">02</option>
                                    <option value="3">03</option>
                                    <option value="4">04</option>
                                    <option value="5">05</option>
                                    <option value="6">06</option>
                                    <option value="7">07</option>
                                    <option value="8">08</option>
                                    <option value="9">09</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                </select>
                                <span>/</span>
                                <select
                                    name="year_end"
                                    class="rounded-md border border-gray-25 bg-white px-2 py-1 text-caption focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                >
                                    <option value="0">----</option>
                                    <?php
                                    for ($i = $thisYear - 5; $i <= ($thisYear + 5); $i++) {
                                        ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Submit button -->
                    <div>
                        <button
                            class="btn btn--success inline-flex items-center justify-center px-6 py-2"
                            type="submit"
                            value="<?php echo get_lang('Add category'); ?>"
                        >
                            <em class="fa fa-plus mr-2"></em>
                            <span><?php echo get_lang('Add category'); ?></span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        function setDisable(select) {
            // Keep original helper to enable/disable date fields if used elsewhere
            document.form.day_start.disabled = (select.checked) ? true : false;
            document.form.month_start.disabled = (select.checked) ? true : false;
            document.form.year_start.disabled = (select.checked) ? true : false;
            document.form.day_end.disabled = (select.checked) ? true : false;
            document.form.month_end.disabled = (select.checked) ? true : false;
            document.form.year_end.disabled = (select.checked) ? true : false;
        }
    </script>
<?php
Display::display_footer();
