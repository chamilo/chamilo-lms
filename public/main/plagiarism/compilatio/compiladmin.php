<?php
/* For licensing terms, see /license.txt */

exit;

ini_set('soap.wsdl_cache_enabled', 0);
ini_set('default_socket_timeout', '1000');

require_once '../../inc/global.inc.php';

$compilatio = new Compilatio();
$use_space = number_format($quotas->usedSpace / 1000000, 2);
$total_space = $quotas->space / 1000000;

echo "<h3>".get_lang('compilatioDescription')."</h3>";

echo "<b>"
    .get_lang('compilatioQuota')
    .":"
    ." </b><br>"
    .get_lang('compilatioCredit')
    .": "
    .$quotas->usedCredits
    .get_lang('compilatioOn')
    .$quotas->credits;

?>
<br><br>
<?php
if (!isset($_GET['action'])) {
    ?>
<body style="margin:0px;padding:0px">
<form style="margin:0px;" method="GET">
    <input type="submit" name="action" value="Test de Connexion SOAP">
</form>
<?php
} else {
        echo get_lang('compilatioConnectionTestSoap')."<br>";
        echo "1) ".get_lang('compilatioServerConnection')."<br>";
        $compilatio = new Compilatio();
        if ($compilatio) {
            echo get_lang('compilatioConnectionAccomplished')."<br>";
            echo "2) ".get_lang('compilatioSendTextToServer')."<br>";
            $text = get_lang('compilatioTestSendText').$compilatio->getKey();
            $id_compi = $compilatio->SendDoc(
            'Doc de test',
            'test',
            'test',
            'text/plain',
            $text
        );
            if (Compilatio::isMd5($id_compi)) {
                echo get_lang('compilatioSuccessfulTransfer')."<br>";
            } else {
                echo get_lang('compilatioFailedTransfer')."<br>";
            }
        } else {
            echo get_lang('compilatioNotConnection')."<br>";
            echo get_lang('compilatioParamVerification')."<br>";
        }
    }
?>
</body>
