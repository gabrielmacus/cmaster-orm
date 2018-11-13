<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 18/10/2018
 * Time: 10:25 AM
 */

namespace models;


interface ICoreModel
{
    /**
     * Recupera las propiedades del modelo correspondientes a la base de datos
     * @param bool $externalProperties Indica si recupero las propiedades que se poblan de manera extenra
     * @return array
     */
    public function getProperties(bool $externalProperties = false);

    /**
     * Setea el objeto a través de un array
     * @param array $array
     * @return void
     */
    public function arrayToObject(array $array);

    /**
     * Devuelve un diccionario con los datos de las relaciones (muchos a muchos), con el formato <br>
     * [{Nombre de la propiedad} =>
     *     [{Id del elemento relacionado}=> = [{Datos de la relacion}] ]
     * ]
     * @return array
     *
     */
    public function getRelationshipsData():array;

    public function setRelationshipsData(array $data);

    /** Hooks **/
    /**
     * Función ejecutada al iterar el elemento durante su lectura
     * @param int $position Orden en el que se itera el elemento
     * @return void
     */
    public function onFetch(int $position);

}