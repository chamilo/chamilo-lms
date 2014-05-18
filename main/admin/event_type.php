<?php
/* For licensing terms, see /license.txt */
/**
 * Definition of new system event types
 * @package chamilo.admin.events
 */
/**
 * Init and access validation
 */
// name of the language file that needs to be included
$language_file = array('admin');
$cidReset = true;

require_once '../inc/global.inc.php';
require_once '../inc/conf/events.conf.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if (api_get_setting('activate_email_template') != 'true') {
    api_not_allowed();
}

$action = isset($_POST['action']) ? $_POST['action'] : null;
$eventName = isset($_POST['eventList']) ? $_POST['eventList'] : null;
$eventUsers = isset($_POST['eventUsers']) ? $_POST['eventUsers'] : null;
$eventMessage = isset($_POST['eventMessage']) ? $_POST['eventMessage'] : null;
$eventSubject = isset($_POST['eventSubject']) ? $_POST['eventSubject'] : null;
$eventMessageLanguage = isset($_POST['languages']) ? $_POST['languages'] : null;
$activated = isset($_POST['activated']) ? $_POST['activated'] : 0;
$event_name = isset($_REQUEST['event_type_name']) ? addslashes($_REQUEST['event_type_name']) : 0;

if ($action == 'modEventType') {
    if ($eventUsers) {
        $users = explode(';', $eventUsers);
    } else {
        $users = array();
    }        
    if (!empty($event_name)) {
        $eventName = $event_name;
    }    
    save_event_type_message($eventName, $users, $eventMessage, $eventSubject, $eventMessageLanguage, $activated);
    header('location: event_controller.php');
    exit;
}

$ets = get_all_event_types();

$languages = api_get_languages();

$ajaxPath = api_get_path(WEB_CODE_PATH) . 'inc/ajax/events.ajax.php';

$action_array = array(array('url' =>'event_controller.php?action=listing' , 'content' => Display::return_icon('view_text.png', get_lang('ListView'), array(), ICON_SIZE_MEDIUM)));

$key_lang = get_lang('YouHaveSomeUnsavedChanges');
$users = UserManager::get_user_list(array(), array('firstname'));
$new_user_list = array();
foreach ($users as $user) {
    if ($user['status'] == ANONYMOUS) {
        continue;
    }
    $new_user_list[] = $user;
}

/**
 * Header definition
 */ 
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'event_controller.php', 'name' => get_lang('Events'));
$tool_name = get_lang('EventMessageManagement');

Display::display_header($tool_name);

echo Display::actions($action_array);

/**
 * JavaScript code
 * @todo move into HTML header
 */
?>
<script>
    var usersList = <?php echo json_encode($new_user_list) ?>;
    var eventTypes = <?php echo json_encode($ets) ?>;
    var eventsConfig = <?php echo json_encode($event_config) ?>;
    var currentLanguage = <?php echo json_encode(api_get_interface_language()) ?>;
    var flagContentHasChanged = false;
    var key_lang = "<?php echo $key_lang ?>";    
    var event_type_name = "<?php echo $event_name ?>";
    
    $(document).ready(function() {        
        confirmMessage("eventList");
        if (event_type_name != 0) {
            $("#event_list_group").hide();
        }
    });
    
    function ajax(params,func) {
        $.ajax({
            url: "<?php echo $ajaxPath ?>",
            type: "POST",
            data: params,
            success: func
        });
    }

    function refreshUsersList() {    
        removeAllOption($('#usersList'));
        $.each(usersList, function(ind,item) {              
            addOption($('#usersList'), item.user_id, item.firstname + ' '+item.lastname);
        });        
    }

    function getCurrentEventTypeName() {
        var name = false;
        
        if (event_type_name != 0) {
            return event_type_name;
        } else {
            return $('#eventList option:selected').first().attr('value');
        }
        
    }
    
    function self_sent_lock(self_sent) {
        if (self_sent == true) {
            $(".registration_case").show();        
            $("#usersList").attr('disabled', 'true');
            $("#usersSubList").attr('disabled', 'true');
            removeAllOption($('#usersSubList'));
        } else {
            $(".registration_case").show();
            $("#usersList").removeAttr('disabled');
            $("#usersSubList").removeAttr('disabled');
        }
    }

    function showEventType() {
        cleanInput();        
        currentEventName = getCurrentEventTypeName();
        
        $("span#activated_checkbox").css("display", "inline"); // make checkbox visible
        $('input[name=activated]').attr('checked', false);
        
        var self_sent = false;        

        if (typeof(eventsConfig[currentEventName])!='undefined') {
            // if registration, only sent to self_user
            if (eventsConfig[currentEventName].self_sent == true) {                
                self_sent = true;
            }
        }

        self_sent_lock(self_sent);

        // List of events configuration
        $('#eventName').attr('value', currentEventName);
        $('#eventNameTitle').text('');
        //$('#descLangVar').text(eventsConfig[currentEventName].desc_lang_var);

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
        
        if (self_sent == false ) {
        
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
    }

    function submitForm() {
        if ($('#eventId')) {
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
        
        if (flagContentHasChanged == true) {
            if (confirm(key_lang)) {
                flagContentHasChanged = false;
                if (sender == "eventList") {
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
    function cleanInput() {
        $('#eventMessage').val("");
        $('#eventSubject').val("");
    }
</script>
<?php
/**
 * HTML body
 * @todo move as template layout
 */
?>
<div class="page-header">
<h2><?php echo get_lang('EventMessageManagement') ?></h2>
</div>

<form method="POST" onSubmit="return submitForm(); ">
    <div class="row">
        
    <div class="span12" id="event_list_group">
        <h4><?php echo get_lang('Events'); ?></h4>       
        <select class="span6" multiple="1" id="eventList" onchange="confirmMessage(this.name); return false;" name="eventList">
        <?php
        foreach ($event_config as $key => $config) {
            echo '<option value="' . $key . '">' . $config['name_lang_var'] . '</option>';
        }
        ?>
        </select>        
    </div>
        
    <div class="span4">
        <h4><?php echo get_lang('Users'); ?></h4>
        <select multiple="1" id="usersList" class="span3 registration_case"></select>
    </div>
    <div class="span4">          
        <div class="registration_case">
            <button class="arrowr" onclick='moveUsers($("#usersList"),$("#usersSubList")); return false;'></button>
            <br />
            <br />
            <button class="arrowl" onclick='moveUsers($("#usersSubList"),$("#usersList")); return false;'></button>
        </div>
    </div>
    <div class="span4">
        <h4><?php echo get_lang('ToBeWarnedUserList'); ?></h4>
        <select class="span3" multiple="1" id="usersSubList" class="registration_case"></select>
    </div>
    </div>    

    <br />
    <h2 id="eventNameTitle"></h2>
        <span id="activated_checkbox">
            <input type="checkbox" name="activated" value="1" />
            <label for="activated" style="display:inline;"><?php echo get_lang('ActivateEvent'); ?></label>        
        </span>
    <br />
    <select id="languages" name="languages" style="margin-top:20px;" onclick='confirmMessage(this.name); return false;'>
<?php foreach ($languages["name"] as $key => $value) {
    $english_name = $languages['folder'][$key]; ?>
            <option value="<?php echo $english_name; ?>" <?php echo ($english_name == api_get_interface_language()) ? "selected=selected" : ""; ?>><?php echo $value; ?></option>
<?php } ?>
    </select>

    <input type="hidden" name="action" value="modEventType" />
    <input type="hidden" name="eventId" id="eventId"  />
    <input type="hidden" name="eventUsers" id="eventUsers" />
    <input type="hidden" id="eventName" value="<?php echo $event_name ?>"/>

    <br />
    <!--	<div id="descLangVar">
        </div>-->
    <br />

    <label for="eventSubject">
        <h4><?php echo get_lang('Subject'); ?></h4>
    </label>
    <input class="span6" type="text" id="eventSubject" name="eventSubject" onchange="contentChanged(); return false;" />
    <br /><br />
    <table>
        <tr>
            <td>
                <label for="eventMessage"><h4><?php echo get_lang('Message'); ?></h4></label>
            </td>
            <td class="available_keys" style="padding-left: 30px;">
                <h4><?php echo get_lang('AvailableEventKeys'); ?></h4>
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
    <input type="submit" value="<?php echo get_lang('Save'); ?>" />

</form>
<?php
Display :: display_footer();

