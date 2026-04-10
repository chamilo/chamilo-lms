<?php

declare(strict_types=1);

function get_lp_Id($idPageT)
{
    $idPageT = (int) $idPageT;
    $lpid = 0;

    $sql = "SELECT lp_id FROM plugin_oel_tools_teachdoc WHERE id = $idPageT ";
    $result = Database::query($sql);
    while ($Part = Database::fetch_array($result)) {
        $lpid = $Part['lp_id'];
    }

    return $lpid;
}

function get_directory($lpid)
{
    $lpid = (int) $lpid;

    $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
    $tblCLp = Database::get_course_table(TABLE_LP_MAIN);
    $courseDir = '';
    $sqlC = "SELECT directory FROM $course_table INNER JOIN $tblCLp ON $tblCLp.c_id = $course_table.id WHERE $tblCLp.iid = $lpid";
    $resultC = Database::query($sqlC);
    while ($PartC = Database::fetch_array($resultC)) {
        $courseDir = $PartC['directory'];
    }

    return $courseDir;
}

function update_lp_infos($lp_id, $title, $local_folder): void
{
    $tblCLp = Database::get_course_table(TABLE_LP_MAIN);
    $title = oel_escape_string($title);
    $sqlU = "UPDATE $tblCLp SET $tblCLp.path = '$local_folder/.' , $tblCLp.default_view_mod = 'embedframe' , $tblCLp.title = '$title' ";
    $sqlU .= " WHERE $tblCLp.iid = $lp_id;";
    Database::query($sqlU);
}

function getCollectionPages($idPage): array
{
    $result = [];
    $sql = 'SELECT id , title , type_node , behavior , ';
    $sql .= ' colors , quizztheme , leveldoc ';
    $sql .= ' FROM plugin_oel_tools_teachdoc ';
    $sql .= " WHERE id = $idPage;";

    $ip = 0;
    $ipReal = 0;
    $resultOne = Database::query($sql);

    while ($row = Database::fetch_array($resultOne)) {
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
    $resultSubs = Database::query($sqlSubs);

    while ($rowS = Database::fetch_array($resultSubs)) {
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
    $result = [];

    $ip = 0;

    $sqlSubs = 'SELECT id , title , type_node , behavior , leveldoc , colors , quizztheme ';
    $sqlSubs .= ' FROM plugin_oel_tools_teachdoc ';
    $sqlSubs .= " WHERE behavior = 3 AND id_parent = $idPage;";
    $resultSubs = Database::query($sqlSubs);

    while ($rowS = Database::fetch_array($resultSubs)) {
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
    $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
    $tblCLp = Database::get_course_table(TABLE_LP_MAIN);
    $courseDir = '';
    $sqlC = "SELECT directory FROM $course_table ";
    $sqlC .= " INNER JOIN $tblCLp ON $tblCLp.c_id = $course_table.id  ";
    $sqlC .= " WHERE $tblCLp.iid = $lpid";
    $resultC = Database::query($sqlC);
    while ($PartC = Database::fetch_array($resultC)) {
        $courseDir = $PartC['directory'];
    }

    return $courseDir;
}
