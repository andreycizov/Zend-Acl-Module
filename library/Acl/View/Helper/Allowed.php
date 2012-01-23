<?php
require_once 'Zend/View/Helper/Abstract.php';

class Acl_View_Helper_Allowed extends Zend_View_Helper_Abstract {
  
  public function allowed($action, $controller=null, $module=null)
  {
      $request = Zend_Controller_Front::getInstance()->getRequest();
      if(!$controller)
        $controller = $request->getControllerName();
      
      if(!$module)
        $module = $request->getModuleName();
      
      $acl = Zend_Registry::get('acl');
      return $acl->isUserAllowed(
        $module,
        $controller, 
        $action);
  }
}
