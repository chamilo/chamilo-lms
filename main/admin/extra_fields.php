<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');

$cidReset = true;

$cidReset = true;
require_once '../inc/global.inc.php';

$extraFieldType =  isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));

$tool_name = null;

$action = isset($_GET['action']) ? $_GET['action'] : null;
if (!in_array($extraFieldType, ExtraField::getValidExtraFieldTypes())) {
    api_not_allowed();
}

$check = Security::check_token('request');
$token = Security::get_token();

$obj = new ExtraField($extraFieldType);

$obj->setupBreadcrumb($interbreadcrumb, $action);

//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_extra_fields&type='.$extraFieldType;

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = $obj->getJqgridColumnNames();

//Column config
$column_model = $obj->getJqgridColumnModel();

//Autowidth
$extra_params['autowidth'] = 'true';
//height auto
$extra_params['height'] = 'auto';
$extra_params['sortname'] = 'field_order';

$action_links = $obj->getJqgridActionLinks($token);

$htmlHeadXtra[]='<script>
$(function() {
    // grid definition see the $obj->display() function
    '.Display::grid_js($obj->type.'_fields',  $url, $columns, $column_model, $extra_params, array(), $action_links, true).'

    $("#field_type").on("change", function() {
        id = $(this).val();
        switch(id) {
            case "1":
                $("#example").html("'.addslashes(Display::return_icon('userfield_text.png')).'");
                break;
            case "2":
                $("#example").html("'.addslashes(Display::return_icon('userfield_text_area.png')).'");
                break;
            case "3":
                $("#example").html("'.addslashes(Display::return_icon('add_user_field_howto.png')).'");
                break;
            case "4":
                $("#example").html("'.addslashes(Display::return_icon('userfield_drop_down.png')).'");
                break;
            case "5":
                $("#example").html("'.addslashes(Display::return_icon('userfield_multidropdown.png')).'");
                break;
            case "6":
                $("#example").html("'.addslashes(Display::return_icon('userfield_data.png')).'");
                break;
            case "7":
                $("#example").html("'.addslashes(Display::return_icon('userfield_date_time.png')).'");
                break;
            case "8":
                $("#example").html("'.addslashes(Display::return_icon('userfield_doubleselect.png')).'");
                break;
            case "9":
                $("#example").html("'.addslashes(Display::return_icon('userfield_divider.png')).'");
                break;
            case "10":
                $("#example").html("'.addslashes(Display::return_icon('userfield_user_tag.png')).'");
                break;
            case "11":
                $("#example").html("'.addslashes(Display::return_icon('userfield_data.png')).'");
                break;
        }
    });
});
</script>';

// The header.
Display::display_header($tool_name);

// Action handling: Add

switch ($action) {
    case 'add':
        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }
        $url  = api_get_self().'?type='.$obj->type.'&action='.Security::remove_XSS($_GET['action']);
        $form = $obj->return_form($url, 'add');

        // The validation or display
        if ($form->validate()) {
            //if ($check) {
                $values = $form->exportValues();
                $res    = $obj->save($values);
                if ($res) {
                    Display::display_confirmation_message(get_lang('ItemAdded'));
                }
            //}
            $obj->display();
        } else {
            echo '<div class="actions">';
            echo '<a href="'.api_get_self().'?type='.$obj->type.'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
            echo '</div>';
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $form->display();
        }
        break;
    case 'edit':
        // Action handling: Editing
        $url  = api_get_self().'?type='.$obj->type.'&action='.Security::remove_XSS($_GET['action']).'&id='.intval($_GET['id']);
        $form = $obj->return_form($url, 'edit');

        // The validation or display
        if ($form->validate()) {
            //if ($check) {
                $values = $form->exportValues();
                $res    = $obj->update($values);
                Display::display_confirmation_message(sprintf(get_lang('ItemUpdated'), $values['field_variable']), false);
            //}
            $obj->display();
        } else {
            echo '<div class="actions">';
            echo '<a href="'.api_get_self().'?type='.$obj->type.'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
            echo '</div>';
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $form->display();
        }
        break;
    case 'delete':
        // Action handling: delete
        //if ($check) {
            $res = $obj->delete($_GET['id']);
            if ($res) {
                Display::display_confirmation_message(get_lang('ItemDeleted'));
            }
        //}
        $obj->display();
        break;
    default:
        $obj->display();
        break;
}
Display :: display_footer();


/*

CREATE TABLE IF NOT EXISTS lp_field(
    id INT NOT NULL auto_increment,
    field_type int NOT NULL DEFAULT 1,
    field_variable	varchar(64) NOT NULL,
    field_display_text	varchar(64),
    field_default_value text,
    field_order int,
    field_visible tinyint default 0,
    field_changeable tinyint default 0,
    field_filter tinyint default 0,
    tms	DATETIME NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY(id)
);
DROP TABLE IF EXISTS lp_field_options;
CREATE TABLE IF NOT EXISTS lp_field_options (
    id int NOT NULL auto_increment,
    field_id int NOT NULL,
    option_value text,
    option_display_text varchar(64),
    option_order int,
    tms	DATETIME NOT NULL default '0000-00-00 00:00:00',
    priority VARCHAR(255),
    priority_message VARCHAR(255),
    PRIMARY KEY (id)
);
DROP TABLE IF EXISTS lp_field_values;
CREATE TABLE IF NOT EXISTS lp_field_values(
    id bigint NOT NULL auto_increment,
    lp_id int unsigned NOT NULL,
    field_id int NOT NULL,
    field_value	text,
    comment VARCHAR(100) default '',
    tms DATETIME NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY(id)
);

ALTER TABLE lp_field_values ADD INDEX (lp_id, field_id);



 */
