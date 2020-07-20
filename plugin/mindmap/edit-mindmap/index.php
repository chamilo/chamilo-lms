<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>MindMap</title>
    <link rel="stylesheet" type="text/css" href="vendor/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/css/app.css">
    <script type="text/javascript" src="vendor/js/base.js"></script>

    <script type="text/javascript">

        var dataMMLoad = ''
        var titleMM = ''

        <?php
        $globalInclude = __DIR__.'/../../main/inc/global.inc.php';
        if (file_exists($globalInclude)) {
            require_once $globalInclude;
        } else {
            $globalInclude = __DIR__.'/../../../main/inc/global.inc.php';
            if (file_exists($globalInclude)) {
                require_once $globalInclude;
            }
        }

        if (isset($_GET['id'])) {
            $idMM = (int) $_GET['id'];
            echo 'var idMM = '.$idMM.';';

            $sql = "SELECT title, mindmap_data FROM plugin_mindmap ";
            $sql .= " WHERE id = $idMM;";

            $resultParts = Database::query($sql);

            while ($part = Database::fetch_array($resultParts)) {
                $titleMM = $part['title'];
                $dataMMLoad = $part['mindmap_data'];
                if ($dataMMLoad != '') {
                    echo 'dataMMLoad = '.$dataMMLoad.';';
                }
                echo 'titleMM = "'.$titleMM.'";';
            }
        }
        ?>
    </script>
</head>
<body>

<div class="app-tool-bar" id="app-tool-bar">
    <ul id="main-menu" class="dropdown-menu">
        <li>
            <a href="javascript:void(0);" title="File" onClick="saveMapProcess();" tabindex="-1"><i
                    class="icon-save"></i></a>
            <ul class="dropdown-menu" role="menu">
                <li><a tabindex="-1" href="javascript:void(0);" command="SaveMapInStorage"><span class="short-cut">Ctrl+S</span>Save
                        Map In Storage</a></li>
            </ul>
        </li>
        <li class="dropdown-submenu" role="menu-trigger">
            <a href="javascript:void(0);" title="Edit" tabindex="-1"><i class="icon-edit"></i></a>
            <ul class="dropdown-menu" role="menu">
                <li><a tabindex="-1" href="javascript:void(0);" command="Copy"><span class="short-cut">Ctrl+C</span>Copy</a>
                </li>
                <li><a tabindex="-1" href="javascript:void(0);" command="Cut"><span
                            class="short-cut">Ctrl+X</span>Cut</a></li>
                <li><a tabindex="-1" href="javascript:void(0);" command="Paste"><span class="short-cut">Ctrl+V</span>Paste</a>
                </li>
                <li class="divider"></li>
                <li><a tabindex="-1" href="javascript:void(0);" command="Undo"><span class="short-cut">Ctrl+Z</span>Undo</a>
                </li>
                <li><a tabindex="-1" href="javascript:void(0);" command="Redo"><span class="short-cut">Ctrl+Y</span>Redo</a>
                </li>
            </ul>
        </li>
        <li class="divider"></li>
        <li id="help"><a href="help/introduce.html" title="Help" tabindex="-1"><i class="icon-question-sign"></i></a>
        </li>
    </ul>
</div>


<div class="app-container" id="map-container">

    <ul class="dropdown-menu" id="context-menu">
        <li><a tabindex="-1" href="javascript:void(0);" command="CreateNewRootNode">Create New Root Node</a></li>
        <li class="divider"></li>
        <li><a tabindex="-1" href="javascript:void(0);" command="Copy"><span class="short-cut">Ctrl+C</span>Copy</a>
        </li>
        <li><a tabindex="-1" href="javascript:void(0);" command="Cut"><span class="short-cut">Ctrl+X</span>Cut</a></li>
        <li><a tabindex="-1" href="javascript:void(0);" command="Paste"><span class="short-cut">Ctrl+V</span>Paste</a>
        </li>
        <li class="divider"></li>
        <li><a tabindex="-1" href="javascript:void(0);" command="Undo"><span class="short-cut">Ctrl+Z</span>Undo</a>
        </li>
        <li><a tabindex="-1" href="javascript:void(0);" command="Redo"><span class="short-cut">Ctrl+Y</span>Redo</a>
        </li>

    </ul>


    <ul class="dropdown-menu" id="node-context-menu">
        <li><a tabindex="-1" href="javascript:void(0);" command="AppendChildNode">Append Child Node</a></li>
        <li><a tabindex="-1" href="javascript:void(0);" command="EditNodeContent">Edit Node</a></li>
        <li><a tabindex="-1" href="javascript:void(0);" command="DeleteNode">Delete</a></li>
        <li class="divider"></li>
        <li><a tabindex="-1" href="javascript:void(0);" command="Copy"><span class="short-cut">Ctrl+C</span>Copy</a>
        </li>
        <li><a tabindex="-1" href="javascript:void(0);" command="Cut"><span class="short-cut">Ctrl+X</span>Cut</a></li>
        <li><a tabindex="-1" href="javascript:void(0);" command="Paste"><span class="short-cut">Ctrl+V</span>Paste</a>
        </li>
    </ul>
</div>

<div class="app-footer">
    <div class="container">
        <p style="color:white;">MindMap</p>
    </div>
</div>

<div style="display:none;">
    <input type="file" name="mapJson" id="mapJson"/>
</div>

<script type="text/javascript" src="vendor/js/mindmap.js"></script>

<script type="text/javascript">
    kampfer.mindMap.init()
</script>
<script type="text/javascript" src="vendor/js/jquery.js"></script>
<script type="text/javascript" src="app-base.js"></script>
</body>
</html>
