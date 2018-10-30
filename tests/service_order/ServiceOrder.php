<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:31 PM
 */

namespace tests\service_order;


use models\CoreModel;

class ServiceOrder extends CoreModel
{
    public $description;
    /**
     * @dao tests\customer\CustomerDAO
     */
    public $customer;
    /**
     * @dao tests\tag\TagDAO
     * @link_dao tests\service_order_tag\ServiceOrderTagDAO
     */
    public $tags =[];
}