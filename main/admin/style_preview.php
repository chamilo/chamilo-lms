<?php
include_once('../inc/global.inc.php');
if (isset($_GET['style']) AND $_GET['style']<>'')
{
	$style=Security::remove_XSS($_GET['style']);
	//$htmlHeadXtra[] = '<link href="../css/'.$_GET['style'].'/default.css" rel="stylesheet" type="text/css">';
	echo '<link href="../css/'.$style.'/default.css" rel="stylesheet" type="text/css">';
}
else
{
	$currentstyle = api_get_setting('stylesheets');
	echo '<link href="../css/'.$currentstyle.'/default.css" rel="stylesheet" type="text/css">';
}


//Display::display_header($tool_name);
include(api_get_path(INCLUDE_PATH).'banner.inc.php');

?>
<!-- start of #main wrapper for #content and #menu divs -->
  <!--   Begin Of script Output   -->
  <div class="maincontent">
    <h3>tool title</h3>
    <div id="courseintro">
      <p>This is the introduction text.
    </div>
    <div id="courseintro_icons">
    <a href="#"><?php Display::display_icon('edit.gif', get_lang('Edit')); ?></a><a href="#"><?php Display::display_icon('delete.gif', get_lang('Delete')); ?></a></div>
    <div class="normal-message"> Normal Message </div>
    <div class="error-message"> Error Message </div>
    <table width="750">
      <tr>
        <td>
        <table>
            <tr>
              <td width="220">
              <table id="smallcalendar">
                  <tr id="title">
                    <td width="10%"><a href="#"><<</a></td>
                    <td width="80%" colspan="5" align="center"> 2006</td>
                    <td width="10%"><a href="#">>></a></td>
                  </tr>
                  <tr>
                    <td class="weekdays">Mon</td>
                    <td class="weekdays">Tue</td>
                    <td class="weekdays">Wed</td>
                    <td class="weekdays">Thu</td>
                    <td class="weekdays">Fri</td>
                    <td class="weekdays">Sat</td>
                    <td class="weekdays">Sun</td>
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
          <table class="data_table" width="100%">
            <tr>
              <th style="width:100px"><a href="#">Firstname</a>&nbsp;&#8595; </th>
              <th style="width:100px"><a href="#">Lastname</a></th>
            </tr>
            <tr class="row_even">
              <td >Firstname</td>
              <td >Lastname</td>
            </tr>
            <tr class="row_odd">
              <td >Julio</td>
              <td >Montoya</td>
            </tr>
            <tr class="row_even">
              <td >Patrick</td>
              <td >Cool</td>
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
  <div class="menu" style="width:200px">
    <form action="#" method="post" id="loginform" name="loginform"></br>
      <label>Username</label></br>
      <input type="text" name="login" id="login" size="15" value="" /></br>
      <label>Password</label></br>
      <input type="password" name="password" id="password" size="15" /></br>
  	  <button class="login" type="submit" name="submitAuth"disabled="disabled" >Enter</button>
    </form>
    <div class="menusection"><span class="menusectioncaption">User</span>
      <ul class="menulist">
        <li><a href="#">Course Management</a></li>
        <li><a href="#">Create Course</a></li>
      </ul>
    </div>
    <div class="note"><b>Example notice</b><br />
      To modify this notice, go to the administration area of the portal.</div>
  </div>
<?php
Display::display_footer();
?>