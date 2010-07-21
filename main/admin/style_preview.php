<?php
/* For licensing terms, see /chamilo_license.txt */

$language_file = array('create_course', 'courses', 'admin');

require_once '../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

// Access restriction.
api_protect_admin_script();

// Manipulation of the platform-wide css setting.
if (isset($_GET['style']) && $_GET['style'] != '') {
	$_setting['stylesheets'] = Security::remove_XSS($_GET['style']);
}

// Hiding the link "Teacher/Student view", it is not needed to be shown here.
$_setting['student_view_enabled'] = 'false';

require_once api_get_path(INCLUDE_PATH).'header.inc.php';

$week_days_short = api_get_week_days_short();
$months_long = api_get_months_long();

?>
  <div class="maincontent" id="content">
    <h3><?php echo get_lang('Title'); ?></h3>
    <div id="courseintro">
      <p><?php echo get_lang('IntroductionText'); ?></p>
    </div>
    <div id="courseintro_icons">
    <a href="#"><?php Display::display_icon('edit.gif', get_lang('Edit')); ?></a><a href="#"><?php Display::display_icon('delete.gif', get_lang('Delete')); ?></a></div>
    <div class="normal-message">Normal Message</div>
    <div class="confirmation-message">Confirmation Message</div>
    <div class="warning-message">Warning Message</div>
    <div class="error-message">Error Message</div>
    <table width="750">
      <tr>
        <td>
        <table>
            <tr>
              <td width="220">
              <table id="smallcalendar" class="data_table">
                  <tr id="title">
                    <td width="10%"><a href="#"><?php Display::display_icon('action_prev.png'); ?></a></td>
                    <td width="80%" colspan="5" align="center"><?php echo $months_long[6]; ?> 2010</td>
                    <td width="10%"><a href="#"><?php Display::display_icon('action_next.png'); ?></a></td>
                  </tr>
                  <tr>
                    <td class="weekdays"><?php echo $week_days_short[1]; ?></td>
                    <td class="weekdays"><?php echo $week_days_short[2]; ?></td>
                    <td class="weekdays"><?php echo $week_days_short[3]; ?></td>
                    <td class="weekdays"><?php echo $week_days_short[4]; ?></td>
                    <td class="weekdays"><?php echo $week_days_short[5]; ?></td>
                    <td class="weekdays"><?php echo $week_days_short[6]; ?></td>
                    <td class="weekdays"><?php echo $week_days_short[0]; ?></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="days_weekend">1</td>
                  </tr>
                  <tr>
                    <td class="days_week">2</td>
                    <td class="days_week">3</td>
                    <td class="days_week">4</td>
                    <td class="days_week">5</td>
                    <td class="days_week">6</td>
                    <td class="days_weekend">7</td>
                    <td class="days_weekend">8</td>
                  </tr>
                  <tr>
                    <td class="days_week">9</td>
                    <td class="days_week">10</td>
                    <td class="days_week">11</td>
                    <td class="days_week">12</td>
                    <td class="days_week">13</td>
                    <td class="days_weekend">14</td>
                    <td class="days_weekend">15</td>
                  </tr>
                  <tr>
                    <td class="days_week">16</td>
                    <td class="days_week">17</td>
                    <td class="days_week">18</td>
                    <td class="days_week">19</td>
                    <td class="days_week">20</td>
                    <td class="days_weekend">21</td>
                    <td class="days_weekend">22</td>
                  </tr>
                  <tr>
                    <td class="days_week">23</td>
                    <td class="days_today">24</td>
                    <td class="days_week">25</td>
                    <td class="days_week">26</td>
                    <td class="days_week">27</td>
                    <td class="days_weekend">28</td>
                    <td class="days_weekend">29</td>
                  </tr>
                  <tr>
                    <td class="days_week">30</td>
                    <td class="days_week">31</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
        <td width="500">
          <table width="100%">
            <tr>
              <td></td>
              <td align="right"></td>
            </tr>
          </table>
          <table class="data_table" style="width: 250px;">
            <tr>
              <th style="width: 50%;"><a href="#"><?php echo get_lang('FirstName'); ?></a>&nbsp;&#8595; </th>
              <th><a href="#"><?php echo get_lang('LastName'); ?></a></th>
            </tr>
            <tr class="row_odd">
              <td>Julio</td>
              <td>Montoya</td>
            </tr>
            <tr class="row_even">
              <td>Yannick</td>
              <td>Warnier</td>
            </tr>
          </table>
          <table width="100%">
            <tr>
              <td></td>
              <td align="right"></td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </div>
  <div class="menu" id="menu">
    <form action="#" method="post" id="formLogin" name="formLogin"><br />
      <label><?php echo get_lang('UserName'); ?></label><br />
      <input type="text" name="login" id="login" size="15" value="" /><br />
      <label><?php echo get_lang('Password'); ?></label><br />
      <input type="password" name="password" id="password" size="15" /><br />
  	  <button class="login" type="submit" name="submitAuth"disabled="disabled" ><?php echo get_lang('LoginEnter'); ?></button>
  	  <div class="clear">
        &nbsp;
  	  </div>
    </form>
    <div class="menusection"><span class="menusectioncaption"><?php echo get_lang('User'); ?></span>
      <ul class="menulist">
        <li><a href="#"><?php echo get_lang('CourseManagement'); ?></a></li>
        <li><a href="#"><?php echo get_lang('CourseCreate'); ?></a></li>
      </ul>
    </div>
    <div class="note"><b>Example notice</b><br />
      To modify this notice, go to the administration area of the portal.</div>
  </div>
<?php
Display::display_footer();
?>