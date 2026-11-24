<?php

declare(strict_types=1);

use Chamilo\CourseBundle\Entity\CLp;

function get_lp_Id($idPageT)
{
    $idPageT = (int) $idPageT;
    $lpid = 0;

    $rows = Database::getManager()->getConnection()->createQueryBuilder()
        ->select('lp_id')
        ->from('plugin_oel_tools_teachdoc')
        ->where('id = :id')
        ->setParameter('id', $idPageT)
        ->executeQuery()
        ->fetchAllAssociative()
    ;
    foreach ($rows as $Part) {
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
    // Broken today: c_lp.c_id does not exist anymore (Unknown column), left raw.
    $resultC = Database::query($sqlC);
    while ($PartC = Database::fetch_array($resultC)) {
        $courseDir = $PartC['directory'];
    }

    return $courseDir;
}

function update_lp_infos($lp_id, $title, $local_folder): void
{
    $lp_id = (int) $lp_id;
    Database::getManager()->createQueryBuilder()
        ->update(CLp::class, 'lp')
        ->set('lp.path', ':path')
        ->set('lp.defaultViewMod', ':viewMod')
        ->set('lp.title', ':title')
        ->where('lp.iid = :iid')
        ->setParameter('path', (string) $local_folder.'/.')
        ->setParameter('viewMod', 'embedframe')
        ->setParameter('title', (string) $title)
        ->setParameter('iid', $lp_id)
        ->getQuery()
        ->execute()
    ;
}

function getCollectionPages($idPage): array
{
    $idPage = (int) $idPage;
    $result = [];
    $connection = Database::getManager()->getConnection();

    $ip = 0;
    $ipReal = 0;
    $resultOne = $connection->createQueryBuilder()
        ->select('id', 'title', 'type_node', 'behavior', 'colors', 'quizztheme', 'leveldoc')
        ->from('plugin_oel_tools_teachdoc')
        ->where('id = :id')
        ->setParameter('id', $idPage)
        ->executeQuery()
        ->fetchAllAssociative()
    ;

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

    $resultSubs = $connection->createQueryBuilder()
        ->select('id', 'title', 'type_node', 'behavior', 'leveldoc', 'colors', 'quizztheme')
        ->from('plugin_oel_tools_teachdoc')
        ->where('type_node <> -1 AND behavior <> 3 AND id_parent = :parent')
        ->setParameter('parent', $idPage)
        ->orderBy('order_lst', 'ASC')
        ->executeQuery()
        ->fetchAllAssociative()
    ;

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
    $idPage = (int) $idPage;
    $result = [];

    $ip = 0;

    $resultSubs = Database::getManager()->getConnection()->createQueryBuilder()
        ->select('id', 'title', 'type_node', 'behavior', 'leveldoc', 'colors', 'quizztheme')
        ->from('plugin_oel_tools_teachdoc')
        ->where('behavior = 3 AND id_parent = :parent')
        ->setParameter('parent', $idPage)
        ->executeQuery()
        ->fetchAllAssociative()
    ;

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
    $lpid = (int) $lpid;
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
