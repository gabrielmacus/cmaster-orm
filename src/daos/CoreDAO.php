<?php
/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 17/10/2018
 * Time: 19:10
 */

namespace daos;



use db\IConnection;
use db\IPagination;
use exceptions\DAOException;
use models\CoreModel;
use models\ICoreModel;

//TODO: CREATE multiple
abstract class CoreDAO implements ICoreDAO
{


    protected $paginationData = [];

    /**
     * Objeto de conexión a la base de datos
     * @var IConnection
     */
    protected $connection;
    /**
     * Nombre del recurso en la base de datos
     * @var string
     */
    protected $resource;

    /**
     * Prefijo de los atributos en la base de datos
     * @var string
     */
    protected $prefix;

    /**
     * Array de items recuperados de la base de datos
     * @var array
     */
    protected $items =[];

    /**
     * CoreDAO constructor.
     * @param $connection
     * @param $resource
     */
    public function __construct(IConnection $connection,string $resource = null,string $prefix = null)
    {
        if(empty($this->resource))
        {
            $this->resource = isset($resource) ? $resource : $this->generateResourceName();

        }
       $this->prefix = isset($prefix)? $prefix : $this->generatePrefix();
        $this->connection = $connection;

    }

    /**
     * Genera el prefijo para el atributo en la base de datos a partir del nombre de la tabla
     */
    public function generatePrefix()
    {
        return $this->resource;
    }


    public function generateResourceName()
    {
        $shortName = str_replace("DAO","",(new \ReflectionObject($this))->getShortName());

        $resourceName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));

        return $resourceName;
    }

    public function getItems():array
    {

        return $this->items;
    }

    public function getConnection():IConnection
    {
        return $this->connection;
    }

    public function getPaginationData(): array
    {
        if(isset($this->paginationData["total"]) && isset($this->paginationData["limit"]))
        {
            $this->paginationData["pages"] = ceil($this->paginationData["total"] / $this->paginationData["limit"]);
            $this->paginationData["next"] = ($this->paginationData["page"]  < $this->paginationData["pages"])?$this->paginationData["page"] + 1:false;
            $this->paginationData["prev"] = ($this->paginationData["page"]  > 1)?$this->paginationData["page"] - 1:false;


        }


        return $this->paginationData;
    }

    public function getPaginationConfiguration(): array
    {
       return [
           "maxLimit"=>50,
           "defaultLimit"=>20
       ];
    }

    /**
     * Procesa los parametros de consulta, por ejemplo podrian provenir de la variable
     * $_GET, y devuelve un array ["statement"=>"","input_parameters"=>]
     * para PDOStatement::execute()
     * @see http://php.net/manual/es/pdo.prepare.php
     * @param array $params
     * @return array
     *
     */
    public function processQuery(array $params):array
    {
        /**
         * @var $model CoreModel
         */
        $this->paginationData = [];
        $input_parameters = [];
        $where = [];
        $fields = empty($params["fields"]) || !is_array($params["fields"])?
           "*":implode(",",array_map(function($field){return $this->prefix."_".$field;},$params["fields"]));


       if(!empty($params["where"]) && is_array($params["where"]))
       {
           foreach ($params["where"] as $k => $param)
           {
               if(!empty($param["prop"]) &&  array_key_exists("value",$param))
               {
                   $paramResult = $this->processParam($param);

                   if(is_array($paramResult[0]))
                   {
                       $input_parameters = array_merge($input_parameters,$paramResult[0]);
                   }
                   else
                   {
                       $input_parameters[]=$paramResult[0];
                   }

                   $where[] = $paramResult[1];
               }
               else if (!empty($param["group"]) && is_array($param["group"]) )
               {
                   $paramResult = $this->processParamGroup($param["group"],isset($param["operator"])?$param["operator"]:null);

                   $input_parameters = array_merge($input_parameters,$paramResult[0]);
                   $where[]= "($paramResult[1])";

               }

           }
       }

       $pagination = "";
       if(!empty($params["pagination"]) && is_array($params["pagination"]))
       {
           $params["pagination"]["page"] = !empty($params["pagination"]["page"]) && is_numeric($params["pagination"]["page"])?$params["pagination"]["page"]:1;
           $params["pagination"]["offset"] = ($params["pagination"]["page"] - 1) * $params["pagination"]["limit"];
           $pagination= " LIMIT {$params["pagination"]["limit"]} OFFSET {$params["pagination"]["offset"]}";
           $this->paginationData = $params["pagination"];
       }

       $statement ="SELECT {$fields} FROM {$this->resource} ".(!empty($where)?"WHERE ".implode(" AND ",$where):"").$pagination;


        return ["statement"=>$statement,"input_parameters"=>$input_parameters];
    }

    public function processParamGroup(array $paramGroup,string $operator = null)
    {
        $whereGroup = [];
        $input_parameters = [];

        foreach ($paramGroup as $param)
        {
            $paramResult = $this->processParam($param);

            if(is_array($paramResult[0]))
            {
                $input_parameters = array_merge($input_parameters,$paramResult[0]);
            }
            else
            {
                $input_parameters[]=$paramResult[0];
            }
            $whereGroup[] = $paramResult[1];
        }


        if(empty($operator) || !in_array(trim($operator),$this->getAllowedLogicalOperators()))
        {
            $operator = $this->getDefaultLogicalOperator();
        }

        $whereGroup = implode(" {$operator} ",$whereGroup);

        return [$input_parameters,$whereGroup];
    }

    /**
     * @see https://dev.mysql.com/doc/refman/8.0/en/logical-operators.html
     * @return array
     */
    public function getAllowedLogicalOperators(): array
    {
        return ["AND","OR","XOR"];
    }

    public function getDefaultLogicalOperator(): string
    {
       return "AND";
    }


    public function processParam(array $param)
    {
        $modelClass = $this->getModel(true);

        if(!property_exists($modelClass,$param["prop"]))
        {
            throw new DAOException("El parámetro $param[prop] es inexistente");
        }

        $operator = $this->getDefaultComparisonOperator();

        if(!empty($param["operator"]) && in_array(trim($param["operator"]),$this->getAllowedComparisonOperators()))
        {
            $operator =$param["operator"];
        }

        //Operadores especiales
        if(in_array(trim($operator),["IN","NOT IN"]))
        {
            $placeholder = is_array($param["value"])? rtrim(str_repeat("?,",count($param["value"])),","):"?";

            $statement = "{$this->prefix}_$param[prop] {$operator} ($placeholder)";
        }
        else
        {
            $statement = "{$this->prefix}_$param[prop] {$operator} ?";
        }


        return [$param["value"],$statement];
    }

    /**
     * @see https://www.techonthenet.com/mysql/comparison_operators.php
     * @return array
     */
    public function getAllowedComparisonOperators(): array
    {
        return ['=','!=','>','<','>=','<=','LIKE','IN','NOT IN','IS','IS NOT'];
    }

    /**
     * @see https://www.techonthenet.com/mysql/comparison_operators.php
     * @return string
     */
    public function getDefaultComparisonOperator(): string
    {
        return '=';
    }

    public function processPagination(&$params)
    {
        if(!isset($this->getPaginationConfiguration()["maxLimit"]))
        {
            throw new DAOException("No hay un límite máximo de paginación establecido");
        }
        if(isset($params["pagination"]["limit"]) && $this->getPaginationConfiguration()["maxLimit"] < $params["pagination"]["limit"])
        {
            $params["pagination"]["limit"] = $this->getPaginationConfiguration()["maxLimit"];
        }
        else if(isset($params["pagination"]) && !isset($params["pagination"]["limit"]))
        {
            $params["pagination"]["limit"] =isset( $this->getPaginationConfiguration()["defaultLimit"])? $this->getPaginationConfiguration()["defaultLimit"]:20;
        }

    }


    public function read(array $params)
    {
       $params["where"][] = ["prop"=>"deleted_at","value"=>NULL,"operator"=>"IS"];

       $this->processPagination($params);

       $this->execute($this->processQuery($params));

        return $this->getItems();

    }

    /**
     * Ejecuta la consulta contra la base de datos
     * @param $query
     * @throws DAOException
     * @return int|void
     */
    public function execute($query)
    {

        if(!is_array($query))
        {
            throw  new DAOException("El parámetro utilizado para ejecutar la consulta no tiene el formato correcto)");
        }

        /**
         * @var $conn \PDO
         */
        $conn = $this->getConnection();
        /**
         * @var $result \PDOStatement
         */

        $statement = $conn->prepare($query["statement"]);
        $result = $statement->execute($query["input_parameters"]);

        if(!$result)
        {
            throw new DAOException("Error al ejecutar la consulta. {$statement->errorInfo()[0]} - {$statement->errorInfo()[1]} - {$statement->errorInfo()[2]}");
        }


        if($conn->lastInsertId())
        {
            return $conn->lastInsertId();
        }

        //Lectura

        $rowCount = $statement->rowCount();

        if(!empty($this->paginationData))
        {
            $this->paginationData["items"] = $rowCount;
            //Cuento los registros totales
            $countStatement = preg_replace("/LIMIT (\d*)/","",$query["statement"]);
            $countStatement = preg_replace("/OFFSET (\d*)/","",$countStatement);
            $countStatement = "SELECT count(*) as 'total' FROM (".trim($countStatement).") as t";
            $countStatement  = $conn->prepare($countStatement);
            $countResult = $countStatement->execute($query["input_parameters"]);
            if(!$countResult)
            {
                throw new DAOException("Error al contar los registros. {$countStatement->errorInfo()[0]} - {$countStatement->errorInfo()[1]} - {$countStatement->errorInfo()[2]}");
            }
            $total  = $countStatement->fetch(\PDO::FETCH_ASSOC)['total'];
            $this->paginationData["total"] = intval($total);

        }

        $this->items = [];

        if($rowCount)
        {
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC))
            {

                $model = $this->getModel();
                $arr= [];

                foreach ($row as $k =>$v)
                {

                    $arr[str_replace($this->prefix."_","",$k)] = $v;
                }

                $model->arrayToObject($arr);
                $this->items[] = $model;
            }
        }

    }


    public function readAll()
    {

        $query = $this->processQuery(["where"=>[["prop"=>"deleted_at","value"=>NULL,"operator"=>"IS"]]]);
        $this->execute($query);

        return $this->getItems();
    }

    public function readById($id)
    {


        $this->execute($this->processQuery(["where"=>[["prop"=>"id","value"=>$id],["prop"=>"deleted_at","value"=>NULL,"operator"=>"IS"]]]));
        $items = $this->getItems();
        return reset($items);
    }

    /**
     * Por defecto, el modelo que devuelvo se crea en base al nombre de la clase DAO actual
     * @param  $className
     * @return ICoreModel
     */
    public function getModel($className=false)
    {
        $r = new \ReflectionObject($this);


        $ModelClass = "\\".$r->getNamespaceName()."\\".str_replace("DAO","",($r->getShortName()));

        if($className)
        {
            return $ModelClass;
        }

        $model = new $ModelClass();

        return $model;

    }

    public function processUpsertDelete(array $params):array
    {

        $action = !empty($params["action"]) && in_array($params["action"],['INSERT','UPDATE','DELETE']) ?$params["action"]: 'INSERT';

        if(in_array($action,['UPDATE','INSERT']) && (empty($params["model"]) || !is_a($params["model"],ICoreModel::class)))
        {
            throw new DAOException("La operación de creación o actualización requiere un modelo");
        }

        $input_parameters = [];
        $set = [];
        if($action == "UPDATE" || $action == "INSERT")
        {

            $properties = $params["model"]->getProperties();

            foreach ($properties/*$params["model"]*/ as $property)
            {
                if(isset($params["model"]->$property))
                {
                    $key = $property;
                    $value = $params["model"]->$property;

                    $annotations = $this->processPopulateAnnotations($key);
                    if(!$annotations || empty($annotations["dao"]))
                    {
                        $value = !is_array($value)?$value:json_encode($value);
                        $set[] = $this->prefix."_".$key." = ?" ;
                        $input_parameters[] = $value;
                    }

                }

            }
        }


        if($action == "UPDATE" || $action == "DELETE")
        {
            if(empty($params["where"]) || !is_array($params["where"]))
            {
                throw new DAOException("La acción {$action} no posee parámetros");
            }

            $actionQuery = $this->processQuery(["where"=>$params["where"],"fields"=>["id"]]);
            $input_parameters = array_merge($input_parameters,$actionQuery["input_parameters"]);
            $subquery  = $actionQuery["statement"];

            if($action == "UPDATE")
            {
                $statement ="{$action} {$this->resource} SET ".implode(",",$set)." WHERE {$this->prefix}_id IN (SELECT * FROM ({$subquery}) as t)";

            }
            else{
                $statement ="{$action} FROM {$this->resource} WHERE {$this->prefix}_id IN (SELECT * FROM ({$subquery}) as t)";

            }

        }
        else
            {
            $statement = "{$action} INTO {$this->resource} SET ".implode(",",$set);


        }

        return ["statement"=>$statement,"input_parameters"=>$input_parameters];
    }


    public function create(ICoreModel &$model)
    {
        $query =$this->processUpsertDelete(["model"=>$model,"action"=>"INSERT"]);

        $id = $this->execute($query);

        //Hago esto para que me conserve los elementos que se poblan exteramente (asociados)
        $newModel =$this->readById($id);

        $model->id = $newModel->id;

        $savedRelationships =  $this->saveRelationships($model);

        $model = $newModel;

        foreach ($savedRelationships as $key => $item)
        {
            $model->$key = $item;
        }

        return $id;
    }

    public function update(ICoreModel &$model,$where = null)
    {
        if(empty($where) && !isset($model->id))
        {
            throw new DAOException("El objeto a actualizar no tiene un id especificado");
        }
        $where = $where ?? [["prop"=>"id","value"=>$model->id,"operator"=>"="]];

        $query =$this->processUpsertDelete(["model"=>$model,"where"=>$where,"action"=>"UPDATE"]);

        $savedRelationships = $this->saveRelationships($model);

        foreach ($savedRelationships as $key => $item)
        {
            $model->$key = $item;
        }


        $this->execute($query);

    }

    public function deleteById($id,bool $softDelete = true)
    {
        if(!$softDelete)
        {
            $query =$this->processUpsertDelete(["where"=>[["prop"=>"id","value"=>$id]],"action"=>"DELETE"]);
        }
        else
        {
            /**
             * @var $model CoreModel
             */
            $model = $this->getModel();
            $now = new \DateTime();
            $model->deleted_at = $now->format("Y-m-d H:i:s");
            $query =$this->processUpsertDelete(["model"=>$model,"where"=>[["prop"=>"id","value"=>$id]],"action"=>"UPDATE"]);
        }

        $this->execute($query);
    }

    public function &populate(string $property)
    {
        $localPopData = $this->processPopulateAnnotations($property);
        /**
         * @var $ExternalDAO ICoreDAO
         */
        //FIXME: CORRECT THIS!!
        eval('$ExternalDAO = new '.$localPopData["dao"].'($this->connection);');
        $in = [];
        $items = $this->getItems();
        $map = [];

        if(isset($localPopData["link_dao"]))
        {
            //Muchos a muchos
            //FIXME:Correct this
            /**
             * @var $LinkDAO ICoreDAO
             */
            eval('$LinkDAO = new '.$localPopData["link_dao"].'($this->connection);');

            foreach ($items as $k => $item)
            {
                if(!in_array($item->id,$in))
                {
                    $in[] = $item->id;
                }

                $map[$item->id] = &$this->items[$k];
            }


            if(count($in) > 0)
            {
                $query= ["where"=>[["prop"=>$this->getResourceName(),"value"=>$in,"operator"=>"IN"]]];
                $linkItems  = $LinkDAO->read($query);
                $linkMap = [];
                $in = [];
                $externalResourceName = $ExternalDAO->getResourceName();
                $resourceName =$this->getResourceName();
                foreach ($linkItems as $item)
                {
                    $in[] = $item->$externalResourceName;
                    $linkMap[$item->$externalResourceName][] = ["resource"=>$item->$resourceName,"link"=>$item];
                }


                if(count($in) > 0 )
                {
                    $query= ["where"=>[["prop"=>"id","value"=>$in,"operator"=>"IN"]]];
                    $itemsToPopulateWith = $ExternalDAO->read($query);

                    foreach ($itemsToPopulateWith as $item)
                    {
                        foreach ($linkMap[$item->id] as $value)
                        {
                            $map[$value["resource"]]->$property[] = $item;
                            $rd = $map[$value["resource"]]->getRelationshipsData();
                            $rd[$property][$item->id] = $value["link"];
                            $map[$value["resource"]]->setRelationshipsData($rd);
                        }
                    }

                }
            }



        }
        elseif(isset($localPopData["external_field"]))
        {
            $external_field = $localPopData["external_field"];

            foreach ($items as $k => $item)
            {
                if(!in_array($item->id,$in))
                {
                    $in[] = $item->id;
                }

                $map[$item->id] = &$this->items[$k];
            }

            if(count($in) > 0)
            {
                $query= ["where"=>[["prop"=>$external_field,"value"=>$in,"operator"=>"IN"]]];

                $itemsToPopulateWith  = $ExternalDAO->read($query);

                foreach ($itemsToPopulateWith as $value)
                {
                    $map[$value->$external_field]->$property[] = $value;
                }

            }
        }
        else
        {

            foreach ($items as $k => $item)
            {
                if(!in_array($item->$property,$in))
                {
                    $in[] = $item->$property;
                }

                $map[$item->$property][] = &$this->items[$k];
            }

            if(count($in) > 0)
            {
                $query= ["where"=>[["prop"=>"id","value"=>$in,"operator"=>"IN"]]];

                $itemsToPopulateWith  = $ExternalDAO->read($query);

                foreach ($itemsToPopulateWith as $value)
                {
                    foreach ($map[$value->id] as $k => $item)
                    {

                        $map[$value->id][$k]->$property = $value;
                    }

                }
            }
        }

        return $ExternalDAO;

    }

    /**
     * Parsea las anotaciones que indican como se va a poblar el campo en cuestión
     * <br>
     * @dao Clase que corresponde al DAO del modelo referenciado
     * @external_field Nombre del campo del modelo externo que corresponde al id del modelo corriente
     * @link_dao DAO correspondiente al modelo que funge como enlace
     *
     * @param string $property
     * @return mixed
     * @throws DAOException
     */
    public function processPopulateAnnotations(string $property)
    {

        $model = $this->getModel();
        $r = new \ReflectionObject($model);

        if(!property_exists($r->getName(),$property))
        {
            throw new DAOException("La propiedad $property es inexistente");
        }

        $rp = new \ReflectionProperty($r->getName(),$property);
        $comment = $rp->getDocComment();
        $pattern = "/@(\w+)(.*)/";
        preg_match_all($pattern,$comment,$matches);

        if(empty($matches) || empty($matches[1]) || empty($matches[2]) )
        {
            return false;
        }



        $options = $matches[1];
        $values = $matches[2];
        $options_values = [];
        foreach ($options as $k => $option)
        {
            $options_values[trim($option)] = trim($values[$k]);
        }


        return $options_values;
    }

    public function getResourceName()
    {
       return $this->resource;
    }


   public function processItemToRelate(&$item,ICoreDAO $dao = null)
    {

        if(!is_a($item,CoreModel::class) && !is_numeric($item))
        {
            throw new DAOException("El elemento a asociar no es válido. Debe ser un id u objeto del tipo ".CoreModel::class);
        }
        if(is_a($item,CoreModel::class) && !isset($item->id) && !isset($dao))
        {
            throw new DAOException("Se debe pasar como argumento el DAO correspondiente en caso de querer asociar un elemento inexistente");
        }

         /**
         * @var $item CoreModel
         */
        if(is_a($item,CoreModel::class) && !isset($item->id))
        {
            //Creo el item que voy a asociar
            $dao->create($item);
        }

        return !is_numeric($item)?$item->id:$item;

    }

    public function saveRelationships(ICoreModel &$model)
    {

        $savedRelationships = [];
        $properties = $model->getProperties(true);
         foreach ($properties as $property)
         {
             $key = $property;
             $annotations = $this->processPopulateAnnotations($key);
             if(isset($model->$key) && $annotations && !empty($annotations["dao"]))
             {

                 $value = $model->$key;

                 /**
                  * @var $ExternalDAO ICoreDAO
                  */
                 $ExternalDAO = $annotations["dao"];
                 //FIXME: Arreglar esto!
                 eval('$ExternalDAO = new $ExternalDAO($this->connection);');

                 if(isset($annotations["link_dao"]))
                 {
                     $value = !is_array($value)? [$value]:$value;


                     $LinkDAO = $annotations["link_dao"];
                     /**
                      * @var $LinkDAO ICoreDAO
                      */
                     //FIXME: Arreglar esto!
                     eval('$LinkDAO = new $LinkDAO($this->connection);');

                     foreach ($value as $k=>$v)
                     {
                         $toDelete = false;
                         $Link = $LinkDAO->getModel();

                         foreach ($v as $h => $i)
                         {
                             if(strpos($h,"relation_data_") === 0)
                             {
                                 /*
                                 $prop = str_replace("relation_data_","",$h);
                                 $Link->$prop = $i;
                                 unset($model->$key[$k]->$h);
                                 */
                                 $prop = str_replace("relation_data_","",$h);



                                 if($prop !== "delete"){
                                     $Link->$prop = $i;

                                     unset($model->$key[$k]->$h);
                                 }
                                 elseif($i === true)
                                 {
                                     if(( isset($v->relation_data_id) && is_numeric($v->relation_data_id)) || (isset($Link->id) && is_numeric($Link->id)))
                                     {

                                         $toDelete=true;
                                     }

                                 }

                             }
                         }


                         if($toDelete)
                         {
                           //Chequeo si el objeto no es del tipo correcto
                           if(!is_a($v,$ExternalDAO->getModel(true)))
                           {
                               throw new DAOException("El elemento de la relación a eliminar no posee el tipo correcto. Se esperaba:".
                                   $ExternalDAO->getModel(true)." y se obtuvo ".get_class($v));
                           }

                           $LinkDAO->deleteById($Link->id);
                         }
                         else
                         {

                             $externalResource = $ExternalDAO->getResourceName();
                             $localResource =  $this->getResourceName();
                             $Link->$externalResource = $this->processItemToRelate($v,$ExternalDAO);
                             $Link->$localResource = $model->id;

                             if(empty($Link->id))
                             {

                                 $LinkDAO->create($Link);
                             }
                             else{

                                 $LinkDAO->update($Link);
                             }
                             $savedRelationships[$key][] = $v;
                         }




                     }

                 }
                 elseif(isset($annotations["external_field"]))
                 {
                     $value = !is_array($value)? [$value]:$value;
                     $externalField = $annotations["external_field"];
                     foreach ($value as $k=>$v)
                     {
                         $id = $this->processItemToRelate($v,$ExternalDAO);
                         $externalModel = $ExternalDAO->getModel();
                         $externalModel->id = $id;
                         $externalModel->$externalField = $externalModel->id;
                         $ExternalDAO->update($externalModel);
                         $savedRelationships[$key][] = $v;
                     }

                 }
                 else
                 {
                     $id = $this->processItemToRelate($value,$ExternalDAO);
                     $this->execute(["statement"=>"UPDATE ".$this->getResourceName()." SET ".$this->prefix."_{$key} = {$id} WHERE ".$this->prefix."_id = ".$model->id,"input_parameters"=>[]]);
                     $savedRelationships[$key] = $value;
                     //$this->update($modelToUpdate);
                 }


             }

         }

         //Elimino las relaciones que esten marcadas para eliminacion (muchos a muchos)
         /*$rd =  $model->getRelationshipsData();

         foreach ($rd as $k=>$v)
         {
             $relationshipsToDelete= [];

             foreach ($v as $i => $j)
             {
                 if(isset($j["delete"]) && $j["delete"] === true)
                 {
                     $relationshipsToDelete[]=$j["id"];
                 }
             }

         }*/



         return $savedRelationships;
    }


}