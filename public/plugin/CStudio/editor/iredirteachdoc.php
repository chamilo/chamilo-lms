<!doctype html>
<html>
    <head>
        <meta charset="utf-8" />

        <style>
            body,html{
                position : relative;
                margin:0;
                background-color:#E6E6E6;
                overflow:hidden;
            }
            .form-progress-contains {
                position : fixed;
                width : 90%;
                left:5%;
                top : 50%;
                height : 200px;
                margin-top : -100px;
                background-color: #E6E6E6;
                text-align : center;
                z-index: 1000;
            }
            .form-studio-update {
                position : relative;
                width : 90%;
                margin-left : 5%;
                margin-right : 5%;
                height : 200px;
                background-color: #E6E6E6;
                text-align : center;
                border : solid 0px GRAY;
            }
            .form-progress-update {
                position : absolute;
                width : 400px;
                left : 50%;
                margin-left : -200px;
                height : 8px;
                border-radius : 4px;
                border : solid 1px gray;
                overflow : hidden;
            }
            .form-progress-update-bar {
                position : absolute;
                width : 0%;
                height : 11px;
                background-color : #7FB3D5;
            }
            .luditopheader {
                position: relative;
                width: 100%;
                height: 70px;
                background-size: contain;
                background-position: center center;
                background-repeat: no-repeat;
                background-image: url(../img/base/oel-teachdoc.png);
            }
        </style>

    </head>
    <body style="width:98%;height:98%;margin:1%;padding:0;" >
        
        <div id="logsreturn" style="position:fixed;bottom:1px;left:1px;color:red;height:450px;overflow:auto;z-index: 2000;" ></div>

        <div class="form-progress-contains">
            </br></br>
            <div class="luditopheader" ></div>
            </br></br>
            <div class="form-progress-update">
                <div class="form-progress-update-bar" ></div>
            </div>
        </div>

    <script src="jscss/jquery.js"></script>
    
    <script>
        <?php
            if (isset($_GET['i'])) {
                $quitExept = 0;
                if (isset($_GET['quit'])) {
                    $quitExept = (int) $_GET['quit'];
                }
                $idPageTop = $_GET['i'];
                if (isset($_GET['redir'])) {
                    $RedirLP = $_GET['redir'];
                    $RedirLP = str_replace('t@d', '/', $RedirLP);
                    $RedirLP = str_replace('t@@d', '?', $RedirLP);
                    $RedirLP = str_replace('t@@@d', '&', $RedirLP);
                    if (1 == $quitExept) {
                        $RedirLP = str_replace('action=view', 'action=list', $RedirLP);
                        $RedirLP = str_replace('isStudentView=true', 'isStudentView=false', $RedirLP);
                        $RedirLP .= '&isStudentView=false';
                    }
                    echo "var idPageTop = $idPageTop;";
                    echo "var RedirLP = '$RedirLP';";
                }
            }
        ?>
    </script>

    <script src="jscss/chamidoc-render.js?v=35"></script>
    </body>
</html>
