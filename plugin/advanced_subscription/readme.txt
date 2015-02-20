<h1>
<a id="user-content-advanced-subscription-plugin-for-chamilo-lms" class="anchor" href="#advanced-subscription-plugin-for-chamilo-lms" aria-hidden="true"><span class="octicon octicon-link"></span></a>Advanced subscription plugin for Chamilo LMS</h1>

<p>Plugin for managing the registration queue and communication to sessions
from an external website creating a queue to control session subscription
and sending emails to approve student subscription request</p>

<h1>
<a id="user-content-requirements" class="anchor" href="#requirements" aria-hidden="true"><span class="octicon octicon-link"></span></a>Requirements</h1>

<p>Chamilo LMS 1.10 or greater</p>

<h1>
<a id="user-content-settings" class="anchor" href="#settings" aria-hidden="true"><span class="octicon octicon-link"></span></a>Settings</h1>

<table>
<thead>
<tr>
<th>Parameters</th>
<th>Description</th>
</tr>
</thead>
<tbody>
<tr>
<td>Webservice url</td>
<td>Url to external website to get user profile (SOAP)</td>
</tr>
<tr>
<td>Induction requirement</td>
<td>Checkbox to enable induction as requirement</td>
</tr>
<tr>
<td>Courses count limit</td>
<td>Number of times a student is allowed at most to course by year</td>
</tr>
<tr>
<td>Yearly hours limit</td>
<td>Teaching hours a student is allowed at most  to course by year</td>
</tr>
<tr>
<td>Yearly cost unit converter</td>
<td>The cost of a taxation unit value (TUV)</td>
</tr>
<tr>
<td>Yearly cost limit</td>
<td>Number of TUV student courses is allowed at most to cost by year</td>
</tr>
<tr>
<td>Year start date</td>
<td>Date (dd/mm) when the year limit is renewed</td>
</tr>
<tr>
<td>Minimum percentage profile</td>
<td>Minimum percentage required from external website profile</td>
</tr>
</tbody>
</table>

<h1>
<a id="user-content-hooks" class="anchor" href="#hooks" aria-hidden="true"><span class="octicon octicon-link"></span></a>Hooks</h1>

<p>This plugin use the next hooks:</p>

<ul class="task-list">
<li>HookAdminBlock</li>
<li>HookWSRegistration</li>
<li>HookNotificationContent</li>
<li>HookNotificationTitle</li>
</ul>

<h1>
<a id="user-content-web-services" class="anchor" href="#web-services" aria-hidden="true"><span class="octicon octicon-link"></span></a>Web services</h1>

<ul class="task-list">
<li>HookAdvancedSubscription..WSSessionListInCategory</li>
<li>HookAdvancedSubscription..WSSessionGetDetailsByUser</li>
<li>HookAdvancedSubscription..WSListSessionsDetailsByCategory</li>
</ul>

<p>See <code>/plugin/advanced_subscription/src/HookAdvancedSubscription.php</code> to check Web services inputs and outputs</p>

<h1>
<a id="user-content-how-plugin-works" class="anchor" href="#how-plugin-works" aria-hidden="true"><span class="octicon octicon-link"></span></a>How plugin works?</h1>

<p>After install plugin, fill the parameters needed (described above)
Use Web services to communicate course session inscription from external website
This allow to student to search course session and subscribe if is qualified
and allowed to subscribe.
The normal process is:</p>

<ul class="task-list">
<li>Student search course session</li>
<li>Student read session info depending student data</li>
<li>Student request a subscription</li>
<li>A confirmation email is send to student</li>
<li>An email is send to users (superior or admins) who will accept or reject student request</li>
<li>When the user aceept o reject, an email will be send to student, superior or admins respectively</li>
<li>To complete the subscription, the request must be validated and accepted by an admin</li>
</ul>
