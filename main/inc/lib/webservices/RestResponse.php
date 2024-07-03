<?php

/* For licensing terms, see /license.txt */

/**
 * Class RestApiResponse.
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
        $this->errorMessage = '';
        $this->data = [];
    }

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

    public function format(): string
    {
        $json = ['error' => $this->error];

        if ($this->error) {
            $json['message'] = $this->errorMessage;
        } else {
            $json['data'] = $this->data;
        }

        return json_encode(
            $json,
            'test' === api_get_setting('server_type') ? JSON_PRETTY_PRINT : 0
        );
    }
}
