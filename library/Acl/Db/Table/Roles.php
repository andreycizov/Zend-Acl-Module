<?php

class Acl_Db_Table_Roles extends Zend_Db_Table_Abstract
{
    protected $_name = 'acl_roles';
    
    protected $_dependentTables = array ('Acl_Db_Table_Rules');
    
    static function getRoles(){
      $tbl = new self();
      
      $roles = $tbl->fetchAll($tbl->select());
      $r = array();
      foreach($roles as $role) {
        $r[$role->id] = $role->role_name;
      }
      return $r;
    }
}