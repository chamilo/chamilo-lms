<header id="cm-header">
    <!-- Topbar -->
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-md-3">
                <div class="logo">
                    <a href="<?php echo api_get_path(WEB_PATH); ?>"><?php echo return_logo(); ?></a>
                </div>
            </div>
            <div class="col-xs-12 col-md-9">
                <div class="row">
                    <div class="col-sm-4">
                    </div>
                    <div class="col-sm-3">
                    </div>
                    <div class="col-sm-5">
                        <ol class="header-ol">
                            <?php echo returnNotificationMenu(); ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Fixed navbar -->
    <nav class="navbar navbar-default"></nav>
    <div class="nav-tools">

    </div>
</header>

<div class="container-fluid">

    <h2><?php echo Display::page_header(get_lang('AttendanceSheetReport')); ?></h2>

    <h3><?php echo $attendanceName; ?> <span class="label label-default"><?php echo $attendanceCalendar['date']; ?></span></h3>

    <div class="well mt-2">
        <p><?php echo get_lang('Course').' : '.$courseName; ?></p>
        <p><?php echo get_lang('Trainer').' : '.$trainer; ?></p>
    </div>

    <?php if (!empty($users_in_course)) { ?>

        <div class="input-group">
            <input type="text" id="search-user" onkeyup="searchUser()" />
        </div>

        <form method="post" action="index.php?action=attendance_sheet_add&<?php echo api_get_cidreq().$param_filter; ?>&attendance_id=<?php echo $attendance_id; ?>" >

            <table id="table-user-calendar" class="table table-hover">
                <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users_in_course as $user) {
    $attendance = new Attendance();
    $signature = $attendance->getSignature($user['user_id'], $calendarId);
    $signed = !empty($signature);
    $isBlocked = $attendance->isCalendarBlocked($calendarId); ?>
                    <tr>
                        <td>
                            <?php
                                if ($signed) {
                                    echo Display::return_icon('checkbox_on.png', get_lang('Presence'), null, ICON_SIZE_TINY);
                                } else {
                                    echo Display::return_icon('checkbox_off.png', get_lang('Presence'), null, ICON_SIZE_TINY);
                                } ?>
                        </td>
                        <td><?php echo api_get_person_name($user['firstname'], $user['lastname']); ?></td>
                        <td>
                            <?php
                                if (!$isBlocked) {
                                    if ($signed) {
                                        echo '<div class="list-data">
                                            <span class="item"></span>
                                            <a id="sign-'.$user['user_id'].'-'.$calendarId.'" class="btn btn-primary attendance-sign-view" href="javascript:void(0)">
                                                <em class="fa fa-search"></em> '.get_lang('SignView').'
                                            </a>
                                        </div>';
                                    } else {
                                        echo '<input type="hidden" name="check_presence['.$calendarId.'][]" value="'.$user['user_id'].'" />';
                                        echo '<div class="list-data">
                                            <span class="item"></span>
                                            <a id="sign-'.$user['user_id'].'-'.$calendarId.'" class="btn btn-primary attendance-sign" href="javascript:void(0)">
                                                <em class="fa fa-pencil"></em> '.get_lang('Sign').'
                                            </a>
                                        </div>';
                                    }
                                } ?>
                        </td>
                    </tr>
                <?php
} ?>
                </tbody>
            </table>

        </form>
    <?php } ?>

</div>
<?php
if ($allowSignature) {
                                    include_once 'attendance_signature.inc.php';
                                }
