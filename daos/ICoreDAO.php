<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 18/10/2018
 * Time: 10:59 AM
 */

namespace daos;


use db\IPagination;
use exceptions\DAOException;
use models\CoreModel;
use models\ICoreModel;

interface ICoreDAO
{

    /**
     * Genera el $resource a partir del nombre de la clase
     * @return string
     */
    public function generateResourceName();

    public function generatePrefix();

    public function getItems();

    public function getResourceName();

    public function execute($query);

    /**
     * Procesa la inserción/modificación/eliminación de filas a la base de datos
     * @param array $params
     * @return mixed
     */
    public function processUpsertDelete(array $params);
    /**
     * Procesa el array con parametros de consulta y devuelve un string u objeto de consulta
     * @param array $params
     * @return mixed
     */
    public function processQuery(array $params);

    /**
     * Procesa un parámetro de consulta de forma individual
     * @param array $param
     * @return mixed
     * @throws DAOException
     */
    public function processParam(array $param);

    /**
     * Procesa un grupo de parámetros de consulta
     * @param array $paramGroup
     * @param string $operator
     * @return mixed
     */
    public function processParamGroup(array $paramGroup,string $operator = null);

    public function getConnection();

    public function create(ICoreModel &$model);

    public function read(array $params);

    public function readAll();

    public function readById($id);

    public function update(ICoreModel &$model,$where = null);

    public function deleteById($id,bool $softDelete = true);

    /**
     * Devuelve una instancia del modelo con el que va a trabajar el dao
     * @param $className  boolean Obtener solo el nombre de la clase
     * @return ICoreModel
     */
    public function getModel($className = false);

    /**
     * Devuelve una array con los operadores de comparación permitidos para utilizar en las consultas. Ej: =,<,>,>=,<=...
     * @return array
     */
    public function getAllowedComparisonOperators():array;

    /**
     * Devuelve el operador de comparación a ser usado en caso de no recibir un operador válido en la consulta. Por lo general es '='
     * @return string
     */
    public function getDefaultComparisonOperator():string ;


    /**
     * Devuelve una array con los operadores lógicos permitidos para utilizar en las consultas. Ej: OR,AND,XOR...
     * @return array
     */
    public function getAllowedLogicalOperators():array;

    /**
     * Devuelve el operador lógico a ser usado en caso de no recibir un operador válido en la consulta. Por lo general es 'AND'
     * @return string
     */
    public function getDefaultLogicalOperator():string ;


    public function processPopulateAnnotations(string $property);

    /**
     * Puebla el campo del modelo de acuerdo a la relación correspondiente (Uno a muchos,muchos a uno, muchos a muchos...)
     * @param string $property
     * @return mixed
     */
    public function populate(string $property);


    /**
     * Datos de paginación para tener como referencia a la hora de continuar paginando
     * @return array
     */
    public function getPaginationData():array;

    /**
     * Datos de configuración de paginación
     * @return array
     */
    public function getPaginationConfiguration():array;

    /**
     * Procesa los parametros de paginación
     * @param $params
     * @return void
     */
    public function processPagination(&$params);
    /*
    public function setPagination(IPagination $pagination);
    public function getPagination():IPagination;*/


    /**
     * Guarda las asociaciones correspondientes al objeto creado/actualizado, y crea los elementos a asociar en caso de que estos no existan.<br>
     * Dichos elementos pueden contener datos sobre la relacion (muchos a muchos), en caso de que los mismos tengan propiedades con el formato relation_data_<br>
     * Esta función debe ser llamada <b>después</b> de guardar el objeto en cuestión<br>
     * @see CoreModel
     * @param ICoreModel $model Modelo al que se le van a asociar los elementos
     * @return void
     */
    public function saveRelationships(ICoreModel &$model);

    /**
     * Transforma el item a asociar en id y en caso de que no exista, lo crea
     * @param $item
     * @param $dao ICoreDAO DAO correspondiente al item que se da como primer argumento. Se utiliza para crear el mismo en caso de que no exista
     * @throws DAOException
     */
    public function processItemToRelate(&$item,ICoreDAO $dao = null);

}