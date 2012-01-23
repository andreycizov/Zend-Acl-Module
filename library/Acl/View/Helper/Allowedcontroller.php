<?php
require_once 'Zend/View/Helper/Abstract.php';

class Acl_View_Helper_AllowedController extends Zend_View_Helper_Abstract {
  
  public function allowedcontroller($controller)
  {
      $request = Zend_Controller_Front::getInstance()->getRequest();
      $acl = Zend_Registry::get('acl');
      return $acl->isUserAllowed(
        $request->getModuleName(),
        $controller,
        'index');
  }
}
