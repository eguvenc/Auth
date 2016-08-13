<?php

namespace Obullo\Auth\MFA\Adapter\Database\Table;

use Obullo\Auth\MFA\Adapter\Database\AbstractTable;
use Obullo\Auth\MFA\CredentialsInterface as Credentials;

/**
 * Mongo AbstractTable
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Mongo extends AbstractTable
{
     /**
     * Db collection
     *
     * @var object
     */
    protected $collection;

    /**
     * Constructor
     *
     * @param mongo database
     */
    public function __construct($database)
    {
        $collection = $this->getTableName();

        $this->collection = $database->{$collection};
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
        $row = $this->collection->findOne(
            array(
                $this->getIdentityColumn() => $credentials->getIdentityValue()
            ),
            implode(", ", $this->getColumns())
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
        $row = $this->collection->finOne(
            array(
                $this->getRememberTokenColumn() => $tokenValue
            ),
            implode(", ", $this->getColumns())
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
        return $this->collection->update(
            [
                $this->getIdentityColumn() => $identityValue
            ],
            [
                '$set' => array($this->getRememberTokenColumn() => $tokenValue),
            ]
        );
    }
}
