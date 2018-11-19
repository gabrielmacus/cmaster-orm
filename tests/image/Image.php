<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:31 PM
 */

namespace tests\image;


 use tests\multimedia\Multimedia;

 class Image extends Multimedia
{
    public $type = 'image';

     /**
      * @dao tests\tag\TagDAO
      * @parent_property images
      * @link_dao tests\multimedia_tag\MultimediaTagDAO
      */
     public $tags=[];
}