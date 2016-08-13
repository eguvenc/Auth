<?php

namespace Obullo\Auth\MFA\Adapter\Database;

/**
 * Abstract Table
 *
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
abstract class AbstractTable implements TableInterface
{
    /**
     * Columns ( db select fields )
     *
     * @var array
     */
    protected $columns = array();

    /**
     * Tablename
     *
     * @var string
     */
    protected $tablename;

    /**
     * Identity column name
     *
     * @var string
     */
    protected $identityColumn;

    /**
     * Password column name
     *
     * @var string
     */
    protected $passwordColumn;

    /**
     * Remember token column name
     *
     * @var string
     */
    protected $rememberTokenColumn;

    /**
     * Set column names
     *
     * @param array $columns colum names
     *
     * @return void
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Set tablename
     *
     * @param string $tablename tablename
     *
     * @return void
     */
    public function setTableName($tablename)
    {
        $this->tablename = $tablename;
    }

    /**
     * Set identity column name
     *
     * @param string $identityColumn identity column
     *
     * @return void
     */
    public function setIdentityColumn($identityColumn)
    {
        $this->identityColumn = $identityColumn;
    }

    /**
     * Set credential column
     *
     * @param string $passwordColumn password column
     *
     * @return void
     */
    public function setPasswordColumn($passwordColumn)
    {
        $this->passwordColumn = $passwordColumn;
    }

    /**
     * Set remember me token column name
     *
     * @param string $rememberTokenColumn remember me column
     *
     * @return void
     */
    public function setRememberTokenColumn($rememberTokenColumn)
    {
        $this->rememberTokenColumn = $rememberTokenColumn;
    }

    /**
     * Returns to column names
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Returns to tablename
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tablename;
    }

    /**
     * Returns to identity column name
     *
     * @return string
     */
    public function getIdentityColumn()
    {
        return $this->identityColumn;
    }

    /**
     * Returns to password column
     *
     * @return string
     */
    public function getPasswordColumn()
    {
        return $this->passwordColumn;
    }

    /**
     * Returns to remember token column name
     *
     * @return string
     */
    public function getRememberTokenColumn()
    {
        return $this->rememberTokenColumn;
    }
}
