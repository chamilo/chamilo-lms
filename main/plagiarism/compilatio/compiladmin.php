<?php
/* For licensing terms, see /license.txt */

exit;

require_once '../../inc/global.inc.php';

$use_space = number_format($quotas->usedSpace / 1000000, 2);
$total_space = $quotas->space / 1000000;

echo "<h3>".get_lang('CompilatioDescription')."</h3>";

echo "<b>"
    .get_lang('CompilatioQuota')
    .":"
    ." </b><br>"
    .sprintf(get_lang('CompilatioCreditXOnY'), $quotas->usedCredits, $quotas->credits);

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
        echo get_lang('CompilatioConnectionTestSoap')."<br>";
        echo "1) ".get_lang('CompilatioServerConnection')."<br>";

        try {
            $compilatio = new Compilatio();
        } catch (Exception $e) {
            $compilatio = null;
            echo get_lang('CompilatioNoConnection')."<br>";
            echo get_lang('CompilatioParamVerification')."<br>";
        }

        if ($compilatio) {
            echo get_lang('CompilatioConnectionSuccessful')."<br>";
            echo "2) ".get_lang('CompilatioSendTextToServer')."<br>";
            $text = sprintf(get_lang('CompilatioTextSendingTestKeyX'), $compilatio->getKey());
            try {
                $id_compi = $compilatio->sendDoc(
                    'Doc de test',
                    'test',
                    'test',
                    $text
                );
                echo get_lang('CompilatioSuccessfulTransfer')."<br>";
            } catch (Exception $e) {
                echo get_lang('CompilatioFailedTransfer')."<br>";
                echo get_lang('CompilatioParamVerification')."<br>";
            }
        }
    }
?>
</body>
