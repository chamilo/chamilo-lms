<?php
/* For licensing terms, see /license.txt */

/**
 * Class LtiAuthException.
 */
class LtiAuthException extends Exception
{
    const INVALID_REQUEST = 1;
    const INVALID_SCOPE = 2;
    const UNSUPPORTED_RESPONSE_TYPE = 3;
    const UNAUTHORIZED_CLIENT = 4;
    const ACCESS_DENIED = 5;
    const UNREGISTERED_REDIRECT_URI = 6;
    const INVALID_RESPONSE_MODE = 7;
    const MISSING_RESPONSE_MODE = 8;
    const INVALID_PROMPT = 9;

    /**
     * @var string
     */
    private $type;

    /**
     * LtiAuthException constructor.
     *
     * @param int $code
     */
    public function __construct($code = 0, Throwable $previous = null)
    {
        switch ($code) {
            case self::INVALID_SCOPE:
                $this->type = 'invalid_scope';
                $message = 'Invalid scope';
                break;
            case self::UNSUPPORTED_RESPONSE_TYPE:
                $this->type = 'unsupported_response_type';
                $message = 'Unsupported responde type';
                break;
            case self::UNAUTHORIZED_CLIENT:
                $this->type = 'unauthorized_client';
                $message = 'Unauthorized client';
                break;
            case self::ACCESS_DENIED:
                $this->type = 'access_denied';
                $message = 'Access denied';
                break;
            case self::UNREGISTERED_REDIRECT_URI:
                $message = 'Unregistered redirect_uri';
                break;
            case self::INVALID_RESPONSE_MODE:
                $message = 'Invalid response_mode';
                break;
            case self::MISSING_RESPONSE_MODE:
                $message = 'Missing response_mode';
                break;
            case self::INVALID_PROMPT:
                $message = 'Invalid prompt';
                break;
            case self::INVALID_REQUEST:
            default:
                $this->type = 'invalid_request';
                $message = 'Invalid request';
                break;
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return LtiAuthException
     */
    public static function invalidRequest(Throwable $previous = null)
    {
        return new self(self::INVALID_REQUEST, $previous);
    }

    /**
     * @return LtiAuthException
     */
    public static function invalidScope(Throwable $previous = null)
    {
        return new self(self::INVALID_SCOPE, $previous);
    }

    /**
     * @return LtiAuthException
     */
    public static function unsupportedResponseType(Throwable $previous = null)
    {
        return new self(self::UNSUPPORTED_RESPONSE_TYPE, $previous);
    }

    /**
     * @return LtiAuthException
     */
    public static function unauthorizedClient(Throwable $previous = null)
    {
        return new self(self::UNAUTHORIZED_CLIENT, $previous);
    }

    /**
     * @return LtiAuthException
     */
    public static function accessDenied(Throwable $previous = null)
    {
        return new self(self::ACCESS_DENIED, $previous);
    }

    /**
     * @return LtiAuthException
     */
    public static function unregisteredRedirectUri(Throwable $previous = null)
    {
        return new self(self::UNREGISTERED_REDIRECT_URI, $previous);
    }

    /**
     * @return LtiAuthException
     */
    public static function invalidRespondeMode(Throwable $previous = null)
    {
        return new self(self::INVALID_RESPONSE_MODE, $previous);
    }

    /**
     * @return LtiAuthException
     */
    public static function missingResponseMode(Throwable $previous = null)
    {
        return new self(self::MISSING_RESPONSE_MODE, $previous);
    }

    /**
     * @return LtiAuthException
     */
    public static function invalidPrompt(Throwable $previous = null)
    {
        return new self(self::INVALID_PROMPT, $previous);
    }
}
