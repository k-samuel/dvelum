<?php
class Db_Manager implements Db_Manager_Interface
{
    protected $_dbConnections = array();
    protected $_dbConfigs = array();

    /**
     * @var Config_Abstract
     */
    protected $_appConfig;

    /**
     * @param Config_Abstract $appConfig - Application config (main)
     */
    public function __construct(Config_Abstract $appConfig)
    {
        $this->_appConfig = $appConfig;
    }

    /**
     * Get Database connection
     * @param string $name
     * @param integer $shard
     * @throws Exception
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDbConnection($name, $shard = false)
    {
        $connectionName = $name . intval($shard);
        $workMode = $this->_appConfig->get('development');
        if(!isset($this->_dbConnections[$workMode][$connectionName]))
        {
            $cfg = $this->getDbConfig($name);
            $cfg = $cfg->__toArray();

            if($shard){
                $sharding = Db_Sharding::factory();
                $shardInfo = $sharding->getShardInfo($shard);
                if($shardInfo){
                    $cfg['host'] = $shardInfo['dbHost'];
                }
            }

            $db = Zend_Db::factory($cfg['adapter'] ,  $cfg);
            /*
             * Enable Db profiler for development mode Attention! Db Profiler causes
             * memory leaks at background tasks. (Dev mode)
             */
            if($this->_appConfig->get('development')){
                $db->getProfiler()->setEnabled(true);
                Debug::addDbProfiler($db->getProfiler());
            }
            $this->_dbConnections[$workMode][$connectionName] = $db;
        }
        return $this->_dbConnections[$workMode][$connectionName];
    }
    /**
     * Get Db Connection config
     * @param string $name
     * @throws Exception
     * @return Config_Abstract
     */
    public function getDbConfig($name)
    {
        $workMode = $this->_appConfig->get('development');

        if($workMode == Application::MODE_INSTALL)
            $workMode = Application::MODE_DEVELOPMENT;

        if(!isset($this->_dbConfigs[$workMode][$name]))
        {
            $dbConfigPaths = $this->_appConfig->get('db_configs');

            if(!isset($dbConfigPaths[$workMode]))
                throw new Exception('Invalid application work mode ' . $workMode);

            $this->_dbConfigs[$workMode][$name] = Config::storage()->get($dbConfigPaths[$workMode]['dir'].$name.'.php' , true , false);
        }

        return $this->_dbConfigs[$workMode][$name];
    }
}