<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Db\Exception\DatabaseException;
use \PDOException;
use Toknot\Di\Object;
use Toknot\Db\Driver\MySQL;
use Toknot\Db\Driver\SQLite;
use \PDO;
use Toknot\Di\ArrayObject;

class Connect extends Object {

    private $dsn = null;
    private $username = null;
    private $password = null;
    private $driverOptions = null;
    private static $supportDriver = array();
    private $connectInstance = null;
    
    /**
     * create Database connect and bind to DatabaseObject instance
     * 
     * @param \Toknot\Db\DatabaseObject $connectObject
     */
    public function __construct(DatabaseObject &$connectObject) {
        $this->dsn = $connectObject->dsn;
        $this->username = $connectObject->username;
        $this->password = $connectObject->password;
        if($connectObject->driverOptions instanceof StringObject) {
            $this->driverOptions = array($connectObject->driverOptions);
        }
        try {
            $this->connectDatabase();
            $connectObject->setConnectInstance($this);
        } catch (DatabaseException $e) {
            echo $e;
        }
    }

    public function getConnectInstance() {
        return $this->connectInstance;
    }

    private function connectDatabase() {
        if (class_exists('PDO')) {
            try {
                $this->connectInstance = new PDO($this->dsn, $this->username, $this->password, $this->driverOptions);
            } catch (PDOException $pdoe) {
                throw new DatabaseException($pdoe->getMessage(), $pdoe->getCode());
            }
        } else {
            $databaseType = strtolower(strtok($this->dsn, ':'));
            switch ($databaseType) {
                case 'mysql':
                    $this->connectInstance = $this->connectMySQL();
                case 'sqlite':
                    $this->connectInstance = $this->connectSQLite();
                default :
                    $this->scanDriver();
                    if (in_array($databaseType, self::$supportDriver)) {
                        $this->connectInstance = $this->importDriver();
                    } else {
                        throw new DatabaseException('Not Support Database', 0);
                    }
                    break;
            }
        }
    }

    private function importDriver() {
        $classList = array_keys(self::$supportDriver);
        return new $classList($this->dsn, $this->username, $this->password, $this->driverOptions);
    }

    private function scanDriver() {
        $path = __DIR__ . '/Driver';
        $driverFile = scandir($path);
        foreach ($driverFile as $className) {
            if ($className == '.' || $className == '..') {
                continue;
            }
            self::$supportDriver[$className] = strtolower($className);
        }
    }

    private function connectMySQL() {
        return new MySQL($this->dsn, $this->username, $this->password, $this->driverOptions);
    }

    private function connectSQLite() {
        return new SQLite($this->dsn, $this->driverOptions);
    }
}