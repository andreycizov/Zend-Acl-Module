<?php

class Acl_Db_Table_Rules extends Zend_Db_Table_Abstract
{
    protected $_name = 'acl_rules';
    
    protected $_referenceMap = array (
      'Acl_Db_Table_Roles'=> array (
        'columns'=>'role_id',
        'refTableClass'=>'Acl_Db_Table_Roles',
        'refColumns'=>'id'
      )
   );
      
}