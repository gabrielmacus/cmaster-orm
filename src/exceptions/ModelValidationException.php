<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 29/11/2018
 * Time: 04:00 PM
 */

namespace exceptions;


class ModelValidationException extends \Exception
{

    /**
     * ModelValidationException constructor.
     */
    public function __construct(array $validationErrors)
    {
        parent::__construct(json_encode($validationErrors));
    }


    /**
     * @return mixed
     */
    public function getValidationErrors()
    {
        return json_decode($this->message,true);
    }
}