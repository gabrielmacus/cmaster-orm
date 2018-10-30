<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:31 PM
 */

namespace tests\multimedia;


use daos\CoreDAO;

abstract class MultimediaDAO extends CoreDAO
{
    //Si quiero que las clases hijo hereden el nombre del recurso, debo declararlo en la variable de la clase padre
    protected $resource = 'multimedia';
}