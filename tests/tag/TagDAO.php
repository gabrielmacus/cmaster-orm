<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:31 PM
 */

namespace tests\tag;


use daos\CoreDAO;

class TagDAO extends CoreDAO
{
    public function getPaginationConfiguration(): array
    {
        $configuration = parent::getPaginationConfiguration();
        $configuration["defaultLimit"] = 2;
        return  $configuration;
    }


}