<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

$userInfo = api_get_user_info();
$userInfo['user_id'] = (int) $userInfo['user_id'];
$id = (int) $_GET['id'];
$formSent = 0;
$errorMsg = '';

// Database Table Definitions
$tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
$tool_name = get_lang('Edit session category');
$interbreadcrumb[] = [
    'url' => 'session_list.php',
    "name" => get_lang('Session list'),
];
$interbreadcrumb[] = [
    'url' => "session_category_list.php",
    "name" => get_lang('Sessions categories list'),
];

$sql = "SELECT * FROM $tbl_session_category WHERE id='".$id."' ORDER BY name";
$result = Database::query($sql);
if (!$infos = Database::fetch_array($result)) {
    header('Location: session_list.php');
    exit();
}
$year_start = $month_start = $day_start = null;
$year_end = $month_end = $day_end = null;

if ($infos['date_start']) {
    list($year_start, $month_start, $day_start) = explode('-', $infos['date_start']);
}

if ($infos['date_end']) {
    list($year_end, $month_end, $day_end) = explode('-', $infos['date_end']);
}

if (!api_is_platform_admin() && !SessionManager::sessionHasSessionAdmin($id, $userInfo['user_id']) && !api_is_session_admin()) {
    api_not_allowed(true);
}

if (isset($_POST['formSent']) && $_POST['formSent']) {
    $formSent = 1;
    $name = $_POST['name'];
    $year_start = $_POST['year_start'];
    $month_start = $_POST['month_start'];
    $day_start = $_POST['day_start'];
    $year_end = $_POST['year_end'];
    $month_end = $_POST['month_end'];
    $day_end = $_POST['day_end'];
    $return = SessionManager::edit_category_session(
        $id,
        $name,
        $year_start,
        $month_start,
        $day_start,
        $year_end,
        $month_end,
        $day_end
    );
    if ($return == strval(intval($return))) {
        Display::addFlash(Display::return_message(get_lang('Category update')));
        header('Location: session_category_list.php');
        exit();
    }
}

$thisYear = date('Y');
$thisMonth = date('m');
$thisDay = date('d');

// display the header
Display::display_header($tool_name);
if (!empty($return)) {
    echo Display::return_message($return, 'error', false);
}
?>
<div class="row">
    <div class="col-md-12">
        <form method="post" name="form" action="<?php echo api_get_self(); ?>?id=<?php echo $id; ?>" class="form-horizontal">
        <input type="hidden" name="formSent" value="1">
        <legend><?php echo $tool_name; ?> </legend>
        <div class="form-group">
            <label class="col-sm-3 control-label"><?php echo get_lang('Name'); ?></label>
            <div class="col-sm-6">
                <input class="form-control" type="text" name="name" size="50" maxlength="50" value="<?php if ($formSent) {
    echo api_htmlentities($name, ENT_QUOTES);
} else {
    echo api_htmlentities($infos['name'], ENT_QUOTES);
} ?>">
            </div>
            <div class="col-sm-3"></div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-6">
                <?php echo get_lang('The time limit of a category is referential, will not affect the boundaries of a training session'); ?>
                <a href="javascript://" onclick="if(document.getElementById('options').style.display == 'none'){document.getElementById('options').style.display = 'block';}else{document.getElementById('options').style.display = 'none';}">
                    <?php echo get_lang('Edit time limit'); ?></a>
            </div>
        </div>
        <div style="display: <?php echo $formSent ? 'display' : 'none'; ?>;" id="options">
            <div class="form-group">
                <label class="col-sm-3 control-label"><?php echo get_lang('Start date'); ?></label>
                <div class="col-sm-6">
                    <select name="day_start">
                        <option value="1">01</option>
                        <option value="2" <?php if (2 == $day_start) {
    echo 'selected="selected"';
} ?> >02</option>
                        <option value="3" <?php if (3 == $day_start) {
    echo 'selected="selected"';
} ?> >03</option>
                        <option value="4" <?php if (4 == $day_start) {
    echo 'selected="selected"';
} ?> >04</option>
                        <option value="5" <?php if (5 == $day_start) {
    echo 'selected="selected"';
} ?> >05</option>
                        <option value="6" <?php if (6 == $day_start) {
    echo 'selected="selected"';
} ?> >06</option>
                        <option value="7" <?php if (7 == $day_start) {
    echo 'selected="selected"';
} ?> >07</option>
                        <option value="8" <?php if (8 == $day_start) {
    echo 'selected="selected"';
} ?> >08</option>
                        <option value="9" <?php if (9 == $day_start) {
    echo 'selected="selected"';
} ?> >09</option>
                        <option value="10" <?php if (10 == $day_start) {
    echo 'selected="selected"';
} ?> >10</option>
                        <option value="11" <?php if (11 == $day_start) {
    echo 'selected="selected"';
} ?> >11</option>
                        <option value="12" <?php if (12 == $day_start) {
    echo 'selected="selected"';
} ?> >12</option>
                        <option value="13" <?php if (13 == $day_start) {
    echo 'selected="selected"';
} ?> >13</option>
                        <option value="14" <?php if (14 == $day_start) {
    echo 'selected="selected"';
} ?> >14</option>
                        <option value="15" <?php if (15 == $day_start) {
    echo 'selected="selected"';
} ?> >15</option>
                        <option value="16" <?php if (16 == $day_start) {
    echo 'selected="selected"';
} ?> >16</option>
                        <option value="17" <?php if (17 == $day_start) {
    echo 'selected="selected"';
} ?> >17</option>
                        <option value="18" <?php if (18 == $day_start) {
    echo 'selected="selected"';
} ?> >18</option>
                        <option value="19" <?php if (19 == $day_start) {
    echo 'selected="selected"';
} ?> >19</option>
                        <option value="20" <?php if (20 == $day_start) {
    echo 'selected="selected"';
} ?> >20</option>
                        <option value="21" <?php if (21 == $day_start) {
    echo 'selected="selected"';
} ?> >21</option>
                        <option value="22" <?php if (22 == $day_start) {
    echo 'selected="selected"';
} ?> >22</option>
                        <option value="23" <?php if (23 == $day_start) {
    echo 'selected="selected"';
} ?> >23</option>
                        <option value="24" <?php if (24 == $day_start) {
    echo 'selected="selected"';
} ?> >24</option>
                        <option value="25" <?php if (25 == $day_start) {
    echo 'selected="selected"';
} ?> >25</option>
                        <option value="26" <?php if (26 == $day_start) {
    echo 'selected="selected"';
} ?> >26</option>
                        <option value="27" <?php if (27 == $day_start) {
    echo 'selected="selected"';
} ?> >27</option>
                        <option value="28" <?php if (28 == $day_start) {
    echo 'selected="selected"';
} ?> >28</option>
                        <option value="29" <?php if (29 == $day_start) {
    echo 'selected="selected"';
} ?> >29</option>
                        <option value="30" <?php if (30 == $day_start) {
    echo 'selected="selected"';
} ?> >30</option>
                        <option value="31" <?php if (31 == $day_start) {
    echo 'selected="selected"';
} ?> >31</option>
                  </select>
                  /
                  <select name="month_start">
                        <option value="1">01</option>
                        <option value="2" <?php if (2 == $month_start) {
    echo 'selected="selected"';
} ?> >02</option>
                        <option value="3" <?php if (3 == $month_start) {
    echo 'selected="selected"';
} ?> >03</option>
                        <option value="4" <?php if (4 == $month_start) {
    echo 'selected="selected"';
} ?> >04</option>
                        <option value="5" <?php if (5 == $month_start) {
    echo 'selected="selected"';
} ?> >05</option>
                        <option value="6" <?php if (6 == $month_start) {
    echo 'selected="selected"';
} ?> >06</option>
                        <option value="7" <?php if (7 == $month_start) {
    echo 'selected="selected"';
} ?> >07</option>
                        <option value="8" <?php if (8 == $month_start) {
    echo 'selected="selected"';
} ?> >08</option>
                        <option value="9" <?php if (9 == $month_start) {
    echo 'selected="selected"';
} ?> >09</option>
                        <option value="10" <?php if (10 == $month_start) {
    echo 'selected="selected"';
} ?> >10</option>
                        <option value="11" <?php if (11 == $month_start) {
    echo 'selected="selected"';
} ?> >11</option>
                        <option value="12" <?php if (12 == $month_start) {
    echo 'selected="selected"';
} ?> >12</option>
                  </select>
                  /
                <select name="year_start">
                        <?php
                        for ($i = $thisYear - 5; $i <= ($thisYear + 5); $i++) {
                            ?>
                                <option value="<?php echo $i; ?>" <?php if ($year_start == $i) {
                                echo 'selected="selected"';
                            } ?> ><?php echo $i; ?></option>
                        <?php
                        } ?>
                </select>
                </div>
                <div class="col-sm-3"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label"><?php echo get_lang('End date'); ?></label>
                <div class="col-sm-6">
                    <select name="day_end">
                        <option value="0">--</option>
                        <option value="1" <?php if (1 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >01</option>
                        <option value="2" <?php if (2 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >02</option>
                        <option value="3" <?php if (3 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >03</option>
                        <option value="4" <?php if (4 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >04</option>
                        <option value="5" <?php if (5 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >05</option>
                        <option value="6" <?php if (6 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >06</option>
                        <option value="7" <?php if (7 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >07</option>
                        <option value="8" <?php if (8 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >08</option>
                        <option value="9" <?php if (9 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >09</option>
                        <option value="10" <?php if (10 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >10</option>
                        <option value="11" <?php if (11 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >11</option>
                        <option value="12" <?php if (12 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >12</option>
                        <option value="13" <?php if (13 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >13</option>
                        <option value="14" <?php if (14 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >14</option>
                        <option value="15" <?php if (15 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >15</option>
                        <option value="16" <?php if (16 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >16</option>
                        <option value="17" <?php if (17 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >17</option>
                        <option value="18" <?php if (18 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >18</option>
                        <option value="19" <?php if (19 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >19</option>
                        <option value="20" <?php if (20 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >20</option>
                        <option value="21" <?php if (21 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >21</option>
                        <option value="22" <?php if (22 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >22</option>
                        <option value="23" <?php if (23 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >23</option>
                        <option value="24" <?php if (24 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >24</option>
                        <option value="25" <?php if (25 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >25</option>
                        <option value="26" <?php if (26 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >26</option>
                        <option value="27" <?php if (27 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >27</option>
                        <option value="28" <?php if (28 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >28</option>
                        <option value="29" <?php if (29 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >29</option>
                        <option value="30" <?php if (30 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >30</option>
                        <option value="31" <?php if (31 == $day_end) {
                            echo 'selected="selected"';
                        } ?> >31</option>
                  </select>
                  /
                  <select name="month_end">
                        <option value="0">--</option>
                        <option value="1" <?php if (1 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >01</option>
                        <option value="2" <?php if (2 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >02</option>
                        <option value="3" <?php if (3 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >03</option>
                        <option value="4" <?php if (4 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >04</option>
                        <option value="5" <?php if (5 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >05</option>
                        <option value="6" <?php if (6 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >06</option>
                        <option value="7" <?php if (7 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >07</option>
                        <option value="8" <?php if (8 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >08</option>
                        <option value="9" <?php if (9 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >09</option>
                        <option value="10" <?php if (10 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >10</option>
                        <option value="11" <?php if (11 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >11</option>
                        <option value="12" <?php if (12 == $month_end) {
                            echo 'selected="selected"';
                        } ?> >12</option>
                  </select>
                  /
                  <select name="year_end">
                        <option value="0">----</option>
                        <?php
                        for ($i = $thisYear - 5; $i <= ($thisYear + 5); $i++) {
                            ?>
                         <option value="<?php echo $i; ?>" <?php if ($year_end == $i) {
                                echo 'selected="selected"';
                            } ?> ><?php echo $i; ?></option>
                        <?php
                        } ?>
                 </select>
                </div>
                <div class="col-sm-3"></div>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-6">
                <button class="btn btn--success" type="submit" value="<?php echo get_lang('Edit'); ?>">
                    <?php echo get_lang('Edit'); ?>
                </button>
            </div>
        </div>
    </form>
    </div>
</div>


<script>
<?php if ("0000" == $year_start) {
                            echo "setDisable(document.form.nolimit);\r\n";
                        } ?>
function setDisable(select){
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
