<?php

/* For licensing terms, see /license.txt */

/**
 * Plugin.
 *
 * @author Jose Angel Ruiz
 */
$cidReset = true;
require_once __DIR__.'/config.php';

api_protect_admin_script();

/** @var \CleanDeletedFilesPlugin $plugin */
$plugin = CleanDeletedFilesPlugin::create();
$plugin_info = $plugin->get_info();
$isPlatformAdmin = api_is_platform_admin();

if ($plugin->isEnabled() && $isPlatformAdmin) {
    $htmlHeadXtra[] = '<script>
    $(function() {
        $(".delete-file").click(function(e) {
            e.preventDefault();
            var path = $(this).prop("href").substr(7);
            if (confirm("'.$plugin->get_lang("ConfirmDelete").'")) {
                $.post(
                    "src/ajax.php",
                    {a:"delete-file", path:path},
                    function(data){
                        if (data.status == "false") {
                            alert(data.message);
                        } else {
                            location.reload();
                        }
                    },
                    "json"
                );
            }
        });

        $(".select_all").click(function(e) {
            var id = $(this).prop("id").substr(7);
            if( $(this).prop("checked") ) {
                $(".checkbox-"+id).prop( "checked", true);
            } else {
                $(".checkbox-"+id).prop( "checked", false);
            }
        });

        $("#delete-selected-files").click(function(e) {
            if (confirm("'.$plugin->get_lang("ConfirmDeleteFiles").'")) {
                var list = [];
                $.each($(".checkbox-item:checked"), function() {
                    list.push($(this).prop("id"));
                });

                $.post(
                    "src/ajax.php",
                    {a:"delete-files-list", list:list},
                    function(data){
                        if (data.status == "false") {
                            alert(data.message);
                        } else {
                            location.reload();
                        }
                    },
                    "json"
                );
            }
        });
    });
    </script>';

    $nameTools = $plugin->get_lang("FileList");
    Display::display_header($nameTools);
    echo Display::page_header($nameTools);

    $pathList = [
        "app/courses",
        "app/upload",
    ];

    function findDeletedFiles($pathRelative)
    {
        global $sizePath;
        $pathAbsolute = api_get_path(SYS_PATH).$pathRelative;
        $result = [];
        if (is_dir($pathAbsolute)) {
            $dir = dir($pathAbsolute);
            while ($file = $dir->read()) {
                if (is_file($pathAbsolute.'/'.$file)) {
                    $filesize = round(filesize($pathAbsolute.'/'.$file) / 1024, 1);
                    $pos = strpos($file, "DELETED");
                    if ($pos !== false) {
                        $result[] = [
                            'path_complete' => $pathAbsolute.'/'.$file,
                            'path_relative' => $pathRelative.'/'.$file,
                            'size' => $filesize,
                        ];
                        $sizePath += $filesize;
                    }
                } else {
                    if ($file != '..' && $file != '.') {
                        $result = array_merge($result, findDeletedFiles($pathRelative.'/'.$file));
                    }
                }
            }
        }

        return $result;
    }

    $sizeTotal = 0;
    $i = 0;
    foreach ($pathList as $pathItem) {
        $sizePath = 0;
        $filesDeletedList = findDeletedFiles($pathItem);
        echo Display::page_subheader($plugin->get_lang("path_dir").": ".$pathItem);

        if (count($filesDeletedList) > 0) {
            echo "<ul>";
            echo "<li>".$plugin->get_lang('FilesDeletedMark').": <strong>".count($filesDeletedList)."</strong>";
            echo "<li>".$plugin->get_lang('FileDirSize').": ";
            if ($sizePath >= 1024) {
                echo "<strong>".round($sizePath / 1024, 1)." Mb</strong>";
            } else {
                echo "<strong>".$sizePath." Kb</strong>";
            }
            echo "</ul>";

            $header = [
                [
                    '<input type="checkbox" id="select_'.$i.'" class="select_all" />',
                    false,
                    null,
                    ['style' => 'text-align:center'],
                ],
                [$plugin->get_lang('path_dir'), true],
                [$plugin->get_lang('size'), true, null, ['style' => 'min-width:85px']],
                [get_lang('Actions'), false],
            ];

            $data = [];
            $deleteIcon = Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL);

            foreach ($filesDeletedList as $value) {
                $tools = Display::url(
                    $deleteIcon,
                    'file://'.$value['path_complete'],
                    ['class' => 'delete-file']
                );

                $row = [
                    '<input type="checkbox"
                        class="checkbox-'.$i.' checkbox-item"
                        id="file://'.$value['path_complete'].'" />',
                    $value['path_relative'],
                    $value['size'].' '.($value['size'] >= 1024 ? 'Mb' : 'Kb'),
                    $tools,
                ];
                $data[] = $row;
            }

            echo Display::return_sortable_table(
                $header,
                $data,
                [],
                ['per_page' => 100],
                []
            );
        } else {
            $message = $plugin->get_lang('NoFilesDeleted');
            echo Display::return_message($message, 'warning', false);
        }
        $sizeTotal += $sizePath;
        echo '<hr>';
        $i++;
    }

    if ($sizeTotal >= 1024) {
        echo $plugin->get_lang('SizeTotalAllDir').": <strong>".round($sizeTotal / 1024, 1).' Mb</strong>';
    } else {
        echo $plugin->get_lang('SizeTotalAllDir').": <strong>".$sizeTotal.' Kb</strong>';
    }
    echo '<hr>';
    echo '<a href="#" id="delete-selected-files" class="btn btn-primary">'.
        $plugin->get_lang("DeleteSelectedFiles").
        '</a>';

    Display::display_footer();
}
