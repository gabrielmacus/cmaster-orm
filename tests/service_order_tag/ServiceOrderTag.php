<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:31 PM
 */

namespace tests\service_order_tag;


use models\LinkModel;

class ServiceOrderTag extends LinkModel
{

    public $tag;

    public $service_order;

    public $caption;
}