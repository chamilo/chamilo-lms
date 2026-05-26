<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;

/**
 * chamidoc plugin\CStudio\0_dal\dal.chamidoc_object.php.
 *
 * @author Damien Renou <rxxxx.dxxxxx@gmail.com>
 *
 * @version 18/05/2024
 *
 * @param mixed $idPageT
 */
function get_lp_Id($idPageT)
{
    $VDB = new VirtualDatabase();

    $idPageT = (int) $idPageT;
    $lpid = 0;

    $sql = "SELECT lp_id FROM plugin_oel_tools_teachdoc WHERE id = $idPageT ";

    return $VDB->get_value_by_query($sql, 'lp_id');
}

function getCourseIdFromLp($lpId): ?int
{
    $lpRepo = Container::getLpRepository();
    $lp = $lpRepo->find($lpId);

    return $lp->getFirstResourceLink()?->getCourse()?->getId();
}

function get_directory($lpid)
{
    $VDB = new VirtualDatabase();

    $courseDir = '';
    if ('chamil' == $VDB->engine) {
        $courseDir = '';
    }

    return $courseDir;
}

function update_lp_infos($lp_id, $title, $local_folder): void
{
    $VDB = new VirtualDatabase();

    if ('chamil' == $VDB->engine) {
        $tblCLp = $VDB->get_course_table(TABLE_LP_MAIN);
        $title = oel_escape_string($title);
        $sqlU = "UPDATE $tblCLp SET $tblCLp.path = '$local_folder/.' , $tblCLp.default_view_mod = 'embedframe' , $tblCLp.title = '$title' ";
        $sqlU .= " WHERE $tblCLp.iid = $lp_id;";
        $VDB->query($sqlU);
    }
}

function getCollectionPages($idPage): array
{
    $VDB = new VirtualDatabase();
    $idPage = (int) $idPage;

    $result = [];
    $sql = 'SELECT id , title , type_node , behavior , ';
    $sql .= ' colors , quizztheme , leveldoc ';
    $sql .= ' FROM plugin_oel_tools_teachdoc ';
    $sql .= " WHERE id = $idPage;";

    $ip = 0;
    $ipReal = 0;

    $resultOne = $VDB->query_to_array($sql);

    foreach ($resultOne as $row) {
        if ('' == $row['colors']) {
            $row['colors'] = 'white-chami.css';
        }
        if ('' == $row['quizztheme']) {
            $row['quizztheme'] = 'white-quizz.css';
        }
        if ('' == $row['leveldoc']) {
            $row['leveldoc'] = 2;
        }
        if (0 == $row['leveldoc']) {
            $row['leveldoc'] = 2;
        }

        $result[$ip] = [
            'id' => $row['id'],
            'next_id' => 0,
            'prev_id' => 0,
            'title' => $row['title'],
            'type_node' => $row['type_node'],
            'behavior' => $row['behavior'],
            'leveldoc' => $row['leveldoc'],
            'colors' => $row['colors'],
            'quizztheme' => $row['quizztheme'],
            'index' => $ipReal,
        ];
    }

    ++$ip;
    ++$ipReal;

    $sqlSubs = 'SELECT id , title , type_node , behavior , leveldoc , colors , quizztheme FROM plugin_oel_tools_teachdoc ';
    $sqlSubs .= " WHERE type_node <> -1 AND behavior <> 3 AND id_parent = $idPage ORDER BY order_lst;";

    $resultSubs = $VDB->query_to_array($sqlSubs);

    foreach ($resultSubs as $rowS) {
        if ('' == $rowS['colors']) {
            $rowS['colors'] = 'white-chami.css';
        }
        if ('' == $rowS['quizztheme']) {
            $rowS['quizztheme'] = 'white-quizz.css';
        }
        if ('' == $rowS['leveldoc']) {
            $rowS['leveldoc'] = 2;
        }
        if (0 == $rowS['leveldoc']) {
            $rowS['leveldoc'] = 2;
        }

        $result[$ip] = [
            'id' => $rowS['id'],
            'title' => $rowS['title'],
            'type_node' => $rowS['type_node'],
            'behavior' => $rowS['behavior'],
            'leveldoc' => $rowS['leveldoc'],
            'colors' => $rowS['colors'],
            'quizztheme' => $rowS['quizztheme'],
            'index' => $ipReal,
        ];
        if (3 != $rowS['type_node']) {
            ++$ipReal;
        }
        ++$ip;
    }

    return $result;
}

function getAlonePages($idPage): array
{
    $VDB = new VirtualDatabase();
    $idPage = (int) $idPage;

    $result = [];

    $ip = 0;

    $sqlSubs = 'SELECT id , title , type_node , behavior , leveldoc , colors , quizztheme ';
    $sqlSubs .= ' FROM plugin_oel_tools_teachdoc ';
    $sqlSubs .= " WHERE behavior = 3 AND id_parent = $idPage;";

    $resultSubs = $VDB->query_to_array($sqlSubs);

    foreach ($resultSubs as $rowS) {
        if ('' == $rowS['colors']) {
            $rowS['colors'] = 'white-chami.css';
        }
        if ('' == $rowS['quizztheme']) {
            $rowS['quizztheme'] = 'white-quizz.css';
        }
        if ('' == $rowS['leveldoc']) {
            $rowS['leveldoc'] = 2;
        }
        if (0 == $rowS['leveldoc']) {
            $rowS['leveldoc'] = 2;
        }

        $result[$ip] = [
            'id' => $rowS['id'],
            'title' => $rowS['title'],
            'type_node' => $rowS['type_node'],
            'behavior' => $rowS['behavior'],
            'leveldoc' => $rowS['leveldoc'],
            'colors' => $rowS['colors'],
            'quizztheme' => $rowS['quizztheme'],
            'index' => $ip,
        ];
        ++$ip;
    }

    return $result;
}

function getDirectoryRender($lpid)
{
    $VDB = new VirtualDatabase();
    $courseDir = '';

    if ('chamil' == $VDB->engine) {
        $course_table = $VDB->get_main_table(TABLE_MAIN_COURSE);
        $tblCLp = $VDB->get_course_table(TABLE_LP_MAIN);
        $sqlC = "SELECT directory FROM $course_table ";
        $sqlC .= " INNER JOIN $tblCLp ON $tblCLp.c_id = $course_table.id  ";
        $sqlC .= " WHERE $tblCLp.iid = $lpid";
        $courseDir = $VDB->get_value_by_query($sqlC, 'directory');
    }

    return $courseDir;
}
