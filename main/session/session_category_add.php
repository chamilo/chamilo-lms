<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.admin
 *
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
    'name' => get_lang('SessionList'),
];
$interbreadcrumb[] = [
    'url' => "session_category_list.php",
    "name" => get_lang('ListSessionCategory'),
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
        Display::addFlash(Display::return_message(get_lang('SessionCategoryAdded')));
        header('Location: session_category_list.php');
        exit();
    }
}
$thisYear = date('Y');
$thisMonth = date('m');
$thisDay = date('d');
$tool_name = get_lang('AddACategory');

//display the header
Display::display_header($tool_name);
if (!empty($return)) {
    echo Display::return_message($return, 'error', false);
}
?>
<div class="row">
    <div class="col-md-12">
        <form method="post" name="form" action="<?php echo api_get_self(); ?>" class="form-horizontal">
            <input type="hidden" name="formSent" value="1">
            <legend><?php echo $tool_name; ?></legend>
            <div class="form-group">
                <label class="col-sm-3 control-label"><?php echo get_lang('SessionCategoryName'); ?></label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="name" placeholder="<?php echo get_lang('Category'); ?>" size="50" maxlength="50" value="<?php if ($formSent) {
    echo api_htmlentities($name, ENT_QUOTES, $charset);
} ?>">
                </div>
                <div class="col-md-3"></div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <?php echo get_lang('TheTimeLimitsAreReferential'); ?> <a href="javascript://" onclick="if(document.getElementById('options').style.display == 'none'){document.getElementById('options').style.display = 'block';}else{document.getElementById('options').style.display = 'none';}"><?php echo get_lang('AddTimeLimit'); ?></a>
                </div>
                <div class="col-md-3"></div>
            </div>

            <div style="display: none" id="options">

            <div class="form-group">
                <label class="col-sm-3 control-label"><?php echo get_lang('DateStart'); ?></label>
                <div class="col-sm-6">
                    <select name="day_start">
                        <option value="1">01</option>
                        <option value="2" <?php if ((!$formSent && $thisDay == 2) || ($formSent && $day_start == 2)) {
    echo 'selected="selected"';
} ?> >02</option>
                        <option value="3" <?php if ((!$formSent && $thisDay == 3) || ($formSent && $day_start == 3)) {
    echo 'selected="selected"';
} ?> >03</option>
                        <option value="4" <?php if ((!$formSent && $thisDay == 4) || ($formSent && $day_start == 4)) {
    echo 'selected="selected"';
} ?> >04</option>
                        <option value="5" <?php if ((!$formSent && $thisDay == 5) || ($formSent && $day_start == 5)) {
    echo 'selected="selected"';
} ?> >05</option>
                        <option value="6" <?php if ((!$formSent && $thisDay == 6) || ($formSent && $day_start == 6)) {
    echo 'selected="selected"';
} ?> >06</option>
                        <option value="7" <?php if ((!$formSent && $thisDay == 7) || ($formSent && $day_start == 7)) {
    echo 'selected="selected"';
} ?> >07</option>
                        <option value="8" <?php if ((!$formSent && $thisDay == 8) || ($formSent && $day_start == 8)) {
    echo 'selected="selected"';
} ?> >08</option>
                        <option value="9" <?php if ((!$formSent && $thisDay == 9) || ($formSent && $day_start == 9)) {
    echo 'selected="selected"';
} ?> >09</option>
                        <option value="10" <?php if ((!$formSent && $thisDay == 10) || ($formSent && $day_start == 10)) {
    echo 'selected="selected"';
} ?> >10</option>
                        <option value="11" <?php if ((!$formSent && $thisDay == 11) || ($formSent && $day_start == 11)) {
    echo 'selected="selected"';
} ?> >11</option>
                        <option value="12" <?php if ((!$formSent && $thisDay == 12) || ($formSent && $day_start == 12)) {
    echo 'selected="selected"';
} ?> >12</option>
                        <option value="13" <?php if ((!$formSent && $thisDay == 13) || ($formSent && $day_start == 13)) {
    echo 'selected="selected"';
} ?> >13</option>
                        <option value="14" <?php if ((!$formSent && $thisDay == 14) || ($formSent && $day_start == 14)) {
    echo 'selected="selected"';
} ?> >14</option>
                        <option value="15" <?php if ((!$formSent && $thisDay == 15) || ($formSent && $day_start == 15)) {
    echo 'selected="selected"';
} ?> >15</option>
                        <option value="16" <?php if ((!$formSent && $thisDay == 16) || ($formSent && $day_start == 16)) {
    echo 'selected="selected"';
} ?> >16</option>
                        <option value="17" <?php if ((!$formSent && $thisDay == 17) || ($formSent && $day_start == 17)) {
    echo 'selected="selected"';
} ?> >17</option>
                        <option value="18" <?php if ((!$formSent && $thisDay == 18) || ($formSent && $day_start == 18)) {
    echo 'selected="selected"';
} ?> >18</option>
                        <option value="19" <?php if ((!$formSent && $thisDay == 19) || ($formSent && $day_start == 19)) {
    echo 'selected="selected"';
} ?> >19</option>
                        <option value="20" <?php if ((!$formSent && $thisDay == 20) || ($formSent && $day_start == 20)) {
    echo 'selected="selected"';
} ?> >20</option>
                        <option value="21" <?php if ((!$formSent && $thisDay == 21) || ($formSent && $day_start == 21)) {
    echo 'selected="selected"';
} ?> >21</option>
                        <option value="22" <?php if ((!$formSent && $thisDay == 22) || ($formSent && $day_start == 22)) {
    echo 'selected="selected"';
} ?> >22</option>
                        <option value="23" <?php if ((!$formSent && $thisDay == 23) || ($formSent && $day_start == 23)) {
    echo 'selected="selected"';
} ?> >23</option>
                        <option value="24" <?php if ((!$formSent && $thisDay == 24) || ($formSent && $day_start == 24)) {
    echo 'selected="selected"';
} ?> >24</option>
                        <option value="25" <?php if ((!$formSent && $thisDay == 25) || ($formSent && $day_start == 25)) {
    echo 'selected="selected"';
} ?> >25</option>
                        <option value="26" <?php if ((!$formSent && $thisDay == 26) || ($formSent && $day_start == 26)) {
    echo 'selected="selected"';
} ?> >26</option>
                        <option value="27" <?php if ((!$formSent && $thisDay == 27) || ($formSent && $day_start == 27)) {
    echo 'selected="selected"';
} ?> >27</option>
                        <option value="28" <?php if ((!$formSent && $thisDay == 28) || ($formSent && $day_start == 28)) {
    echo 'selected="selected"';
} ?> >28</option>
                        <option value="29" <?php if ((!$formSent && $thisDay == 29) || ($formSent && $day_start == 29)) {
    echo 'selected="selected"';
} ?> >29</option>
                        <option value="30" <?php if ((!$formSent && $thisDay == 30) || ($formSent && $day_start == 30)) {
    echo 'selected="selected"';
} ?> >30</option>
                        <option value="31" <?php if ((!$formSent && $thisDay == 31) || ($formSent && $day_start == 31)) {
    echo 'selected="selected"';
} ?> >31</option>
                    </select>
                    /
                    <select name="month_start">
                          <option value="1">01</option>
                          <option value="2" <?php if ((!$formSent && $thisMonth == 2) || ($formSent && $month_start == 2)) {
    echo 'selected="selected"';
} ?> >02</option>
                          <option value="3" <?php if ((!$formSent && $thisMonth == 3) || ($formSent && $month_start == 3)) {
    echo 'selected="selected"';
} ?> >03</option>
                          <option value="4" <?php if ((!$formSent && $thisMonth == 4) || ($formSent && $month_start == 4)) {
    echo 'selected="selected"';
} ?> >04</option>
                          <option value="5" <?php if ((!$formSent && $thisMonth == 5) || ($formSent && $month_start == 5)) {
    echo 'selected="selected"';
} ?> >05</option>
                          <option value="6" <?php if ((!$formSent && $thisMonth == 6) || ($formSent && $month_start == 6)) {
    echo 'selected="selected"';
} ?> >06</option>
                          <option value="7" <?php if ((!$formSent && $thisMonth == 7) || ($formSent && $month_start == 7)) {
    echo 'selected="selected"';
} ?> >07</option>
                          <option value="8" <?php if ((!$formSent && $thisMonth == 8) || ($formSent && $month_start == 8)) {
    echo 'selected="selected"';
} ?> >08</option>
                          <option value="9" <?php if ((!$formSent && $thisMonth == 9) || ($formSent && $month_start == 9)) {
    echo 'selected="selected"';
} ?> >09</option>
                          <option value="10" <?php if ((!$formSent && $thisMonth == 10) || ($formSent && $month_start == 10)) {
    echo 'selected="selected"';
} ?> >10</option>
                          <option value="11" <?php if ((!$formSent && $thisMonth == 11) || ($formSent && $month_start == 11)) {
    echo 'selected="selected"';
} ?> >11</option>
                          <option value="12" <?php if ((!$formSent && $thisMonth == 12) || ($formSent && $month_start == 12)) {
    echo 'selected="selected"';
} ?> >12</option>
                    </select>
                    /
                      <select name="year_start">
                      <?php
                      for ($i = $thisYear - 5; $i <= ($thisYear + 5); $i++) {
                          ?>
                              <option value="<?php echo $i; ?>" <?php if ((!$formSent && $thisYear == $i) || ($formSent && $year_start == $i)) {
                              echo 'selected="selected"';
                          } ?> ><?php echo $i; ?></option>
                      <?php
                      }
                      ?>
                        </select>
                </div>
                <div class="col-md-3"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label"><?php echo get_lang('DateEnd'); ?></label>
                <div class="col-sm-6">
                    <select name="day_end">
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
                  /
                  <select name="month_end">
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
                  /
                <select name="year_end">
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
                <div class="col-md-3">

                </div>
            </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <button class="btn btn-success" type="submit" value="<?php echo get_lang('AddACategory'); ?>"><em class="fa fa-plus"></em> <?php echo get_lang('AddACategory'); ?></button>
                </div>
                <div class="col-md-3"></div>
            </div>
        </form>
</div>
<script>
function setDisable(select) {
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
