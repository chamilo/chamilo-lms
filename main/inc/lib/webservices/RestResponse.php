<?php
/* For licensing terms, see /license.txt */

/**
 * Class RestApiResponse
 */
class RestResponse
{
    /**
     * @var bool
     */
    private $error;
    /**
     * @var string
     */
    private $errorMessage;
    /**
     * @var array
     */
    private $data;

    /**
     * RestApiResponse constructor.
     */
    public function __construct()
    {
        $this->error = true;
        $this->errorMessage = null;
        $this->data = [];
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->error = false;
        $this->data = $data;
    }

    /**
     * @param string $message
     */
    public function setErrorMessage($message)
    {
        $this->error = true;
        $this->errorMessage = $message;
    }

    /**
     * @return string
     */
    public function format()
    {
        $json = ['error' => $this->error];

        if ($this->error) {
            $json['message'] = $this->errorMessage;
        } else {
            $json['data'] = $this->data;
        }

        return json_encode($json, JSON_PRETTY_PRINT);
    }
}
