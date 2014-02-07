<div class="span6">
    <div class="well_border">
        <h4>
            <img src="{{ _p.web_img_path }}icons/22/user.png" alt="{{ 'Users' | get_lang }}">
            {{ 'Users' | get_lang }}
        </h4>
        <div style="list-style-type:none">
            <form method="get" class="form-search" action="{{ _p.web_main }}admin/user_list.php">
                <input class="span3" type="text" name="keyword" value="">
                <button class="btn" type="submit">Search</button>
            </form>
        </div>
        <ul>
            <li>
                <a href="{{ _p.web_main }}admin/user_list.php">
                    User list
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/user_add.php">
                    Add a user
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/user_export.php">
                    Export users list
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/user_import.php">
                    Import users list
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/extra_fields.php?type=user">
                    Profiling
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="span6">
    <div class="well_border ">
        <h4>
            <img src="{{ _p.web_img_path }}icons/22/session.png" alt="{{ 'Sessions' | trans }}">{{ 'Sessions' | trans }}
        </h4>
        <div style="list-style-type:none">
            <form method="GET" class="form-search" action="{{ _p.web_main }}session/session_list.php">
                <input class="span3" type="text" name="keyword" value="">
                <button class="btn" type="submit">Search</button>
            </form>
        </div>
        <ul>
            <li>
                <a href="{{ _p.web_main }}session/session_list.php">
                    Training sessions list
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}session/session_add.php">
                    Add a training session
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}session_category_list.php">
                    ListSessionCategory
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}session/session_import.php">
                    Import sessions list
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}session/session_export.php">
                    Export sessions list
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/../coursecopy/copy_course_session.php">
                    CopyFromCourseInSessionToAnotherSession
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/user_move_stats.php">
                    MoveUserStats
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/career_dashboard.php">
                    CareersAndPromotions
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/usergroups.php">
                    Classes
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/exercise_report.php">
                    ExerciseReport
                </a>
            </li>
            <li>
                <a href="{{ _p.web_main }}admin/extra_fields.php?type=session">
                    ManageSessionFields
                </a>
            </li>
        </ul>

    </div>
</div>
