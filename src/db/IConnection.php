<?php
/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 17/10/2018
 * Time: 19:13
 */

namespace db;

/**
 * Interfaz de conexión a base de datos. Diseñada para poder utilizarse de manera agnóstica
 *
 * Interface IConnection
 * @package db
 */
interface IConnection
{

    public function getDsn();
    public function getUser();
    public function getPassword();



}