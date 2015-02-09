Hook Management plugin<br/><br/>

<article class="markdown-body entry-content" itemprop="mainContentOfPage"><h1>
<a id="user-content-hook-management-plugin-for-chamilo-lms" class="anchor" href="#hook-management-plugin-for-chamilo-lms" aria-hidden="true"><span class="octicon octicon-link"></span></a>Hook Management plugin for Chamilo LMS</h1>

<p>Enable hooks in Chamilo to allow plugin to extend functionality.</p>

<p>Hooks structure is based on Observer pattern</p>

<p>The base structure is composed by 3 Interfaces</p>

<ul class="task-list">
<li>HookEvent: This will call the hook methods in Chamilo code</li>
<li>HookObserver: This will be executed when a Hook event is called</li>
<li>HookManagement: Manage hooks, creation, instantiation, persistence, connection to database and is implemented to a Plugin</li>
</ul>

<p>On this version exists Hooks for:</p>

<table>
<thead>
<tr>
<th>Number</th>
<th>Directory</th>
<th>EventClass</th>
<th>ObserverInterface</th>
<th>Reference</th>
</tr>
</thead>
<tbody>
<tr>
<td>1</td>
<td>/main/inc/lib/usermanager.lib.php</td>
<td>HookCreateUser</td>
<td>HookCreateUserObserverInterface</td>
<td>Usermanager::createUser()</td>
</tr>
<tr>
<td>2</td>
<td>/main/inc/lib/usermanager.lib.php</td>
<td>HookUpdateUser</td>
<td>HookUpdateUserObserverInterface</td>
<td>Usermanager::updateUser()</td>
</tr>
<tr>
<td>3</td>
<td>/main/admin/index.php</td>
<td>HookAdminBlock</td>
<td>HookAdminBlockObserverInterface</td>
<td>ADMIN BLOCK</td>
</tr>
</tbody>
</table>

<h1>
<a id="user-content-what-i-need-to-use-hook" class="anchor" href="#what-i-need-to-use-hook" aria-hidden="true"><span class="octicon octicon-link"></span></a>What I need to use Hook?</h1>

<p>You need to create a class extending <code>HookObserver</code> class
and implementing any (or many) Hook Observer Interfaces, e.g. <code>HookCreateUserObserverInterface</code>.
An observer can implement many Hook observer interface.
This was done to allow Plugin to have a unique Hook Observer class
Dont forget to add your Hook Observer class to autoload file</p>

<h1>
<a id="user-content-how-to-add-myhookobserver-to-my-plugin" class="anchor" href="#how-to-add-myhookobserver-to-my-plugin" aria-hidden="true"><span class="octicon octicon-link"></span></a>How to add MyHookObserver to my plugin?</h1>

<p>Before this, the hook management plugin must be enabled</p>

<p>When installing your plugin (or other functions) you should call
the attach method from an specific Hook Observer class, e.g. <code>HookCreateUser</code> class</p>

<pre><code>$myHookObserver = MyHookObserver::create();
HookCreateUser::create()-&gt;attach($myHookObserver);
</code></pre>

<h1>
<a id="user-content-how-to-remove-myhookobserver-to-my-plugin" class="anchor" href="#how-to-remove-myhookobserver-to-my-plugin" aria-hidden="true"><span class="octicon octicon-link"></span></a>How to remove MyHookObserver to my plugin?</h1>

<p>For remove the HookObserver, this should be detached from specific Hook Event class</p>

<pre><code>$myHookObserver = MyHookObserver::create();
HookCreateUser::create()-&gt;detach($myHookObserver);
</code></pre>

<h1>
<a id="user-content-how-to-add-hookevents-to-chamilo" class="anchor" href="#how-to-add-hookevents-to-chamilo" aria-hidden="true"><span class="octicon octicon-link"></span></a>How to add HookEvents to Chamilo?</h1>

<p>To expand Hook in Chamilo you should do:
1. Identify an event could be customized by a plugin
2. Create an interface for the Hook Event and and Hook Observer.
 The names should be like the Hooks interfaces already created,
 with The Pattern: HookXyzEventInterface and HookXyzObserverInterface,
 e.g. Hook event: <code>HookUpdateUserEventInterface</code>, Hook observer: <code>HookUpdateUserObserverInterface</code>
3. Add at least a notify method to Hook Event Interface and update method to Hook Observer Interface
4. Create a class extending <code>HookEvent</code> class and implementing your Hook Event Interface
5. Complete the notify method calling to Hook observer update
6. Add your Interfaces and Class to autoload file
7. Test your hook. if your Observer require data, you can use the data property from Hook Event</p>
</article>
