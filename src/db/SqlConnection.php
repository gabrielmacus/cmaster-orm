<?php
/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 17/10/2018
 * Time: 19:13
 */

namespace db;
use PDO;

/**
 * Clase de conexión a base de datos MySql
 * Class MySqlConnection
 * @package db
 */
class SqlConnection  extends \PDO  implements IConnection
{
    /**
     * String de conexion a base de datos. Ejemplo: mysql:host=localhost;dbname=cmaster
     * @var $dsn
     */
    protected $dsn;
    protected $user;
    protected $password;
    /**
     * @var $connection \PDO
     */
    protected $connection;

    /**
     * Connection constructor.
     * @param $dsn
     * @param $user
     * @param $password
     * @param $options
     */
    public function __construct($dsn, $user, $password,$options=null)
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;

        parent::__construct($this->getDsn(),$this->getUser(),$this->getPassword(),$options);
    }



    public function getDsn()
    {
        return $this->dsn;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getPassword()
    {

        return $this->password;
    }




}