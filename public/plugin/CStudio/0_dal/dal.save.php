<?php

declare(strict_types=1);

/**
 * chamidoc plugin\CStudio\0_dal\dal.save.php.
 *
 * @author Damien Renou <rxxxx.dxxxxx@gmail.com>
 *
 * @version 18/05/2024
 *
 * @param mixed $idPage
 */
function oel_tools_max_order($idPage)
{
    $idPage = (int) $idPage;
    $MaxOrder = 2;

    $sqlMaxOrder = 'SELECT max(order_lst) as order_max FROM plugin_oel_tools_teachdoc ';
    $sqlMaxOrder .= " WHERE id_parent = $idPage or id = $idPage ";

    $VDB = new VirtualDatabase();
    $MaxOrder = $VDB->get_value_by_query($sqlMaxOrder, 'order_max');

    ++$MaxOrder;

    return $MaxOrder;
}

function oel_tools_insert_element($titlenew, $idTopPage, $userId, $MaxOrder, $idUrl, $typeNode)
{
    $idTopPage = (int) $idTopPage;
    $idUrl = (int) $idUrl;
    $userId = (int) $userId;
    $MaxOrder = (int) $MaxOrder;

    $objectId = 'KO';

    $date = new DateTime();
    $year = $date->format('Y');
    $month = $date->format('m');
    $day = $date->format('j');

    $dateStr = $day.'/'.$month.'/'.$year;

    $localcolors = get_local_theme($idTopPage);
    $localQuizzTheme = get_local_quizz_theme($idTopPage);

    $params = [
        'title' => $titlenew,
        'date_create' => $dateStr,
        'id_parent' => $idTopPage,
        'id_user' => $userId,
        'order_lst' => $MaxOrder,
        'type_node' => $typeNode,
        'behavior' => 2,
        'colors' => $localcolors,
        'quizztheme' => $localQuizzTheme,
        'id_url' => $idUrl,
        'recent_save' => 0,
        'lp_id' => 0,
        'type_base' => 0,
        'base_html' => '',
        'base_css' => '',
        'gpscomps' => '',
        'gpsstyle' => '',
        'local_folder' => '',
        'options' => '',
    ];

    $table = 'plugin_oel_tools_teachdoc';

    $VDB = new VirtualDatabase();
    $result = $VDB->insert($table, $params);

    if ($result) {
        $objectId = $VDB->insert_id();
    } else {
        $objectId = 'KO';
    }

    return $objectId;
}

function oel_tools_update_element_compo($GpsComps, $GpsStyle, $idPage): void
{
    $idPage = (int) $idPage;

    $table = 'plugin_oel_tools_teachdoc';
    $params = [
        'gpscomps' => $GpsComps,
        'gpsstyle' => $GpsStyle,
    ];
    $whereConditions = [
        'id = ?' => $idPage,
    ];
    $VDB = new VirtualDatabase();
    $VDB->update($table, $params, $whereConditions);
    echo 'Compo - Saved OK';
}

function oel_tools_save_element_compo($baseHtml, $baseCss, $idPage): void
{
    $idPage = (int) $idPage;
    $table = 'plugin_oel_tools_teachdoc';
    $params = [
        'base_html' => $baseHtml,
        'base_css' => $baseCss,
    ];
    $whereConditions = [
        'id = ?' => $idPage,
    ];
    $VDB = new VirtualDatabase();
    $VDB->update($table, $params, $whereConditions);
}

function get_local_theme($idPageT)
{
    $idPageT = (int) $idPageT;
    $id_parent = 0;
    $localcolors = '';

    $VDB = new VirtualDatabase();

    $sql = "SELECT id_parent FROM plugin_oel_tools_teachdoc 
    WHERE id = $idPageT ";
    $id_parent = $VDB->get_value_by_query($sql, 'id_parent');

    if ($id_parent > 0) {
        $sql = "SELECT colors FROM plugin_oel_tools_teachdoc 
        WHERE id = $id_parent ";
        $localcolors = $VDB->get_value_by_query($sql, 'colors');
    }

    if ('' == $localcolors) {
        $sql = "SELECT colors FROM plugin_oel_tools_teachdoc 
        WHERE id = $idPageT and  id_parent = 0";
        $localcolors = $VDB->get_value_by_query($sql, 'colors');
    }

    return $localcolors;
}

function get_local_quizz_theme($idPageT)
{
    $idPageT = (int) $idPageT;
    $localcolors = '';

    $sql = "SELECT quizztheme FROM plugin_oel_tools_teachdoc 
    WHERE id = $idPageT ";

    $VDB = new VirtualDatabase();

    return $VDB->get_value_by_query($sql, 'quizztheme');
}

function get_local_folder($idPageT)
{
    $idPageT = (int) $idPageT;
    $id_parent = 0;
    $localfolder = '';

    $VDBgetlocal = new VirtualDatabase();

    $sql = "SELECT id_parent FROM plugin_oel_tools_teachdoc 
    WHERE id = $idPageT ";
    $id_parent = $VDBgetlocal->get_value_by_query($sql, 'id_parent');

    if ($id_parent > 0) {
        $sql = "SELECT local_folder FROM plugin_oel_tools_teachdoc 
        WHERE id = $id_parent ";
        $localfolder = $VDBgetlocal->get_value_by_query($sql, 'local_folder');
    }
    if ('' == $localfolder) {
        $sql = "SELECT local_folder FROM plugin_oel_tools_teachdoc 
        WHERE id = $idPageT and  id_parent = 0";
        $localfolder = $VDBgetlocal->get_value_by_query($sql, 'local_folder');
    }

    return $localfolder;
}

function get_top_page_id($idPage)
{
    $idPage = (int) $idPage;
    $topPage = 0;

    $sqlNS = 'SELECT id_parent FROM plugin_oel_tools_teachdoc ';
    $sqlNS .= " WHERE id = $idPage ";

    $VDB = new VirtualDatabase();

    return $VDB->get_value_by_query($sqlNS, 'id_parent');
}

function get_top_page_by_lpid($idLudiLP)
{
    $idLudiLP = (int) $idLudiLP;
    $sql = 'SELECT id FROM plugin_oel_tools_teachdoc ';
    $sql .= " WHERE lp_id = $idLudiLP AND id_parent = 0 ";

    $idLudiProject = '';

    $VDB = new VirtualDatabase();

    return $VDB->get_value_by_query($sql, 'id');
}

function range_all_pages($idTopPage, $idPage, $action, $idUrl): void
{
    $idPage = (int) $idPage;
    $action = (int) $action;
    $findBefore = false;
    $idOld = 0;
    $orderOld = 0;
    $orderActual = 0;
    $idBefore = 0;
    $orderBefore = 0;
    $idAfter = 0;
    $orderAfter = 0;
    $table = 'plugin_oel_tools_teachdoc';

    // MAJ
    $sqlSubs = 'SELECT id,order_lst FROM plugin_oel_tools_teachdoc ';
    $sqlSubs .= " WHERE type_node <> -1 AND id_parent = $idTopPage ORDER BY order_lst ";

    $indexOrder = 1;

    $VDB = new VirtualDatabase();
    $resultSubs = $VDB->query_to_array($sqlSubs);

    foreach ($resultSubs as $key => $PartSub) {
        $id = $PartSub['id'];
        $sqlUpdate = ' UPDATE plugin_oel_tools_teachdoc ';
        $sqlUpdate .= " SET order_lst = $indexOrder ";
        $sqlUpdate .= " WHERE plugin_oel_tools_teachdoc.id = $id ";
        $VDB->query($sqlUpdate);
        ++$indexOrder;
    }

    // BEFORE NEXT
    $resultSubs = $VDB->query_to_array($sqlSubs);
    foreach ($resultSubs as $key => $PartSub) {
        if ($findBefore) {
            $idAfter = $PartSub['id'];
            $orderAfter = $PartSub['order_lst'];
            $findBefore = false;
        }
        if ($idPage == $PartSub['id']) {
            $findBefore = true;
            $orderActual = $PartSub['order_lst'];
            $idBefore = $idOld;
            $orderBefore = $orderOld;
        }
        $idOld = $PartSub['id'];
        $orderOld = $PartSub['order_lst'];
    }

    if (0 == $action) {
        if ($idBefore > 0) {
            $params = ['order_lst' => $orderActual];
            $VDB->update($table, $params, ['id = ? AND id_url = ?' => [$idBefore, $idUrl]]);
            if ($orderActual > 0) {
                $params = ['order_lst' => ($orderActual - 1)];
                $VDB->update($table, $params, ['id = ? AND id_url = ?' => [$idPage, $idUrl]]);
                echo 'OK';
            } else {
                echo 'KO';
            }
        }
    }

    if (1 == $action) {
        if ($idAfter > 0) {
            $params = ['order_lst' => $orderActual];
            $VDB->update($table, $params, ['id = ? AND id_url = ?' => [$idAfter, $idUrl]]);

            if ($orderActual > 0) {
                $params = ['order_lst' => ($orderActual + 1)];
                $VDB->update($table, $params, ['id = ? AND id_url = ?' => [$idPage, $idUrl]]);
                echo 'OK';
            } else {
                echo 'KO';
            }
        }
    }
}

function get_oel_tools_infos($idPageTop)
{
    $VDB = new VirtualDatabase();

    $idPageTop = (int) $idPageTop;
    $UrlWhere = '';

    $arrayKeys = [
        'lp_id' => 0,
        'title' => '',
        'local_folder' => '',
        'optionsProject' => '',
        'optionsProjectImg' => '',
        'optionsProjectCheck' => '',
        'optionsProjectMessKo' => '',
        'quizztheme' => '',
        'date_create' => '',
    ];

    if (($VDB->w_is_platform_admin() || $VDB->w_is_session_admin()) && $VDB->w_get_multiple_access_url()) {
        $idurl = $VDB->w_get_current_access_url_id();
        $UrlWhere = " AND id_url = $idurl ";
    }

    $sql = 'SELECT title, lp_id,local_folder,options,quizztheme,date_create FROM plugin_oel_tools_teachdoc ';
    $sql .= "WHERE id = $idPageTop $UrlWhere";

    $result = $VDB->query_to_array($sql);
    foreach ($result as $key => $Part) {
        $arrayKeys['lp_id'] = $Part['lp_id'];
        $arrayKeys['title'] = $Part['title'];
        $arrayKeys['local_folder'] = $Part['local_folder'];
        $arrayKeys['optionsProject'] = $Part['options'];
        $arrayKeys['quizztheme'] = $Part['quizztheme'];
        $arrayKeys['date_create'] = $Part['date_create'];
        $arrayKeys['optionsProjectLang'] = 'en';
        $arrayKeys['optionsProjectMessKo'] = 'Page incomplete';
        $arrayKeys['optionsProjectImg'] = '';
        $arrayKeys['optionsProjectCheck'] = ' ';

        $optionsProject = $Part['options'];

        if ('' != $optionsProject) {
            $optionsProject .= '@@@@@';
            $optD = explode('@', $optionsProject);
            $arrayKeys['optionsProjectCheck'] = ' '.$optD[1];

            $imgSrc = $optD[0];
            $arrayKeys['optionsProjectImg'] = $imgSrc;

            if ('' != $optD[2]) {
                $arrayKeys['optionsProjectMessKo'] = $optD[2];
            } else {
                $arrayKeys['optionsProjectMessKo'] = 'Page incomplete';
            }

            if ('' != $optD[3]) {
                $arrayKeys['optionsProjectLang'] = $optD[3];
            } else {
                $arrayKeys['optionsProjectLang'] = 'en';
            }
        }
    }

    return $arrayKeys;
}

function get_oel_tools_options($idPage)
{
    $options = '';
    $idPage = (int) $idPage;
    $sql = 'SELECT options';
    $sql .= ' FROM plugin_oel_tools_teachdoc ';
    $sql .= " WHERE id = $idPage ";
    $VDB = new VirtualDatabase();

    return $VDB->get_value_by_query($sql, 'options');
}

function get_oel_tools_editor($idPage)
{
    $idPage = (int) $idPage;

    $arrayKeys = [
        'title' => 'Empty title',
        'base_html' => '',
        'base_css' => '',
        'type_base' => 0,
        'gpscomps' => '',
        'gpsstyle' => '',
        'id_parent' => $idPage,
        'colors' => '',
        'quizztheme' => '',
        'options' => '',
        'optionsstr' => '',
        'type_node' => 1,
    ];

    $sql = 'SELECT title,base_html,base_css,gpscomps,gpsstyle,type_base,id_parent,colors,quizztheme,type_node,options';
    $sql .= ' FROM plugin_oel_tools_teachdoc ';
    $sql .= " WHERE id = $idPage ";

    $VDB = new VirtualDatabase();
    $resultParts = $VDB->query_to_array($sql);

    foreach ($resultParts as $key => $value) {
        $Part = $value;
        $arrayKeys['title'] = $Part['title'];
        $arrayKeys['base_html'] = $Part['base_html'];
        $arrayKeys['base_css'] = $Part['base_css'];
        $arrayKeys['type_base'] = $Part['type_base'];
        $arrayKeys['gpscomps'] = $Part['gpscomps'];
        $arrayKeys['gpsstyle'] = $Part['gpsstyle'];
        $arrayKeys['id_parent'] = $Part['id_parent'];
        $arrayKeys['colors'] = $Part['colors'];
        $arrayKeys['quizztheme'] = $Part['quizztheme'];
        $arrayKeys['type_node'] = $Part['type_node'];
        $arrayKeys['optionsstr'] = $Part['options'];
        $arrayKeys['options'] = get_oel_tools_options((int) $arrayKeys['id_parent']);
    }
    if ('' == $arrayKeys['colors']) {
        $arrayKeys['colors'] = 'white-chami.css';
    }
    if ('' == $arrayKeys['quizztheme']) {
        $arrayKeys['quizztheme'] = 'white-quizz.css';
    }
    // if id_parent is null or undefined, set it to 0
    if ('' == $arrayKeys['id_parent']) {
        $arrayKeys['id_parent'] = 0;
    }
    if (0 == $arrayKeys['id_parent']) {
        $arrayKeys['id_parent'] = $idPage;
    }

    return $arrayKeys;
}

function update_oel_tools_color($idPage, $colors_data): void
{
    $idPage = (int) $idPage;

    $table = 'plugin_oel_tools_teachdoc';
    $params = ['colors' => $colors_data];
    $VDB = new VirtualDatabase();
    $VDB->update($table, $params, ['id = ?' => $idPage]);
    $VDB->update($table, $params, ['id_parent = ?' => $idPage]);
}

function update_oel_tools_quizztheme($idPage, $quizztheme_data): void
{
    $idPage = (int) $idPage;

    $table = 'plugin_oel_tools_teachdoc';
    $params = ['quizztheme' => $quizztheme_data];
    $VDB = new VirtualDatabase();
    $VDB->update($table, $params, ['id = ?' => $idPage]);
    $VDB->update($table, $params, ['id_parent = ?' => $idPage]);
}

function get_oel_main_color_quizztheme($quizztheme)
{
    $mainColor = '#b3b3b3';
    if ('eco-chami.css' == $quizztheme) {
        $mainColor = '#52BE80';
    }
    if ('hahmlet-blue.css' == $quizztheme) {
        $mainColor = '#2980B9';
    }
    if ('office-chami.css' == $quizztheme) {
        $mainColor = '#5D6D7E';
    }
    if ('orange-chami.css' == $quizztheme) {
        $mainColor = '#DC7633';
    }
    if ('paper-chami.css' == $quizztheme) {
        $mainColor = '#99A3A4';
    }
    if ('white-chami.css' == $quizztheme) {
        $mainColor = '#289FED';
    }
    if ('white-sky.css' == $quizztheme) {
        $mainColor = '#2980B9';
    }
    if ('white-road.css' == $quizztheme) {
        $mainColor = '#002060';
    }

    return $mainColor;
}

function getCollectionContents($idPage): array
{
    $result = [];
    $sql = 'SELECT id, 
    title,
    type_node,
    behavior,
    colors,
    local_folder,
    base_html,
    base_css,
    gpscomps,
    gpsstyle,
    options
    ';
    $sql .= ' FROM plugin_oel_tools_teachdoc ';
    $sql .= " WHERE type_node <> -1 and id = $idPage ";

    $ip = 0;
    $ipReal = 0;

    $VDB = new VirtualDatabase();
    $resultOne = $VDB->query_to_array($sql);

    foreach ($resultOne as $key => $row) {
        if ('' == $row['colors']) {
            $row['colors'] = 'white-chami.css';
        }
        $result[$ip] = [
            'id' => $row['id'],
            'next_id' => 0,
            'prev_id' => 0,
            'title' => $row['title'],
            'type_node' => $row['type_node'],
            'behavior' => $row['behavior'],
            'type_base' => 0,
            'colors' => $row['colors'],
            'local_folder' => $row['local_folder'],
            'base_html' => $row['base_html'],
            'base_css' => $row['base_css'],
            'gpscomps' => $row['gpscomps'],
            'gpsstyle' => $row['gpsstyle'],
            'options' => $row['options'],
            'index' => $ipReal,
        ];
    }

    ++$ip;
    ++$ipReal;
    $sqlSubs = 'SELECT id, 
    title,
    type_node, 
    behavior, 
    colors,
    local_folder, 
    base_html,
    base_css,
    gpscomps,
    gpsstyle,
    options
    ';
    $sqlSubs .= 'FROM plugin_oel_tools_teachdoc ';
    $sqlSubs .= " WHERE type_node <> -1 AND id_parent = $idPage ORDER BY order_lst ";

    $resultSubs = $VDB->query_to_array($sqlSubs);

    foreach ($resultSubs as $key => $rowS) {
        if ('' == $rowS['colors']) {
            $rowS['colors'] = 'white-chami.css';
        }

        $result[$ip] = [
            'id' => $rowS['id'],
            'title' => $rowS['title'],
            'type_node' => $rowS['type_node'],
            'behavior' => $rowS['behavior'],
            'type_base' => 0,
            'local_folder' => $rowS['local_folder'],
            'base_html' => $rowS['base_html'],
            'base_css' => $rowS['base_css'],
            'gpscomps' => $rowS['gpscomps'],
            'gpsstyle' => $rowS['gpsstyle'],
            'colors' => $rowS['colors'],
            'options' => $rowS['options'],
            'index' => $ipReal,
        ];
        if (3 != $rowS['type_node']) {
            ++$ipReal;
        }
        ++$ip;
    }

    return $result;
}

function oel_add_ctr_rights($idPage): void
{
    $lst_ids = '';

    if (isset($_SESSION['idsessionedition'])) {
        $lst_ids = (string) $_SESSION['idsessionedition'];
    }

    if ('' == $lst_ids) {
        $lst_ids = ';'.(string) $idPage.';';
    } else {
        if (!str_contains($lst_ids, ';'.$idPage.';')) {
            $lst_ids .= (string) $idPage.';';
        }

        $idPageTop = get_top_page_id($idPage);

        if (!str_contains($lst_ids, ';'.$idPageTop.';') && 0 != $idPageTop) {
            $lst_ids .= (string) $idPageTop.';';
        }
    }

    $_SESSION['idsessionedition'] = (string) $lst_ids;
}

function get_collection_oel_tools_logs($filter = ''): array
{
    $result = [];
    $sql = 'SELECT id, 
    id_user,
    id_page, 
    id_project, 
    type_log,
    title, logs,
    date_create,
    result ';
    $sql .= ' FROM plugin_oel_tools_logs ';

    if ('admin' == $filter) {
        $sql .= " WHERE logs LIKE 'export_%' AND type_log = 2 LIMIT 20 ";
    } else {
        $sql .= ' WHERE send_xapi <> 1 ';
    }

    $ip = 0;

    $VDB = new VirtualDatabase();
    $resultOne = $VDB->query_to_array($sql);

    foreach ($resultOne as $key => $row) {
        $ip = $row['id'];
        $result[$ip] = [
            'id' => $row['id'],
            'id_user' => $row['id_user'],
            'id_page' => $row['id_page'],
            'id_project' => $row['id_project'],
            'title' => $row['title'],
            'logs' => $row['logs'],
            'date_create' => $row['date_create'],
            'result' => $row['result'],
        ];
    }

    return $result;
}

function update_collection_oel_tools_logs($idLog): void
{
    $idLog = (int) $idLog;
    $sqlU = 'UPDATE plugin_oel_tools_logs SET send_xapi = 1 ';
    $sqlU .= " WHERE id = $idLog ";
    $VDB = new VirtualDatabase();
    $VDB->query($sqlU);
}
