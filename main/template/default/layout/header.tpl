<!-- Topbar -->
{if $show_toolbar == 1}
    <div class="topbar">
        <div class="topbar-inner">
            <div class="container-fluid">
                <h3><a href="{'WEB_PATH'|get_path}">Chamilo</a></h3>
                
                {if $_u.logged}
                <ul class="nav">
                    <li class="active"><a href="{'WEB_PATH'|get_path}/user_portal.php">{"MyCourses"|get_lang}</a></li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" href="#">{'Teaching'|get_lang}</a>
                        <ul class="dropdown-menu">
                            <li><a href="{'WEB_CODE_PATH'|get_path}create_course/add_course.php">{"AddCourse"|get_lang}</a></li>
                            <li><a href="{'WEB_CODE_PATH'|get_path}auth/courses.php">{"Catalog"|get_lang}</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" href="#">{'Tracking'|get_lang}</a>
                        <ul class="dropdown-menu">
                            <li><a href="{'WEB_CODE_PATH'|get_path}mySpace/">{"CoursesReporting"|get_lang}</a></li>
                            <li><a href="{'WEB_CODE_PATH'|get_path}mySpace/index.php?view=admin">{"AdminReports"|get_lang}</a></li>
                            <li><a href="{'WEB_CODE_PATH'|get_path}tracking/exams.php">{"ExamsReporting"|get_lang}</a></li>
                            <li class="divider"></li>
                            <li><a href="{'WEB_CODE_PATH'|get_path}dashboard/">{"Dashboard"|get_lang}</a></li>
                        </ul>
                    </li>
                    {if $_u.is_admin == 1}
                    <li class="dropdown">
                        <a class="dropdown-toggle" href="#">{'Administration'|get_lang}</a>
                        <ul class="dropdown-menu">
                            <li><a href="{'WEB_CODE_PATH'|get_path}admin/">{"Home"|get_lang}</a></li>
                            <li><a href="{'WEB_CODE_PATH'|get_path}admin/user_list.php">{"UserList"|get_lang}</a></li>
                            <li><a href="{'WEB_CODE_PATH'|get_path}admin/course_list.php">{"CourseList"|get_lang}</a></li>
                            <li><a href="{'WEB_CODE_PATH'|get_path}admin/session_list.php">{"SessionsList"|get_lang}</a></li>
                            <li><a href="{'WEB_CODE_PATH'|get_path}admin/settings.php">{"Settings"|get_lang}</a></li>
                        </ul>
                    </li>
                    {/if}
                </ul>
                {/if}
                
                {if $_u.is_admin == 1}
                <form action="{'WEB_CODE_PATH'|get_path}admin/user_list.php" method="get">
                <input type="text" placeholder="{'SearchUsers'|get_lang}" name="keyword">
                </form>
                {/if}
                
                {if $_u.logged}
                    <ul class="nav secondary-nav">
                        <li><a href="{'WEB_CODE_PATH'|get_path}social/home.php"><img src="{$_u.avatar_small}"/></a></li>
                        <li class="dropdown">
                            <a class="dropdown-toggle" href="#">{$_u.complete_name}</a>
                            <ul class="dropdown-menu">
                                <li><a href="{'WEB_CODE_PATH'|get_path}social/home.php">{"Profile"|get_lang}</a></li>
                                <li><a href="{'WEB_CODE_PATH'|get_path}calendar/agenda_js.php?type=personal">{"MyAgenda"|get_lang}</a></li>
                                <li><a href="{'WEB_CODE_PATH'|get_path}messages/inbox.php">{"Messages"|get_lang}</a></li>
                                <li><a href="{'WEB_CODE_PATH'|get_path}auth/my_progress.php">{"MyReporting"|get_lang}</a></li>
                                <li class="divider"></li>
                                <li><a href="{'WEB_CODE_PATH'|get_path}social/invitations.php">{"PendingInvitations"|get_lang}</a></li>
                            </ul>
                        </li>
                        <li><a href="{'WEB_PATH'|get_path}index.php?logout=logout">Logout</a></li>
                    </ul>
                {/if}
            </div>
        </div><!-- /topbar-inner -->
    </div><!-- /topbar -->
    <div id="topbar_push"></div>
{/if}
    
<div id="header">
    {* header *}
    {$header1}
    
    {* header right *}
    {$header2}   
</div>    

{* menu *}
{$header3}
