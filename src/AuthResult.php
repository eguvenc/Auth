<?php

namespace Obullo\Authentication;

/**
 * Auth Result Controller
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class AuthResult
{
    /**
     * General Failure
     */
    const FAILURE = 0;

    /**
     * Failure due to identity being ambiguous.
     */
    const FAILURE_IDENTITY_AMBIGUOUS = -1;

    /**
     * Failure due to invalid credential being supplied.
     */
    const FAILURE_CREDENTIAL_INVALID = -2;

    /**
     * Temporary auth has been created
     */
    const TEMPORARY_AUTH = -3;

    /**
     * Authentication success.
     */
    const SUCCESS =  1;

    /**
     * Authentication result code
     *
     * @var int
     */
    protected $code;

    /**
     * The identity used in the authentication attempt
     *
     * @var mixed
     */
    protected $identifier;

    /**
     * An array of string reasons why the authentication attempt was unsuccessful
     *
     * If authentication was successful, this should be an empty array.
     *
     * @var array
     */
    protected $messages = array();

    /**
     * Sets the result code, identity, and failure messages
     *
     * @param int   $code       result code
     * @param mixed $identifier identifier
     * @param array $messages   messages
     */
    public function __construct($code, $identifier, $messages = array())
    {
        $this->code = (int) $code;
        $this->identifier = $identifier;
        $this->messages = (array)$messages;
    }

    /**
     * Returns whether the result represents a successful authentication attempt
     *
     * @return bool
     */
    public function isValid()
    {
        return ($this->getCode() > 0) ? true : false;
    }

    /**
     * Get the result code for this authentication attempt
     *
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Returns the identity used in the authentication attempt
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns an array of string reasons why the authentication attempt was unsuccessful
     *
     * If authentication was successful, this method returns an empty array.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Set custom error code
     * 
     * @param int $code error code
     *
     * @return void
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Set custom error messages
     * 
     * @param string $message message
     *
     * @return void
     */
    public function setMessage($message)
    {
        $this->messages[] = $message;
    }

    /**
     * Gets all messages
     * 
     * @return array
     */
    public function getArray()
    {
        return array(
            'code' => $this->code,
            'messages' => $this->messages,
            'identifier' => $this->identifier
        );
    }

    /**
     * Sets successful login database result row
     * 
     * @param mixed $resultRow results
     *
     * @return void
     */
    public function setResultRow($resultRow)
    {
        $this->resultRow = $resultRow;
    }

    /**
     * Returns to successful login database result row
     * 
     * @return mixed
     */
    public function getResultRow()
    {
        return $this->resultRow;
    }

}