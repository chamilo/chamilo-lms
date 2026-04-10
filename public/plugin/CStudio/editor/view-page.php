<?php

$version = '1.42';
$idPage = 0;
$action = '';

require_once __DIR__.'/../0_dal/dal.global_lib.php';

require_once __DIR__.'/../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../inc/tranformSource.php';

require_once __DIR__.'/../0_dal/dal.save.php';

if (!file_exists('files')) {
    //mkdir('files', 0777, true);
}
if (!file_exists('files/tmp')) {
    //mkdir('files/tmp', 0777, true);
}
$mod = 'template';
if (isset($_GET['mod'])) {
    $mod = $_GET['mod'];
}

if (isset($_GET['id'])) {
    $idPage = (int) $_GET['id'];
} else {
    echo 'Error';

    exit;
}

?>
<!doctype html>
  <html lang="en" >
  <head>
    <meta charset="utf-8" />
    <title>view</title>
    <script>
        var modExport = "<?php echo $mod; ?>";
        var idPageRef = <?php echo $idPage; ?>;
    </script>

    <script src="jscss/jquery.js?v=<?php echo $version; ?>"></script>
    <link href="jscss/oel-teachdoc.css?v=<?php echo $version; ?>" rel="stylesheet" />
    <link href="templates/styles/classic.css?v=<?php echo $version; ?>" rel="stylesheet"/>
    <link href="jscss/oel-teachdoc.css?v=<?php echo $version; ?>" rel="stylesheet" />
    <link href="templates/styles/plug.css?v=<?php echo $version; ?>" rel="stylesheet" />
    <script src="../vendor/html2canvas/html2canvas.min.js?v=<?php echo $version; ?>"></script>
  
</head>
<body style="background:white;" >

<?php if ('template' == $mod) { ?>
<div id="baseHtmltoRender" style="position:absolute;left:0px;width:650px;" >
<?php } ?>

<?php if ('page' == $mod) { ?>
<div id="baseHtmltoRender" style="position:absolute;left:0px;width:700px;" >
<?php } ?>

<?php

  if ('' != $idPage && 0 != $idPage) {
      $Part = get_oel_tools_editor($idPage);
      $base_html = getSrcForEditor($Part['base_html']);
      $base_css = $Part['base_css'];
      $colors_data = $Part['colors'];
      $quizztheme_data = $Part['quizztheme'];
  }

// replace video tag by img
// $base_html = str_replace('<video ', '<div ', $base_html);
// $base_html = str_replace('</video>', '</div>', $base_html);

echo $base_html;

?>
    <style>
        <?php echo $base_css; ?>
        .panel {
            width : 98%!important;
            margin : 0px!important;
        }
        video {
            width : 90%;
            margin-left : 5%;
            margin-right : 5%;
            background-color : gray;
            height : auto;
        }
        .videoByLudi {
            width : 90%;
            margin-left : 5%;
            margin-right : 5%;
            height : 300px;
            background-color : gray;
        }
        .separatorteach {
            position : relative;
            width : 98%;
            margin-left : 1%;
            margin-right : 1%;
            height : 32px;
            border : 0px solid gray;
        }
        .separatorteach:before {
            content : "";
            position : absolute;
            height : 6px;
            background-color : #b3b3b3;
            margin-left : 1%;
            width : 98%;
            top : 12px;
            border-radius : 3px;
        }
        .cell{
            border:dashed 1px #A9CCE3;
            vertical-align: middle;
            padding:10px;
            width: 8%;
            min-width: 250px;
            display: table-cell;
            height: 0;
            height: auto!important;
            min-height: 75px;
        }
        .row {
            display: table;
            padding-top: 10px;
            padding-right: 10px;
            padding-bottom: 10px;
            padding-left: 10px;
            width: 100%;
            height: 0;
            height: auto!important;
            min-height: 75px;
        }
        .panel{
            padding-right : 30px!important;
            padding-left : 30px!important;
        }
    </style>
    <?php
        echo '<link href="'.$VDB->w_get_path(WEB_PLUGIN_PATH);
echo 'CStudio/editor/templates/colors/'.$colors_data;
echo '?v='.$version.'" rel="stylesheet" />';
?>
</div>

<?php if ('template' == $mod) { ?>
    <div id="renderHtmlCanvas" style="position:absolute;left:0px;top:0px;width:100px;height:140px;" >
        <img id="imgHtmlCanvas" style="width:100%;" src="img/cube-oe.gif" />
    </div>
<?php } ?>

<?php if ('page' == $mod) { ?>
    <div id="renderHtmlCanvas" style="position:absolute;left:0px;top:0px;width:200px;height:280px;" >
        <img id="imgHtmlCanvas" style="width:100%;" id="imgHtmlCanvas" src="img/cube-oe.gif" />
    </div>
<?php } ?>

<script src="jscss/viewpage.js?v=<?php echo $version; ?>"></script>
</body>
</html>
