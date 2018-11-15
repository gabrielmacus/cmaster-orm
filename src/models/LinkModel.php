<?php
/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 17/10/2018
 * Time: 19:02
 */

namespace models;


abstract class LinkModel extends CoreModel
{
    /**
     * Referencia a que propiedad del objeto pertenecen los elementos vinculados, en el caso de que haya dos propiedades con elementos vinculados del mismo tipo, utilizando la misma tabla link
     * @var string $property
     */
    public $property;
}