<?php
/* For licensing terms, see /license.txt */
/**
*   @package chamilo.admin
*/

// name of the language file that needs to be included
$language_file = array('admin','registration');

// resetting the course id
$cidReset = true;

// including some necessary files
require_once '../inc/global.inc.php';
require_once '../inc/lib/xajax/xajax.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[]= array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[]= array('url' => 'usergroups.php','name' => get_lang('Classes'));

// Database Table Definitions

// setting the name of the tool
$tool_name = get_lang('SubscribeUsersToClass');

$add_type = 'multiple';
if (isset($_REQUEST['add_type']) && $_REQUEST['add_type']!='') {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$htmlHeadXtra[] = '
<script>
function add_user_to_session (code, content) {

    document.getElementById("user_to_add").value = "";
    document.getElementById("ajax_list_users_single").innerHTML = "";

    destination = document.getElementById("elements_in");

    for (i=0;i<destination.length;i++) {
        if(destination.options[i].text == content) {
                return false;
        }
    }

    destination.options[destination.length] = new Option(content,code);
    destination.selectedIndex = -1;
    sortOptions(destination.options);
}
function remove_item(origin) {
    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            origin.options[i]=null;
            i = i-1;
        }
    }
}

function validate_filter() {
    document.formulaire.add_type.value = \''.$add_type.'\';
    document.formulaire.form_sent.value=0;
    document.formulaire.submit();
}

function checked_in_no_group(checked) {
    $("#first_letter_user")
    .find("option")
    .attr("selected", false);
        document.formulaire.form_sent.value="2";
    document.formulaire.submit();
}

function change_select(val) {
    $("#user_with_any_group_id").attr("checked", false);
    document.formulaire.form_sent.value="2";
    document.formulaire.submit();
}

</script>';

$form_sent  = 0;
$errorMsg   = '';

$extra_field_list= UserManager::get_extra_fields();
$new_field_list = array();
if (is_array($extra_field_list)) {
    foreach ($extra_field_list as $extra_field) {
        //if is enabled to filter and is a "<select>" field type
        if ($extra_field[8]==1 && $extra_field[2]==4 ) {
            $new_field_list[] = array(
                'name'=> $extra_field[3],
                'variable' => $extra_field[1], 'data'=> $extra_field[9]
            );
        }
    }
}

$usergroup = new UserGroup();
$id = intval($_GET['id']);

if (empty($id)) {
    api_not_allowed(true);
}
$first_letter_user = '';

if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent              = $_POST['form_sent'];
    $elements_posted        = isset($_POST['elements_in_name']) ? $_POST['elements_in_name'] : null;
    $first_letter_user      = $_POST['firstLetterUser'];

    if (!is_array($elements_posted)) {
        $elements_posted=array();
    }
    if ($form_sent == 1) {
        //added a parameter to send emails when registering a user
        $usergroup->subscribe_users_to_usergroup($id, $elements_posted);
        header('Location: usergroups.php');
        exit;
    }
}


//Filter by Extra Fields
$use_extra_fields = false;
if (is_array($extra_field_list)) {
    if (is_array($new_field_list) && count($new_field_list)>0 ) {
        foreach ($new_field_list as $new_field) {
            $varname = 'field_'.$new_field['variable'];
            if (UserManager::is_extra_field_available($new_field['variable'])) {
                if (isset($_POST[$varname]) && $_POST[$varname]!='0') {
                    $use_extra_fields = true;
                    $extra_field_result[] = UserManager::get_extra_user_data_by_value(
                        $new_field['variable'],
                        $_POST[$varname]
                    );
                }
            }
        }
    }
}

if ($use_extra_fields) {
    $final_result = array();
    if (count($extra_field_result)>1) {
        for ($i=0;$i<count($extra_field_result)-1;$i++) {
            if (is_array($extra_field_result[$i+1])) {
                $final_result  = array_intersect($extra_field_result[$i], $extra_field_result[$i+1]);
            }
        }
    } else {
        $final_result = $extra_field_result[0];
    }
}

// Filters
$filters = array(
    array('type' => 'text', 'name' => 'username', 'label' => get_lang('Username')),
    array('type' => 'text', 'name' => 'firstname', 'label' => get_lang('FirstName')),
    array('type' => 'text', 'name' => 'lastname', 'label' => get_lang('LastName')),
    array('type' => 'text', 'name' => 'official_code', 'label' => get_lang('OfficialCode')),
    array('type' => 'text', 'name' => 'email', 'label' => get_lang('Email'))
);

$searchForm = new FormValidator('search', 'get', api_get_self().'?id='.$id);
$searchForm->add_header(get_lang('AdvancedSearch'));
$renderer =& $searchForm->defaultRenderer();

$searchForm->addElement('hidden', 'id', $id);
foreach ($filters as $param) {
    $searchForm->addElement($param['type'], $param['name'], $param['label']);
}
$searchForm->addElement('button', 'submit', get_lang('Search'));

$filterData = array();
if ($searchForm->validate()) {
    $filterData = $searchForm->getSubmitValues();
}

$data       = $usergroup->get($id);
$list_in    = $usergroup->get_users_by_usergroup($id);
$list_all   = $usergroup->get_users_by_usergroup();

$order = array('lastname');
if (api_is_western_name_order()) {
    $order = array('firstname');
}

$conditions = array();

if (!empty($first_letter_user)) {
    $conditions['lastname'] = $first_letter_user;
}

if (!empty($filters) && !empty($filterData)) {
    foreach ($filters as $filter) {
        if (isset($filter['name']) && isset($filterData[$filter['name']])) {
            $value = $filterData[$filter['name']];
            if (!empty($value)) {
                $conditions[$filter['name']] = $value;
            }
        }
    }
}

$elements_not_in = $elements_in = array();
$complete_user_list = UserManager::get_user_list_like(array(), $order);

if (!empty($complete_user_list)) {
    foreach ($complete_user_list as $item) {
        if ($use_extra_fields) {
            if (!in_array($item['user_id'], $final_result)) {
                continue;
            }
        }
        // Avoid anonymous users
        if ($item['status'] == 6 ) {
            continue;
        }

        if (in_array($item['user_id'], $list_in)) {
            $person_name = api_get_person_name(
                $item['firstname'],
                $item['lastname']
            ).' ('.$item['username'].') '.$item['official_code'];
            $elements_in[$item['user_id']] = $person_name;
        }
    }
}

$user_with_any_group = isset($_REQUEST['user_with_any_group']) && !empty($_REQUEST['user_with_any_group']) ? true : false;

if ($user_with_any_group) {
    $user_list = UserManager::get_user_list_like($conditions, $order, true);
    $new_user_list = array();
    foreach ($user_list as $item) {
        if (!in_array($item['user_id'], $list_all)) {
            $new_user_list[] = $item;
        }
    }
    $user_list = $new_user_list;
} else {
    $user_list = UserManager::get_user_list_like($conditions, $order, true);
}

if (!empty($user_list)) {
    foreach ($user_list as $item) {
        if ($use_extra_fields) {
            if (!in_array($item['user_id'], $final_result)) {
                continue;
            }
        }
        if ($item['status'] == 6 ) continue; //avoid anonymous users
        $person_name = api_get_person_name(
            $item['firstname'],
            $item['lastname']
        ).' ('.$item['username'].') '.$item['official_code'];
        if (in_array($item['user_id'], $list_in)) {
            //$elements_in[$item['user_id']] = $person_name;
        } else {
            $elements_not_in[$item['user_id']] = $person_name;
        }
    }
}

$add_type == 'unique' ? true : false;

Display::display_header($tool_name);

echo '<div class="actions">';
echo '<a href="usergroups.php">'.
    Display::return_icon('back.png', get_lang('Back'), array(), ICON_SIZE_MEDIUM).'</a>';

echo Display::url(get_lang('AdvancedSearch'), '#', array('class' => 'advanced_options', 'id' => 'advanced_search'));

echo '<a href="usergroup_user_import.php">'.
    Display::return_icon('import_csv.png', get_lang('Import'), array(), ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

echo '<div id="advanced_search_options" style="display:none">';
$searchForm->display();
echo '</div>';
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?id=<?php echo $id; if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;">
<?php
echo '<legend>'.$tool_name.': '.$data['name'].'</legend>';

if ($add_type=='multiple') {
    if (is_array($extra_field_list)) {
        if (is_array($new_field_list) && count($new_field_list)>0 ) {
            echo '<h3>'.get_lang('FilterByUser').'</h3>';
            foreach ($new_field_list as $new_field) {
                echo $new_field['name'];
                $varname = 'field_'.$new_field['variable'];
                echo '&nbsp;<select name="'.$varname.'">';
                echo '<option value="0">--'.get_lang('Select').'--</option>';
                foreach ($new_field['data'] as $option) {
                    $checked='';
                    if (isset($_POST[$varname])) {
                        if ($_POST[$varname] == $option[1]) {
                            $checked = 'selected="true"';
                        }
                    }
                    echo '<option value="'.$option[1].'" '.$checked.'>'.$option[1].'</option>';
                }
                echo '</select>';
                echo '&nbsp;&nbsp;';
            }
            echo '<input type="button" value="'.get_lang('Filter').'" onclick="validate_filter()" />';
            echo '<br /><br />';
        }
    }
}
echo Display::input('hidden', 'id', $id);
echo Display::input('hidden', 'form_sent', '1');
echo Display::input('hidden', 'add_type', null);

if (!empty($errorMsg)) {
    Display::display_normal_message($errorMsg);
}
?>

<div class="row">
    <div class="span5">
        <div class="multiple_select_header">
        <b><?php echo get_lang('UsersInPlatform') ?> :</b>
        <?php echo get_lang('FirstLetterUser'); ?> :
        <select id="first_letter_user" name="firstLetterUser" onchange="change_select();">
            <option value = "%">--</option>
            <?php
            echo Display :: get_alphabet_options($first_letter_user);
            ?>
        </select>
        </div>
    <?php
    echo Display::select(
        'elements_not_in_name',
        $elements_not_in,
        '',
        array('class'=>'span5', 'multiple'=>'multiple','id'=>'elements_not_in','size'=>'15px'),
        false
    );
    ?>
    <br />
      <label class="control-label">
          <input type="checkbox" <?php if ($user_with_any_group) echo 'checked="checked"';?> onchange="checked_in_no_group(this.checked);" name="user_with_any_group" id="user_with_any_group_id">
          <?php echo get_lang('UsersRegisteredInAnyGroup'); ?>
      </label>
    </div>
    <div class="span2">
        <div style="padding-top:54px;width:auto;text-align: center;">
        <button class="arrowr" type="button" onclick="moveItem(document.getElementById('elements_not_in'), document.getElementById('elements_in'))" onclick="moveItem(document.getElementById('elements_not_in'), document.getElementById('elements_in'))">
        </button>
        <br /><br />
        <button class="arrowl" type="button" onclick="moveItem(document.getElementById('elements_in'), document.getElementById('elements_not_in'))" onclick="moveItem(document.getElementById('elements_in'), document.getElementById('elements_not_in'))">
        </button>
        </div>
    </div>
    <div class="span5">
        <div class="multiple_select_header">
            <b><?php echo get_lang('UsersInGroup') ?> :</b>
        </div>
    <?php
        echo Display::select(
            'elements_in_name[]',
            $elements_in,
            '',
            array('class'=>'span5', 'multiple'=>'multiple','id'=>'elements_in','size'=>'15px'),
            false
        );
        unset($sessionUsersList);
    ?>
    </div>
</div>

<?php
    echo '<button class="save" type="button" value="" onclick="valide()" >'.
        get_lang('SubscribeUsersToClass').'</button>';
?>
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
    for (i = 0 ; i < newOptions.length ; i++)
        options[i] = newOptions[i];
}

function mysort(a, b) {
    if(a.text.toLowerCase() > b.text.toLowerCase()){
        return 1;
    }
    if(a.text.toLowerCase() < b.text.toLowerCase()){
        return -1;
    }
    return 0;
}

function valide() {
    var options = document.getElementById('elements_in').options;
    for (i = 0 ; i<options.length ; i++)
        options[i].selected = true;
    document.forms.formulaire.submit();
}
</script>
<?php
Display::display_footer();
