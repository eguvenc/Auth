<?php

namespace Obullo\Auth\Adapter\Table;

use Obullo\Auth\Adapter\Table\AbstractTable;
use Obullo\Auth\User\CredentialsInterface as Credentials;

/**
 * Mongo AbstractTable
 *
 * @copyright 2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Mongo extends AbstractTable
{
     /**
     * Db
     *
     * @var object
     */
    protected $database;

    /**
     * Constructor
     *
     * @param mongo database
     */
    public function __construct($database)
    {
        $this->database = $database;
    }
    
    /**
     * Returns to collection object
     *
     * @return object
     */
    public function getCollection()
    {
        $table = $this->getTableName();

        return $this->database->{$table};
    }

    /**
     * Execute mongo query
     *
     * @param object $credentials credentials
     *
     * @return mixed boolean|array
     */
    public function query(Credentials $credentials)
    {
        $row = $this->getCollection()->findOne(
            array(
                $this->getIdentityColumn() => $credentials->getIdentityValue()
            ),
            $this->getColumns()
        );
        if (empty($row)) {
            return false;
        }
        $row['_id'] = (string)$row['_id'];
        return $row;
    }

    /**
     * Recalled query using remember cookie
     *
     * @param string $tokenValıe rememberMe token
     *
     * @return mixed boolean|array
     */
    public function recall($tokenValue)
    {
        $row = $this->getCollection()->findOne(
            array(
                $this->getRememberTokenColumn() => $tokenValue
            ),
            $this->getColumns()
        );
        if (empty($row)) {
            return false;
        }
        $row['_id'] = (string)$row['_id'];
        return $row;
    }
    
    /**
     * Update remember me token upon every login & logout
     *
     * @param string $tokenValue    token value
     * @param string $identityValue value
     *
     * @return mixed
     */
    public function updateRememberToken($tokenValue, $identityValue)
    {
        return $this->getCollection()->update(
            [
                $this->getIdentityColumn() => $identityValue
            ],
            [
                '$set' => array($this->getRememberTokenColumn() => $tokenValue),
            ]
        );
    }
}
