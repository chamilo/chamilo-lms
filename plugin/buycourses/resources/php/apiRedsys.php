<?php
/**
* NOTA SOBRE LA LICENCIA DE USO DEL SOFTWARE.
*
* El uso de este software está sujeto a las Condiciones de uso de software que
* se incluyen en el paquete en el documento "Aviso Legal.pdf". También puede
* obtener una copia en la siguiente url:
* http://www.redsys.es/comercio-electronico/condiciones-de-uso.pdf
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
    /*  InputData Array */
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

    /*  3DES Function  */
    public function encrypt_3DES($message, $key)
    {
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

    /*  Get Order Number */
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

    /*  Convert Array to JSON Object */
    public function arrayToJson()
    {
        $json = json_encode($this->vars_pay); //(PHP 5 >= 5.2.0)

        return $json;
    }

    public function createMerchantParameters()
    {
        // The data array is transformed into a Json object
        $json = $this->arrayToJson();
        // Base64 data is encoded
        return $this->encodeBase64($json);
    }

    public function createMerchantSignature($key)
    {
        // Base64 key is decoded
        $key = $this->decodeBase64($key);
        // The parameter is generated Ds_MerchantParameters
        $ent = $this->createMerchantParameters();
        // The key is diversified with the Order Number
        $key = $this->encrypt_3DES($this->getOrder(), $key);
        // MAC256 param Ds_MerchantParameters
        $res = $this->mac256($ent, $key);
        // Base64 data is encoded
        return $this->encodeBase64($res);
    }

    /*  Get Order Number */
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

    /*  Convert String to Array */
    public function stringToArray($datosDecod)
    {
        $this->vars_pay = json_decode($datosDecod, true); //(PHP 5 >= 5.2.0)
    }

    public function decodeMerchantParameters($datos)
    {
        // Base64 data is decoded
        $decodec = $this->base64_url_decode($datos);
        // The decoded data is passed to the data array
        $this->stringToArray($decodec);

        return $decodec;
    }

    public function createMerchantSignatureNotif($key, $datos)
    {
        // Base64 key is decoded
        $key = $this->decodeBase64($key);
        // Base64 data is decoded
        $decodec = $this->base64_url_decode($datos);
        // The decoded data is passed to the data array
        $this->stringToArray($decodec);
        // The key is diversified with the Order Numb
        $key = $this->encrypt_3DES($this->getOrderNotif(), $key);
        // MAC256 of the Ds_Parameters parameter that Redsys sends
        $res = $this->mac256($datos, $key);
        // Base64 data is encoded
        return $this->base64_url_encode($res);
    }

    /*  INPUT SOAP notifications */
    public function createMerchantSignatureNotifSOAPRequest($key, $datos)
    {
        // Base64 key is decoded
        $key = $this->decodeBase64($key);
        // Request data is obtained
        $datos = $this->getRequestNotifSOAP($datos);
        // The key is diversified with the Order Numb
        $key = $this->encrypt_3DES($this->getOrderNotifSOAP($datos), $key);
        // MAC256 of the Ds_Parameters parameter that Redsys sends
        $res = $this->mac256($datos, $key);
        // Base64 data is encoded
        return $this->encodeBase64($res);
    }

    /*  OUTPUT SOAP notifications */
    public function createMerchantSignatureNotifSOAPResponse($key, $datos, $numPedido)
    {
        // Base64 key is decoded
        $key = $this->decodeBase64($key);
        // Request data is obtained
        $datos = $this->getResponseNotifSOAP($datos);
        // The key is diversified with the Order Numb
        $key = $this->encrypt_3DES($numPedido, $key);
        // MAC256 of the Ds_Parameters parameter that Redsys sends
        $res = $this->mac256($datos, $key);
        // Base64 data is encoded
        return $this->encodeBase64($res);
    }
}
