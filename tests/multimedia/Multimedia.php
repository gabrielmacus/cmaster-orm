<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:31 PM
 */

namespace tests\multimedia;


use models\CoreModel;

abstract class Multimedia extends CoreModel
{
    public $name;
    public $size;
    public $type;
    public $extension;


}