<?php
require_once '../inc/lib/nusoap/nusoap.php';
require_once '../inc/conf/configuration.php';
?>
<html>
<body>
<div class="results">
<?php
$server = $_configuration['root_web'].'main/webservices/';
$serversys = $_configuration['root_sys'].'main/webservices/';
//$script = 'registration.soap.php';
$script = $_POST['script'];
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
$list = scandir($serversys);
$scripts = array();
foreach ($list as $item) {
  if (substr($item,0,1) == '.') { continue; }
  if (substr($item,-8)=='soap.php') {
    $scripts[] = $item;
  }
}
?>
</div>
<form method="POST" action="">
<label for="script">Script</label>
<select name="script">
<?php 
foreach ($scripts as $script) {
  echo '<option value="'.$script.'">'.$script.'</script>';
}
?>
</select><br />
<label for="function">Function</label>
<input type="text" name="function" value="<?php echo $_POST['function'];?>"></input><br />
<label for="param[0]">Param 0</label>
<input type="text" name="param[0]" value="<?php echo $_POST['param'][0];?>"></input><br />
<input type="submit" name="submit" value="Send"/>
</form>
