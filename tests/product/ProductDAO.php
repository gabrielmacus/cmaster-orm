<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:31 PM
 */

namespace tests\product;
use daos\CoreDAO;
use models\CoreModel;
use models\ICoreModel;

class ProductDAO extends CoreDAO
{


    public function beforeValidation(ICoreModel &$model)
    {
        $model->description = "Set on before validation";
        parent::beforeValidation($model);
    }

    public function validate(CoreModel &$model, array $validationParams = null, array $messages = null, array $aliases = null): bool
    {
        $validationParams = [
            "name"=>"required|between:5,100",
            "description"=>"required|between:20,200"
        ];

        $messages = [
            "required"=>":attribute es requerido"
        ];
        $aliases = [
            "name" => "Nombre"
        ];

        return parent::validate($model, $validationParams,$messages,$aliases);
    }


}