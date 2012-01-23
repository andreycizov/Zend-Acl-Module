<?php
require_once 'Zend/View/Helper/Abstract.php';

class Acl_View_Helper_LinkIfAllowed extends Zend_View_Helper_Abstract {
  
  public function linkIfAllowed($title, $action, $controller=null, $module=null, $args=array(), $classes="")
  {
      $request = Zend_Controller_Front::getInstance()->getRequest();
      if(!$controller)
        $controller = $request->getControllerName();
      
      if(!$module)
        $module = $request->getModuleName();
      
      $acl = Zend_Registry::get('acl');
      if($acl->isUserAllowed(
        $module,
        $controller, 
        $action)) {
      	
      	$arg = array_merge($args, 
      		array('module'=>$module, 'controller'=>$controller, 'action'=>$action));
      	return "<a href=" . $this->view->url($arg) . " class=" . $classes . ">" . $title . "</a>";
      } else {
      	return '';
      }
  }
}
