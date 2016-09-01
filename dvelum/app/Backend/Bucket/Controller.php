<?php 
class Backend_Bucket_Controller extends Backend_Controller_Crud{
 protected $_listFields = ["shard","id"];
 protected $_listLinks = ["shard"];
 protected $_canViewObjects = ["shard"];
} 