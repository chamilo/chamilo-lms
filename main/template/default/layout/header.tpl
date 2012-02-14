<!-- Topbar -->
{if $show_toolbar == 1}
    <div class="topbar">
        <div class="topbar-inner">
            <div class="container-fluid">
                <h3><a href="{$_p.web}">{"siteName"|api_get_setting}</a></h3>
                
                {if $_u.logged}
                <ul class="nav">
                    <li class="active"><a href="{$_p.web}user_portal.php">{"MyCourses"|get_lang}</a></li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" href="#">{'Teaching'|get_lang}</a>
                        <ul class="dropdown-menu">
                            <li><a href="{$_p.web_main}create_course/add_course.php">{"AddCourse"|get_lang}</a></li>
                            <li><a href="{$_p.web_main}auth/courses.php">{"Catalog"|get_lang}</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" href="#">{'Tracking'|get_lang}</a>
                        <ul class="dropdown-menu">
                            <li><a href="{$_p.web_main}mySpace/">{"CoursesReporting"|get_lang}</a></li>
                            <li><a href="{$_p.web_main}mySpace/index.php?view=admin">{"AdminReports"|get_lang}</a></li>
                            <li><a href="{$_p.web_main}tracking/exams.php">{"ExamsReporting"|get_lang}</a></li>
                            <li class="divider"></li>
                            <li><a href="{$_p.web_main}dashboard/">{"Dashboard"|get_lang}</a></li>
                        </ul>
                    </li>
                    {if $_u.is_admin == 1}
                    <li class="dropdown">
                        <a class="dropdown-toggle" href="#">{'Administration'|get_lang}</a>
                        <ul class="dropdown-menu">
                            <li><a href="{$_p.web_main}admin/">{"Home"|get_lang}</a></li>
                            <li><a href="{$_p.web_main}admin/user_list.php">{"UserList"|get_lang}</a></li>
                            <li><a href="{$_p.web_main}admin/course_list.php">{"CourseList"|get_lang}</a></li>
                            <li><a href="{$_p.web_main}admin/session_list.php">{"SessionsList"|get_lang}</a></li>
                            <li><a href="{$_p.web_main}admin/settings.php">{"Settings"|get_lang}</a></li>
                        </ul>
                    </li>
                    {/if}
                </ul>
                {/if}
                
                {if $_u.is_admin == 1}
                <form action="{$_p.web_main}admin/user_list.php" method="get">
                <input type="text" placeholder="{'SearchUsers'|get_lang}" name="keyword">
                </form>
                {/if}
                
                {if $_u.logged}
                    <ul class="nav secondary-nav">
                        <li><a href="{$_p.web_main}social/home.php"><img src="{$_u.avatar_small}"/></a></li>
                        <li class="dropdown">
                            <a class="dropdown-toggle" href="#">{$_u.complete_name}</a>
                            <ul class="dropdown-menu">
                                <li><a href="{$_p.web_main}social/home.php">{"Profile"|get_lang}</a></li>
                                <li><a href="{$_p.web_main}calendar/agenda_js.php?type=personal">{"MyAgenda"|get_lang}</a></li>
                                <li><a href="{$_p.web_main}messages/inbox.php">{"Messages"|get_lang}</a></li>
                                <li><a href="{$_p.web_main}auth/my_progress.php">{"MyReporting"|get_lang}</a></li>
                                <li class="divider"></li>
                                <li><a href="{$_p.web_main}social/invitations.php">{"PendingInvitations"|get_lang}</a></li>
                            </ul>
                        </li>
                        <li><a href="{$_p.web}index.php?logout=logout">Logout</a></li>
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
    
    {* header 2 *}
    {$header2}   
</div>    

{* menu *}

{if $header3}
<div id="header3">
    <div class="subnav">
    {$header3}
    </div>
</div>
{/if}
