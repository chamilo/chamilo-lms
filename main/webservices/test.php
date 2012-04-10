<?php
exit;

require_once '../inc/global.inc.php';
require_once '../inc/lib/nusoap/nusoap.php';
require_once '../inc/conf/configuration.php';

?>
<html>
<body>
<div class="results">
<?php
$server = api_get_path(WEB_CODE_PATH).'webservices/';
$serversys = api_get_path(SYS_CODE_PATH).'webservices/';
//$script = 'registration.soap.php';
$script = isset($_POST['script']) ? $_POST['script'] : false;
$function = isset($_POST['function']) ? $_POST['function'] : false;

$contact= $server.$script.'?wsdl';

$client = new nusoap_client($contact);
$err = $client->getError();
if ($err) {
    // Display the error
    echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
    // At this point, you know the call that follows will fail
}
$response = array();
if (!empty($function)) {
    $response = $client->call($function);
    echo '<pre>';
    print_r($response);
    echo '#</pre>';
} else {
    echo "empty function $function";
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
<input type="text" name="function" value="<?php echo $function;?>"></input><br />
<label for="param[0]">Param 0</label>
<input type="text" name="param[0]" ></input><br />
<input type="submit" name="submit" value="Send"/>
</form>
