<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'User Remote Services';
$strings['plugin_comment'] = 'Appends site-specific iframe-targetted user-identifying links to the menu bar.';

$strings['salt'] = 'Salt';
$strings['salt_help'] =
'Secret character string, used to generate the <em>hash</em> URL parameter. The longest, the best.
<br/>Remote user services can check the generated URL authenticity with the following PHP expression :
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Where
<br/><code>$salt</code> is this input value,
<br/><code>$userId</code> is the number of the user referenced by the <em>username</em> URL parameter value and
<br/><code>$hash</code> contains the <em>hash</em> URL parameter value.';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Add service to menu bar';
$strings['DeleteServices'] = 'Remove services from menu bar';
$strings['ServicesToDelete'] = 'Services to remove from menu bar';
$strings['ServiceTitle'] = 'Service title';
$strings['ServiceURL'] = 'Service web site location (URL)';
