<?php

namespace zyzo\MeteorDDP;


class MongoAdapter {

    /**
     * @var \MongoDB
     */
    private $mongoDB;

    /**
     * @var \Threaded
     */
    private $sharedInfo;


    public function __construct() {
        $this->sharedInfo = new \Threaded();
        $this->sharedInfo['initialized'] = true;
    }

    public function connect($server, $options, $db) {
            $this->sharedInfo['server'] = $server;
            $this->sharedInfo['options'] = $options;
            $this->sharedInfo['db'] = $db;
            $this->sharedInfo['initialized'] = true;
        try {
            // test connection
            $this->getMongoDB();
        } catch (\MongoException $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public function insertOrUpdate($collection, $id, $fields)
    {
        $mongoDB = $this->getMongoDB();
        $collection = $mongoDB->$collection;
        $collection->update(
            array("_id" => $id),
            $fields !== null ? $fields : array(),
            array("upsert" => true)
        );
    }

    public function update($collection, $id, $fields, $cleared)
    {
        $mongoDB = $this->getMongoDB();
        $collection = $mongoDB->$collection;
        $collection->update(
            array("_id" => $id),
            $fields !== null ? $fields : array()
        );
        if ($cleared !== null && count($cleared) !== 0) {
            $fmtCleared = array();
            foreach($cleared as $x) {
                $fmtCleared[$x] = "";
            }
            $collection->update(
                array("_id" => $id),
                array('$unset' => $fmtCleared)
            );
        }
    }


    public function remove($collection, $id)
    {
        $mongoDB = $this->getMongoDB();
        $collection = $mongoDB->$collection;
        $collection->remove(array("_id" => $id));
    }

    private function getMongoDB()
    {
        if ($this->mongoDB === null) {
            if ($this->sharedInfo['initialized'] !== true) {
                throw new \Exception("Connection to database unitialized", MongoAdapter::UNITIALIZED_EXCEPTION);
            } else {
                $mongoClient = new \MongoClient($this->sharedInfo['server'], $this->sharedInfo['options']);
                $db = $this->sharedInfo['db'];
                $this->mongoDB = $mongoClient->$db;
            }
        }
        return $this->mongoDB;
    }

}