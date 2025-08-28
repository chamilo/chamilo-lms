<?php
/* For licensing terms, see /license.txt */

exit;

ini_set('soap.wsdl_cache_enabled', 0);
ini_set('default_socket_timeout', '1000');

require_once '../../inc/global.inc.php';

$compilatio = new Compilatio();
$use_space = number_format($quotas->usedSpace / 1000000, 2);
$total_space = $quotas->space / 1000000;

echo "<h3>".get_lang('Compilatio anti-plagiarism module')."</h3>";

echo "<b>"
    .get_lang('Quotas')
    .":"
    ." </b><br>"
    .sprintf(get_lang('Credits: %s on %s'), $quotas->usedCredits, $quotas->credits);

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
        echo get_lang('SOAP connection test...')."<br>";
        echo "1) ".get_lang("Connection to Compilatio's SOAP server")."<br>";
        $compilatio = new Compilatio();
        if ($compilatio) {
            echo get_lang('Connection successful.')."<br>";
            echo "2) ".get_lang('Sending text to the Compilatio server')."<br>";
            $text = sprintf(get_lang('This is a text sending test to the Compilatio server via its API.\nCompilatio key used: %s'), $compilatio->getKey());
            $id_compi = $compilatio->SendDoc(
            'Doc de test',
            'test',
            'test',
            'text/plain',
            $text
        );
            if (Compilatio::isMd5($id_compi)) {
                echo get_lang('Transfer successful.')."<br>";
            } else {
                echo get_lang('Transfer failed.')."<br>";
                echo get_lang('Check your key, your server ports and possibly your proxy settings.')."<br>";
            }
        } else {
            echo get_lang("Could not connect to Compilatio's SOAP server.")."<br>";
            echo get_lang('Check your key, your server ports and possibly your proxy settings.')."<br>";
        }
    }
?>
</body>
