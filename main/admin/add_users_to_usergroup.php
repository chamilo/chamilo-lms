<?php
/* For licensing terms, see /license.txt */

// resetting the course id
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Symfony\Component\HttpFoundation\JsonResponse;

$cidReset = true;

// including some necessary files
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$relation = isset($_REQUEST['relation']) ? (int) $_REQUEST['relation'] : 0;
$usergroup = new UserGroup();
$groupInfo = $usergroup->get($id);
$usergroup->protectScript($groupInfo);

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'usergroups.php', 'name' => get_lang('Classes')];

// setting the name of the tool
$tool_name = get_lang('SubscribeUsersToClass');

$htmlHeadXtra[] = '
<script>

$(function () {
    $("#relation").change(function() {
        window.location = "add_users_to_usergroup.php?id='.$id.'" +"&relation=" + $(this).val();
    });
});

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
    document.formulaire.form_sent.value=0;
    document.formulaire.submit();
}

function checked_in_no_group(checked)
{
    $("#relation")
    .find("option")
    .attr("selected", false);

    $("#first_letter_user")
    .find("option")
    .attr("selected", false);
        document.formulaire.form_sent.value="2";
    document.formulaire.submit();
}

function change_select(reset) {
    $("#user_with_any_group_id").attr("checked", false);
    document.formulaire["form_sent"].value = "2";

    var select = $(document.formulaire["elements_not_in_name"]);

    select.empty();

    if (reset) {
        document.formulaire["first_letter_user"].value = "";

        if ('.(api_get_configuration_value('usergroup_add_user_show_all_student_by_default') ? 0 : 1).') {
            document.formulaire["form_sent"].value = "1";

            return;
        }
    }

    $.post("'.api_get_self().'", $(document.formulaire).serialize(), function(data) {
        document.formulaire["form_sent"].value = "1";

        $.each(data, function(index, item) {
            select.append($("<option>", {
                value: index,
                text: item
            }));
        });
    });
}

</script>';

$form_sent = 0;
$extra_field_list = UserManager::get_extra_fields();
$new_field_list = [];
if (is_array($extra_field_list)) {
    foreach ($extra_field_list as $extra_field) {
        //if is enabled to filter and is a "<select>" field type
        if ($extra_field[8] == 1 && $extra_field[2] == 4) {
            $new_field_list[] = [
                'name' => $extra_field[3],
                'variable' => $extra_field[1], 'data' => $extra_field[9],
            ];
        }
    }
}

if (empty($id)) {
    api_not_allowed(true);
}

$first_letter_user = '';

if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    $elements_posted = $_POST['elements_in_name'] ?? null;
    $first_letter_user = $_POST['firstLetterUser'];

    if (!is_array($elements_posted)) {
        $elements_posted = [];
    }

    // If "social group" you need to select a role
    if ($groupInfo['group_type'] == UserGroup::SOCIAL_CLASS && empty($relation)) {
        Display::addFlash(Display::return_message(get_lang('SelectRole'), 'warning'));
        header('Location: '.api_get_self().'?id='.$id);
        exit;
    }

    if ($form_sent == 1) {
        Display::addFlash(Display::return_message(get_lang('Updated')));
        // Added a parameter to send emails when registering a user
        $usergroup->subscribe_users_to_usergroup(
            $id,
            $elements_posted,
            true,
            $relation
        );
        header('Location: usergroups.php');
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'export') {
    $users = $usergroup->getUserListByUserGroup($id);
    if (!empty($users)) {
        $data = [
            ['UserName', 'ClassName'],
        ];
        foreach ($users as $user) {
            $data[] = [$user['username'], $groupInfo['name']];
        }
        $filename = 'export_user_class_'.api_get_local_time();
        Export::arrayToCsv($data, $filename);
        exit;
    }
}

// Filter by Extra Fields
$use_extra_fields = false;
$extra_field_result = [];
if (is_array($extra_field_list)) {
    if (is_array($new_field_list) && count($new_field_list) > 0) {
        foreach ($new_field_list as $new_field) {
            $varname = 'field_'.$new_field['variable'];
            if (UserManager::is_extra_field_available($new_field['variable'])) {
                if (isset($_POST[$varname]) && $_POST[$varname] != '0') {
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
    $final_result = [];
    if (count($extra_field_result) > 1) {
        for ($i = 0; $i < count($extra_field_result) - 1; $i++) {
            if (is_array($extra_field_result[$i + 1])) {
                $final_result = array_intersect($extra_field_result[$i], $extra_field_result[$i + 1]);
            }
        }
    } else {
        $final_result = $extra_field_result[0];
    }
}

// Filters
$filters = [
    ['type' => 'text', 'name' => 'username', 'label' => get_lang('Username')],
    ['type' => 'text', 'name' => 'firstname', 'label' => get_lang('FirstName')],
    ['type' => 'text', 'name' => 'lastname', 'label' => get_lang('LastName')],
    ['type' => 'text', 'name' => 'official_code', 'label' => get_lang('OfficialCode')],
    ['type' => 'text', 'name' => 'email', 'label' => get_lang('Email')],
];

$searchForm = new FormValidator('search', 'get', api_get_self().'?id='.$id);
$searchForm->addHeader(get_lang('AdvancedSearch'));

$searchForm->addElement('hidden', 'id', $id);
$searchForm->addHidden('relation', $relation);
foreach ($filters as $param) {
    $searchForm->addElement($param['type'], $param['name'], $param['label']);
}
$searchForm->addButtonSearch();

$data = $usergroup->get($id);
$list_in = $usergroup->getUsersByUsergroupAndRelation($id, $relation);
$list_all = $usergroup->get_users_by_usergroup();

$order = ['lastname'];
if (api_is_western_name_order()) {
    $order = ['firstname'];
}

$orderListByOfficialCode = 'true' === api_get_setting('order_user_list_by_official_code');
if ($orderListByOfficialCode) {
    $order = ['official_code', 'lastname'];
}

$conditions = [];
if (!empty($first_letter_user) && strlen($first_letter_user) >= 3) {
    foreach ($filters as $filter) {
        $conditions[$filter['name']] = $first_letter_user;
    }
}

$filterData = [];
if ($searchForm->validate()) {
    $filterData = $searchForm->getSubmitValues();

    foreach ($filters as $filter) {
        if (isset($filterData[$filter['name']])) {
            $value = $filterData[$filter['name']];
            if (!empty($value)) {
                $conditions[$filter['name']] = $value;
            }
        }
    }
}

$elements_not_in = $elements_in = [];

foreach ($list_in as $listedUserId) {
    $userInfo = api_get_user_info($listedUserId);

    $elements_in[$listedUserId] = formatCompleteName($userInfo, $orderListByOfficialCode);
}

$user_with_any_group = !empty($_REQUEST['user_with_any_group']);
$user_list = [];

if (!empty($conditions)) {
    $user_list = UserManager::getUserListLike($conditions, $order, true, 'OR');
}

if ($user_with_any_group) {
    $new_user_list = [];
    foreach ($user_list as $item) {
        if (!in_array($item['user_id'], $list_all)) {
            $new_user_list[] = $item;
        }
    }
    $user_list = $new_user_list;
}

if (!empty($user_list)) {
    foreach ($user_list as $item) {
        if ($use_extra_fields) {
            if (!in_array($item['user_id'], $final_result)) {
                continue;
            }
        }

        // Avoid anonymous users
        if ($item['status'] == ANONYMOUS) {
            continue;
        }

        if (!in_array($item['user_id'], $list_in)) {
            $elements_not_in[$item['user_id']] = formatCompleteName($item, $orderListByOfficialCode);
        }
    }
}

if (api_get_configuration_value('usergroup_add_user_show_all_student_by_default')
    && empty($elements_not_in)
    && empty($first_letter_user)
) {
    $initialUserList = UserManager::getUserListLike([], $order, true, 'OR');
    $elements_not_in = [];

    foreach ($initialUserList as $userInfo) {
        if (!in_array($userInfo['id'], $list_in)) {
            $elements_not_in[$userInfo['id']] = formatCompleteName($userInfo, $orderListByOfficialCode);
        }
    }
}

function formatCompleteName(array $userInfo, bool $orderListByOfficialCode): string
{
    if ($orderListByOfficialCode) {
        $officialCode = !empty($userInfo['official_code']) ? $userInfo['official_code'].' - ' : '? - ';

        return $officialCode.$userInfo['complete_name_with_username'];
    }

    $officialCode = !empty($userInfo['official_code']) ? ' - '.$userInfo['official_code'] : null;

    return $userInfo['complete_name_with_username']." $officialCode";
}

if (ChamiloApi::isAjaxRequest()) {
    JsonResponse::create($elements_not_in)->send();
    exit;
}

Display::display_header($tool_name);

echo '<div class="actions">';
echo '<a href="usergroups.php">'.
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM).'</a>';

echo Display::url(get_lang('AdvancedSearch'), '#', ['class' => 'advanced_options', 'id' => 'advanced_search']);

echo '<a href="usergroup_user_import.php">'.
    Display::return_icon('import_csv.png', get_lang('Import'), [], ICON_SIZE_MEDIUM).'</a>';

echo '<a href="'.api_get_self().'?id='.$id.'&action=export">'.
    Display::return_icon('export_csv.png', get_lang('Export'), [], ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

echo '<div id="advanced_search_options" style="display:none">';
$searchForm->display();
echo '</div>';
?>
    <form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?id=<?php echo $id;
    if (!empty($_GET['add'])) {
        echo '&add=true';
    } ?>" style="margin:0px;">
        <?php
        echo '<legend>'.$tool_name.': '.$data['name'].'</legend>';

        if (is_array($extra_field_list)) {
            if (is_array($new_field_list) && count($new_field_list) > 0) {
                echo '<h3>'.get_lang('FilterByUser').'</h3>';
                foreach ($new_field_list as $new_field) {
                    echo $new_field['name'];
                    $varname = 'field_'.$new_field['variable'];
                    echo '&nbsp;<select name="'.$varname.'">';
                    echo '<option value="0">--'.get_lang('Select').'--</option>';
                    foreach ($new_field['data'] as $option) {
                        $checked = '';
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

        echo Display::input('hidden', 'id', $id);
        echo Display::input('hidden', 'form_sent', '1');
        echo Display::input('hidden', 'add_type', null);

        ?>
        <div class="row">
            <div class="col-md-5">
                <?php if ($data['group_type'] == UserGroup::SOCIAL_CLASS) {
            ?>
                    <select name="relation" id="relation" class="form-control">
                        <option value=""><?php echo get_lang('SelectARelationType'); ?></option>
                        <option
                            value="<?php echo GROUP_USER_PERMISSION_ADMIN; ?>" <?php echo (isset($relation) && $relation == GROUP_USER_PERMISSION_ADMIN) ? 'selected=selected' : ''; ?> >
                            <?php echo get_lang('Admin'); ?></option>
                        <option
                            value="<?php echo GROUP_USER_PERMISSION_READER; ?>" <?php echo (isset($relation) && $relation == GROUP_USER_PERMISSION_READER) ? 'selected=selected' : ''; ?> >
                            <?php echo get_lang('Reader'); ?></option>
                        <option
                            value="<?php echo GROUP_USER_PERMISSION_PENDING_INVITATION; ?>" <?php echo (isset($relation) && $relation == GROUP_USER_PERMISSION_PENDING_INVITATION) ? 'selected=selected' : ''; ?> >
                            <?php echo get_lang('PendingInvitation'); ?></option>
                        <option
                            value="<?php echo GROUP_USER_PERMISSION_MODERATOR; ?>" <?php echo (isset($relation) && $relation == GROUP_USER_PERMISSION_MODERATOR) ? 'selected=selected' : ''; ?> >
                            <?php echo get_lang('Moderator'); ?></option>
                        <option
                            value="<?php echo GROUP_USER_PERMISSION_HRM; ?>" <?php echo (isset($relation) && $relation == GROUP_USER_PERMISSION_HRM) ? 'selected=selected' : ''; ?> >
                            <?php echo get_lang('Drh'); ?></option>
                    </select>
                    <?php
        } ?>

                <div class="multiple_select_header">
                    <b><?php echo get_lang('UsersInPlatform'); ?> :</b>
                    <div class="input-group">
                        <input id="first_letter_user" name="firstLetterUser" type="text" class="form-control"
                               value="<?php echo Security::remove_XSS($first_letter_user); ?>"
                               placeholder="<?php echo get_lang('Search'); ?>"
                               onkeydown="return 13 !== event.keyCode;">
                        <span class="input-group-btn">
                    <button class="btn btn-default" type="button" onclick="change_select();">
                        <?php echo get_lang('Filter'); ?>
                    </button>
                    <button class="btn btn-default" type="button" onclick="change_select(true);">
                        <?php echo get_lang('Reset'); ?>
                    </button>
                </span>
                    </div>
                </div>
                <?php
                echo Display::select(
                    'elements_not_in_name',
                    $elements_not_in,
                    '',
                    [
                        'class' => 'form-control',
                        'multiple' => 'multiple',
                        'id' => 'elements_not_in',
                        'size' => '15px',
                    ],
                    false
                );
                ?>
                <br/>
                <label class="control-label">
                    <input type="checkbox" <?php if ($user_with_any_group) {
                    echo 'checked="checked"';
                } ?> onchange="checked_in_no_group(this.checked);" name="user_with_any_group" id="user_with_any_group_id">
                    <?php echo get_lang('UsersRegisteredInAnyGroup'); ?>
                </label>
            </div>
            <div class="col-md-2">
                <div style="padding-top:54px;width:auto;text-align: center;">
                    <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('elements_not_in'), document.getElementById('elements_in'))">
                        <em class="fa fa-arrow-right"></em>
                    </button>
                    <br/><br/>
                    <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('elements_in'), document.getElementById('elements_not_in'))">
                        <em class="fa fa-arrow-left"></em>
                    </button>
                </div>
            </div>
            <div class="col-md-5">
                <div class="multiple_select_header">
                    <b><?php echo get_lang('UsersInGroup'); ?> :</b>
                </div>
                <?php
                echo Display::select(
                    'elements_in_name[]',
                    $elements_in,
                    '',
                    [
                        'class' => 'form-control',
                        'multiple' => 'multiple',
                        'id' => 'elements_in',
                        'size' => '15px',
                    ],
                    false
                );
                unset($sessionUsersList);
                ?>
            </div>
        </div>
        <?php
        echo '<button class="btn btn-primary" type="button" value="" onclick="valide()" ><em class="fa fa-check"></em>'.
            get_lang('SubscribeUsersToClass').'</button>';
        ?>
    </form>
    <script>
        function moveItem(origin, destination) {
            for (var i = 0; i < origin.options.length; i++) {
                if (origin.options[i].selected) {
                    destination.options[destination.length] = new Option(origin.options[i].text, origin.options[i].value);
                    origin.options[i] = null;
                    i = i - 1;
                }
            }
            destination.selectedIndex = -1;
            sortOptions(destination.options);
        }

        function sortOptions(options) {
            newOptions = [];
            for (i = 0; i < options.length; i++)
                newOptions[i] = options[i];

            newOptions = newOptions.sort(mysort);
            options.length = 0;
            for (i = 0; i < newOptions.length; i++)
                options[i] = newOptions[i];
        }

        function mysort(a, b) {
            if (a.text.toLowerCase() > b.text.toLowerCase()) {
                return 1;
            }
            if (a.text.toLowerCase() < b.text.toLowerCase()) {
                return -1;
            }
            return 0;
        }

        function valide() {
            var options = document.getElementById('elements_in').options;
            for (i = 0; i < options.length; i++)
                options[i].selected = true;
            document.forms.formulaire.submit();
        }
    </script>
<?php
Display::display_footer();
