<?php
/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 17/10/2018
 * Time: 22:51
 */

include "vendor/autoload.php";

spl_autoload_register(function ($class) {

    $dir = __DIR__ . "/" .str_replace("\\","/",$class).".php";

    if(file_exists($dir))
    {
        include_once $dir ;
    }

    //For composer package
    $dir = __DIR__ . "/src/" .str_replace("\\","/",$class).".php";

    if(file_exists($dir))
    {
        include_once $dir ;
    }

});
