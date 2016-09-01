<?php
class Sharding
{
    static protected $instance = false;
    static protected $cache;

    protected $config;
    protected $buckets = [];
    protected $shards = [];

    /**
     * @var Model $shardModel
     */
    protected $shardModel;
    /**
     * @var Model $bucketModel
     */
    protected $bucketModel;

    /**
     * Factory method
     * @return Sharding
     */
    static public function factory()
    {
        if(!static::$instance){
            static::$instance = new static();
        }
        return static::$instance;
    }

    protected function __construct()
    {
        $this->config = Config::storage()->get('sharding.php');
        $this->shardModel = Model::factory($this->config->get('shard_object'));

        $list = $this->shardModel->getList();
        if(!empty($list)){
            $this->shards = Utils::rekey($this->shardModel->getPrimaryKey(),$list);
        }

        $this->bucketModel = Model::factory($this->config->get('bucket_object'));
        $list = $this->bucketModel->getList();
        if(!empty($list)){
            $this->buckets = Utils::rekey($this->bucketModel->getPrimaryKey(),$list);
        }
    }

    /**
     * Get bucket field name
     * @return string
     */
    public function getBucketField()
    {
        return $this->config->get('bucket_field');
    }
    /**
     * Get object bucket by id
     * @param $objectName
     * @param $objectId
     * @return integer|bool bucket_id of false
     */
    public function getBucket($objectName, $objectId)
    {
        $idModel = Model::factory(Db_Object_Config::getInstance($objectName)->getIdObject());
        $item = $idModel->getItem($objectId);

        $bucketField = $this->config->get('bucket_field');
        if(!empty($item) && isset($item[$bucketField])){
            return $item[$bucketField];
        }

        return false;
    }

    /**
     * Get bucket for new object (Resharding by switching shard for new records)
     * @param $objectName
     * @throws Exception
     * @return integer
     */
    public function getBucketForNewObject($objectName)
    {
        $bucketField = $this->config->get('bucket_field');
        $ruleObject = $this->config->get('bucket_rule_object');
        $ruleModel = Model::factory($ruleObject);
        $bucketModel = Model::factory($this->config->get('bucket_object'));

        $list = $ruleModel->getList(false,['object'=>$objectName],[$bucketField]);
        $acceptedBuckets = [];

        foreach ($list as $k=>$v){
            $bId = $v[$bucketField];
            if(isset($this->buckets[$bId]) && $this->buckets[$bId]['accept_new']){
                $acceptedBuckets[] = $bId;
            }
        }

        if(empty($acceptedBuckets)){
            foreach ($this->buckets as $bid=>$info){
                if($info['accept_new']){
                    $acceptedBuckets[] = $bid;
                }
            }
        }

        if(empty($acceptedBuckets)){
            throw new Exception('No free buckets');
        }

        shuffle($acceptedBuckets);
        return array_shift($acceptedBuckets);
    }

    /**
     * Reserve object id, add to routing table
     * @param $objectName
     * @param $bucket
     * @return bool|string
     */
    public function reserveIndex($objectName,$bucket)
    {
        $objectConfig = Db_Object_Config::getInstance($objectName);
        $idObject = $objectConfig->getIdObject();
        $model = Model::factory($idObject);
        $bucketField = $this->config->get('bucket_field');

        $db = $model->getDbConnection();

        try{
            $db->beginTransaction();
            $db->insert(
                $model->table(),
                [
                    $bucketField => $bucket
                ]
            );
            $id = $db->lastInsertId($model->table(),$objectConfig->getPrimaryKey());
            $db->commit();
            return $id;
        }catch (Exception $e){
            $db->rollBack();
            $model->logError('Sharding::reserveIndex '.$e->getMessage());
            return false;
        }
    }

    /**
     * Delete reserved index
     * @param $objectName
     * @param $id
     * @return void
     */
    public function deleteReservedIndex($objectName,$id)
    {
        $objectConfig = Db_Object_Config::getInstance($objectName);
        $idObject = $objectConfig->getIdObject();
        $model = Model::factory($idObject);
        $model->remove($id);
    }

    /**
     * Get shard_id by bucket
     * @param $bucketId
     * @return integer | boolean
     */
    public function getBucketShard($bucketId)
    {
        if(!isset($this->buckets[$bucketId])){
            $bucketObject = $this->config->get('bucket_object');
            $bucketModel = Model::factory($bucketObject);
            $info = $bucketModel->getCachedItem($bucketId);
            if(empty($info)){
                return false;
            }
            $this->buckets[$bucketId] = $info;
        }
        return $this->buckets[$bucketId]['shard'];
    }

    /**
     * Get bucket info
     * @param $id
     * @return array |bool
     */
    public function getBucketInfo($id)
    {
        if(!isset($this->buckets[$id])){
            $bucketObject = $this->config->get('bucket_object');
            $bucketModel = Model::factory($bucketObject);
            $info = $bucketModel->getCachedItem($id);
            if(empty($info)){
                return false;
            }
            $this->buckets[$id] = $info;
        }
        return $this->buckets[$id];
    }

    /**
     * Get shard info by id
     * @param $id
     * @return array|bool
     */
    public function getShardInfo($id)
    {
        if(isset($this->shards[$id])){
            return $this->shards[$id];
        }else{
            return false;
        }
    }
}