<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:31 PM
 */

namespace tests\customer;


use daos\CoreDAO;
use models\ICoreModel;

class CustomerDAO extends CoreDAO
{
    public function getPaginationConfiguration(): array
    {
        $c = parent::getPaginationConfiguration();
        $c["defaultLimit"] = 10;
        return $c;
    }

    public function afterCreate(ICoreModel &$model)
    {
        parent::afterCreate($model);

        if($model->name == "After Create")
        {
            $model->name = "Changed on ".$model->name;
        }

    }

    public function beforeCreate(ICoreModel &$model)
    {

        parent::beforeCreate($model);
        if($model->zip_code >= 4000){

            $model->zip_code = 3999;
        }
    }



    public function afterRead(array &$items)
    {
        parent::afterRead($items);

        foreach ($items as $k => $v)
        {
            if($v->name == "After Read")
            {
                $items[$k]-> name ="Changed on ".$v->name;
            }
        }

    }

    public function beforeRead(array &$params = null)
    {
        parent::beforeRead($params);
        if($params["where"][0]["prop"] == "replace_by_surname")
        {
            $params["where"][0]["prop"] = "surname";
        }
    }

    public function afterUpdate(ICoreModel &$model)
    {
        parent::afterUpdate($model);
        if($model->name == "After Update")
        {
            $model->name = "Changed on ".$model->name;
        }
    }

    public function beforeUpdate(ICoreModel &$model, array &$where)
    {
        parent::beforeUpdate($model, $where);

        if($model->zip_code == 10000){
            $model->zip_code = 20000;
        }
    }

    public function afterDeleteById($id, bool $softDelete)
    {
        parent::afterDeleteById($id, $softDelete);

        if(file_exists("created_on_after_delete_by_id.txt"))
        {
            unlink("created_on_after_delete_by_id.txt");
        }

        file_put_contents("created_on_after_delete_by_id.txt","ok 1");
    }



    public function beforeDeleteById($id, bool $softDelete)
    {
        parent::beforeDeleteById($id, $softDelete);

        if(file_exists("created_on_before_delete_by_id.txt")) {
            unlink("created_on_before_delete_by_id.txt");
        }
        file_put_contents("created_on_before_delete_by_id.txt","ok 2");
    }


}