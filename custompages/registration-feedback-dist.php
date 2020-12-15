<?php
/* For licensing terms, see /license.txt */
/**
 * Quick display for user registration.
 *
 * @package chamilo.custompages
 */
/**
 * HTML output.
 */
require_once api_get_path(SYS_PATH).'main/inc/global.inc.php';
require_once __DIR__.'/language.php';
$rootWeb = api_get_path('WEB_PATH');

?>
<html>
<head>
    <title><?php echo custompages_get_lang('Registration'); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script type="text/javascript" src="<?php echo $rootWeb; ?>web/assets/jquery/dist/jquery.min.js"></script>
</head>
<body>
<img src="/custompages/images/page-background.png" class="backgroundimage" />
<section id="registration">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <div class="form-wrap">
                    <div class="logo">
                        <img src="/custompages/images/header.png">
                    </div>
                    <div id="registration-form-box" class="form-box">
                        <div class="block-form-login">
                            <?php echo $content['info']; ?>
                        </div>
                    </div>
                    <div id="footer">
                        <img src="../../custompages/images/footer.png" />
                    </div> <!-- #footer -->
                </div>
            </div>
        </div>
    </div>
</section>
</body>
</html>
