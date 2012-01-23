<?php

class Acl_Controller_Plugin_AclDb extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
      /*
       * TODO: caching should be here
       * */
      
      /*
       * TODO: check if the user is still enabled
       * */
      $identity = Zend_Auth::getInstance();
      if($identity->hasIdentity() && $identity->getIdentity()) {
        Zend_Registry::set('user', $identity->getIdentity());
      }
      else {
        $user = new stdClass();
        $user->role_id = 0;
        Zend_Registry::set('user', $user);
      }
      $acl = new Acl_Db();
      Zend_Registry::set('acl', $acl);
      
      if(!$this->_actionExists($request)) {
        return;
      }
      
      if(!$acl->isUserAllowed(
      $request->getModuleName(),
      $request->getControllerName(), $request->getActionName() ))
      {
          $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
          $redirector->gotoSimple('index', 'login', 'default')
               ->redirectAndExit();
          throw new Zend_Acl_Exception('You are not authorized to view this page', 403);
      }
    }
    
     /** 
     * Return whether a given request (module-controller-action) exists 
     * 
     * @param Zend_Controller_Request_Abstract $request Request to check 
     * @return boolean Whether the action exists 
     */ 
      private function _actionExists($request) { 
          $dispatcher = Zend_Controller_Front::getInstance()->getDispatcher(); 
          
          // Check controller 
          if (!$dispatcher->isDispatchable($request)) { 
              return false; 
          } 
          
          // Check action 
          $controllerClassName = $dispatcher->formatControllerName( $request->getControllerName() ); 
          $controllerClassFile = str_replace('_', '/', $controllerClassName) . '.php'; 
          if ($request->getModuleName() != $dispatcher->getDefaultModule()) { 
            $controllerClassName = ucfirst($request->getModuleName()) . '_' . $controllerClassName; 
          } 
          try { 
            require_once 'Zend/Loader.php'; 
            Zend_Loader::loadFile($controllerClassFile, $dispatcher->getControllerDirectory($request->getModuleName()), true); 
            $actionMethodName = $dispatcher->formatActionName($request->getActionName()); 
            if (@in_array($actionMethodName, get_class_methods($controllerClassName))) { 
                    return true; 
            } 
            return false; 
          } catch(Exception $e) { 
              return false; 
          } 
      } 
}