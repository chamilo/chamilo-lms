<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

class OutcomeResponseStatus
{
    public const SEVERITY_STATUS = 'status';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';

    public const CODEMAJOR_SUCCESS = 'success';
    public const CODEMAJOR_PROCESSING = 'processing';
    public const CODEMAJOR_FAILURE = 'failure';
    public const CODEMAJOR_UNSUPPORTED = 'unsupported';

    private string $codeMajor = '';

    private string $severity = '';

    private string $messageRefIdentifier = '';

    private string $operationRefIdentifier = '';

    private string $description = '';

    /**
     * Get codeMajor.
     *
     * @return string
     */
    public function getCodeMajor()
    {
        return $this->codeMajor;
    }

    /**
     * Set codeMajor.
     *
     * @param string $codeMajor
     *
     * @return OutcomeResponseStatus
     */
    public function setCodeMajor($codeMajor)
    {
        $this->codeMajor = $codeMajor;

        return $this;
    }

    /**
     * Get severity.
     *
     * @return string
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * Set severity.
     *
     * @param string $severity
     *
     * @return OutcomeResponseStatus
     */
    public function setSeverity($severity)
    {
        $this->severity = $severity;

        return $this;
    }

    /**
     * Get messageRefIdentifier.
     *
     * @return int
     */
    public function getMessageRefIdentifier()
    {
        return $this->messageRefIdentifier;
    }

    /**
     * Set messageRefIdentifier.
     *
     * @param int $messageRefIdentifier
     *
     * @return OutcomeResponseStatus
     */
    public function setMessageRefIdentifier($messageRefIdentifier)
    {
        $this->messageRefIdentifier = $messageRefIdentifier;

        return $this;
    }

    /**
     * Get operationRefIdentifier.
     *
     * @return int
     */
    public function getOperationRefIdentifier()
    {
        return $this->operationRefIdentifier;
    }

    /**
     * Set operationRefIdentifier.
     *
     * @param int $operationRefIdentifier
     *
     * @return OutcomeResponseStatus
     */
    public function setOperationRefIdentifier($operationRefIdentifier)
    {
        $this->operationRefIdentifier = $operationRefIdentifier;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return OutcomeResponseStatus
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }
}
