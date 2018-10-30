<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:31 PM
 */

namespace tests\customer;


use models\CoreModel;

class Customer extends CoreModel
{
    public $name;
    public $surname;
    public $age;
    public $email;
    public $zip_code;

    /**
     * @dao tests\service_order\ServiceOrderDAO
     * @external_field customer
     */
    public $serviceOrders = [];
}