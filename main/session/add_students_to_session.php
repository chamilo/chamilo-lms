<?php
/* For licensing terms, see /license.txt */

/**
 *   @package chamilo.admin
 */
// resetting the course id
$cidReset = true;

// including some necessary files
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// setting breadcrumbs
$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('SessionList'),
];

// Setting the name of the tool
$tool_name = get_lang('SubscribeStudentsToSession');
$add_type = 'multiple';
if (isset($_REQUEST['add_type']) && $_REQUEST['add_type'] != '') {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}
$form_sent = 0;
$errorMsg = '';
$users = $sessions = [];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
SessionManager::protectSession($id);

$htmlResult = '';
if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    if ($form_sent == 1) {
        $sessionSourceList = $_POST['sessions'];
        $sessionDestinationList = $_POST['sessions_destination'];
        $result = SessionManager::copyStudentsFromSession(
            $sessionSourceList,
            $sessionDestinationList
        );
        foreach ($result as $message) {
            $htmlResult .= $message;
        }
    }
}

$session_list = SessionManager::get_sessions_list([], ['name']);
$sessionList = [];
foreach ($session_list as $session) {
    $sessionList[$session['id']] = Security::remove_XSS($session['name']);
}
Display::display_header($tool_name);
?>

<form name="formulaire" method="post" action="<?php echo api_get_self().'?id='.$id; ?>" style="margin:0px;" >
<?php echo '<legend>'.$tool_name.' </legend>';
echo $htmlResult;
echo Display::input('hidden', 'form_sent', '1');
?>
    <table border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td align="center">
                <b><?php echo get_lang('Sessions'); ?> :</b>
            </td>
            <td></td>
            <td align="center">
                <b><?php echo get_lang('Sessions'); ?> :</b>
            </td>
        </tr>
        <tr>
            <td align="center">
                <?php
                 echo Display::select(
                     'sessions[]',
                     $sessionList,
                     '',
                     ['style' => 'width:100%', 'multiple' => 'multiple', 'id' => 'sessions', 'size' => '15px'],
                     false
                 );
                ?>
            </td>
            <td align="center">
            </td>
            <td align="center">
                <?php
                echo Display::select(
                    'sessions_destination[]',
                    $sessionList,
                    '',
                    ['style' => 'width:100%', 'id' => 'courses', 'size' => '15px'],
                    false
                );
                ?>
            </td>
        </tr>
        <tr>
            <td colspan="3" align="center">
                <br />
                <?php
                echo '<button class="save" type="submit"" >'.
                    get_lang('SubscribeStudentsToSession').'</button>';
                ?>
            </td>
        </tr>
    </table>
</form>
<script>
    function moveItem(origin , destination) {
        for(var i = 0 ; i<origin.options.length ; i++) {
            if(origin.options[i].selected) {
                destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
                origin.options[i]=null;
                i = i-1;
            }
        }
        destination.selectedIndex = -1;
        sortOptions(destination.options);
    }

    function sortOptions(options) {
        newOptions = new Array();
        for (i = 0 ; i<options.length ; i++)
            newOptions[i] = options[i];

        newOptions = newOptions.sort(mysort);
        options.length = 0;
        for(i = 0 ; i < newOptions.length ; i++)
            options[i] = newOptions[i];
    }

    function mysort(a, b){
        if (a.text.toLowerCase() > b.text.toLowerCase()){
            return 1;
        }
        if (a.text.toLowerCase() < b.text.toLowerCase()){
            return -1;
        }
        return 0;
    }

    function valide(){
        var options = document.getElementById('session_in_promotion').options;
        for (i = 0 ; i<options.length ; i++)
            options[i].selected = true;
        document.forms.formulaire.submit();
    }

    function loadUsersInSelect(select) {
        var xhr_object = null;
        if(window.XMLHttpRequest) // Firefox
            xhr_object = new XMLHttpRequest();
        else if(window.ActiveXObject) // Internet Explorer
            xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
        else  // XMLHttpRequest non supporté par le navigateur
            alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");

        xhr_object.open("POST", "loadUsersInSelect.ajax.php");
        xhr_object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        nosessionUsers = makepost(document.getElementById('session_not_in_promotion'));
        sessionUsers = makepost(document.getElementById('session_in_promotion'));
        nosessionClasses = makepost(document.getElementById('origin_classes'));
        sessionClasses = makepost(document.getElementById('destination_classes'));
        xhr_object.send("nosessionusers="+nosessionUsers+"&sessionusers="+sessionUsers+"&nosessionclasses="+nosessionClasses+"&sessionclasses="+sessionClasses);

        xhr_object.onreadystatechange = function() {
            if(xhr_object.readyState == 4) {
                document.getElementById('content_source').innerHTML = result = xhr_object.responseText;
                //alert(xhr_object.responseText);
            }
        }
    }

    function makepost(select) {
        var options = select.options;
        var ret = "";
        for (i = 0 ; i<options.length ; i++)
            ret = ret + options[i].value +'::'+options[i].text+";;";
        return ret;
    }
</script>
<?php
Display::display_footer();
