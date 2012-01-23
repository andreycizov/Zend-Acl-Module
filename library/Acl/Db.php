<?php
class Acl_Db extends Zend_Acl {
    private $_authentication_salt = "293849238029584380v54tuvwnrvacpfoisru0942u[tr034utcoiwaejfxpiejwrxm3i4tucp4uthc349ytc34uctoaiuhcnw4txyw8a47txn4w87txaneiofuhszl4hyx7ityw4xtni&x9rxw]";
  
    static function hash($data) {
      $acl_db = new self;
      return hash('sha256', hash('sha256', $data . $acl_db->_authentication_salt));
    }
  
    protected $_rule_tree = null;
    
    protected $_roles = null;
      
    private $_tbl_roles = null;
    private $_tbl_rules = null;
    private $_tbl_res = null;
    
    private $_cache_name = 'acl_cache';
    private $_cache = null;
    
    public function getResourceTable() {
      return $this->_tbl_res;
    }
    
    public function getRuleTable(){
      return $this->_tbl_rules;
    }
    
    public function getRoleTable(){
      return $this->_tbl_roles;
    }
    
    public function __construct()
    {
      $this->_cache = Zend_Controller_Front::getInstance()
                      ->getParam('bootstrap')
                      ->getResource('cachemanager')
                      ->getCache($this->_cache_name);
                      
      $this->_tbl_roles = new Acl_Db_Table_Roles();
      $this->_tbl_rules = new Acl_Db_Table_Rules();
      $this->_tbl_res   = new Acl_Db_Table_Resources();
      
      $this->initRoles();
      $this->initResources();
      
      $this->deny(null);
      $this->initRolePermissions();
    }
 
    private function initRoles()
    {
        foreach ($this->listRoles() as $role) {
          $this->addRole(new Zend_Acl_Role($role['id']));
          $this->_roles[$role->id] = $role;
        }
    }
    
    private function _initResources() {
      
    }
 
    private function initResources()
    {
      foreach($this->listResources() as $r) {
        $this->addResource(new Zend_Acl_Resource($r['id']));
      }
    }
 
    private function initRolePermissions()
    {
      $roles = $this->listRoles();
      $acls = $this->listRules();
      foreach ($acls as $acl) {
          $role = $roles[$acl->role_id];
          if($acl->permission) {
            try {
              $this->allow($role->id, $acl->resource_id);
            }
            catch (Zend_Acl_Exception $e) {
              
            }
          }
      }
    }
    
    public function listRules()
    {
      $r = null;
      if(!$r = $this->_cache->load('rules')) {
        $r = $this->_tbl_rules->fetchAll($this->_tbl_rules->select());
        
        $this->_cache->save($r);
      }
      return $r;
    }
 
    public function listRoles()
    {
      $r = null;
      if(!$r = $this->_cache->load('roles')) {
        $roles = $this->_tbl_roles->fetchAll(
        $this->_tbl_roles->select());
        foreach($roles as $role){
          $r[$role->id] = $role;
        }
        $this->_cache->save($r);
       }
       return $r;
    }
    
    public function flushResources(){
      $this->_cache->remove('resources');
    }
    
    public function flushRoles(){
      $this->_cache->remove('roles');
    }
    
    public function flushRules(){
      $this->_cache->remove('rules');
    }
    
    
    public function getResources(){
      return $this->listResources();
    }
 
    public function listResources()
    {
      $r = null;
      if(!$r = $this->_cache->load('resources')) {
        $resource_list = $this->_tbl_res->fetchAll(
        $this->_tbl_res
        ->select()
          );
        $r = array();
        foreach($resource_list as $res) {
          $r[] = $res->toArray();
        }
        
        $this->_cache->save($r);
     }
     return $r;
    }
   
    static function buildID($module, $controller, $action) {
      return $module .' '.$controller. ' '.$action;
    }

    public function isUserAllowed($module, $controller, $action)
    {
      try {
        return $this->isAllowed(Zend_Registry::get('user')->role_id, $this->buildID($module, $controller, $action));
      }
      catch (Zend_Acl_Exception $e) {
        // TODO: LOG ERROR HERE;
        return false;
      }
    }
}