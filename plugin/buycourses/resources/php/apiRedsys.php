<?php
/**
* NOTA SOBRE LA LICENCIA DE USO DEL SOFTWARE.
*
* El uso de este software está sujeto a las Condiciones de uso de software que
* se incluyen en el paquete en el documento "Aviso Legal.pdf". También puede
* obtener una copia en la siguiente url:
* http://www.redsys.es/wps/portal/redsys/publica/areadeserviciosweb/descargaDeDocumentacionYEjecutables
*
* Redsys es titular de todos los derechos de propiedad intelectual e industrial
* del software.
*
* Quedan expresamente prohibidas la reproducción, la distribución y la
* comunicación pública, incluida su modalidad de puesta a disposición con fines
* distintos a los descritos en las Condiciones de uso.
*
* Redsys se reserva la posibilidad de ejercer las acciones legales que le
* correspondan para hacer valer sus derechos frente a cualquier infracción de
* los derechos de propiedad intelectual y/o industrial.
*
* Redsys Servicios de Procesamiento, S.L., CIF B85955367
*/
class RedsysAPI
{
    /*  Array de DatosEntrada */
    public $vars_pay = [];

    /*  Set parameter */
    public function setParameter($key, $value)
    {
        $this->vars_pay[$key] = $value;
    }

    /*  Get parameter */
    public function getParameter($key)
    {
        return $this->vars_pay[$key];
    }

    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    ////////////					FUNCIONES AUXILIARES:							  ////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////

    /*  3DES Function  */
    public function encrypt_3DES($message, $key)
    {
        // Se cifra
        $l = ceil(strlen($message) / 8) * 8;

        return substr(openssl_encrypt($message.str_repeat("\0", $l - strlen($message)), 'des-ede3-cbc', $key, OPENSSL_RAW_DATA, "\0\0\0\0\0\0\0\0"), 0, $l);
    }

    /*  Base64 Functions  */
    public function base64_url_encode($input)
    {
        return strtr(base64_encode($input), '+/', '-_');
    }

    public function encodeBase64($data)
    {
        $data = base64_encode($data);

        return $data;
    }

    public function base64_url_decode($input)
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    public function decodeBase64($data)
    {
        $data = base64_decode($data);

        return $data;
    }

    /*  MAC Function */
    public function mac256($ent, $key)
    {
        $res = hash_hmac('sha256', $ent, $key, true); //(PHP 5 >= 5.1.2)

        return $res;
    }

    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    ////////////	   FUNCIONES PARA LA GENERACIÓN DEL FORMULARIO DE PAGO:			  ////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////

    /*  Obtener Número de pedido */
    public function getOrder()
    {
        $numPedido = "";
        if (empty($this->vars_pay['DS_MERCHANT_ORDER'])) {
            $numPedido = $this->vars_pay['Ds_Merchant_Order'];
        } else {
            $numPedido = $this->vars_pay['DS_MERCHANT_ORDER'];
        }

        return $numPedido;
    }

    /*  Convertir Array en Objeto JSON */
    public function arrayToJson()
    {
        $json = json_encode($this->vars_pay); //(PHP 5 >= 5.2.0)

        return $json;
    }

    public function createMerchantParameters()
    {
        // Se transforma el array de datos en un objeto Json
        $json = $this->arrayToJson();
        // Se codifican los datos Base64
        return $this->encodeBase64($json);
    }

    public function createMerchantSignature($key)
    {
        // Se decodifica la clave Base64
        $key = $this->decodeBase64($key);
        // Se genera el parámetro Ds_MerchantParameters
        $ent = $this->createMerchantParameters();
        // Se diversifica la clave con el Número de Pedido
        $key = $this->encrypt_3DES($this->getOrder(), $key);
        // MAC256 del parámetro Ds_MerchantParameters
        $res = $this->mac256($ent, $key);
        // Se codifican los datos Base64
        return $this->encodeBase64($res);
    }

    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////// FUNCIONES PARA LA RECEPCIÓN DE DATOS DE PAGO (Notif, URLOK y URLKO): ////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////

    /*  Obtener Número de pedido */
    public function getOrderNotif()
    {
        $numPedido = "";
        if (empty($this->vars_pay['Ds_Order'])) {
            $numPedido = $this->vars_pay['DS_ORDER'];
        } else {
            $numPedido = $this->vars_pay['Ds_Order'];
        }

        return $numPedido;
    }

    public function getOrderNotifSOAP($datos)
    {
        $posPedidoIni = strrpos($datos, "<Ds_Order>");
        $tamPedidoIni = strlen("<Ds_Order>");
        $posPedidoFin = strrpos($datos, "</Ds_Order>");

        return substr($datos, $posPedidoIni + $tamPedidoIni, $posPedidoFin - ($posPedidoIni + $tamPedidoIni));
    }

    public function getRequestNotifSOAP($datos)
    {
        $posReqIni = strrpos($datos, "<Request");
        $posReqFin = strrpos($datos, "</Request>");
        $tamReqFin = strlen("</Request>");

        return substr($datos, $posReqIni, ($posReqFin + $tamReqFin) - $posReqIni);
    }

    public function getResponseNotifSOAP($datos)
    {
        $posReqIni = strrpos($datos, "<Response");
        $posReqFin = strrpos($datos, "</Response>");
        $tamReqFin = strlen("</Response>");

        return substr($datos, $posReqIni, ($posReqFin + $tamReqFin) - $posReqIni);
    }

    /*  Convertir String en Array */
    public function stringToArray($datosDecod)
    {
        $this->vars_pay = json_decode($datosDecod, true); //(PHP 5 >= 5.2.0)
    }

    public function decodeMerchantParameters($datos)
    {
        // Se decodifican los datos Base64
        $decodec = $this->base64_url_decode($datos);
        // Los datos decodificados se pasan al array de datos
        $this->stringToArray($decodec);

        return $decodec;
    }

    public function createMerchantSignatureNotif($key, $datos)
    {
        // Se decodifica la clave Base64
        $key = $this->decodeBase64($key);
        // Se decodifican los datos Base64
        $decodec = $this->base64_url_decode($datos);
        // Los datos decodificados se pasan al array de datos
        $this->stringToArray($decodec);
        // Se diversifica la clave con el Número de Pedido
        $key = $this->encrypt_3DES($this->getOrderNotif(), $key);
        // MAC256 del parámetro Ds_Parameters que envía Redsys
        $res = $this->mac256($datos, $key);
        // Se codifican los datos Base64
        return $this->base64_url_encode($res);
    }

    /*  Notificaciones SOAP ENTRADA */
    public function createMerchantSignatureNotifSOAPRequest($key, $datos)
    {
        // Se decodifica la clave Base64
        $key = $this->decodeBase64($key);
        // Se obtienen los datos del Request
        $datos = $this->getRequestNotifSOAP($datos);
        // Se diversifica la clave con el Número de Pedido
        $key = $this->encrypt_3DES($this->getOrderNotifSOAP($datos), $key);
        // MAC256 del parámetro Ds_Parameters que envía Redsys
        $res = $this->mac256($datos, $key);
        // Se codifican los datos Base64
        return $this->encodeBase64($res);
    }

    /*  Notificaciones SOAP SALIDA */
    public function createMerchantSignatureNotifSOAPResponse($key, $datos, $numPedido)
    {
        // Se decodifica la clave Base64
        $key = $this->decodeBase64($key);
        // Se obtienen los datos del Request
        $datos = $this->getResponseNotifSOAP($datos);
        // Se diversifica la clave con el Número de Pedido
        $key = $this->encrypt_3DES($numPedido, $key);
        // MAC256 del parámetro Ds_Parameters que envía Redsys
        $res = $this->mac256($datos, $key);
        // Se codifican los datos Base64
        return $this->encodeBase64($res);
    }
}
