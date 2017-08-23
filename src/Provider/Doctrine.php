<?php

namespace Obullo\Auth\Provider;

use Doctrine\DBAL\Driver\Connection;
use Obullo\Auth\Provider\AbstractProvider;
use Obullo\Auth\User\CredentialsInterface as Credentials;

/**
 * Doctrine DBAL PDO Adapter
 *
 * @copyright 2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Doctrine extends AbstractProvider
{
    /**
     * Db connection
     *
     * @var object
     */
    protected $conn;

    /**
     * Constructor
     *
     * @param Connection $conn doctrine dbal connection
     */
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Execute sql query
     *
     * @param object $credentials credentials
     *
     * @return mixed boolean|array
     */
    public function query(Credentials $credentials)
    {
        $stmt = $this->conn->prepare(
            sprintf(
                'SELECT %s FROM %s WHERE BINARY %s = ?',
                implode(", ", $this->getColumns()),
                $this->getTableName(),
                $this->getIdentityColumn()
            )
        );
        $stmt->bindValue(1, $credentials->getIdentityValue(), \PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Execute recaller query
     *
     * @param string $token rememberMe token
     *
     * @return array
     */
    public function recall($tokenValue)
    {
        $stmt = $this->conn->prepare(
            sprintf(
                'SELECT %s FROM %s WHERE %s = ?',
                implode(", ", $this->getColumns()),
                $this->getTablename(),
                $this->getRememberTokenColumn()
            )
        );
        $stmt->bindValue(1, $tokenValue, \PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Update remember me token upon every login & logout
     *
     * @param string $tokenValue    value
     * @param string $identityValue value
     *
     * @return mixed
     */
    public function updateRememberToken($tokenValue, $identityValue)
    {
        $stmt = $this->conn->prepare(
            sprintf(
                'UPDATE %s SET %s = ? WHERE BINARY %s = ?',
                $this->getTablename(),
                $this->getRememberTokenColumn(),
                $this->getIdentityColumn()
            )
        );
        $stmt->bindValue(1, $tokenValue, \PDO::PARAM_STR);
        $stmt->bindValue(2, $identityValue, \PDO::PARAM_STR);

        return $stmt->execute();
    }
}
