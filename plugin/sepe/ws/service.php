<?php

/* For licensing terms, see /license.txt */

ini_set('log_errors_max_len', 0);
ini_set('soap.wsdl_cache_enabled', '0');
ini_set('soap.wsdl_cache_ttl', '0');

require_once '../../../main/inc/global.inc.php';
require_once '../../../vendor/autoload.php';

ini_set("soap.wsdl_cache_enabled", 0);
$libpath = api_get_path(LIBRARY_PATH);
require_once api_get_path(SYS_PLUGIN_PATH).'sepe/ws/Sepe.php';

require_once $libpath.'nusoap/class.nusoap_base.php';
require_once api_get_path(SYS_PLUGIN_PATH).'sepe/src/wsse/soap-server-wsse.php';

$ns = api_get_path(WEB_PLUGIN_PATH)."sepe/ws/ProveedorCentroTFWS.wsdl";
$wsdl = api_get_path(SYS_PLUGIN_PATH)."sepe/ws/ProveedorCentroTFWS.wsdl";
$serviceUrl = api_get_path(WEB_PLUGIN_PATH).'sepe/ws/service.php';

/**
 * Class CustomServer.
 */
class CustomServer extends Zend\Soap\Server
{
    /**
     * {@inheritdoc}
     */
    public function __construct($wsdl = null, array $options = null)
    {
        parent::__construct($wsdl, $options);

        // Response of handle will always be returned
        $this->setReturnResponse(true);
    }

    public function handle($request = null)
    {
        $response = parent::handle($request);
        $response = str_replace(
            'xmlns:ns1="http://impl.ws.application.proveedorcentro.meyss.spee.es"',
            'xmlns:ns1="http://impl.ws.application.proveedorcentro.meyss.spee.es" xmlns:impl="http://impl.ws.application.proveedorcentro.meyss.spee.es" xmlns:sal="http://salida.bean.domain.common.proveedorcentro.meyss.spee.es" xmlns:ent="http://entsal.bean.domain.common.proveedorcentro.meyss.spee.es" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"',
            $response
        );

        $response = $this->addNamespaceToTag($response, 'RESPUESTA_DATOS_CENTRO', 'sal');
        $response = $this->addNamespaceToTag($response, 'RESPUESTA_OBT_LISTA_ACCIONES', 'sal');
        $response = $this->addNamespaceToTag($response, 'RESPUESTA_ELIMINAR_ACCION', 'sal');
        $response = $this->addNamespaceToTag($response, 'RESPUESTA_OBT_ACCION', 'sal');

        $response = $this->addNamespaceToTag($response, 'ACCION_FORMATIVA', 'ent');
        $response = $this->addNamespaceToTag($response, 'ID_ACCION', 'ent');
        $response = $this->addNamespaceToTag($response, 'DATOS_IDENTIFICATIVOS', 'ent');

        // Dentro de ACCION_FORMATIVA no hay ent:ID_ACCION
        $response = str_replace(
            '<ent:ACCION_FORMATIVA><ent:ID_ACCION>',
            '<ent:ACCION_FORMATIVA><ID_ACCION>',
            $response
        );

        $response = str_replace(
            '</ent:ID_ACCION><SITUACION>',
            '</ID_ACCION><SITUACION>',
            $response
        );

        //$response = file_get_contents('/tmp/log4.xml');
        header('Content-Length:'.strlen($response));
        echo $response;
        exit;
    }

    private function addNamespaceToTag($response, $tag, $namespace)
    {
        return str_replace(
            $tag,
            $namespace.":".$tag,
            $response
        );
    }
}

function authenticate($WSUser, $WSKey)
{
    $tUser = Database::get_main_table(TABLE_MAIN_USER);
    $tApi = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
    $login = Database::escape_string($WSUser);
    $WSKey = Database::escape_string($WSKey);

    $sql = "SELECT u.user_id, u.status FROM $tUser u, $tApi a
            WHERE
                u.username='".$login."' AND
                u.user_id = a.user_id AND
                a.api_service = 'dokeos' AND
                a.api_key='".$WSKey."'";
    $result = Database::query($sql);

    if (Database::num_rows($result) > 0) {
        $row = Database::fetch_row($result);
        if ($row[1] == '4') {
            return true;
        }
    }

    return false;
}

$doc = new DOMDocument();
$post = file_get_contents('php://input');
if (!empty($post)) {
    $doc->loadXML($post);

    $WSUser = $doc->getElementsByTagName('Username')->item(0)->nodeValue;
    $WSKey = $doc->getElementsByTagName('Password')->item(0)->nodeValue;

    $s = new WSSESoapServer($doc);
    if (!empty($WSUser) && !empty($WSKey)) {
        if (authenticate($WSUser, $WSKey)) {
            // pointing to the current file here
            $options = [
                'soap_version' => SOAP_1_1,
            ];
            $soap = new CustomServer($wsdl, $options);
            $soap->setObject(new Sepe());

            if ($s->process()) {
                $xml = $s->saveXML();
                //header('Content-type: application/xml');
                $soap->handle($xml);
                exit;
            } else {
                error_log('not processed');
            }
        } else {
            error_log('Claves incorrectas');
        }
    } else {
        error_log('not processed');
    }
} else {
    $contents = file_get_contents($wsdl);
    header('Content-type: application/xml');
    echo $contents;
    exit;
}
exit;
