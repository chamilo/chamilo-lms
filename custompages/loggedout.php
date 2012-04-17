<?php
/**
 * Displayed after the user has been logged out.
 */

$called_direcly = !function_exists('api_get_path');
if ($called_direcly)
{
    return '';
}

require_once('language.php');
$www = api_get_path('WEB_PATH');

?>
<!DOCTYPE html>
<html>
    <head>
        <title>Custompage - logged out</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <!--[if !IE 6]><!-->
        <link rel="stylesheet" type="text/css" href="<?php echo $www ?>custompages/style.css" />
        <!--<![endif]-->
        <!--[if IE 6]>
        <link rel="stylesheet" type="text/css" href="/custompages/style-ie6.css" />
        <![endif]-->

        <script type="text/javascript" src="<?php echo $www ?>custompages/jquery-1.5.1.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                if (top.location != location) 
                    top.location.href = document.location.href ;
            });
        </script>
    </head>
    <body>
        <div id="backgroundimage">
            <img src="<?php echo $www ?>/custompages/images/page-background.png" class="backgroundimage" alt="background"/>
        </div>
        <div id="wrapper">
            <div id="header">
                <img src="<?php echo $www ?>/custompages/images/header.png" alt="Logo" />
            </div>

            <div id="login-form-box" class="form-box">
                <div id="login-form-info" class="form-info">
                    You have been logged out.
                </div>
            </div>
            <a href="<?php echo $www . 'user_portal.php'; ?>">Go to your portal</a>
            <div id="footer">
                <img src="<?php echo $www ?>/custompages/images/footer.png" alt="footer"/>
            </div> 
        </div> 
    </body>
</html>
