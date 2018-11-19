<?php

/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 19/10/2018
 * Time: 12:27 PM
 */
include "../autoload.php";

use PHPUnit\Framework\TestCase;


class DAOTest extends TestCase
{
    /**
     * @var \db\SqlConnection $connection
     */

    public function setUp()
    {
        $connection = new \db\SqlConnection("mysql:host=localhost;dbname=tachyon_test", "root", "powersoccergbi");

        $connection->exec("TRUNCATE customer");
        $connection->exec("TRUNCATE service_order");
        $connection->exec("TRUNCATE tag");
        $connection->exec("TRUNCATE service_order_tag");
        $connection->exec("TRUNCATE multimedia");
        $connection->exec("TRUNCATE multimedia_tag");
    }

    /**
     * @dataProvider daoProvider
     */
    public function testGenerateResourceName($customerDAO,$serviceOrderDAO)
    {
        $this->assertEquals('customer',$customerDAO->generateResourceName());
        $this->assertEquals('service_order',$serviceOrderDAO->generateResourceName());
        $this->assertEquals('customer',$customerDAO->generatePrefix());
        $this->assertEquals('service_order',$serviceOrderDAO->generatePrefix());
    }
    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     */
    public function testGetModel($customerDAO,$serviceOrderDAO)
    {
        $this->assertEquals($customerDAO->getModel(true),'\\tests\\customer\\Customer');
        $this->assertEquals($serviceOrderDAO->getModel(true),'\\tests\\service_order\\ServiceOrder');
    }


    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     */
    public function testProcessParam($customerDAO,$serviceOrderDAO)
    {
        $param = ["prop"=>"name","value"=>"Gabriel"];
        list($value,$statement) = $customerDAO->processParam($param);
        $this->assertEquals('Gabriel',$value);
        $this->assertEquals('customer_name = ?',$statement);

        $param = ["prop"=>"zip_code","value"=>100,"operator"=>">"];
        list($value,$statement) = $customerDAO->processParam($param);
        $this->assertEquals(100,$value);
        $this->assertEquals('customer_zip_code > ?',$statement);

        $param = ["prop"=>"zip_code","value"=>[3100,3200],"operator"=>"IN"];
        list($value,$statement) = $customerDAO->processParam($param);
        $this->assertEquals([3100,3200],$value);
        $this->assertEquals('customer_zip_code IN (?,?)',$statement);

        $param = ["prop"=>"zip_code","value"=>[3100,3200],"operator"=>"NOT IN"];
        list($value,$statement) = $customerDAO->processParam($param);
        $this->assertEquals([3100,3200],$value);
        $this->assertEquals('customer_zip_code NOT IN (?,?)',$statement);


        $param = ["prop"=>"zip_code","value"=>3100,"operator"=>"INEXISTANT"];
        list($value,$statement) = $customerDAO->processParam($param);
        $this->assertEquals('customer_zip_code = ?',$statement);

        $this->expectException(\exceptions\DAOException::class);
        $param = ["prop"=>"not_existant_field","value"=>3100];
        list($value,$statement) = $customerDAO->processParam($param);


        $param = ["prop"=>"deleted_at","value"=>null,"operator"=>"IS"];
        list($value,$statement) = $customerDAO->processParam($param);
        $this->assertEquals([null],$value);
        $this->assertEquals('customer_deleted_at IS ?',$statement);


        $param = ["prop"=>"deleted_at","value"=>null,"operator"=>"IS NOT"];
        list($value,$statement) = $customerDAO->processParam($param);
        $this->assertEquals([null],$value);
        $this->assertEquals('customer_deleted_at IS NOT ?',$statement);

    }


    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     */
    public function testProcessParamGroup($customerDAO,$serviceOrderDAO)
    {
        $paramGroup = [
            ["prop"=>"name","value"=>"%Gabriel%","operator"=>"LIKE"],
            ["prop"=>"age","value"=>18,"operator"=>">"],
            ["prop"=>"zip_code","value"=>[3100,3200],"operator"=>"IN"]
        ];
        list($values,$statement) = $customerDAO->processParamGroup($paramGroup);
        $this->assertEquals(['%Gabriel%',18,3100,3200],$values);
        $this->assertEquals('customer_name LIKE ? AND customer_age > ? AND customer_zip_code IN (?,?)',$statement);

        list($values,$statement) = $customerDAO->processParamGroup($paramGroup,'OR');
        $this->assertEquals('customer_name LIKE ? OR customer_age > ? OR customer_zip_code IN (?,?)',$statement);

    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     */
    public function testProcessQuery($customerDAO,$serviceOrderDAO)
    {
        $query = [
            "where"=>[
                ["prop"=>"zip_code","value"=>[3100,3200],"operator"=>"IN"],
                ["prop"=>"age","value"=>18,"operator"=>">"],
                [
                    "group"=>[ ["prop"=>"name","value"=>"Gabriel","operator"=>"LIKE"], ["prop"=>"surname","value"=>"Macus"] ],
                    "operator"=>"OR"
                ]
            ]
        ];

        $processedQuery =  $customerDAO->processQuery($query);
        $this->assertEquals('SELECT * FROM customer WHERE customer_zip_code IN (?,?) AND customer_age > ? AND (customer_name LIKE ? OR customer_surname = ?)',$processedQuery["statement"]);
        $this->assertEquals([3100,3200,18,'Gabriel','Macus'],$processedQuery["input_parameters"]);

        $query = [
            "fields"=>['name','surname','id'],
            "where"=>[
                ["prop"=>"zip_code","value"=>[3100,3200],"operator"=>"IN"],
                ["prop"=>"age","value"=>18,"operator"=>">"],
                [
                    "group"=>[ ["prop"=>"name","value"=>"Gabriel","operator"=>"LIKE"], ["prop"=>"surname","value"=>"Macus"] ],
                    "operator"=>"OR"
                ],
                [
                    "group"=>[ ["prop"=>"email","value"=>"gabrielmacus@gmail.com","operator"=>"LIKE"], ["prop"=>"id","value"=>10] ]
                ]
            ]
        ];
        $processedQuery =  $customerDAO->processQuery($query);
        $this->assertEquals('SELECT customer_name,customer_surname,customer_id FROM customer WHERE customer_zip_code IN (?,?) AND customer_age > ? AND (customer_name LIKE ? OR customer_surname = ?) AND (customer_email LIKE ? AND customer_id = ?)',$processedQuery["statement"]);
        $this->assertEquals([3100,3200,18,'Gabriel','Macus','gabrielmacus@gmail.com',10],$processedQuery["input_parameters"]);

    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     */
    public function testProcessUpsertDelete($customerDAO,$serviceOrderDAO)
    {
        $customer = new \tests\customer\Customer();
        $customer->name ="Gabriel";
        $customer->surname="Macus";
        $customer->zip_code = 3100;

        //insert
        $result  = $customerDAO->processUpsertDelete(["model"=>$customer]);
        $this->assertEquals('INSERT INTO customer SET customer_name = ?,customer_surname = ?,customer_zip_code = ?',$result["statement"]);
        $this->assertEquals(["Gabriel","Macus",3100],$result["input_parameters"]);

        //update
        $result  = $customerDAO->processUpsertDelete(["model"=>$customer,"action"=>"UPDATE",
            "where"=>[["prop"=>"id","value"=>1]]
        ]);
        $this->assertEquals('UPDATE customer SET customer_name = ?,customer_surname = ?,customer_zip_code = ? WHERE customer_id IN (SELECT * FROM (SELECT customer_id FROM customer WHERE customer_id = ?) as t)',$result["statement"]);
        $this->assertEquals(["Gabriel","Macus",3100,1],$result["input_parameters"]);


        $result  = $customerDAO->processUpsertDelete(["model"=>$customer,"action"=>"UPDATE",
            "where"=>[
                ["prop"=>"name","value"=>'%Gabriel%','operator'=>"LIKE"],
                ["group"=>[
                    ["prop"=>"id","value"=>1,"operator"=>"!="],
                    ["prop"=>"zip_code","value"=>[3100,3200],"operator"=>"IN"]
                ],"operator"=>"OR"]

            ]
        ]);
        $this->assertEquals('UPDATE customer SET customer_name = ?,customer_surname = ?,customer_zip_code = ? WHERE customer_id IN (SELECT * FROM (SELECT customer_id FROM customer WHERE customer_name LIKE ? AND (customer_id != ? OR customer_zip_code IN (?,?))) as t)',$result["statement"]);
        $this->assertEquals(["Gabriel","Macus",3100,'%Gabriel%',1,3100,3200],$result["input_parameters"]);

        //delete

        $result  = $customerDAO->processUpsertDelete(["model"=>$customer,"action"=>"DELETE",
            "where"=>[["prop"=>"id","value"=>1]]
        ]);
        $this->assertEquals('DELETE FROM customer WHERE customer_id IN (SELECT * FROM (SELECT customer_id FROM customer WHERE customer_id = ?) as t)',$result["statement"]);
        $this->assertEquals([1],$result["input_parameters"]);




    }


    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     */
    public function testCreate($customerDAO,$serviceOrderDAO)
    {
        $customer = new \tests\customer\Customer();
        $customer->email ="gabrielmacus@gmail.com";
        $customer->name ="Gabriel";
        $customer->age = 22;
        $customer->surname="Macus";
        $customer->zip_code=3100;

        $customerDAO->create($customer);
        $this->assertEquals($customer->id,1);

        $service_order = new \tests\service_order\ServiceOrder();
        $service_order->description ="Aparato en buen estado.";

        $serviceOrderDAO->create($service_order);
        $this->assertEquals($service_order->id,1);





    }


    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     */
    public function testUpdate($customerDAO,$serviceOrderDAO)
    {
        $customer = new \tests\customer\Customer();
        $customer->email ="gabrielmacus@gmail.com";
        $customer->name ="Gabriel";
        $customer->age = 18;
        $customer->surname="Macus";
        $customer->zip_code=3200;

        $customerDAO->create($customer);

        $customer->age=22;
        $customer->zip_code=3100;

        $customerDAO->update($customer);

        $customer = $customerDAO->readById(1);
        $this->assertEquals($customer->age,22);
        $this->assertEquals($customer->zip_code,3100);


        $customer->id = null;
        $this->expectException(\exceptions\DAOException::class);
        $customerDAO->update($customer);

    }


    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     */
    public function testDelete($customerDAO,$serviceOrderDAO)
    {
        $customer = new \tests\customer\Customer();
        $customer->email ="gabrielmacus@gmail.com";
        $customer->name ="Gabriel";
        $customer->age = 18;
        $customer->surname="Macus";
        $customer->zip_code=3200;

        $customerDAO->create($customer);

        /**
         * @var $conn \db\SqlConnection
         */
        $conn = $customerDAO->getConnection();

        //Soft delete
        $customerDAO->deleteById(1);
        $this->assertEquals(count($customerDAO->readAll()),0);
        $this->assertEquals($conn->query("SELECT * FROM customer WHERE customer_deleted_at IS NOT NULL")->rowCount(),1);
        $this->assertEquals($customerDAO->readById(1),false);

        $conn->exec("UPDATE customer SET customer_deleted_at = NULL");

        //Hard delete
        $customerDAO->deleteById(1,false);
        $this->assertEquals(count($customerDAO->readAll()),0);
        $this->assertEquals($customerDAO->readById(1),false);
        $this->assertEquals($conn->query("SELECT * FROM customer")->rowCount(),0);

    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     */
    public function testProcessPopulate($customerDAO,$serviceOrderDAO)
    {

        $annotations = $customerDAO->processPopulateAnnotations("serviceOrders");
        $this->assertEquals($annotations["dao"],"tests\\service_order\\ServiceOrderDAO");
        $this->assertEquals($annotations["external_field"],"customer");

        $annotations = $serviceOrderDAO->processPopulateAnnotations("customer");
        $this->assertEquals($annotations["dao"],"tests\\customer\\CustomerDAO");


        $annotations = $serviceOrderDAO->processPopulateAnnotations("tags");
        $this->assertEquals($annotations["dao"],"tests\\tag\\TagDAO");
        $this->assertEquals($annotations["link_dao"],"tests\\service_order_tag\\ServiceOrderTagDAO");

        $this->expectException(\exceptions\DAOException::class);
        $customerDAO->processPopulateAnnotations("inexistentProperty");
    }

    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     */
    public function testPopulateOneToMany($customerDAO,$serviceOrderDAO)
    {
        //Un cliente puede tener muchas ordenes de servicio. La orden de servicio puede tener solo un cliente

        $i=0;
        $customer = new \tests\customer\Customer();
        $customer->email = "customer$i@demo.com";
        $customer->name = $this->generateRandomString(5);
        $customer->surname = $this->generateRandomString(5);
        $customer->age =mt_rand(18,40);
        $customer->zip_code = mt_rand(3100,5000);
        $customerDAO->create($customer);


        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "Demo service order";
        $serviceOrderDAO->create($serviceOrder);
        $serviceOrderDAO->execute(["statement"=>"UPDATE service_order SET service_order_customer = {$customer->id} WHERE service_order_id = {$serviceOrder->id}","input_parameters"=>[]]);


        $i=1;
        $customer = new \tests\customer\Customer();
        $customer->email = "customer$i@demo.com";
        $customer->name = $this->generateRandomString(5);
        $customer->surname = $this->generateRandomString(5);
        $customer->age =mt_rand(18,40);
        $customer->zip_code = mt_rand(3100,5000);
        $customerDAO->create($customer);


        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "Demo service order 2";
        $serviceOrder->customer = $customer->id;
        $serviceOrderDAO->create($serviceOrder);
        $serviceOrderDAO->execute(["statement"=>"UPDATE service_order SET service_order_customer = {$customer->id} WHERE service_order_id = {$serviceOrder->id}","input_parameters"=>[]]);

        $serviceOrderDAO->readAll();
        $serviceOrderDAO->populate("customer");
        $items = $serviceOrderDAO->getItems();

        $this->assertEquals($items[0]->customer->id,1);
        $this->assertEquals($items[1]->customer->id,2);



        /*for($i = 0;$i <5;$i++)
        {
            $customer->email = "customer$i@demo.com";
            $customer->name = $this->generateRandomString(5);
            $customer->surname = $this->generateRandomString(5);
            $customer->age =mt_rand(18,40);
            $customer->zip_code = mt_rand(3100,5000);
            $customerDAO->create($customer);


        }*/



    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     */
    public function testPopulateManyToOne($customerDAO,$serviceOrderDAO)
    {
        //Un cliente puede tener muchas ordenes de servicio. La orden de servicio puede tener solo un cliente

        $i=1;
        $customer = new \tests\customer\Customer();
        $customer->email = "customer$i@demo.com";
        $customer->name = $this->generateRandomString(5);
        $customer->surname = $this->generateRandomString(5);
        $customer->age =mt_rand(18,40);
        $customer->zip_code = mt_rand(3100,5000);
        $customerDAO->create($customer);

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "Demo service order";
        $serviceOrderDAO->create($serviceOrder);
        $serviceOrderDAO->execute(["statement"=>"UPDATE service_order SET service_order_customer = {$customer->id} WHERE service_order_id = {$serviceOrder->id}","input_parameters"=>[]]);

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "Demo service order 2";
        $serviceOrderDAO->create($serviceOrder);
        $serviceOrderDAO->execute(["statement"=>"UPDATE service_order SET service_order_customer = {$customer->id} WHERE service_order_id = {$serviceOrder->id}","input_parameters"=>[]]);


        $i=2;
        $customer = new \tests\customer\Customer();
        $customer->email = "customer$i@demo.com";
        $customer->name = $this->generateRandomString(5);
        $customer->surname = $this->generateRandomString(5);
        $customer->age =mt_rand(18,40);
        $customer->zip_code = mt_rand(3100,5000);
        $customerDAO->create($customer);

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "Demo service order 3";
        $serviceOrderDAO->create($serviceOrder);
        $serviceOrderDAO->execute(["statement"=>"UPDATE service_order SET service_order_customer = {$customer->id} WHERE service_order_id = {$serviceOrder->id}","input_parameters"=>[]]);

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "Demo service order 4";
        $serviceOrderDAO->create($serviceOrder);
        $serviceOrderDAO->execute(["statement"=>"UPDATE service_order SET service_order_customer = {$customer->id} WHERE service_order_id = {$serviceOrder->id}","input_parameters"=>[]]);

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "Demo service order 5";
        $serviceOrderDAO->create($serviceOrder);
        $serviceOrderDAO->execute(["statement"=>"UPDATE service_order SET service_order_customer = {$customer->id} WHERE service_order_id = {$serviceOrder->id}","input_parameters"=>[]]);


        $i=3;
        $customer = new \tests\customer\Customer();
        $customer->email = "customer$i@demo.com";
        $customer->name = $this->generateRandomString(5);
        $customer->surname = $this->generateRandomString(5);
        $customer->age =mt_rand(18,40);
        $customer->zip_code = mt_rand(3100,5000);
        $customerDAO->create($customer);

        $customerDAO->readAll();
        $items = $customerDAO->getItems();
        $customerDAO->populate("serviceOrders",["order"=>["+id"]]);

        $this->assertEquals($items[0]->serviceOrders[0]->description,"Demo service order");
        $this->assertEquals($items[0]->serviceOrders[1]->description,"Demo service order 2");
        $this->assertCount(2,$items[0]->serviceOrders);

        $this->assertEquals($items[1]->serviceOrders[0]->description,"Demo service order 3");
        $this->assertEquals($items[1]->serviceOrders[1]->description,"Demo service order 4");
        $this->assertEquals($items[1]->serviceOrders[2]->description,"Demo service order 5");
        $this->assertCount(3,$items[1]->serviceOrders);

        $this->assertCount( 0,$items[2]->serviceOrders);


    }


    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testPaginationProcess($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {
        $params = ["pagination"=>["limit"=>1000]];
        $customerDAO->processPagination($params);
        $this->assertEquals($params["pagination"]["limit"],50);

        $params = ["pagination"=>[]];
        $customerDAO->processPagination($params);
        $this->assertEquals($params["pagination"]["limit"],10);
    }



    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testPagination($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {

        for($i = 0;$i <43;$i++)
        {
            $customer = new \tests\customer\Customer();
            $customer->email = "customer$i@demo.com";
            $customer->name = $this->generateRandomString(5);
            $customer->surname = $this->generateRandomString(5);
            $customer->age =mt_rand(18,40);
            $customer->zip_code = mt_rand(3100,5000);
            $customerDAO->create($customer);
        }

        $customerDAO->read(["pagination"=>[],"order"=>["+id"]]);
        $items = $customerDAO->getItems();

        $this->assertCount( 10,$items);
        $ids = array_map(function($el){return $el->id;},$items);
        $this->assertEquals($ids,range(1,10));

        $customerDAO->read(["pagination"=>["page"=>$customerDAO->getPaginationData()["next"]],"order"=>["+id"]]);
        $items = $customerDAO->getItems();
        $this->assertCount( 10,$items);
        $ids = array_map(function($el){return $el->id;},$items);
        $this->assertEquals($ids,range(11,20));


        $customerDAO->read(["pagination"=>["page"=>$customerDAO->getPaginationData()["next"]],"order"=>["+id"]]);
        $items = $customerDAO->getItems();
        $this->assertCount( 10,$items);
        $ids = array_map(function($el){return $el->id;},$items);
        $this->assertEquals($ids,range(21,30));


        $customerDAO->read(["pagination"=>["page"=>$customerDAO->getPaginationData()["next"]],"order"=>["+id"]]);
        $items = $customerDAO->getItems();
        $this->assertCount( 10,$items);
        $ids = array_map(function($el){return $el->id;},$items);
        $this->assertEquals($ids,range(31,40));


        $customerDAO->read(["pagination"=>["page"=>$customerDAO->getPaginationData()["next"]],"order"=>["+id"]]);
        $items = $customerDAO->getItems();
        $this->assertCount( 3,$items);
        $ids = array_map(function($el){return $el->id;},$items);
        $this->assertEquals($ids,range(41,43));



    }



    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testProcessItemToRelate($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {
        //Muchas ordenes de servicio pueden tener muchas etiquetas
        $tag = new \tests\tag\Tag();
        $tag->name = "Carga superior";
        $id = $serviceOrderDAO->processItemToRelate($tag,$tagDAO);
        $this->assertEquals($id,1);

        $tag = new \tests\tag\Tag();
        $tag->id = 24;
        $tag->name = "demo";
        $id = $serviceOrderDAO->processItemToRelate($tag);
        $this->assertEquals($id,24);

        $id = 211;
        $id = $serviceOrderDAO->processItemToRelate($id);
        $this->assertEquals($id,211);


    }
    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testProcessItemToRelatePassingArray($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {
        //Paso un array en lugar de un objeto
        //Muchas ordenes de servicio pueden tener muchas etiquetas
        $tag =  ["id"=>"511","name"=>"Carga Superior"];
        $id = $serviceOrderDAO->processItemToRelate($tag,$tagDAO);
        $this->assertEquals($id,511);

        $tag =  ["id"=>"1900","name"=>"Carga Inferior"];
        $id = $serviceOrderDAO->processItemToRelate($tag,$tagDAO);
        $this->assertEquals($id,1900);


    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testSaveRelationships($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {
        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "El cliente es agresivo";

        $tag = new \tests\tag\Tag();
        $tag->name = "DigitalWash";
        $serviceOrder->tags[] = $tag;

        $tag = new \tests\tag\Tag();
        $tag->name = "Carga Superior";
        $serviceOrder->tags[] = $tag;

        $tag = new \tests\tag\Tag();
        $tag->name = "Buen Estado";
        $serviceOrder->tags[] = $tag;
        $serviceOrderDAO->create($serviceOrder);

        $tags = $tagDAO->readAll();
        $this->assertEquals($tags[0]->name, "DigitalWash");
        $this->assertEquals($tags[1]->name, "Carga Superior");
        $this->assertEquals($tags[2]->name, "Buen Estado");
        $this->assertCount(3, $tags);

        $this->assertCount(3, $serviceOrder->tags);
        $this->assertEquals($serviceOrder->tags[0]->id, 1);
        $this->assertEquals($serviceOrder->tags[1]->id, 2);
        $this->assertEquals($serviceOrder->tags[2]->id, 3);


        $tag = new \tests\tag\Tag();
        $tag->name = "Blanco";
        $serviceOrder->tags[] = $tag;
        $serviceOrderDAO->update($serviceOrder);

        $this->assertCount(4, $serviceOrder->tags);
        $this->assertEquals($serviceOrder->tags[0]->id, 1);
        $this->assertEquals($serviceOrder->tags[1]->id, 2);
        $this->assertEquals($serviceOrder->tags[2]->id, 3);
        $this->assertEquals($serviceOrder->tags[3]->id, 4);


        //Corroboro que se hayan guardado los enlaces a las relaciones

        $serviceOrderTagLinks = $serviceOrderTagDAO->read(["order" => "+id"]);
        $this->assertCount(4, $serviceOrderTagLinks);
        $this->assertEquals($serviceOrderTagLinks[0]->tag, 1);
        $this->assertEquals($serviceOrderTagLinks[0]->service_order, 1);
        $this->assertEquals($serviceOrderTagLinks[1]->tag, 2);
        $this->assertEquals($serviceOrderTagLinks[1]->service_order, 1);
        $this->assertEquals($serviceOrderTagLinks[2]->tag, 3);
        $this->assertEquals($serviceOrderTagLinks[2]->service_order, 1);
        $this->assertEquals($serviceOrderTagLinks[3]->tag, 4);
        $this->assertEquals($serviceOrderTagLinks[3]->service_order, 1);
        //

        $customer = new \tests\customer\Customer();
        $customer->email = "gabrielmacus@gmail.com";
        $customer->name = "Gabriel";
        $customer->age = 18;
        $customer->surname = "Macus";
        $customer->zip_code = 3200;

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "PARA EL CLIENTE";
        $customer->serviceOrders[] = $serviceOrder;

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "PARA EL CLIENTE 2";
        $customer->serviceOrders[] = $serviceOrder;

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "PARA EL CLIENTE 3";
        $customer->serviceOrders[] = $serviceOrder;

        $customerDAO->create($customer);


        $this->assertCount( 3,$customer->serviceOrders);
        $this->assertEquals($customer->serviceOrders[0]->id,2);
        $this->assertEquals($customer->serviceOrders[1]->id,3);
        $this->assertEquals($customer->serviceOrders[2]->id,4);

        $customer = new \tests\customer\Customer();
        $customer->email ="rorocha@gmail.com";
        $customer->name ="Roberto";
        $customer->age = 67;
        $customer->surname="Rocha";
        $customer->zip_code=3200;

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "DEMO ID 5";
        $serviceOrder->customer = $customer;
        $serviceOrder->tags[]=$tag;
        $serviceOrderDAO->create($serviceOrder);

        $this->assertEquals($customer->id,2);
        $this->assertEquals($serviceOrder->customer->id,2);
        $this->assertEquals($serviceOrder->tags[0]->id,4);


        //Corroboro que se haya seteado el campo externo//
        $serviceOrders =$serviceOrderDAO->read(["where"=>[["operator"=>"OR","group"=>[["prop"=>"description","value"=>"DEMO ID 5"],["prop"=>"description","value"=>"%PARA EL CLIENTE%","operator"=>"LIKE"]]]],"order"=>["+id"]]);


        foreach ($serviceOrders as $s)
        {
            /**
             * @var $s \tests\service_order\ServiceOrder
             */
            switch ($s->description)
            {
                case 'DEMO ID 5':
                    $this->assertEquals(2,$s->customer);
                    break;

                default:
                    $this->assertEquals(1,$s->customer);
                    break;
            }

        }
        //




    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testSaveRelationshipsNested($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {



        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "El cliente es agresivo";

        $tag = new \tests\tag\Tag();
        $tag->name = "DigitalWash";
        $serviceOrder->tags[] = $tag;

        $tag = new \tests\tag\Tag();
        $tag->name = "Carga Superior";
        $serviceOrder2 = new \tests\service_order\ServiceOrder();
        $serviceOrder2->description = "Desde las etiquetas";
        $tag->serviceOrders[] = $serviceOrder2;
        $serviceOrder->tags[] = $tag;

        $tag = new \tests\tag\Tag();
        $tag->name = "Buen Estado";
        $serviceOrder->tags[] = $tag;

        $customer = new \tests\customer\Customer();
        $customer->email ="rorocha@gmail.com";
        $customer->name ="Roberto";
        $customer->age = 67;
        $customer->surname="Rocha";
        $customer->zip_code=3200;
        $customer->serviceOrders[]=$serviceOrder;
        $customerDAO->create($customer);

        $this->assertEquals(1,$customer->serviceOrders[0]->id);
        $this->assertEquals(1,$customer->serviceOrders[0]->tags[0]->id);
        $this->assertEquals(2,$customer->serviceOrders[0]->tags[1]->id);
        $this->assertEquals(2,$customer->serviceOrders[0]->tags[1]->serviceOrders[0]->id);
        $this->assertEquals(3,$customer->serviceOrders[0]->tags[2]->id);

        //Corroboro que se hayan guardado los enlaces a las relaciones y seteado los campos correspondientes

        $serviceOrderTagLinks = $serviceOrderTagDAO->read(["order" => "+id"]);
        $this->assertCount(4, $serviceOrderTagLinks);


        $this->assertEquals($serviceOrderTagLinks[0]->tag, 1);
        $this->assertEquals($serviceOrderTagLinks[0]->service_order, 1);
        $this->assertEquals($serviceOrderTagLinks[1]->tag, 2);
        $this->assertEquals($serviceOrderTagLinks[1]->service_order, 2);
        $this->assertEquals($serviceOrderTagLinks[2]->tag, 2);
        $this->assertEquals($serviceOrderTagLinks[2]->service_order, 1);
        $this->assertEquals($serviceOrderTagLinks[3]->tag, 3);
        $this->assertEquals($serviceOrderTagLinks[3]->service_order, 1);


        $serviceOrders = $serviceOrderDAO->readAll();
        $this->assertCount(2, $serviceOrders);
        $this->assertEquals(1,$serviceOrders[0]->customer);
        $this->assertEquals(null,$serviceOrders[1]->customer);
        //

    }


    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testSaveRelationshipsDataDinamically($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {
        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "El cliente está loco";

        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 1";
        $tag->relation_data_position = 546;
        $serviceOrder->tags[] =$tag;

        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 2";
        $tag->relation_data_position = 529;
        $serviceOrder->tags[] =$tag;


        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 3";
        $tag->relation_data_position = 521;
        $serviceOrder->tags[] =$tag;

        $serviceOrderDAO->create($serviceOrder);
        $serviceOrderDAO->readAll();
        $items= $serviceOrderDAO->getItems();
        $serviceOrderDAO->populate("tags");

        $this->assertEquals(546,$items[0]->getRelationshipsData()['tags'][1]->position);
        $this->assertEquals(529,$items[0]->getRelationshipsData()['tags'][2]->position);
        $this->assertEquals(521,$items[0]->getRelationshipsData()['tags'][3]->position);


    }


    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testSaveRelationshipsDataUpdate($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {
        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "El cliente está loco";

        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 1";
        $tag->relation_data_position = 546;
        $serviceOrder->tags[] =$tag;

        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 2";
        $tag->relation_data_position = 529;
        $serviceOrder->tags[] =$tag;


        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 3";
        $tag->relation_data_position = 521;
        $serviceOrder->tags[] =$tag;

        $serviceOrderDAO->create($serviceOrder);

        /**
         * @var $serviceOrder \tests\service_order\ServiceOrder
         */
        $serviceOrder = $serviceOrderDAO->readById(1);

        $tag = new \tests\tag\Tag();
        $tag->id = 1;
        $tag->relation_data_position = 10;
        $tag->relation_data_id = 1;
        $serviceOrder->tags[] = $tag;


        $tag = new \tests\tag\Tag();
        $tag->id = 2;
        $tag->relation_data_position = 20;
        $tag->relation_data_id = 2;
        $serviceOrder->tags[] = $tag;

        $tag = new \tests\tag\Tag();
        $tag->id = 3;
        $tag->relation_data_position = 30;
        $tag->relation_data_id = 3;
        $serviceOrder->tags[] = $tag;

        $serviceOrderDAO->update($serviceOrder);

        $items = $serviceOrderDAO->readAll();
        $serviceOrderDAO->populate("tags",["order"=>["+id"]]);


        $this->assertEquals(1,$items[0]->tags[0]->id);
        $this->assertEquals(2,$items[0]->tags[1]->id);
        $this->assertEquals(3,$items[0]->tags[2]->id);

        $this->assertEquals(10,$items[0]->getRelationshipsData()['tags'][1]->position);
        $this->assertEquals(20,$items[0]->getRelationshipsData()['tags'][2]->position);
        $this->assertEquals(30,$items[0]->getRelationshipsData()['tags'][3]->position);

        //Updates relation and changes element


        /**
         * @var $serviceOrder \tests\service_order\ServiceOrder
         */
        $serviceOrder = $serviceOrderDAO->readById(1);

        $tag = new \tests\tag\Tag();
        $tag->name ="Nueva etiqueta 1";
        $tag->relation_data_position = 50;
        $tag->relation_data_id = 1;
        $serviceOrder->tags[] = $tag;


        $tag = new \tests\tag\Tag();
        $tag->name ="Nueva etiqueta 2";
        $tag->relation_data_position = 60;
        $tag->relation_data_id = 2;
        $serviceOrder->tags[] = $tag;

        $tag = new \tests\tag\Tag();
        $tag->name ="Nueva etiqueta 3";
        $tag->relation_data_id = 3;
        $serviceOrder->tags[] = $tag;

        $serviceOrderDAO->update($serviceOrder);

        $items = $serviceOrderDAO->read(["order"=>["+id"]]);
        $serviceOrderDAO->populate("tags",["order"=>["+id"]]);


        $this->assertEquals(4,$items[0]->tags[0]->id);
        $this->assertEquals(5,$items[0]->tags[1]->id);
        $this->assertEquals(6,$items[0]->tags[2]->id);

        $this->assertEquals(50,$items[0]->getRelationshipsData()['tags'][4]->position);
        $this->assertEquals(60,$items[0]->getRelationshipsData()['tags'][5]->position);
        $this->assertEquals(30,$items[0]->getRelationshipsData()['tags'][6]->position);

        $this->assertCount( 3,$serviceOrderTagDAO->readAll());

        $this->assertCount( 6,$tagDAO->readAll());





    }


    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testSaveRelationshipsDataDelete($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {
        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "El cliente está loco";

        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 1";
        $tag->relation_data_position = 546;
        $serviceOrder->tags[] = $tag;

        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 2";
        $tag->relation_data_position = 529;
        $serviceOrder->tags[] = $tag;


        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 3";
        $tag->relation_data_position = 521;
        $serviceOrder->tags[] = $tag;


        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 4";
        $tag->relation_data_position = 525;
        $serviceOrder->tags[] = $tag;

        $serviceOrderDAO->create($serviceOrder);


        $serviceOrderDAO->readAll();
        $items = $serviceOrderDAO->getItems();
        $serviceOrderDAO->populate("tags");
        $this->assertCount(4, $items[0]->tags);

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->id = 1;

        $tag = new \tests\tag\Tag();
        $tag->relation_data_id = 2;
        $tag->relation_data_delete = true;
        $serviceOrder->tags[] = $tag;

        $tag = new \tests\tag\Tag();
        $tag->relation_data_id = 1;
        $tag->relation_data_delete = true;
        $serviceOrder->tags[] = $tag;

        $serviceOrderDAO->update($serviceOrder);

        $serviceOrderDAO->read(["order"=>["+id"]]);
        $items = $serviceOrderDAO->getItems();
        $serviceOrderDAO->populate("tags",["order"=>["+id"]]);
        $this->assertCount(2, $items[0]->tags);
        $this->assertEquals(3, $items[0]->tags[0]->id);
        $this->assertEquals(4, $items[0]->tags[1]->id);


        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->id = 1;

        $tag = new \tests\tag\Tag();
        $tag->relation_data_id = 4;
        $tag->relation_data_delete = true;
        $serviceOrder->tags[] =$tag;
        $serviceOrderDAO->update($serviceOrder);
        $serviceOrderDAO->readAll();
        $items = $serviceOrderDAO->getItems();
        $serviceOrderDAO->populate("tags");
        $this->assertCount(1, $items[0]->tags);
        $this->assertEquals(3, $items[0]->tags[0]->id);




        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->id = 1;
        //Uso una clase distinta al del objeto relacionado al intentar eliminar la relacion
        $tag = new \tests\service_order\ServiceOrder();
        $tag->relation_data_id = 3;
        $tag->relation_data_delete = true;
        $serviceOrder->tags[] =$tag;
        $this->expectException(\exceptions\DAOException::class);
        $serviceOrderDAO->update($serviceOrder);




    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     * @param $imageDAO \tests\image\ImageDAO
     */
    public function testPopulateFromChild($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO,$imageDAO)
    {

        $serviceOrder1 = new \tests\service_order\ServiceOrder();
        $serviceOrder1->description = 'Service Order #1';
        $serviceOrderDAO->create($serviceOrder1);

        $serviceOrder2 = new \tests\service_order\ServiceOrder();
        $serviceOrder2->description = 'Service Order #2';
        $serviceOrderDAO->create($serviceOrder2);

        $serviceOrder3 = new \tests\service_order\ServiceOrder();
        $serviceOrder3->description = 'Service Order #3';
        $serviceOrderDAO->create($serviceOrder3);

        $serviceOrder4 = new \tests\service_order\ServiceOrder();
        $serviceOrder4->description = 'Service Order #4';
        $serviceOrderDAO->create($serviceOrder4);

        $serviceOrder5 = new \tests\service_order\ServiceOrder();
        $serviceOrder5->description = 'Service Order #5';
        $serviceOrderDAO->create($serviceOrder5);

        $serviceOrder6 = new \tests\service_order\ServiceOrder();
        $serviceOrder6->description = 'Service Order #6';
        $serviceOrderDAO->create($serviceOrder6);



        $tag1 = new \tests\tag\Tag();
        $tag1->name= "Tag #1";
        $tag1->serviceOrders[]  = $serviceOrder6;
        $tag1->serviceOrders[]  = $serviceOrder5;
        $tagDAO->create($tag1);


        $tag2 = new \tests\tag\Tag();
        $tag2->name= "Tag #2";
        $tagDAO->create($tag2);

        $tag3 = new \tests\tag\Tag();
        $tag3->name= "Tag #3";
        $tag3->serviceOrders[] = $serviceOrder3;
        $tag3->serviceOrders[] = $serviceOrder1;
        $tagDAO->create($tag3);

        $tag4 = new \tests\tag\Tag();
        $tag4->serviceOrders[] = $serviceOrder4;
        $tag4->serviceOrders[] = $serviceOrder2;
        $tag4->name= "Tag #4";
        $tagDAO->create($tag4);



        $image1= new \tests\image\Image();
        $image1->name = 'cat.jpg';
        $image1->size = 10000;
        $image1->extension = 'jpg';
        $image1->tags[] = clone $tag1;
        $image1->tags[] = clone $tag4;
        $imageDAO->create($image1);

        $image2= new \tests\image\Image();
        $image2->name = 'dog.jpg';
        $image2->size = 10000;
        $image2->extension = 'jpg';
        $image2->tags[] = clone $tag2;
        $image2->tags[] = clone $tag3;
        $imageDAO->create($image2);


        $image3= new \tests\image\Image();
        $image3->name = 'bird.jpg';
        $image3->size = 10000;
        $image3->tags[] = clone $tag2;
        $image3->extension = 'jpg';
        $imageDAO->create($image3);

        $imageDAO->readAll();
        $imageDAO->populate("tags")->populate("serviceOrders");
        $items = $imageDAO->getItems();


        $checkTagsServiceOrders = function ($tags)
        {
         foreach ($tags as $tag)
         {
             switch ($tag->id)
             {
                 case 1:

                     $serviceOrders = array_map(function($el){return $el->id;},$tag->serviceOrders);
                     $this->assertCount(2,$serviceOrders);
                     $this->assertContains(6,$serviceOrders);
                     $this->assertContains(5,$serviceOrders);

                     break;

                 case 2:

                     $serviceOrders = array_map(function($el){return $el->id;},$tag->serviceOrders);
                     $this->assertCount(0,$serviceOrders);

                     break;
                 case 3:

                     $serviceOrders = array_map(function($el){return $el->id;},$tag->serviceOrders);
                     $this->assertCount(2,$serviceOrders);
                     $this->assertContains(3,$serviceOrders);
                     $this->assertContains(1,$serviceOrders);


                     break;
                 case 4:
                     $serviceOrders = array_map(function($el){return $el->id;},$tag->serviceOrders);
                     $this->assertCount(2,$serviceOrders);
                     $this->assertContains(4,$serviceOrders);
                     $this->assertContains(2,$serviceOrders);

                     break;
             }
         }
        };
        $checkTagsServiceOrders = $checkTagsServiceOrders->bindTo($this);




        foreach ($items as $item){
            switch ($item->id)
            {
                case 1:


                    $tags = array_map(function($el){return $el->id;},$item->tags);

                    $this->assertCount(2,$tags);
                    $this->assertContains(1,$tags);
                    $this->assertContains(4,$tags);

                    $checkTagsServiceOrders($item->tags);

                    break;

                case 2:


                    $tags = array_map(function($el){return $el->id;},$item->tags);
                    $this->assertCount(2,$tags);
                    $this->assertContains(2,$tags);
                    $this->assertContains(3,$tags);

                    $checkTagsServiceOrders($item->tags);


                    break;
                case 3:

                    $tags = array_map(function($el){return $el->id;},$item->tags);

                    $this->assertCount(1,$tags);
                    $this->assertContains(2,$tags);

                    $checkTagsServiceOrders($item->tags);

                    break;
            }
        }










    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     * @param $imageDAO \tests\image\ImageDAO
     */
    public function testPopulateManyToMany($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO,$imageDAO)
    {
        //Muchas ordenes de servicio pueden tener muchas etiquetas
        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "#1 Orden de servicio";
        $serviceOrderDAO->create($serviceOrder);


        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "#2 Orden de servicio";
        $serviceOrderDAO->create($serviceOrder);


        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "#3 Orden de servicio";
        $serviceOrderDAO->create($serviceOrder);

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = "#4 Orden de servicio";
        $serviceOrderDAO->create($serviceOrder);



        $tag = new \tests\tag\Tag();
        $tag->name = "#1 Tag";

        $image = new \tests\image\Image();
        $image->name = '1.jpg';
        $image->size = 42323443;
        $image->extension = 'jpg';
        $tag->images[] = $image;

        $image = new \tests\image\Image();
        $image->name = '2.png';
        $image->size = 5675675;
        $image->extension = 'png';
        $tag->images[] = $image;

        $image = new \tests\image\Image();
        $image->name = '3.svg';
        $image->size = 12313;
        $image->extension = 'svg';
        $tag->images[] = $image;


        $tagDAO->create($tag);

        $tag = new \tests\tag\Tag();
        $tag->name = "#2 Tag";
        $tagDAO->create($tag);

        $tag = new \tests\tag\Tag();
        $tag->name = "#3 Tag";
        $tagDAO->create($tag);

        $tag = new \tests\tag\Tag();
        $tag->name = "#4 Tag";

        $image = new \tests\image\Image();
        $image->name = '4.png';
        $image->size = 42323443;
        $image->extension = 'png';
        $tag->images[] = $image;

        $image = new \tests\image\Image();
        $image->name = '5.jpg';
        $image->size = 5675675;
        $image->extension = 'jpg';
        $tag->images[] = $image;

        $image = new \tests\image\Image();
        $image->name = '6.svg';
        $image->size = 12313;
        $image->extension = 'svg';
        $tag->images[] = $image;


        $tagDAO->create($tag);

        $tag = new \tests\tag\Tag();
        $tag->name = "#5 Tag";
        $tagDAO->create($tag);

        $tag = new \tests\tag\Tag();
        $tag->name = "#6 Tag";

        $image = new \tests\image\Image();
        $image->name = '10.png';
        $image->size = 42323443;
        $image->extension = 'png';
        $tag->images[] = $image;

        $image = new \tests\image\Image();
        $image->name = '11.jpg';
        $image->size = 5675675;
        $image->extension = 'jpg';
        $tag->images[] = $image;



        $tagDAO->create($tag);

        $tag = new \tests\tag\Tag();
        $tag->name = "#7 Tag";

        $image = new \tests\image\Image();
        $image->name = '7.png';
        $image->size = 42323443;
        $image->extension = 'png';
        $tag->images[] = $image;

        $image = new \tests\image\Image();
        $image->name = '8.jpg';
        $image->size = 5675675;
        $image->extension = 'jpg';
        $tag->images[] = $image;

        $image = new \tests\image\Image();
        $image->name = '9.svg';
        $image->size = 12313;
        $image->extension = 'svg';
        $tag->images[] = $image;


        $tagDAO->create($tag);

        $tag = new \tests\tag\Tag();
        $tag->name = "#8 Tag";
        $tagDAO->create($tag);


        $serviceOrderTag = new \tests\service_order_tag\ServiceOrderTag();
        $serviceOrderTag->service_order = 1;
        $serviceOrderTag->tag = 8;
        $serviceOrderTag->property = 'tags';
        $serviceOrderTagDAO->create($serviceOrderTag);

        $serviceOrderTag = new \tests\service_order_tag\ServiceOrderTag();
        $serviceOrderTag->service_order = 2;
        $serviceOrderTag->tag = 8;
        $serviceOrderTag->property = 'tags';
        $serviceOrderTagDAO->create($serviceOrderTag);

        $serviceOrderTag = new \tests\service_order_tag\ServiceOrderTag();
        $serviceOrderTag->service_order = 3;
        $serviceOrderTag->tag = 8;
        $serviceOrderTag->property = 'tags';
        $serviceOrderTagDAO->create($serviceOrderTag);


        $serviceOrderTag = new \tests\service_order_tag\ServiceOrderTag();
        $serviceOrderTag->service_order = 1;
        $serviceOrderTag->tag = 7;
        $serviceOrderTag->property = 'tags';
        $serviceOrderTagDAO->create($serviceOrderTag);

        $serviceOrderTag = new \tests\service_order_tag\ServiceOrderTag();
        $serviceOrderTag->service_order = 1;
        $serviceOrderTag->tag = 6;
        $serviceOrderTag->property = 'tags';
        $serviceOrderTagDAO->create($serviceOrderTag);

        $serviceOrderTag = new \tests\service_order_tag\ServiceOrderTag();
        $serviceOrderTag->service_order = 2;
        $serviceOrderTag->tag = 5;
        $serviceOrderTag->property = 'tags';
        $serviceOrderTagDAO->create($serviceOrderTag);

        $serviceOrderTag = new \tests\service_order_tag\ServiceOrderTag();
        $serviceOrderTag->service_order = 2;
        $serviceOrderTag->tag = 4;
        $serviceOrderTag->property = 'tags';
        $serviceOrderTagDAO->create($serviceOrderTag);

        $serviceOrderTag = new \tests\service_order_tag\ServiceOrderTag();
        $serviceOrderTag->service_order = 2;
        $serviceOrderTag->tag = 3;
        $serviceOrderTag->property = 'tags';
        $serviceOrderTagDAO->create($serviceOrderTag);

        $serviceOrderTag = new \tests\service_order_tag\ServiceOrderTag();
        $serviceOrderTag->service_order = 2;
        $serviceOrderTag->tag = 2;
        $serviceOrderTag->property = 'tags';
        $serviceOrderTagDAO->create($serviceOrderTag);

        $serviceOrderTag = new \tests\service_order_tag\ServiceOrderTag();
        $serviceOrderTag->service_order = 3;
        $serviceOrderTag->tag = 1;
        $serviceOrderTag->property = 'tags';
        $serviceOrderTagDAO->create($serviceOrderTag);

        $items = $serviceOrderDAO->readAll();


        $serviceOrderDAO->populate("tags",["order"=>["+id"]])->populate("images",["order"=>["+id"]]);

        $this->assertEquals($items[0]->tags[0]->id,6);
        $this->assertCount(2,$items[0]->tags[0]->images);
        $this->assertEquals("10.png",$items[0]->tags[0]->images[0]->name);
        $this->assertEquals("11.jpg",$items[0]->tags[0]->images[1]->name);

        $this->assertEquals($items[0]->tags[1]->id,7);
        $this->assertCount(3,$items[0]->tags[1]->images);
        $this->assertEquals("7.png",$items[0]->tags[1]->images[0]->name);
        $this->assertEquals("8.jpg",$items[0]->tags[1]->images[1]->name);
        $this->assertEquals("9.svg",$items[0]->tags[1]->images[2]->name);



        $this->assertEquals($items[0]->tags[2]->id,8);
        $this->assertCount(3,$items[0]->tags);


        $this->assertEquals($items[1]->tags[0]->id,2);
        $this->assertEquals($items[1]->tags[1]->id,3);
        $this->assertEquals($items[1]->tags[2]->id,4);
        $this->assertCount(3,$items[1]->tags[2]->images);
        $this->assertEquals("4.png",$items[1]->tags[2]->images[0]->name);
        $this->assertEquals("5.jpg",$items[1]->tags[2]->images[1]->name);
        $this->assertEquals("6.svg",$items[1]->tags[2]->images[2]->name);



        $this->assertEquals($items[1]->tags[3]->id,5);

        $this->assertEquals($items[1]->tags[4]->id,8);

        $this->assertCount(5,$items[1]->tags);

        $this->assertEquals($items[2]->tags[0]->id,1);
        $this->assertCount(3,$items[2]->tags[0]->images);
        $this->assertEquals("1.jpg",$items[2]->tags[0]->images[0]->name);
        $this->assertEquals("2.png",$items[2]->tags[0]->images[1]->name);
        $this->assertEquals("3.svg",$items[2]->tags[0]->images[2]->name);



        $this->assertEquals($items[2]->tags[1]->id,8);
        $this->assertCount(2,$items[2]->tags);



    }

  /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     * @param $imageDAO \tests\image\ImageDAO
     */
    public function testHookCreate($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO,$imageDAO)
    {
        $customer = new \tests\customer\Customer();
        $customer->name ="After Create";
        $customer->surname = "Wade";
        $customer->age = 44;
        $customer->zip_code = 4000;
        $customer->email = "a@a.com";

        $customerDAO->create($customer);

        $this->assertEquals($customer->name,"Changed on After Create");
        $this->assertEquals($customer->zip_code,3999);
    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     * @param $imageDAO \tests\image\ImageDAO
     */
    public function testHookRead($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO,$imageDAO)
    {
        $customer = new \tests\customer\Customer();
        $customer->name ="After Read";
        $customer->surname = "Wade";
        $customer->age = 44;
        $customer->zip_code = 4000;
        $customer->email = "a@a.com";
        $customerDAO->create($customer);

        $customer = new \tests\customer\Customer();
        $customer->name ="After Read";
        $customer->surname = "Bobby";
        $customer->age = 23;
        $customer->zip_code = 4000;
        $customer->email = "a@a.com";
        $customerDAO->create($customer);

        //After read
        $customerDAO->readAll();
        $items = $customerDAO->getItems();
        $this->assertEquals($items[0]->name,"Changed on After Read");
        $this->assertEquals($items[0]->surname,"Wade");

        $this->assertEquals($items[1]->name,"Changed on After Read");
        $this->assertEquals($items[1]->surname,"Bobby");

        $customer = $customerDAO->readById(2);
        $this->assertEquals($customer->name,"Changed on After Read");
        $this->assertEquals($customer->surname,"Bobby");

        //Before read
        $customerDAO->read(["where"=>[["prop"=>"replace_by_surname","value"=>"Bobby"]]]);
        $this->assertEquals($customerDAO->getItems()[0]->surname,"Bobby");


    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     * @param $imageDAO \tests\image\ImageDAO
     */
    public function testHookUpdate($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO,$imageDAO)
    {
        $customer = new \tests\customer\Customer();
        $customer->name ="After Update";
        $customer->surname = "Wade";
        $customer->age = 44;
        $customer->zip_code = 5000;
        $customer->email = "a@a.com";
        $customerDAO->create($customer);

        $customer->zip_code = 10000;
        $customerDAO->update($customer);

        $this->assertEquals($customer->zip_code,20000);
        $this->assertEquals($customer->name,"Changed on After Update");


    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     * @param $imageDAO \tests\image\ImageDAO
     */
    public function testHookDelete($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO,$imageDAO)
    {
        $customer = new \tests\customer\Customer();
        $customer->name ="Bob";
        $customer->surname = "Wade";
        $customer->age = 44;
        $customer->zip_code = 5000;
        $customer->email = "a@a.com";
        $customerDAO->create($customer);
        $customerDAO->deleteById($customer->id);

        $this->assertEquals(file_get_contents("created_on_after_delete_by_id.txt"),"ok 1");
        $this->assertEquals(file_get_contents("created_on_before_delete_by_id.txt"),"ok 2");

    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     * @param $imageDAO \tests\image\ImageDAO
     */
    public function testHookOnFetch($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO,$imageDAO)
    {
        for($i=0;$i<3;$i++)
        {
            $customer = new \tests\customer\Customer();
            $customer->name ="On fetch";
            $customer->surname = "Wade";
            $customer->age = 44;
            $customer->zip_code = 5000;
            $customer->email = "a@a.com";
            $customerDAO->create($customer);
        }
        $customerDAO->readAll();

        foreach ($customerDAO->getItems() as $k => $item)
        {
            $this->assertEquals($item->age,$k);
        }



    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     * @param $imageDAO \tests\image\ImageDAO
     */
    public function testUpdatedAtCreatedAt($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO,$imageDAO)
    {
        $customer = new \tests\customer\Customer();
        $customer->name ="On fetch";
        $customer->surname = "Wade";
        $customer->age = 44;
        $customer->zip_code = 5000;
        $customer->email = "a@a.com";
        $customerDAO->create($customer);

        $date = new DateTime($customer->created_at);
        $now = new DateTime();
        $this->assertEquals($date->format("d-m-Y H:i"),$now->format("d-m-Y H:i"));
        $this->assertEquals($customer->updated_at,null);

        $customer->age = 45;
        $customerDAO->update($customer);
        $customer = $customerDAO->readById(1);
        $date = new DateTime($customer->updated_at);

        $this->assertEquals($date->format("d-m-Y H:i"),$now->format("d-m-Y H:i"));


    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testGetRelationshipIdInRelatedItem($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = 'Demo 123';

        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 3";
        $tagDAO->create($tag);
        $serviceOrder->tags[]= $tag;

        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 2";
        $tagDAO->create($tag);
        $serviceOrder->tags[]= $tag;

        $serviceOrderDAO->create($serviceOrder);
        $serviceOrderDAO->readAll();
        $serviceOrderDAO->populate("tags");

        foreach ($serviceOrderDAO->getItems()[0]->tags as $tag)
        {
            if($tag->name == "Tag 3")
            {
                $this->assertEquals($tag->relation_data_id,1);
            }
            elseif($tag->name == "Tag 2")
            {
                $this->assertEquals($tag->relation_data_id,2);
            }


        }

    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testRelateSameTypeObjectsTwoDifferentProperties($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {

        $serviceOrder2 = new \tests\service_order\ServiceOrder();
        $serviceOrder2->description = 'Demo 456';

        $serviceOrder = new \tests\service_order\ServiceOrder();
        $serviceOrder->description = 'Demo 123';

        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 3";
        $tagDAO->create($tag);
        $serviceOrder->tags[] =clone $tag;
        $serviceOrder2->tags[] =clone $tag;
        $serviceOrder->tags2[]=clone $tag;

        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 2";
        $tagDAO->create($tag);
        $serviceOrder->tags[]=clone $tag;
        $serviceOrder->tags2[]=clone $tag;

        $tag = new \tests\tag\Tag();
        $tag->name = "Tag 5";
        $tagDAO->create($tag);
        $serviceOrder->tags[] = clone $tag;
        $serviceOrder2->tags2[] = clone $tag;


        $serviceOrderDAO->create($serviceOrder);
        $serviceOrderDAO->create($serviceOrder2);


        $serviceOrderDAO->read(["where"=>[["prop"=>"id","value"=>"1"]],"order"=>["+id"]]);
        $items = $serviceOrderDAO->getItems();
        $serviceOrderDAO->populate("tags",["order"=>["+id"]]);
        $serviceOrderDAO->populate("tags2",["order"=>["+id"]]);

        $this->assertEquals("Tag 3,Tag 2,Tag 5",implode(",",array_map(function($el){ return $el->name; },$items[0]->tags)));
        $this->assertEquals("1,2,3",implode(",",array_map(function($el){ return $el->id; },$items[0]->tags)));
        $this->assertEquals("Tag 3,Tag 2",implode(",",array_map(function($el){ return $el->name; },$items[0]->tags2)));
        $this->assertEquals("1,2",implode(",",array_map(function($el){ return $el->id; },$items[0]->tags2)));


        $serviceOrderDAO->read(["where"=>[["prop"=>"id","value"=>"2"]]]);
        $items = $serviceOrderDAO->getItems();
        $serviceOrderDAO->populate("tags");
        $serviceOrderDAO->populate("tags2");


        $this->assertEquals("Tag 3",implode(",",array_map(function($el){ return $el->name; },$items[0]->tags)));
        $this->assertEquals("1",implode(",",array_map(function($el){ return $el->id; },$items[0]->tags)));
        $this->assertEquals("Tag 5",implode(",",array_map(function($el){ return $el->name; },$items[0]->tags2)));
        $this->assertEquals("3",implode(",",array_map(function($el){ return $el->id; },$items[0]->tags2)));


        $tagDAO->read(["order"=>["+id"]]);
        $tagDAO->populate("serviceOrders",["order"=>["+id"]]);
        $items = $tagDAO->getItems();

        foreach ($items as $item)
        {
            switch ($item->id)
            {
                case 1:
                    $this->assertEquals("Demo 123,Demo 456",implode(",",array_map(function($el){ return $el->description; },$item->serviceOrders)));
                    break;
                case 2:
                    $this->assertEquals("Demo 123",implode(",",array_map(function($el){ return $el->description; },$item->serviceOrders)));
                    break;
                case 3:
                    $this->assertEquals("Demo 123",implode(",",array_map(function($el){ return $el->description; },$item->serviceOrders)));
                    break;
            }
        }


    }

    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     */
    public function testOrder($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO)
    {

        $customer = new \tests\customer\Customer();
        $customer->name = "Alberto";
        $customer->surname ="Zapata";
        $customer->zip_code = 2000;
        $customer->age = 99;
        $customer->email = "a@a.com";
        $customerDAO->create($customer);

        $customer = new \tests\customer\Customer();
        $customer->name = "Yolanda";
        $customer->surname ="Mota";
        $customer->zip_code = 500;
        $customer->age = 70;
        $customer->email = "a@a.com";
        $customerDAO->create($customer);

        $customer = new \tests\customer\Customer();
        $customer->name = "Ramon";
        $customer->surname ="Valdez";
        $customer->zip_code = 1000;
        $customer->age = 88;
        $customer->email = "a@a.com";
        $customerDAO->create($customer);


        $customer = new \tests\customer\Customer();
        $customer->name = "Ramon";
        $customer->surname ="Abila";
        $customer->zip_code = 1000;
        $customer->age = 88;
        $customer->email = "a@a.com";
        $customerDAO->create($customer);

        $customerDAO->read(["order"=>["id"]]);
        $items = $customerDAO->getItems();

        $ids = array_map(function($el){ return intval($el->id); },$items);
        $this->assertEquals([4,3,2,1],$ids);


        $customerDAO->read(["order"=>["+name","surname"]]);
        $items = $customerDAO->getItems();
        $ids = array_map(function($el){ return $el->id; },$items);
        $this->assertEquals([1,3,4,2],$ids);


        $customerDAO->read(["order"=>["surname"]]);
        $items = $customerDAO->getItems();
        $ids = array_map(function($el){ return $el->id; },$items);
        $this->assertEquals([1,3,2,4],$ids);




    }


    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     * @param $imageDAO \tests\image\ImageDAO
     */
    public function testQueryPopulate($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO,$imageDAO)
    {


        $customer1 = new \tests\customer\Customer();
        $customer1->name ="Name";
        $customer1->surname = "Surname";
        $customer1->email = "email@email.com";
        $customer1->zip_code = 3000;
        $customer1->age = 20;
        $customerDAO->create($customer1);

        $serviceOrder1 = new \tests\service_order\ServiceOrder();
        $serviceOrder1->description = 'Service order #1';
        $serviceOrder1->customer = $customer1;
        $serviceOrderDAO->create($serviceOrder1);

        $serviceOrder2 = new \tests\service_order\ServiceOrder();
        $serviceOrder2->description = 'Service order #2';
        $serviceOrder2->customer = $customer1;
        $serviceOrderDAO->create($serviceOrder2);

        $serviceOrder3 = new \tests\service_order\ServiceOrder();
        $serviceOrder3->description = 'Service order #3';
        $serviceOrder3->customer = $customer1;
        $serviceOrderDAO->create($serviceOrder3);

        $item = $customerDAO->readById(1);
        $customerDAO->populate("serviceOrders",["where"=>[["prop"=>"description","value"=>"%order%","operator"=>"LIKE"],["prop"=>"id","value"=>"2"]]]);


        $this->assertCount(1,$item->serviceOrders);
        $this->assertEquals($item->serviceOrders[0]->id,2);
        $this->assertEquals($item->serviceOrders[0]->description,'Service order #2');

        $item = $customerDAO->readById(1);
        $customerDAO->populate("serviceOrders");

        $this->assertCount(3,$item->serviceOrders);


        $tag1 = new \tests\tag\Tag();
        $tag1->name ="Tag #1";
        $tagDAO->create($tag1);

        $tag2 = new \tests\tag\Tag();
        $tag2->name ="Tag #2";
        $tagDAO->create($tag2);

        $tag3 = new \tests\tag\Tag();
        $tag3->name ="Tag #3";
        $tagDAO->create($tag3);

        $image = new \tests\image\Image();
        $image->name = '9.svg';
        $image->size = 12313;
        $image->extension = 'svg';
        $image->tags[] =$tag1;
        $image->tags[] =$tag2;
        $image->tags[] =$tag3;
        $imageDAO->create($image);

        $item = $imageDAO->readById(1);
        $imageDAO->populate("tags",["order"=>["+id"],"where"=>[["prop"=>"id","value"=>1,"operator"=>">"]]]);

        $this->assertCount(2,$item->tags);
        $this->assertEquals($item->tags[0]->id,2);
        $this->assertEquals($item->tags[0]->name,'Tag #2');
        $this->assertEquals($item->tags[1]->id,3);
        $this->assertEquals($item->tags[1]->name,'Tag #3');

        $item = $imageDAO->readById(1);
        $imageDAO->populate("tags");
        $this->assertCount(3,$item->tags);


    }


    /**
     * @dataProvider daoProvider
     * @param $customerDAO \tests\customer\CustomerDAO
     * @param $serviceOrderDAO \tests\service_order\ServiceOrderDAO
     * @param $tagDAO \tests\tag\TagDAO
     * @param $serviceOrderTagDAO \tests\service_order_tag\ServiceOrderTagDAO
     * @param $imageDAO \tests\image\ImageDAO
     */
    public function testPaginationPopulate($customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO,$imageDAO)
    {

        $customer1 = new \tests\customer\Customer();
        $customer1->name ="Name";
        $customer1->surname = "Surname";
        $customer1->email = "email@email.com";
        $customer1->zip_code = 3000;
        $customer1->age = 20;

        for($i=0;$i<60;$i++)
        {
            $serviceOrder1 = new \tests\service_order\ServiceOrder();
            $serviceOrder1->description= "Description #".($i + 1);
            $customer1->serviceOrders[] = $serviceOrder1;
        }
        $customerDAO->create($customer1);

        foreach (range(1,3) as $page)
        {
            $items = $customerDAO->readAll();
            $customerDAO->populate("serviceOrders",["order"=>["+id"],"pagination"=>["page"=>$page]]);
            $this->assertCount(20,$items[0]->serviceOrders);
            for ($i=0;$i<20;$i++)
            {

                $this->assertEquals("Description #".( ($i + 1) + (($page - 1) * 20)),$items[0]->serviceOrders[$i]->description);

            }
        }

        $image = new \tests\image\Image();
        $image->name = '9.svg';
        $image->size = 12313;
        $image->extension = 'svg';

        for ($i=1;$i<=6;$i++)
        {
            $tag = new \tests\tag\Tag();
            $tag->name = "Tag #{$i}";
            $image->tags[] = $tag;
        }

        $imageDAO->create($image);
       foreach (range(1,3) as $page)
        {
            $items = $imageDAO->readAll();
            $imageDAO->populate("tags",["order"=>["+id"],"pagination"=>["page"=>$page]]);


            $this->assertCount(2,$items[0]->tags);

            for ($i=0;$i<2;$i++)
            {


                $this->assertEquals("Tag #".( ($i + 1) + (($page - 1) * 2)),$items[0]->tags[$i]->name);

            }
        }

 ;

    }


    //TODO: al paginar un populate,  al parametro pagination usarlo en la tabla link (EN caso de q sea muchos a muchos) para que pagine correctamente los resultados


    public function daoProvider()
    {
        $connection = new \db\SqlConnection("mysql:host=localhost;dbname=tachyon_test", "root", "powersoccergbi");
        $customerDAO = new \tests\customer\CustomerDAO($connection);
        $serviceOrderDAO = new \tests\service_order\ServiceOrderDAO($connection);
        $tagDAO =  new \tests\tag\TagDAO($connection);
        $serviceOrderTagDAO = new \tests\service_order_tag\ServiceOrderTagDAO($connection);
        $imageDAO = new \tests\image\ImageDAO($connection);

        return [[$customerDAO,$serviceOrderDAO,$tagDAO,$serviceOrderTagDAO,$imageDAO]];
    }


}