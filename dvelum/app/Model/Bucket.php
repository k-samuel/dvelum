<?php
class Model_Bucket extends Model
{
    public function getTitle(Db_Object $object)
    {
        $shard = new Db_Object($object->getLinkedObject('shard'),$object->get('shard'));
        return $object->getId().' ('.$shard->getTitle().')';
    }
}