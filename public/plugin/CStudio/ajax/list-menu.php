<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc.
 *
 * @version 18/05/2024
 */
if (isset($_POST['id']) || isset($_GET['id'])) {
    require_once __DIR__.'/../0_dal/dal.global_lib.php';

    require_once '../0_dal/dal.vdatabase.php';
    $VDB = new VirtualDatabase();

    require_once '../0_dal/dal.save.php';

    require_once 'inc/functions.php';

    $indexPage = 0;

    $idPage = get_int_from('id');
    $topPage = -1;

    if (0 != $idPage) {
        $base_behavior = 0;
        $newSearch = false;
        $user = $VDB->w_api_get_user_info();
        $sql = ' SELECT title , id_parent , behavior , leveldoc ';
        $sql .= ' FROM plugin_oel_tools_teachdoc ';
        $sql .= " WHERE id = $idPage;";

        $resultParts = $VDB->query_to_array($sql);

        foreach ($resultParts as $Part) {
            $base_title = $Part['title'];
            $base_id_parent = $Part['id_parent'];
            $base_behavior = $Part['behavior'];
            $base_leveldoc = $Part['leveldoc'];
            if (0 == $base_id_parent) {
                $topPage = $idPage;
            } else {
                $topPage = $base_id_parent;
                $newSearch = true;
            }
            if (0 == $base_leveldoc) {
                $base_leveldoc = 2;
            }
        }

        if ($newSearch) {
            $sqlNS = ' SELECT title , id_parent , behavior , leveldoc ';
            $sqlNS .= 'FROM plugin_oel_tools_teachdoc ';
            $sqlNS .= " WHERE id = $topPage;";

            $resultPartSub = $VDB->query_to_array($sqlNS);

            foreach ($resultPartSub as $PartTop) {
                $base_title = $PartTop['title'];
                $base_behavior = $PartTop['behavior'];
                $base_leveldoc = $PartTop['leveldoc'];
                if (0 == $base_leveldoc) {
                    $base_leveldoc = 2;
                }
            }
        }

        echo '<ul class="list-teachdoc">';

        $base_title_safe = htmlspecialchars((string) $base_title, \ENT_QUOTES, 'UTF-8');
        if ($idPage == $topPage) {
            echo "<li class=activeli ><span behavior=$base_behavior leveldoc=$base_leveldoc class='miniMenuLudi' id='labelMenuLudi$topPage' >$base_title_safe</span>";
        } else {
            echo "<li><span class='miniMenuLudi' behavior=$base_behavior leveldoc=$base_leveldoc id='labelMenuLudi$topPage'  onclick='loadSubLudi($topPage);' >$base_title_safe</span>";
        }
        echo '<span onclick="loadContextMenuSub('.$topPage.','.$indexPage.');" class="badge fa fa-pencil"></span>';
        echo '</li>';

        $sqlSubs = 'SELECT title , id, behavior, type_node , leveldoc FROM plugin_oel_tools_teachdoc ';
        $sqlSubs .= " WHERE type_node <> -1 AND id_parent = $topPage ORDER BY order_lst;";

        $resultSubs = $VDB->query_to_array($sqlSubs);

        ++$indexPage;

        foreach ($resultSubs as $PartSub) {
            $base_subtitle = $PartSub['title'];
            $id_subtitle = $PartSub['id'];
            $base_behavior = $PartSub['behavior'];
            $type_node = $PartSub['type_node'];
            $base_leveldoc = $PartSub['leveldoc'];
            $color_leveldoc = '#3b97e3';

            if (0 == $base_leveldoc) {
                $base_leveldoc = 2;
            }

            $styli = '';
            if (3 == $base_behavior) {
                $styli = " style='text-decoration:line-through;color:#CD6155;' ";
            }

            if (1 == $base_leveldoc) {
                $color_leveldoc = " style='background-color:#52BE80!important;' ";
            } // green
            if (2 == $base_leveldoc) {
                $color_leveldoc = " style='background-color:#3b97e3!important;' ";
            } // blue
            if (3 == $base_leveldoc) {
                $color_leveldoc = " style='background-color:#EB984E!important;' ";
            } // yellow

            $base_subtitle_safe = htmlspecialchars((string) $base_subtitle, \ENT_QUOTES, 'UTF-8');
            if ($idPage == $id_subtitle) {
                echo "<li class=activeli typenode=$type_node >";
                echo "<span class='dotSubLudi dotSubLudi$id_subtitle' $color_leveldoc ></span>";
                echo "<span $styli class='miniMenuLudi' behavior=$base_behavior leveldoc=$base_leveldoc id='labelMenuLudi$id_subtitle' >$base_subtitle_safe</span>";
            } else {
                // Content
                if (2 == $type_node || 4 == $type_node) {
                    echo "<li typenode=$type_node ><span class='dotSubLudi dotSubLudi$id_subtitle' $color_leveldoc ></span>";
                    echo "<span $styli class='miniMenuLudi' onclick='loadSubLudi($id_subtitle);' behavior=$base_behavior leveldoc=$base_leveldoc id='labelMenuLudi$id_subtitle' >$base_subtitle_safe</span>";
                } else {
                    // Title Section
                    if (3 == $type_node) {
                        echo "<li typenode=$type_node ><span $styli class='miniMenuLudi' behavior=$base_behavior leveldoc=$base_leveldoc id='labelMenuLudi$id_subtitle' >$base_subtitle_safe</span>";
                    }
                }
            }

            echo '<span onclick="loadContextMenuSub('.$id_subtitle.','.$indexPage.');" class="badge fa fa-pencil"></span>';
            echo '</li>';

            ++$indexPage;
        }

        echo "<li onClick='displaySubPageEdit($topPage);' class=addli ";
        echo " style='text-align:center;cursor:pointer;min-height:32px;' >";
        echo '</li>';

        echo '</ul>';
    }
}
