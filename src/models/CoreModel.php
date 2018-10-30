<?php
/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 17/10/2018
 * Time: 19:02
 */

namespace models;
/**
 * Clase base para los modelos del sistema. Por normal general no deberia usar argumentos en el constructor.<br>
 * Si estoy asociando un elemento no existente a otro elemento, puedo guardar datos de la relacion utilizando una variable dinámica con el siguiente formato: relation_data_{nombre de la variable}
 * Class CoreModel
 * @package models
 */
abstract class CoreModel implements ICoreModel,\JsonSerializable
{
    public $id;
    public $created_at;
    public $updated_at;
    public $deleted_at;
    public $created_by;
    protected $relationshipsData =[];


    public function getProperties(bool $externalProperties = false)
    {
        $ro = new \ReflectionObject($this);

        $p = $ro->getProperties(\ReflectionProperty::IS_PUBLIC);

        $publicProperties = [];

        foreach ($p as $k=>$v)
        {
            $comments = $v->getDocComment();
            $notExternalPopulationField =(empty($comments) || strpos($comments,"@dao") === false);

            if((strpos($v->getName(),"relation_data_") === false || (strpos($v->getName(),"relation_data_") !== 0)) && ($externalProperties || $notExternalPopulationField) && !$v->isStatic())
            {
                $publicProperties[]=$v->getName();
            }

        }
        return $publicProperties;
    }

    public function arrayToObject(array $array)
    {
        foreach ($array as $key => $value)
        {
            $this->$key = $value;
        }


    }

    public function getRelationshipsData(): array
    {
        return $this->relationshipsData;
    }

    public function setRelationshipsData(array $data)
    {
        $this->relationshipsData = $data;
    }

    function jsonSerialize()
    {
        return  get_object_vars($this);
    }


}