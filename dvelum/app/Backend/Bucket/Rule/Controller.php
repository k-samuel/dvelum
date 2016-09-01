<?php
class Backend_Bucket_Rule_Controller extends Backend_Controller_Crud{
    protected $_listFields = ["object","bucket","id"];
    protected $_listLinks = ["bucket"];
    protected $_canViewObjects = ["bucket"];


    /**
     * Get list of sharding objects
     */
    public function objectsListAction()
    {
        $manager = new Db_Object_Manager();
        $list = $manager->getRegisteredObjects();
        $data = [];

        foreach ($list as $name)
        {
            $cfg = Db_Object_Config::getInstance($name);
            if($cfg->hasSharding()){
                $data[] = ['id'=>$name,'title'=>$cfg->getTitle().' ('.$name.')'];
            }
        }
        Response::jsonSuccess($data);
    }
} 