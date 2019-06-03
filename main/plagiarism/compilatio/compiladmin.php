<?php
  /*
  Module Compilatio v0.9 pour Dokeos
  */
  /*
   * admin's section:  allow to see the credit's quota and do a SOAP's connection test
   * Partie admin: permet de voir le quotas de cr?dit et de faire un test de connexion SOAP
  */
  ini_set('soap.wsdl_cache_enabled', 0);
  ini_set('default_socket_timeout', '1000');

  require_once '../../inc/lib/api.lib.php';
  require_once '../compilatio/compilatio.class.php';
  require_once '../compilatio/config.php';

  $compilatio = new compilatio(
      $compilatioParameter['key'],
      $compilatioParameter['$urlsoap'],
      $compilatioParameter['proxy_host'],
      $compilatioParameter['proxy_port']
  );
  $use_space = number_format($quotas->usedSpace/1000000, 2);
  $total_space=$quotas->space/1000000;

  echo "<h3>" . get_lang('compilatioDescription') . "</h3>";

  echo "<b>"
      . get_lang('compilatioQuota')
      . ":"
      . " </b><br>"
      . get_lang('compilatioCredit')
      . ": "
      . $quotas->usedCredits
      . get_lang('compilatioOn')
      . $quotas->credits;

?>
<br><br>
<?
  if(!isset($_GET['action']))
  {
?>
<body style="margin:0px;padding:0px">
<form style="margin:0px;" method="GET">
  <input type="submit" name="action" value="Test de Connexion SOAP">
</form>
<?
  } else {
  echo get_lang('compilatioConnectionTestSoap')."<br>";
  echo "1) ".get_lang('compilatioServerConnection')."<br>";
  $compilatio = new compilatio(
      $compilatioParameter['key'],
      $compilatioParameter['$urlsoap'],
      $compilatioParameter['proxy_host'],
      $compilatioParameter['proxy_port']
  );

  if ($compilatio)
  {
    echo get_lang('compilatioConnectionAccomplished')."<br>";
    echo "2) ".get_lang('compilatioSendTextToServer')."<br>";
    $text = get_lang('compilatioTestSendText'). $compilatioParameter['key'];
    $id_compi = $compilatio->SendDoc(
        'Doc de test',
        'test',
        'test',
        'text/plain',
        $text
    );
    if (isMd5($id_compi)) {
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
