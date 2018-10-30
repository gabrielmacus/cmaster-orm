<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:31 PM
 */

namespace tests\customer;


use daos\CoreDAO;

class CustomerDAO extends CoreDAO
{
    public function getPaginationConfiguration(): array
    {
        $c = parent::getPaginationConfiguration();
        $c["defaultLimit"] = 10;
        return $c;
    }


}