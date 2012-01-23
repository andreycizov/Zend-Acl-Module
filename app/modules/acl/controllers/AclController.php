<?php
/*
 * Index controller for ACL permissions
 * */

class Acl_AclController extends Zend_Controller_Action
{
    var $_acl_actions_desc = array(
      'index'       =>  'List roles',
      'flushCache'  =>  'Flush Role cache',
      'edit'        =>  'Edit role access'
      );
      
    private $_tbl_res = null;
    
    // @TODO: do not allow to delete the guest role
    
    public function flushCacheAction() {
      $acl = Zend_Registry::get('acl');
      $tbl_res = $acl->getResourceTable();
      $tbl_res->delete('1 = 1');
      
      foreach($this->_getResources() as $module=>$controllers) {
        foreach($controllers as $controller=>$actions) {
          foreach($actions as $action=>$description) {
            $id = Acl_Db::buildID($module, $controller, $action);
            $row = $tbl_res->createRow(array(
                'id'=>$id,
                'description'=>$description));
            $row->save();
          }
        }
      }
      $acl->flushRoles();
      $acl->flushRules();
      $acl->flushResources();
      $this->_helper->FlashMessenger(array('info'=>'Resource tree DB cache cleared'));
      $this->_helper->redirector->goToSimple('index');
    }
    
    public function deleteAction() {
      
    }
    
    public function indexAction(){
      $roles = Zend_Registry::get('acl')->getRoleTable();
      $this->view->entities = $roles->fetchAll($roles->select()->order('role_name'));
    }
    
    public function editAction() {
      $id = $this->getRequest()->getParam('id');
      $v = new Zend_Validate_Int();
      if(!$v->isValid($id)) {
        throw new Zend_Exception('Could not find this id');
      }
      $id = (int) $id;
      $roles = Zend_Registry::get('acl')->getRoleTable();
      
      $role = $roles->fetchRow('id = ' . $id);
      $form = $this->_createAclForm($role);
      $form->setAction($this->_helper->url->direct('update', null,null,array('id'=>$id)));
      /*
       * Populate the form from array here
       * */  
        
      $this->view->form = $form;
    }

    public function updateAction() {
      $id = $this->getRequest()->getParam('id');
      $v = new Zend_Validate_Int();
      if(!$v->isValid($id)) {
        throw new Zend_Exception('Could not find this id');
      }
      $id = (int) $id;
      
      $roles = Zend_Registry::get('acl')->getRoleTable();
      
      $role = $roles->fetchRow('id = ' . $id);
      $form = $this->_createAclForm($role);
      
      if(!$form->isValid($_POST))
        throw new Zend_Exception('Something is wrong with your form, sorry');
      
      $form->populate($_POST);
      
      $role->role_name = $form->getValue('name');
      $role->role_desc = $form->getValue('description');
      
      $role->save();
      $this->_helper->FlashMessenger(array('form'=>'Your rule info has been saved'));
      
      $rules = $role->findDependentRowSet('Acl_Db_Table_Rules');
      
      foreach($rules as $rule) {
        $rule->delete();
      }
      
      $tbl_rules = Zend_Registry::get('acl')->getRuleTable();
      
      foreach($form->getRuleToElement() as $rule=>$element) {
        if(in_array($rule, $form->getValue($element))) {
          $row = $tbl_rules->createRow(
              array('resource_id'=>$rule,
                    'role_id'=>$id,
                    'permission'=>true
                  ));
          $row->save();
        }
      }
      Zend_Registry::get('acl')->flushRules();
      Zend_Registry::get('acl')->flushRoles();
      $this->_helper->FlashMessenger(array('form'=>'Your role permissions have been saved'));
      
      $this->_helper->redirector->setGotoSimple('edit', null,null,array('id'=>$id));
    }
    
     
    private function _createAclForm($role) {
      $form = new Acl_Form_Role(array('role'=>$role, 'resources'=>$this->_getResources()));
      
      return $form;
    }

    private function _lcwords($matches) {
      return $matches[1].' '.lcfirst($matches[2]);
    }
    
    private function _only_lcwords($matches) {
    	return lcfirst($matches[0]);
    }
    
    private function _formatName($name) {
      $name = preg_replace_callback('/([^_])([A-Z])/', 'Acl_AclController::_lcwords', $name);
      $name = preg_replace_callback('/([A-Z])/', 'Acl_AclController::_only_lcwords', $name);
      $name = ltrim($name);
      $name = str_replace(' ', '-', $name);
      return $name;
    }
    
    private function _getResources() {
      $modules = array();
      foreach(array_keys($this->_getControllerDirs()) as $m) {
        $controllers = array();
        foreach($this->_getControllers($m) as $c) {
          $actions = array();
          foreach($this->_getActions($m, $c) as $a=>$desc) {
            $actions[$this->_formatName($a)] = $desc;
          }
          $controllers[$this->_formatName($c)] = $actions;
        }
        $modules[$m] = $controllers;
      }
      return $modules;
    }
    
    private function _getControllerDir($module) {
      $front = Zend_Controller_Front::getInstance();
      $cd = $front->getControllerDirectory();
      return $cd[$module];
    }
    
    private function _getControllerDirs(){
      $front = Zend_Controller_Front::getInstance();
      $cd = $front->getControllerDirectory();
      return $cd;
    }

    private function _requireAllControllers() {
      foreach ($this->_getControllerDirs() as $module=>$dir) {
        foreach($this->_getControllers($module) as $c) {
          require_once $dir . '/' . $c . 'Controller.php';
        }
      }
    }
    
    private function _getControllersInDir($module_path) {
    	$r = array();
    	foreach (scandir($module_path) as $f) {
    		if (is_file($module_path  . "/" . $f)) {
    			if(preg_match('/Controller.php$/', $f)) {
    				$i = strpos($f,"Controller.php");
    				$r[] = substr($f, 0, $i);
    			}
    		} else if(is_dir($module_path  . "/" . $f) && $f != '.' && $f != '..') {
    			foreach($this->_getControllersInDir($module_path  . "/" . $f) as $cname) {
    				$r[] = $f . '_' .$cname;
    			}
    		}
    	}
    	return $r;
    }
    
    private function _getControllers($module) {
       $cd = $this->_getControllerDirs();
       $module_path = $cd[$module];
	   $r = $this->_getControllersInDir($module_path);       
       return $r;
    }
    
    private function _getControllerActions($class) {
      $r = array();
      foreach(get_class_methods($class) as $method) {
        /*
         * TODO: not strpos!!! use preg!
         * */
        if(preg_match('/Action$/', $method)) {
           $i = strpos($method, 'Action');
           $r[] = substr($method, 0, $i);
         }
      }
      return $r;
    }
    
    private function _getControllerAdditionalActions($class) {
      $r = array();
      $vars = get_class_vars($class);
      if(isset($vars['_acl_actions_additional'])) {
        if(!is_array($vars['_acl_actions_additional'])) {
          throw new Zend_Exception('_acl_actions_aditional in '. $class .' is not an array!');
        }
        foreach($vars['_acl_actions_additional'] as $action) {
          if(in_array($action, $vars)) {
            throw new Zend_Exception('Cannot use additional action ' . $action . ' - already exists in ' . $class);
          }
          $r[] = $action;
        }
      }
      return $r;
    }

    private function _getControllerAllActions($class) {
      $actions = $this->_getControllerActions($class);
      $actions_additional = $this->_getControllerAdditionalActions($class);
      $intersection = array_intersect($actions, $actions_additional);
      if(count($intersection) > 0) {
        throw new Zend_Exception('Additional actions '.print_r($intersection, true)
          .' intersect with actions in '.$class);
      }
      $r = array_merge($actions, $actions_additional);
      return $r;
    }

    private function _getControllerActionDesc($class) {
      $actions = $this->_getControllerAllActions($class);
      $actions_desc = array_fill(0, count($actions), '');
      $r = array_combine($actions, $actions_desc);
      $vars = get_class_vars($class);
      if(isset($vars['_acl_actions_desc'])) {
        if(!is_array($vars['_acl_actions_desc'])) {
          throw new Zend_Exception('_acl_actions_desc in '. $class .' is not an array!');
        }
        foreach($vars['_acl_actions_desc'] as $action=>$desc) {
          if(in_array($action, $r)) {
            throw new Zend_Exception('Cannot set action desc ' . $action . ' - already set up in '. $class);
          }
          if(!array_key_exists($action, $r)) {
            throw new Zend_Exception('Cannot find action ' . $action . ' in ' . $class);
          }
          $r[$action] = $desc;
        }
      } 
      return $r;
    }

    private function _getActions($m, $c){
      $prefix = ( $m=='default' ? '' : $m . '_');
      
      require_once $this->_getControllerDir($m) . '/' . str_replace('_', '/', $c) . 'Controller.php';
      
      $class = $prefix . $c .'Controller';
      
      return $this->_getControllerActionDesc($class);
    }

}







