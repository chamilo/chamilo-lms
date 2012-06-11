<?php
// name of the language file that needs to be included
$language_file = array('admin', 'events');
$cidReset = true;

require_once '../inc/global.inc.php';
require_once '../inc/conf/events.conf.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$tool_name = get_lang('events_title');

$action = isset($_POST['action']) ? $_POST['action'] : null;
//$eventId = isset($_POST['eventId'])?$_POST['eventId']:null;
$eventName = isset($_POST['eventList']) ? $_POST['eventList'] : null;
$eventUsers = isset($_POST['eventUsers']) ? $_POST['eventUsers'] : null;
$eventMessage = isset($_POST['eventMessage']) ? $_POST['eventMessage'] : null;
$eventSubject = isset($_POST['eventSubject']) ? $_POST['eventSubject'] : null;
$eventMessageLanguage = isset($_POST['languages']) ? $_POST['languages'] : null;
$activated = isset($_POST['activated']) ? $_POST['activated'] : 0;

if ($action == 'modEventType') {
    if ($eventUsers) {
        $users = explode(';', $eventUsers);
    } else {
        $users = array();
    }
    save_event_type_message($eventName, $users, $eventMessage, $eventSubject, $eventMessageLanguage, $activated);

    header('location: event_type.php');
    exit;
}

$ets = get_all_event_types();

$languages = api_get_languages();

$ajaxPath = api_get_path(WEB_CODE_PATH) . 'inc/ajax/events.ajax.php';
Display::display_header($tool_name);

$action_array = array(array('url' =>'event_controller.php?action=listing' , 'content' => get_lang('List')));

echo Display::actions($action_array);
$key_lang = get_lang('unsaved_changes');
$users = UserManager::get_user_list();
?>

<script>
    var usersList = <?php print json_encode($users) ?>;
    var eventTypes = <?php print json_encode($ets) ?>;
    var eventsConfig = <?php print json_encode($event_config) ?>;
    var currentLanguage = <?php print json_encode(api_get_interface_language()) ?>;
    var flagContentHasChanged = false;
    var key_lang = "<?php print $key_lang ?>";
    
    function ajax(params,func) {
        $.ajax({
            url: "<?php echo $ajaxPath ?>",
            type: "POST",
            data: params,
            success: func
        }
    );
    }

    function refreshUsersList() {    
        removeAllOption($('#usersList'));
        $.each(usersList, function(ind,item) {  
            console.log(item.firstname);
            addOption($('#usersList'), item.user_id, item.firstname + ' '+item.lastname);
        });        
    }

    function getCurrentEventTypeInd() {
        var ind=false;
        $.each(eventTypes,function(i,item) {
                if(item.event_type_name == $('#eventList option:selected').first().attr('value')) {
                    ind=i;
                    return false;
                }
            }
        )
        return ind;
    }

    function getCurrentEventTypeName() {
        var name = false;
        return $('#eventList option:selected').first().attr('value');
    }

    function showEventType() {
        cleanInput();
        eInd = getCurrentEventTypeInd();
        currentEventName = getCurrentEventTypeName();

        $("span#activated_checkbox").css("display", "inline"); // make checkbox visible
        $('input[name=activated]').attr('checked', false);

        if (typeof(eventsConfig[currentEventName])!='undefined') {
            // if registration, only sent to self_user
            if (eventsConfig[currentEventName].self_sent == true) {
                //hide
                $(".registration_case").show();        
                $("#usersList").attr('disabled', 'true');
                $("#usersSubList").attr('disabled', 'true');
            } else {
                $(".registration_case").show();
                $("#usersList").removeAttr('disabled');
                $("#usersSubList").removeAttr('disabled');
            }
        } else {
            $(".registration_case").show();
            $("#usersList").removeAttr('disabled');
            $("#usersSubList").removeAttr('disabled');
        }

        // List of events configuration
        $('#eventName').attr('value', currentEventName);
        //                $('#descLangVar').text(eventsConfig[currentEventName].desc_lang_var);

        // Set message and subject accoding to the current interface language
        $.each(eventTypes,function(key,value) {
            if (eventTypes[key]["event_type_name"] == currentEventName) {
                $('#eventNameTitle').text(eventTypes[key]["nameLangVar"]);
            }

            if (eventTypes[key]["event_type_name"] == currentEventName && eventTypes[key]["activated"] == 1) {
                $('input[name=activated]').attr('checked', true);
            }

            if (eventTypes[key]["event_type_name"] == currentEventName && eventTypes[key]["dokeos_folder"] == currentLanguage) {
                $('#eventMessage').val(eventTypes[key]["message"]);
                $('#eventSubject').val(eventTypes[key]["subject"]);
            }
        });

        // Displays the available keys for the mail template (related to an event name)
        $('#keys').find('li').remove();
        if(typeof(eventsConfig[currentEventName]["available_keyvars"])!='undefined') {
            $.each(eventsConfig[currentEventName]["available_keyvars"],function(key,value) {
                $('#keys').append('<li>'+key+'</li>');
            });
        }

        
        $.ajax({
            url: '<?php echo $ajaxPath ?>?action=get_event_users&eventName=' +currentEventName,
            dataType: 'json',
            success: function(data) {
                removeAllOption($('#usersSubList'));
                refreshUsersList();
                usersIds = new Array();
                var json = jQuery.parseJSON(data);                
                $.each(json, function(ind,item) {                    
                    addOption($('#usersSubList'),item.user_id, item.firstname + ' '+item.lastname);
                    usersIds[ind] = item.value;
                    removeOption($('#usersList'),item.user_id);
                });
                $('#eventUsers').attr('value',usersIds.join(';'));
            }
        });
    }

    function submitForm() {
        if($('#eventId')) {
            usersIds = new Array();

            $('#usersSubList option').each(function(ind,item) {
                usersIds[ind] = item.value;
            });

            $('#eventUsers').attr('value',usersIds.join(';'));
            return true;
        }
        return false;
    }

    function addOption(select,value,text) {
        select.append('<option value="'+value+'">'+text+'</option>');
    }

    function removeOption(select,value) {
        select.find('option[value='+value+']').remove();
    }

    function removeAllOption(select) {
        select.find('option').remove();
    }

    function moveUsers(src,dest) {
        src.find('option:selected').each(function(index,opt) {
            text = opt.text;
            val = opt.value;

            addOption(dest,val,text);
            removeOption(src,val);
        });
    }

    /**
     * Change the message of the mail according to the selected language
     */
    function changeLanguage()
    {
        cleanInput();
        currentEventName = getCurrentEventTypeName();
        $.each(eventTypes,function(key,value)
        {
            if(eventTypes[key]["event_type_name"] == currentEventName && eventTypes[key]["dokeos_folder"] == $('#languages option:selected').first().attr('value'))
            {
                $('#eventSubject').val(eventTypes[key]["subject"]);
                $('#eventMessage').val(eventTypes[key]["message"]);
            }
        });

    }

    /**
     * Set flag at true if message and/or content was changed
     */
    function contentChanged()
    {
        flagContentHasChanged = true;
    }

    /**
     * Asks if user want to abandon the changes he's done
     */
    function confirmMessage(sender) {
        console.log(sender);
        if(flagContentHasChanged == true) {
            if(confirm(key_lang)) {
                flagContentHasChanged = false;
                if(sender == "eventList") {
                    cleanInput();
                    showEventType();
                } else if(sender == "languages") {
                    cleanInput();
                    changeLanguage();
                }
            }
        } else {
            if(sender == "eventList")
                showEventType();
            else if(sender == "languages")
                changeLanguage();
        }
    }

    /**
     * Empty the input and the textarea
     */
    function cleanInput()
    {
        $('#eventMessage').val("");
        $('#eventSubject').val("");
    }
</script>
<div class="page-header">
<h2><?php print get_lang('events_title') ?></h2>
</div>
<form method="POST" onSubmit="return submitForm(); ">
    <table id="" width="90%">
        <tr>
            <td width="5%">
                <h4><?php print get_lang('events_listTitle'); ?></h4>
            </td>
            <td width="5%">
                <h4><?php print get_lang('events_userListTile'); ?></h4>
            </td>
            <td width="5%">
                &nbsp;
            </td>
            <td width="5%">
                <h4><?php print get_lang('events_userSubListTile'); ?></h4>
            </td>
        </tr>
        <tr>
            <td>
                <select multiple="1" id="eventList" onchange="confirmMessage(this.name); return false;" name="eventList">
<?php
foreach ($event_config as $key => $config) {
    print '<option value="' . $key . '">' . get_lang($config['name_lang_var']) . '</option>';
}
?>
                </select>
            </td>
            <td>
                <select multiple="1" id="usersList" class="registration_case"></select>
            </td>
            <td valign="middle" class="registration_case">
                <button class="arrowr" onclick='moveUsers($("#usersList"),$("#usersSubList")); return false;'></button>
                <br />
                <br />
                <button class="arrowl" onclick='moveUsers($("#usersSubList"),$("#usersList")); return false;'></button>
            </td>
            <td>
                <select multiple="1" id="usersSubList" class="registration_case"></select>
            </td>
        </tr>
    </table>
    <br />
    
    <span id="activated_checkbox">
        <label for="activated" style="display:inline;"><?php print get_lang('checkbox_activated'); ?></label>
        <input type="checkbox" name="activated" value="1" />
    </span>
    
    <br />
    <h2 id="eventNameTitle"></h2>

    <select id="languages" name="languages" style="margin-top:20px;" onclick='confirmMessage(this.name); return false;'>
<?php foreach ($languages["name"] as $key => $value) {
    $english_name = $languages['folder'][$key]; ?>
            <option value="<?php echo $english_name; ?>" <?php echo ($english_name == api_get_interface_language()) ? "selected=selected" : ""; ?>><?php echo $value; ?></option>
<?php } ?>
    </select>

    <input type="hidden" name="action" value="modEventType" />
    <input type="hidden" name="eventId" id="eventId" />
    <input type="hidden" name="eventUsers" id="eventUsers" />
    <input type="hidden" id="eventName" />

    <br />
    <!--	<div id="descLangVar">
        </div>-->
    <br />

    <label for="eventSubject">
        <h4><?php print get_lang('events_labelSubject'); ?></h4>
    </label>
    <input class="span6" type="text" id="eventSubject" name="eventSubject" onchange="contentChanged(); return false;" />
    <br /><br />
    <table>
        <tr>
            <td>
                <label for="eventMessage"><h4><?php print get_lang('events_labelMessage'); ?></h4></label>
            </td>
            <td class="available_keys" style="padding-left: 30px;">
                <h4><?php print get_lang('availables_keys'); ?></h4>
            </td>
        </tr>
        <tr>
            <td>
                <textarea class="span6" rows="10" name="eventMessage" id="eventMessage" onchange="contentChanged(); return false;">
                </textarea>
            </td>
            <td class="available_keys">
                <div id="keys" style="padding-left: 50px;"><ul></ul></div>
            </td>
        </tr>
    </table>

    <br /><br />

    <input type="submit" value="<?php print get_lang('events_btnMod'); ?>" />

</form>
<?php
Display :: display_footer();