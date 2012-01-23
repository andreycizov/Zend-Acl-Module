<?php
require_once 'Zend/View/Helper/Abstract.php';

class Acl_View_Helper_AllowedModule extends Zend_View_Helper_Abstract {
  
  public function allowedmodule($m)
  {
      $acl = Zend_Registry::get('acl');
      return $acl->isUserAllowed($m, 'index', 'index');
  }
}
