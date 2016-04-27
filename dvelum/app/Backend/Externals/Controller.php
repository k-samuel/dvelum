<?php
class Backend_Externals_Controller extends Backend_Controller
{
    /**
     * @var Externals_Manager
     */
    protected $externalsManager;

    public function __construct()
    {
        parent::__construct();
        $externalsCfg = $this->_configMain->get('externals');
        if(!$externalsCfg['enabled']){
            if(Request::isAjax()){
                Response::jsonError($this->_lang->get('MODULE_DISABLED'));
            }else{
                Response::put($this->_lang->get('MODULE_DISABLED'));
                exit();
            }
        }

        $this->externalsManager =  Externals_Manager::factory();
    }

    /**
     * Get list of available external modules
     */
    public function listAction()
    {
        $result = [];

        $this->externalsManager->scan();

        if($this->externalsManager->hasModules()){
            $result = $this->externalsManager->getModules();
        }

        foreach($result as $k=>&$v) {
            unset($v['autoloader']);
        }unset($v);

        Response::jsonSuccess($result);
    }

    /**
     * Reinstall external module
     */
    public function reinstallAction()
    {
        $this->_checkCanEdit();

        $id = Request::post('id', Filter::FILTER_STRING, false);

        if(!$this->externalsManager->moduleExists($id)){
            Response::jsonError($this->_lang->get('WRONG_REQUEST'));
        }

        if(!$this->externalsManager->install($id , true)) {
            $errors = $this->externalsManager->getErrors();
            Response::jsonError($this->_lang->get('CANT_EXEC').' '.implode(', ', $errors));
        }

        Response::jsonSuccess();
    }

    public function postInstallAction()
    {
        $this->_checkCanEdit();

        $id = Request::post('id', Filter::FILTER_STRING, false);

        if(!$this->externalsManager->moduleExists($id)){
            Response::jsonError($this->_lang->get('WRONG_REQUEST'));
        }

        if(!$this->externalsManager->postInstall($id , true)) {
            $errors = $this->externalsManager->getErrors();
            Response::jsonError($this->_lang->get('CANT_EXEC').' '.implode(', ', $errors));
        }
    }

    /**
     * Enable external module
     */
    public function enableAction()
    {
        $this->_checkCanEdit();

        $id = Request::post('id', Filter::FILTER_STRING, false);

        if(!$this->externalsManager->moduleExists($id)){
            Response::jsonError($this->_lang->get('WRONG_REQUEST'));
        }

        if(!$this->externalsManager->setEnabled($id , true)){
            $errors = $this->externalsManager->getErrors();
            Response::jsonError($this->_lang->get('CANT_EXEC').' '.implode(', ', $errors));
        }

        Response::jsonSuccess();
    }

    /**
     * Disable external module
     */
    public function disableAction()
    {
        $this->_checkCanEdit();

        $id = Request::post('id', Filter::FILTER_STRING, false);

        if(!$this->externalsManager->moduleExists($id)){
            Response::jsonError($this->_lang->get('WRONG_REQUEST'));
        }

        if(!$this->externalsManager->setEnabled($id , false)){
            $errors = $this->externalsManager->getErrors();
            Response::jsonError($this->_lang->get('CANT_EXEC').' '.implode(', ', $errors));
        }
        Response::jsonSuccess();
    }

    /**
     * Uninstall external module
     */
    public function deleteAction()
    {
        $this->_checkCanDelete();

        $id = Request::post('id', Filter::FILTER_STRING, false);

        if(!$this->externalsManager->uninstall($id)){
            $errors = $this->externalsManager->getErrors();
            Response::jsonError($this->_lang->get('CANT_EXEC').' '.implode(', ', $errors));
        }
        Response::jsonSuccess();
    }

    /**
     * Rebuild class map
     */
    public function buildMapAction()
    {
        $this->_checkCanEdit();

        $mapBuilder = new Classmap($this->_configMain);
        $mapBuilder->update();

        if(!$mapBuilder->save()){
            Response::jsonError($this->_lang->get('CANT_EXEC').' Build Map');
        }

        Response::jsonSuccess();
    }

}