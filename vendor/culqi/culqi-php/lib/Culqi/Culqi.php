<?php
namespace Culqi;

use Culqi\Error as Errors;

/**
 * Class Culqi
 *
 * @package Culqi
 */
class Culqi
{
    public $api_key;
    /**
    * La versión de API usada
    */
    const API_VERSION = "v2.0";
    /**
     * La URL Base por defecto
     */
    const BASE_URL = "https://api.culqi.com/v2";
    /**
     * Constructor.
     *
     * @param array|null $options
     *
     * @throws Error\InvalidApiKey
     *
     * @example array('api_key' => "{api_key}")
     *
     */
    public function __construct($options)
    {
        $this->api_key = $options["api_key"];
        if (!$this->api_key) {
          throw new Errors\InvalidApiKey();
        }
        $this->Tokens = new Tokens($this);
        $this->Charges = new Charges($this);
        $this->Subscriptions = new Subscriptions($this);
        $this->Refunds = new Refunds($this);
        $this->Plans = new Plans($this);
        $this->Transfers = new Transfers($this);
        $this->Iins = new Iins($this);
        $this->Cards = new Cards($this);
        $this->Events = new Events($this);
        $this->Customers = new Customers($this); 
        $this->Orders = new Orders($this);
    }
}
