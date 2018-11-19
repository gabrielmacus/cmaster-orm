<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:31 PM
 */

namespace tests\tag;


use models\CoreModel;

class Tag extends CoreModel
{
    /**
     * @var $name string
     */
    public $name;
    /**
     * @dao tests\service_order\ServiceOrderDAO
     * @parent_property tags
     * @link_dao tests\service_order_tag\ServiceOrderTagDAO
     */
    public $serviceOrders = [];

    /**
     * @dao tests\image\ImageDAO
     * @link_dao tests\multimedia_tag\MultimediaTagDAO
     */
    public $images=[];
}