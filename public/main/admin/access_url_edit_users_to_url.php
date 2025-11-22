<?php

/* For licensing terms, see /license.txt */

/*
 * @author Julio Montoya <gugli100@gmail.com>
 */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$xajax = new xajax();
$xajax->registerFunction(['search_users', 'AccessUrlEditUsersToUrl', 'search_users']);

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_global_admin_script();

if (!api_get_multiple_access_url()) {
    header('Location: index.php');

    exit;
}

// Database Table Definitions
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
// setting breadcrumbs
$tool_name = get_lang('Edit users and URLs');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('Multiple access URL / Branding')];

$add_type = 'multiple';
if (isset($_REQUEST['add_type']) && '' != $_REQUEST['add_type']) {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$access_url_id = 1;
if (isset($_REQUEST['access_url_id']) && '' != $_REQUEST['access_url_id']) {
    $access_url_id = Security::remove_XSS($_REQUEST['access_url_id']);
}

$xajax->processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<script>
function add_user_to_url(code, content) {
	document.getElementById("user_to_add").value = "";
	document.getElementById("ajax_list_users").innerHTML = "";
	destination = document.getElementById("destination_users");
	destination.options[destination.length] = new Option(content,code);
	destination.selectedIndex = -1;
	sortOptions(destination.options);
}

function send() {
	if (document.formulaire.access_url_id.value!=0) {
		document.formulaire.form_sent.value=0;
		document.formulaire.add_type.value=\''.$add_type.'\';
		document.formulaire.submit();
	}
}

function remove_item(origin) {
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
</script>';

$message = '';

if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    $UserList = $_POST['sessionUsersList'] ?? [];
    if (!is_array($UserList)) {
        $UserList = [];
    }
    if (1 == $form_sent) {
        if (0 == $access_url_id) {
            Display::addFlash(Display::return_message(get_lang('Select a URL')));
            header('Location: access_url_edit_users_to_url.php');

            exit;
        }
        if (is_array($UserList)) {
            $result = UrlManager::update_urls_rel_user($UserList, $access_url_id, true);
            $url_info = UrlManager::get_url_data_from_id($access_url_id);
            if (!empty($result)) {
                $message .= 'URL: '.$url_info['url'].'<br />';
            }

            if (!empty($result['users_added'])) {
                $message .= '<h4>'.get_lang('Users added').':</h4>';
                $i = 1;
                $user_added_list = [];
                foreach ($result['users_added'] as $user) {
                    $user_info = api_get_user_info($user);
                    if (!empty($user_info)) {
                        $user_added_list[] = $i.'. '.api_get_person_name($user_info['firstname'], $user_info['lastname'], null, null, null, $user_info['username']);
                        $i++;
                    }
                }
                if (!empty($user_added_list)) {
                    $message .= implode(', ', $user_added_list);
                }
            }

            if (!empty($result['users_deleted'])) {
                $message .= '<br /><h4>'.get_lang('Users deleted').': </h4>';
                $user_deleted_list = [];
                $i = 1;
                foreach ($result['users_deleted'] as $user) {
                    $user_info = api_get_user_info($user);
                    if (!empty($user_info)) {
                        $user_deleted_list[] = $i.'. '.api_get_person_name($user_info['firstname'], $user_info['lastname']);
                        $i++;
                    }
                }
                if (!empty($user_deleted_list)) {
                    $message .= implode(', ', $user_deleted_list);
                }
            }
        }
    }
}

Display::display_header($tool_name);

if (!empty($message)) {
    echo Display::return_message($message, 'normal', false);
}

echo '<div class="flex gap-2 items-center mb-4 mt-4">';
echo Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
    api_get_path(WEB_CODE_PATH).'admin/access_urls.php'
);
echo Display::url(
    Display::getMdiIcon(ActionIcon::MULTI_COURSE_URL_ASSIGN, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add user to this URL')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_add_users_to_url.php'
);

$urlAddCsv = Container::getRouter()->generate('chamilo_core_access_url_users_import');
$urlRemoveCsv = Container::getRouter()->generate('chamilo_core_access_url_users_remove');
echo Display::url(
    Display::getMdiIcon(ActionIcon::IMPORT_USERS_TO_URL, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assign users to URLs from CSV')),
    $urlAddCsv
);

echo Display::url(
    Display::getMdiIcon(ActionIcon::REMOVE_USERS_FROM_URL, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Remove users from URLs with a CSV file')),
    $urlRemoveCsv
);
echo '</div>';

Display::page_subheader2($tool_name);

/**
 * Small helper to skip technical/fallback users from lists.
 *
 * We avoid relying only on status when data comes from UrlManager::get_url_rel_user_data(),
 * because that result might not include the status field.
 */
function is_fallback_username(array $user): bool
{
    return isset($user['username']) && 'fallback_user' === $user['username'];
}

?>
    <h2 class="text-xl font-semibold text-gray-800 mt-4 mb-2">
        <?php echo $tool_name; ?>
    </h2>
<?php
$nosessionUsersList = $sessionUsersList = [];
$ajax_search = 'unique' === $add_type ? true : false;

if ($ajax_search) {
    $Users = UrlManager::get_url_rel_user_data($access_url_id);
    foreach ($Users as $user) {
        if (is_fallback_username($user)) {
            continue;
        }

        $sessionUsersList[$user['user_id']] = $user;
    }
} else {
    $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';

    $Users = UrlManager::get_url_rel_user_data(null, $order_clause);
    foreach ($Users as $user) {
        if (is_fallback_username($user)) {
            continue;
        }

        if ($user['access_url_id'] == $access_url_id) {
            $sessionUsersList[$user['user_id']] = $user;
        }
    }

    $sql = "SELECT
                u.id as user_id,
                u.lastname,
                u.firstname,
                u.username
            FROM $tbl_user u
            WHERE u.status NOT IN (".ANONYMOUS.', '.User::ROLE_FALLBACK.')
            '.$order_clause;

    $result = Database::query($sql);
    $Users = Database::store_result($result);
    $user_list_leys = array_keys($sessionUsersList);
    foreach ($Users as $user) {
        if (is_fallback_username($user)) {
            continue;
        }

        if (!in_array($user['user_id'], $user_list_leys)) {
            $nosessionUsersList[$user['user_id']] = $user;
        }
    }
}
$total_users = count($nosessionUsersList) + count($sessionUsersList);
$urlList = Container::getAccessUrlRepository()->findAll();

$url_selected = '';
foreach ($urlList as $url) {
    if ($url->getId() == $access_url_id) {
        $url_selected = $url->getUrl();

        break;
    }
}

?>
    <div class="flex space-x-2 border-gray-300 pb-2 mb-4">
        <a href="<?php echo api_get_self(); ?>?add_type=unique&access_url_id=<?php echo $access_url_id; ?>"
           class="text-sm px-4 py-2 transition <?php echo 'unique' === $add_type
               ? 'border-b-2 border-primary text-primary font-semibold'
               : 'text-gray-500 hover:text-primary'; ?>">
            <?php echo get_lang('Single registration'); ?>
        </a>

        <a href="<?php echo api_get_self(); ?>?add_type=multiple&access_url_id=<?php echo $access_url_id; ?>"
           class="text-sm px-4 py-2 transition <?php echo 'multiple' === $add_type
               ? 'border-b-2 border-primary text-primary font-semibold'
               : 'text-gray-500 hover:text-primary'; ?>">
            <?php echo get_lang('Multiple registration'); ?>
        </a>
    </div>

<br /><br />
    <form
        name="formulaire"
        method="post"
        action="<?php echo api_get_self(); ?>"
        class="space-y-6"
        <?php if ($ajax_search) {
            echo 'onsubmit="valide();"';
        } ?>
    >
        <input type="hidden" name="form_sent" value="1" />
        <input type="hidden" name="add_type" value="<?php echo $add_type; ?>" />

        <!-- URL selector -->
        <div class="flex items-center space-x-4">
            <label for="access_url_id" class="text-sm font-medium text-gray-700">
                <?php echo get_lang('Select URL'); ?>
            </label>
            <select
                name="access_url_id"
                id="access_url_id"
                onchange="send();"
                class="w-1/2 rounded-md border border-gray-300 bg-white p-2 shadow-sm focus:border-primary focus:ring-primary"
            >
                <option value="0"><?php echo get_lang('Select URL'); ?></option>
                <?php foreach ($urlList as $url) { ?>
                    <?php
                    $selected = (!empty($access_url_id) && $url->getId() == $access_url_id) ? 'selected' : '';
                    if (1 == $url->getActive()) {
                        ?>
                        <option value="<?php echo $url->getId(); ?>" <?php echo $selected; ?>>
                            <?php echo $url->getUrl(); ?>
                        </option>
                    <?php } ?>
                <?php } ?>
            </select>
        </div>

        <div class="text-sm text-gray-600">
            <p><?php echo get_lang('Total available users').': '.$total_users; ?></p>
            <p class="mt-1"><?php echo get_lang('Portal users list').': '.count($nosessionUsersList); ?></p>
            <p class="mt-1"><?php echo get_lang('Users of').' '.$url_selected.': '.count($sessionUsersList); ?></p>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700"><?php echo get_lang('Available users'); ?></label>
                <?php if ($ajax_search) { ?>
                    <input
                        type="text"
                        id="user_to_add"
                        onkeyup="xajax_search_users(this.value,document.formulaire.access_url_id.options[document.formulaire.access_url_id.selectedIndex].value)"
                        class="w-full rounded-md border border-gray-300 p-2 text-sm focus:border-primary focus:ring-primary"
                    />
                    <div id="ajax_list_users" class="mt-2"></div>
                <?php } else { ?>
                    <select
                        id="origin_users"
                        name="nosessionUsersList[]"
                        multiple
                        size="15"
                        class="w-full h-[300px] rounded-md border border-gray-300 p-2 text-sm focus:outline-none"
                    >
                        <?php foreach ($nosessionUsersList as $user) { ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo $user['username'].' - '.api_get_person_name($user['firstname'], $user['lastname']); ?>
                            </option>
                        <?php } ?>
                    </select>
                <?php } ?>
            </div>

            <div class="flex flex-col items-center justify-center space-y-4">
                <?php if (!$ajax_search) { ?>
                    <button
                        type="button"
                        onclick="moveSelectedOptions('origin_users', 'destination_users')"
                        class="rounded-full bg-primary p-2 hover:bg-primary/80 focus:outline-none focus:ring"
                    >
                        <i class="mdi mdi-fast-forward-outline text-white text-2xl"></i>
                    </button>
                    <button
                        type="button"
                        onclick="moveSelectedOptions('destination_users', 'origin_users')"
                        class="rounded-full bg-secondary p-2 hover:bg-secondary/80 focus:outline-none focus:ring"
                    >
                        <i class="mdi mdi-rewind-outline text-white text-2xl"></i>
                    </button>
                <?php } else { ?>
                    <button
                        type="button"
                        onclick="removeSelectedOptions('destination_users')"
                        class="rounded-full bg-danger p-2 hover:bg-danger/80 focus:outline-none focus:ring"
                    >
                        <i class="mdi mdi-close text-white text-2xl"></i>
                    </button>
                <?php } ?>
            </div>

            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700"><?php echo get_lang('Assigned users'); ?></label>
                <select
                    id="destination_users"
                    name="sessionUsersList[]"
                    multiple
                    size="15"
                    class="w-full h-[300px] rounded-md border border-gray-300 p-2 text-sm focus:outline-none"
                >
                    <?php foreach ($sessionUsersList as $user) { ?>
                        <option value="<?php echo $user['user_id']; ?>">
                            <?php echo $user['username'].' - '.api_get_person_name($user['firstname'], $user['lastname']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="text-center mt-6">
            <button
                type="button"
                onclick="submitWithAllDestinationOptionsSelected('formulaire', 'destination_users')"
                class="inline-flex items-center justify-center rounded-lg bg-primary px-6 py-2 text-white shadow hover:bg-primary/90 focus:outline-none focus:ring"
            >
                <?php echo get_lang('Save'); ?>
            </button>
        </div>
    </form>

    <script>
        function moveSelectedOptions(originSelectId, targetSelectId) {
            const origin = document.getElementById(originSelectId);
            const target = document.getElementById(targetSelectId);
            const optionsToMove = [];

            Array.from(origin.options).forEach(option => {
                if (option.selected) {
                    optionsToMove.push(new Option(option.text, option.value));
                    option.remove();
                }
            });

            optionsToMove.forEach(option => target.add(option));
            sortSelectOptions(target);
            target.selectedIndex = -1;
        }

        function removeSelectedOptions(selectId) {
            const select = document.getElementById(selectId);
            Array.from(select.options).forEach(option => {
                if (option.selected) {
                    option.remove();
                }
            });
        }

        function sortSelectOptions(selectElement) {
            const sortedOptions = Array.from(selectElement.options)
                .sort((a, b) => a.text.toLowerCase().localeCompare(b.text.toLowerCase()));

            selectElement.innerHTML = '';
            sortedOptions.forEach(option => selectElement.add(option));
        }

        function submitWithAllDestinationOptionsSelected(formId, destinationSelectId) {
            const form = document.forms[formId];
            const select = document.getElementById(destinationSelectId);

            Array.from(select.options).forEach(option => {
                option.selected = true;
            });

            form.submit();
        }
    </script>

<?php
Display::display_footer();
