<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SysAnnouncement;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Framework\Container;

/**
 * This page allows the administrator to manage the system announcements.
 */

// Resetting the course id.
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;
$_SESSION['this_section'] = $this_section;

$action = $_GET['action'] ?? null;
$action_todo = false;

api_protect_admin_script(true);

$allowCareers = ('true' === api_get_setting('announcement.allow_careers_in_global_announcements'));

// Setting breadcrumbs.
$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('Administration'),
];

$repo = Container::getSysAnnouncementRepository();

$roleOptions = api_get_roles();

if (!isset($roleOptions['ROLE_ANONYMOUS'])) {
    $roleOptions = ['ROLE_ANONYMOUS' => get_lang('Anonymous')] + $roleOptions;
} else {
    $roleOptions['ROLE_ANONYMOUS'] = get_lang('Anonymous');
    $roleOptions = ['ROLE_ANONYMOUS' => $roleOptions['ROLE_ANONYMOUS']] + array_diff_key($roleOptions, ['ROLE_ANONYMOUS' => true]);
}

$optionKeyByCanon = [];
foreach ($roleOptions as $optKey => $label) {
    $optionKeyByCanon[api_normalize_role_code((string) $optKey)] = $optKey;
}

$visibleList = $roleOptions;

$tool_name = null;
if (empty($_GET['lang'])) {
    $_GET['lang'] = $_SESSION['user_language_choice'] ?? null;
}

if (!empty($action)) {
    $interbreadcrumb[] = [
        "url" => "system_announcements.php",
        "name" => get_lang('Portal news'),
    ];
    if ('add' === $action) {
        $interbreadcrumb[] = [
            "url" => '#',
            "name" => get_lang('Add an announcement'),
        ];
    }
    if ('edit' === $action) {
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];
    }
} else {
    $tool_name = get_lang('Portal news');
}
$url = api_get_path(WEB_AJAX_PATH).'career.ajax.php';

$htmlHeadXtra[] = '<script>
function showCareer() {
    $("#promotion").show();
    var url = "'.$url.'";
    var id = $(\'#career_id\').val();

    $.getJSON(
        url, {
            "career_id" : id,
            "a" : "get_promotions"
        }
    )
    .done(function(data) {
        $("#promotion_id").empty();
        $("#promotion_id").append(
            $("<option>", {value: "0", text: "'.addslashes(get_lang('All')).'"})
        );
        $.each(data, function(index, value) {
            $("#promotion_id").append(
                $("<option>", {value: value.id, text: value.title})
            );
        });
        $("#promotion_id").selectpicker("refresh");
    });
}
</script>';

Display::display_header($tool_name);
if ('add' !== $action && 'edit' !== $action) {
    $actions = '<a href="?action=add">'.
        Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add an announcement')).'</a>';
    echo Display::toolbarAction('toolbar', [$actions]);
}

$show_announcement_list = true;
$action = $_REQUEST['action'] ?? null;

// Form was posted?
if (isset($_POST['action'])) {
    $action_todo = true;
}

switch ($action) {
    case 'make_visible':
    case 'make_invisible':
        $status = false;
        if ('make_visible' === $action) {
            $status = true;
        }

        /*SystemAnnouncementManager::set_visibility(
            $_GET['id'],
            $_GET['person'],
            $status
        );*/
        echo Display::return_message(get_lang('Update successful'), 'confirmation');
        break;
    case 'delete':
        // Delete an announcement.
        $repo->delete($_GET['id']);
        echo Display::return_message(get_lang('Announcement has been deleted'), 'confirmation');
        break;
    case 'delete_selected':
        foreach ($_POST['id'] as $index => $id) {
            $repo->delete($id);
        }
        echo Display::return_message(get_lang('Announcement has been deleted'), 'confirmation');
        $action_todo = false;
        break;
    case 'add':
        // Add an announcement.
        $values['action'] = 'add';
        // Set default time window: NOW -> NEXT WEEK
        $values['range_start'] = date('Y-m-d H:i:s', api_strtotime(api_get_local_time()));
        $values['range_end'] = date('Y-m-d H:i:s', api_strtotime(api_get_local_time()) + (7 * 24 * 60 * 60));
        $values['range'] =
            substr(api_get_local_time(time()), 0, 16).' / '.
            substr(api_get_local_time(time() + (7 * 24 * 60 * 60)), 0, 16);
        $action_todo = true;
        break;
    case 'edit':
        // Edit an announcement.
        /** @var SysAnnouncement $announcement */
        $announcement = $repo->find($_GET['id']);
        $values['id'] = $announcement->getId();
        $values['title'] = $announcement->getTitle();
        $values['content'] = $announcement->getContent();
        $values['start'] = api_get_local_time($announcement->getDateStart());
        $values['end'] = api_get_local_time($announcement->getDateEnd());
        $values['range'] = substr(api_get_local_time($announcement->getDateStart()), 0, 16).' / '.
            substr(api_get_local_time($announcement->getDateEnd()), 0, 16);

        $userCanonRoles = array_map('api_normalize_role_code', (array) $announcement->getRoles());
        $selectedOptionKeys = [];
        foreach ($userCanonRoles as $canon) {
            if (isset($optionKeyByCanon[$canon])) {
                $selectedOptionKeys[] = $optionKeyByCanon[$canon];
            }
        }
        $values['roles'] = array_values(array_unique($selectedOptionKeys));

        if ($allowCareers) {
            $values['career_id'] = $announcement->getCareer() ? $announcement->getCareer()->getId() : 0;
            $values['promotion_id'] = $announcement->getPromotion() ? $announcement->getPromotion() : 0;
        }

        $values['lang'] = $announcement->getLang();
        $values['action'] = 'edit';
        $groups = SystemAnnouncementManager::get_announcement_groups($announcement->getId());
        $values['group'] = $groups['group_id'] ?? 0;
        $action_todo = true;
        break;
}

if ($action_todo) {
    if ('add' === $action) {
        $form_title = get_lang('Add news');
        $url = api_get_self();
    } elseif ('edit' === $action) {
        $form_title = get_lang('Edit News');
        $url = api_get_self().'?id='.intval($_GET['id']);
    }
    $form = new FormValidator('system_announcement', 'post', $url);
    $form->addHeader($form_title);
    $form->addText('title', get_lang('Title'), true);
    $form->applyFilter('title', 'html_filter');

    $extraOption = [];
    $extraOption['all'] = get_lang('All');
    $form->addSelectLanguage(
        'lang',
        get_lang('Language'),
        $extraOption,
        ['set_custom_default' => 'all']
    );

    $form->addHtmlEditor(
        'content',
        get_lang('Content'),
        true,
        false,
        [
            'ToolbarSet' => 'PortalNews',
            'Width' => '100%',
            'Height' => '300',
        ]
    );
    $form->addDateRangePicker(
        'range',
        get_lang('Start'),
        true,
        ['id' => 'range']
    );

    if ($allowCareers) {
        $career = new Career();
        $careerList = $career->get_all();
        $list = array_column($careerList, 'title', 'id');

        $form->addSelect(
            'career_id',
            get_lang('Career'),
            $list,
            [
                'onchange' => 'javascript: showCareer();',
                'placeholder' => get_lang('Please select an option'),
                'id' => 'career_id',
            ]
        );

        $display = 'none;';
        $options = [];
        if (isset($values['promotion_id'])) {
            $promotion = new Promotion();
            $promotion = $promotion->get($values['promotion_id']);
            if ($promotion) {
                $options = [$promotion['id'] => $promotion['title']];
                $display = 'block';
            }
        }

        $form->addHtml('<div id="promotion" style="display:'.$display.';">');
        $form->addSelect(
            'promotion_id',
            get_lang('Promotion'),
            $options,
            ['id' => 'promotion_id']
        );
        $form->addHtml('</div>');
    }

    $form->addElement(
        'select',
        'roles',
        get_lang('Roles'),
        $roleOptions,
        [
            'multiple' => 'multiple',
            'size' => 8,
        ]
    );
    $form->addRule('roles', get_lang('Required field'), 'required');

    if (!empty($values['roles'])) {
        $form->getElement('roles')->setSelected($values['roles']);
    }

    $form->addElement('hidden', 'id');
    $userGroup = new UserGroupModel();
    $group_list = $userGroup->get_all();

    if (!empty($group_list)) {
        $group_list = array_column($group_list, 'title', 'id');
        $group_list[0] = get_lang('All');
        $form->addSelect(
            'group',
            get_lang('Announcement for a group'),
            $group_list
        );
    }

    $values['group'] = $values['group'] ?? '0';
    $form->addCheckBox('send_mail', null, get_lang('Send mail'));

    if ('add' === $action) {
        $form->addElement('checkbox', 'add_to_calendar', null, get_lang('Add to calendar'));
        $text = get_lang('Add news');
        $class = 'add';
        $form->addHidden('action', 'add');
    } elseif ('edit' === $action) {
        $text = get_lang('Edit News');
        $class = 'save';
        $form->addHidden('action', 'edit');
    }
    $form->addElement('checkbox', 'send_email_test', null, get_lang('Send an email to myself for testing purposes.'));
    $form->addButtonSend($text);
    $form->setDefaults($values);

    if ($form->validate()) {
        $values = $form->getSubmitValues();
        if ('all' === $values['lang']) {
            $values['lang'] = null;
        }

        $rolesNormalized = array_values(
            array_unique(
                array_map('api_normalize_role_code', (array) ($values['roles'] ?? []))
            )
        );

        $sendMail = $values['send_mail'] ?? null;

        switch ($values['action']) {
            case 'add':
                $announcement_id = SystemAnnouncementManager::add_announcement(
                    $values['title'],
                    $values['content'],
                    $values['range_start'],
                    $values['range_end'],
                    $rolesNormalized,
                    $values['lang'],
                    $sendMail,
                    !empty($values['add_to_calendar']),
                    !empty($values['send_email_test'])
                );

                if (false !== $announcement_id) {
                    /*
                    // ADD Picture
                    $picture = $_FILES['picture'];
                    if (!empty($picture['name'])) {
                        $picture_uri = SystemAnnouncementManager::update_announcements_picture(
                            $announcement_id,
                            $picture['tmp_name'],
                            $values['picture_crop_result']
                        );
                    }*/

                    if (isset($values['group'])) {
                        SystemAnnouncementManager::announcement_for_groups(
                            $announcement_id,
                            [$values['group']]
                        );
                    }
                    Display::addFlash(
                        Display::return_message(
                            get_lang('Announcement has been added'),
                            'confirmation'
                        )
                    );
                }

                api_location(api_get_self());

                break;
            case 'edit':
                $sendMailTest = $values['send_email_test'] ?? null;

                if (SystemAnnouncementManager::update_announcement(
                    $values['id'],
                    $values['title'],
                    $values['content'],
                    $values['range_start'],
                    $values['range_end'],
                    $rolesNormalized,
                    $values['lang'],
                    $sendMail,
                    $sendMailTest
                )) {
                    $deletePicture = $values['delete_picture'] ?? '';

                    if ($deletePicture) {
                        //SystemAnnouncementManager::deleteAnnouncementPicture($values['id']);
                    } else {
                        // @todo
                        /*$picture = $_FILES['picture'];
                        if (!empty($picture['name'])) {
                            $picture_uri = SystemAnnouncementManager::update_announcements_picture(
                                $values['id'],
                                $picture['tmp_name'],
                                $values['picture_crop_result']
                            );
                        }*/
                    }

                    if (isset($values['group'])) {
                        SystemAnnouncementManager::announcement_for_groups(
                            $values['id'],
                            [$values['group']]
                        );
                    }
                    Display::addFlash(
                        Display::return_message(
                            get_lang('Announcement has been updated'),
                            'confirmation'
                        )
                    );
                }

                api_location(api_get_self());

                break;
            default:
                break;
        }
        $show_announcement_list = true;
    } else {
        $form->display();
        $show_announcement_list = false;
    }
}

if ($show_announcement_list) {
    $criteria = ['url' => api_get_url_entity()];
    $announcements = $repo->findBy($criteria);
    $announcement_data = [];
    /** @var SysAnnouncement $announcement */
    foreach ($announcements as $announcement) {
        $row = [];
        $row[] = $announcement->getId();
        if ($announcement->isVisible()) {
            $row[] = Display::getMdiIcon(StateIcon::COMPLETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('The announcement is available'));
        } else {
            $row[] = Display::getMdiIcon(StateIcon::WARNING, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('The announcement is not available'));
        }
        $row[] = $announcement->getTitle();
        $row[] = api_convert_and_format_date($announcement->getDateStart());
        $row[] = api_convert_and_format_date($announcement->getDateEnd());

        $announcementRoles = (array) $announcement->getRoles();
        $displayRoles = [];
        foreach ($announcementRoles as $r) {
            $canon = api_normalize_role_code((string) $r);
            $key = $optionKeyByCanon[$canon] ?? null;
            $displayRoles[] = $key ? ($roleOptions[$key] ?? (string) $r) : (string) $r;
        }
        $row[] = implode(', ', $displayRoles);

        $row[] = $announcement->getLang();
        $confirmMsg = addslashes(api_htmlentities(get_lang('Please confirm your choice')));
        $yesText    = addslashes(api_htmlentities(get_lang('Yes')));
        $noText     = addslashes(api_htmlentities(get_lang('No')));
        $row[] =
            '<a href="?action=edit&id='.$announcement->getId().'">'.
            Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).
            '</a>
    <a
        href="?action=delete&id='.$announcement->getId().'"
        class="delete-swal"
        data-title="'.$confirmMsg.'"
        data-confirm-text="'.$yesText.'"
        data-cancel-text="'.$noText.'"
        title="'.api_htmlentities(get_lang('Delete')).'">'.
            Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).
            '</a>';
        $announcement_data[] = $row;
    }
    $table = new SortableTableFromArray($announcement_data);
    $table->per_page= 20;
    $table->total_number_of_items = count($announcement_data);
    $table->set_header(0, '', false, 'width="20px"');
    $table->set_header(1, get_lang('active'));
    $table->set_header(2, get_lang('Title'));
    $table->set_header(3, get_lang('Start'));
    $table->set_header(4, get_lang('End'));
    $table->set_header(5, get_lang('Roles'));
    $table->set_header(6, get_lang('Language'));
    $table->set_header(7, get_lang('Edit'), false, 'width="50px"');
    $form_actions = [];
    $form_actions['delete_selected'] = get_lang('Delete');
    $table->set_form_actions($form_actions);
    $table->display();
}

Display::display_footer();
