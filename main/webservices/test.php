<?php
require_once '../inc/lib/nusoap/nusoap.php';
require_once '../inc/conf/configuration.php';
?>
<html>
<body>
<div class="results">
<?php
$server = $_configuration['root_web'].'main/webservices/';
//$script = 'registration.soap.php';
$script = 'courses_list.soap.php';
$contact= $server.$script.'?wsdl';
$client = new nusoap_client($contact);
$err = $client->getError();
if ($err) {
    // Display the error
    echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
    // At this point, you know the call that follows will fail
}
$response = array();
if (!empty($_POST['function'])) {
  $response = $client->call($_POST['function']);
  echo '<pre>#';
  print_r($response);
  echo '#</pre>';
}
?>
</div>
<form method="POST" action="">
<label for="function">Function</label>
<input type="text" name="function" value="<?php echo $_POST['function'];?>"></input><br />
<label for="param[0]">Param 0</label>
<input type="text" name="param[0]" value="<?php echo $_POST['param'][0];?>"></input><br />
<input type="submit" name="submit" value="Send"/>
</form>
