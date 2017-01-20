<?php
namespace Culqi;

class Culqi
{

    public $api_key;
    public static $api_base = "https://integ-pago.culqi.com/api/v1";


    // Constructor
    public function __construct($options)
    {
        $this->api_key = $options["api_key"];
        if (!$this->api_key) {
            throw new InvalidApiKey();
        }

        $this->Cargos = new Cargos($this);
        $this->Suscripciones = new Suscripciones($this);
        $this->Devoluciones = new Devoluciones($this);
        $this->Planes = new Planes($this);

    }

    // To-do: setAPIKey
    public function setApiKey()
    {
    }

    // setEnv
    public function setEnv($entorno)
    {
        if ($entorno == 'INTEG') {
            self::$api_base = "https://integ-pago.culqi.com/api/v1";
        }
        elseif ($entorno == 'PRODUC') {
            self::$api_base = "https://pago.culqi.com/api/v1";
        }
        else {
             self::$api_base = "https://integ-pago.culqi.com/api/v1";
        }

    }

    // To-do: getEnv
    public function getEnv()
    {
        //this->api_base;
    }



}
