<?php
require_once 'Zend/View/Helper/Abstract.php';

class Acl_View_Helper_AllowedFull extends Zend_View_Helper_Abstract {
  
  public function allowedfull()
  {
      $acl = Zend_Registry::get('acl');
      $default = array( 
        'default',
        'index',
        'index'
        ); 
      $args = func_get_args();  
      foreach($default as $i=>$value) {
        if(!array_key_exists($i, $args))
          $args[$i] = $value;
      }
      return call_user_func_array ( array($acl, 'isUserAllowed'), $args);
  }
}
