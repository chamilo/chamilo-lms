<?php

/* For licensing terms, see /license.txt */
/**
 * List sessions categories.
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);
api_protect_limit_for_session_admin();

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
$htmlHeadXtra[] = '<script>
function selectAll(idCheck,numRows,action) {
    for(i = 0; i < numRows; i++) {
        idcheck = document.getElementById(idCheck + "_" + i);
        idcheck.checked = action == "true";
    }
}
</script>';

$tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

$page = isset($_GET['page']) ? (int) $_GET['page'] : null;
$action = isset($_REQUEST['action']) ? Security::remove_XSS($_REQUEST['action']) : null;
$columns = ['name', 'nbr_session', 'date_start', 'date_end'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $columns) ? Security::remove_XSS($_GET['sort']) : 'name';
$idChecked = isset($_REQUEST['idChecked']) ? Security::remove_XSS($_REQUEST['idChecked']) : null;
$order = $_REQUEST['order'] ?? 'ASC';
$order = $order === 'ASC' ? 'DESC' : 'ASC';
$keyword = isset($_REQUEST['keyword']) ? Security::remove_XSS($_REQUEST['keyword']) : null;

if ($action === 'delete_on_session' || $action === 'delete_off_session') {
    $delete_session = $action === 'delete_on_session' ? true : false;
    SessionManager::delete_session_category($idChecked, $delete_session);
    Display::addFlash(Display::return_message(get_lang('SessionCategoryDelete')));
    header('Location: '.api_get_self().'?sort='.$sort);
    exit();
}

$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('SessionList')];

if (isset($_GET['search']) && $_GET['search'] === 'advanced') {
    $interbreadcrumb[] = ['url' => 'session_category_list.php', 'name' => get_lang('ListSessionCategory')];
    $tool_name = get_lang('SearchASession');
    Display::display_header($tool_name);
    $form = new FormValidator('advanced_search', 'get');
    $form->addElement('header', '', $tool_name);
    $active_group = [];
    $active_group[] = $form->createElement('checkbox', 'active', '', get_lang('Active'));
    $active_group[] = $form->createElement('checkbox', 'inactive', '', get_lang('Inactive'));
    $form->addGroup($active_group, '', get_lang('ActiveSession'), null, false);
    $form->addButtonSearch(get_lang('SearchUsers'));
    $defaults['active'] = 1;
    $defaults['inactive'] = 1;
    $form->setDefaults($defaults);
    $form->display();
} else {
    $limit = 20;
    $from = $page * $limit;
    //if user is crfp admin only list its sessions
    $where = null;
    if (!api_is_platform_admin()) {
        $where .= empty($keyword) ? "" : " WHERE name LIKE '%".Database::escape_string(trim($keyword))."%'";
    } else {
        $where .= empty($keyword) ? "" : " WHERE name LIKE '%".Database::escape_string(trim($keyword))."%'";
    }
    if (empty($where)) {
        $where = " WHERE access_url_id = ".api_get_current_access_url_id()." ";
    } else {
        $where .= " AND access_url_id = ".api_get_current_access_url_id()." ";
    }

    $table_access_url_rel_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
    $query = "SELECT sc.*, (
                SELECT count(s.id) FROM $tbl_session s
                INNER JOIN $table_access_url_rel_session us
                ON (s.id = us.session_id)
                WHERE
                    s.session_category_id = sc.id AND
                    access_url_id = ".api_get_current_access_url_id()."
                ) as nbr_session
	 			FROM $tbl_session_category sc
	 			$where
	 			ORDER BY `$sort` $order
	 			LIMIT $from,".($limit + 1);

    $query_rows = "SELECT count(*) as total_rows
                  FROM $tbl_session_category sc $where ";
    $result_rows = Database::query($query_rows);
    $recorset = Database::fetch_array($result_rows);
    $num = $recorset['total_rows'];
    $result = Database::query($query);
    $Sessions = Database::store_result($result);
    $nbr_results = sizeof($Sessions);
    $tool_name = get_lang('ListSessionCategory');
    Display::display_header($tool_name); ?>
    <div class="actions">
        <div class="row">
            <div class="col-md-6">
                <?php
                echo Display::url(
                    Display::return_icon('new_folder.png', get_lang('AddSessionCategory'), [], ICON_SIZE_MEDIUM),
                    api_get_path(WEB_CODE_PATH).'session/session_category_add.php'
                );
    echo Display::url(
                    Display::return_icon('session.png', get_lang('ListSession'), [], ICON_SIZE_MEDIUM),
                    api_get_path(WEB_CODE_PATH).'session/session_list.php'
                ); ?>
            </div>
            <div class="col-md-6">
                <div class="pull-right">
                    <form method="POST" action="session_category_list.php" class="form-inline">
                        <div class="form-group">
                            <input class="form-control" type="text" name="keyword" value="<?php echo $keyword; ?>"
                                   aria-label="<?php echo get_lang('Search'); ?>"/>
                            <button class="btn btn-default" type="submit" name="name"
                                    value="<?php echo get_lang('Search'); ?>"><em
                                        class="fa fa-search"></em> <?php echo get_lang('Search'); ?></button>
                            <!-- <a href="session_list.php?search=advanced"><?php echo get_lang('AdvancedSearch'); ?></a> -->
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <form method="post" action="<?php echo api_get_self(); ?>?action=delete&sort=<?php echo $sort; ?>"
          onsubmit="if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
        <?php
        if (count($Sessions) == 0 && isset($_POST['keyword'])) {
            echo Display::return_message(get_lang('NoSearchResults'), 'warning');
        } else {
            if ($num > $limit) {
                ?>
                <div>
                    <?php
                    if ($page) {
                        ?>
                        <a href="<?php echo api_get_self(); ?>?page=<?php echo $page
                            - 1; ?>&sort=<?php echo $sort; ?>&order=<?php echo Security::remove_XSS(
                            $order
                        ); ?>&keyword=<?php echo $keyword; ?><?php echo @$cond_url; ?>"><?php echo get_lang(
                                'Previous'
                            ); ?></a>
                        <?php
                    } else {
                        echo get_lang('Previous');
                    } ?>
                    |
                    <?php
                    if ($nbr_results > $limit) {
                        ?>
                        <a href="<?php echo api_get_self(); ?>?page=<?php echo $page
                            + 1; ?>&sort=<?php echo $sort; ?>&order=<?php echo Security::remove_XSS(
                            $order
                        ); ?>&keyword=<?php echo $keyword; ?><?php echo @$cond_url; ?>"><?php echo get_lang(
                                'Next'
                            ); ?></a>
                        <?php
                    } else {
                        echo get_lang('Next');
                    } ?>
                </div>
                <?php
            } ?>

            <table class="table table-hover table-striped data_table" width="100%">
                <tr>
                    <th>&nbsp;</th>
                    <th><a href="<?php echo api_get_self(); ?>?sort=name&order=<?php echo ($sort == 'name') ? $order
                            : 'ASC'; ?>"><?php echo get_lang('SessionCategoryName'); ?></a></th>
                    <th><a href="<?php echo api_get_self(); ?>?sort=nbr_session&order=<?php echo ($sort
                            == 'nbr_session') ? $order : 'ASC'; ?>"><?php echo get_lang('NumberOfSession'); ?></a></th>
                    <th><a href="<?php echo api_get_self(); ?>?sort=date_start&order=<?php echo ($sort == 'date_start')
                            ? $order : 'ASC'; ?>"><?php echo get_lang('StartDate'); ?></a></th>
                    <th><a href="<?php echo api_get_self(); ?>?sort=date_end&order=<?php echo ($sort == 'date_end')
                            ? $order : 'ASC'; ?>"><?php echo get_lang('EndDate'); ?></a></th>
                    <th><?php echo get_lang('Actions'); ?></th>
                </tr>

                <?php
                $i = 0;
            $x = 0;
            foreach ($Sessions as $key => $enreg) {
                if ($key == $limit) {
                    break;
                }
                $sql = 'SELECT COUNT(session_category_id)
                        FROM '.$tbl_session.' s
                        INNER JOIN '.$table_access_url_rel_session.'  us
                        ON (s.id = us.session_id)
                        WHERE
                            s.session_category_id = '.intval($enreg['id']).' AND
                            us.access_url_id = '.api_get_current_access_url_id();

                $rs = Database::query($sql);
                list($nb_courses) = Database::fetch_array($rs); ?>
                    <tr class="<?php echo $i ? 'row_odd' : 'row_even'; ?>">
                        <td><input type="checkbox" id="idChecked_<?php echo $x; ?>" name="idChecked[]"
                                   value="<?php echo $enreg['id']; ?>"></td>
                        <td><?php echo api_htmlentities($enreg['name'], ENT_QUOTES, $charset); ?></td>
                        <td><?php echo "<a href=\"session_list.php?id_category=".$enreg['id']."\">".$nb_courses
                                ." Session(s) </a>"; ?></td>
                        <td><?php echo api_format_date($enreg['date_start'], DATE_FORMAT_SHORT); ?></td>
                        <td>
                            <?php
                            if (!empty($enreg['date_end']) && $enreg['date_end'] != '0000-00-00') {
                                echo api_format_date($enreg['date_end'], DATE_FORMAT_SHORT);
                            } else {
                                echo '-';
                            } ?>
                        </td>
                        <td>
                            <a href="session_category_edit.php?&id=<?php echo $enreg['id']; ?>">
                                <?php Display::display_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL); ?>
                            </a>
                            <a href="<?php echo api_get_self(
                            ); ?>?sort=<?php echo $sort; ?>&action=delete_off_session&idChecked=<?php echo $enreg['id']; ?>"
                               onclick="if(!confirm('<?php echo get_lang(
                                   'ConfirmYourChoice'
                               ); ?>')) return false;">
                                <?php Display::display_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL); ?>
                            </a>
                        </td>
                    </tr>
                    <?php
                    $i = $i ? 0 : 1;
                $x++;
            }
            unset($Sessions); ?>
            </table>
            <br/>
            <div>
                <?php
                if ($num > $limit) {
                    if ($page) {
                        ?>
                        <a href="<?php echo api_get_self(); ?>?page=<?php echo $page
                            - 1; ?>&sort=<?php echo $sort; ?>&order=<?php echo Security::remove_XSS(
                            $_REQUEST['order']
                        ); ?>&keyword=<?php echo $_REQUEST['keyword']; ?><?php echo @$cond_url; ?>">
                            <?php echo get_lang('Previous'); ?></a>
                        <?php
                    } else {
                        echo get_lang('Previous');
                    } ?>
                    |
                    <?php
                    if ($nbr_results > $limit) {
                        ?>

                        <a href="<?php echo api_get_self(); ?>?page=<?php echo $page
                            + 1; ?>&sort=<?php echo $sort; ?>&order=<?php echo Security::remove_XSS(
                            $_REQUEST['order']
                        ); ?>&keyword=<?php echo $_REQUEST['keyword']; ?><?php echo @$cond_url; ?>">
                            <?php echo get_lang('Next'); ?></a>

                        <?php
                    } else {
                        echo get_lang('Next');
                    }
                } ?>
            </div>
            <div class="row">
                <div class="col-sm-4">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default" onclick="selectAll('idChecked',<?php echo $x; ?>,'true');">
                            <?php echo get_lang('SelectAll'); ?>
                        </button>
                        <button type="button" class="btn btn-default" onclick="selectAll('idChecked',<?php echo $x; ?>,'false');">
                            <?php echo get_lang('UnSelectAll'); ?>
                        </button>
                    </div>
                </div>
                <div class="col-sm-6">
                    <select class="selectpicker form-control" name="action">
                        <option value="delete_off_session" selected="selected">
                            <?php echo get_lang('DeleteSelectedSessionCategory'); ?>
                        </option>
                        <option value="delete_on_session">
                            <?php echo get_lang('DeleteSelectedFullSessionCategory'); ?>
                        </option>
                    </select>
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-success" type="submit" name="name" value="<?php echo get_lang('Ok'); ?>">
                        <?php echo get_lang('Ok'); ?>
                    </button>
                </div>
            </div>
            <?php
        } ?>
        </table>
    </form>
    <?php
}
Display::display_footer();
