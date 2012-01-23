<?php
class Acl_Form_Role extends Zend_Form {
  protected $_role = null;
  protected $_resources = null;
  protected $_rules = null;
  
  protected $_rule_to_element_list = array();
  
  public function setRole($role) {
    $this->_role = $role;
  }
  
  public function setResources($r) {
    $this->_resources = $r;
  }
  
  public function init() {
    if(!$this->_role || !$this->_resources)
      throw new Zend_Exception('Role or resources was not set for this form');
    
    $id = new Zend_Form_Element_Hidden('id');
    $id->setDecorators(array('ViewHelper'))
       ->addFilter('Int')
       ->setValue($this->_role->id);
    
    $name = new Zend_Form_Element_Text('name');
    $name
      ->setLabel('Name')
      ->setRequired(true)
      ->addFilter('StripTags')
      ->addFilter('StringTrim')
      ->addValidator('NotEmpty')
      ->setValue($this->_role->role_name);
      
    $desc = new Zend_Form_Element_Text('description');
    $desc
      ->setLabel('Description')
      ->setRequired(true)
      ->addFilter('StripTags')
      ->addFilter('StringTrim')
      ->addValidator('NotEmpty')
      ->setValue($this->_role->role_desc);
    
    
    $this->addElements(array(
      'id'=>$id, 
      'name'=>$name, 
      'description'=>$desc));
    $this->addDisplayGroup(array('id', 'name', 'description'), 'main', array('legend' => 'Role settings'));
    //$this->addDisplayGroup(array('main'), 'hello');
    $this->setDisplayGroupDecorators(array(
      'FormElements',
      'Fieldset'
    ));
    
    $rows = $this->_role->findDependentRowSet('Acl_Db_Table_Rules');
    
    foreach($rows as $row) {
      $this->_rules[$row->resource_id] = $row;
    }
    
    
    foreach($this->_resources as $module=>$controllers) {
        $this->_addModule($module, $controllers);
    }
    
    
    $submit = new Zend_Form_Element_Submit('submit');
    $submit->setAttrib('id', 'submitbutton');
    $this->addElements(array(
      $submit));
  }
  
  /*
   * from Zend_Controller_Dispatcher_Standart
   * */
  
  protected function _formatName($unformatted, $isAction = false)
  {
      // preserve directories
      if (!$isAction) {
          $segments = explode($this->_pathDelimiter, $unformatted);
      } else {
          $segments = (array) $unformatted;
      }

      foreach ($segments as $key => $segment) {
          $segment        = str_replace($this->_wordDelimiter, ' ', strtolower($segment));
          $segment        = preg_replace('/[^a-z0-9 ]/', '', $segment);
          $segments[$key] = str_replace(' ', '', ucwords($segment));
      }

      return implode('_', $segments);
  }
  
  protected $_pathDelimiter = '_';
  protected $_wordDelimiter = array('-', '.');
  
  /*
   * ENDOF: from Zend_Controller_Dispatcher_Standart
   * */
  
  
  
  protected function _addModule($module, $controllers) {
    $controller_items = array();
    foreach($controllers as $controller=>$actions) {
      $action_names = array();
      
      $cb_id = $this->_formatName($module).'_'.$this->_formatName($controller);
      
      $group = new Zend_Form_Element_MultiCheckbox($cb_id);
      $group->setLabel($controller);   
       
      $to_select = array(); 
      foreach($actions as $action=>$desc) {
        $label = $desc?$desc:$action;
        
        $id = Acl_Db::buildID($module, $controller, $action);
        
        $group->addMultiOption( $id, $label );
        if( isset($this->_rules[ $id ]) ) {
          if($this->_rules[$id]->permission) {
            $to_select[] = $id;
          }
        }
        $this->_rule_to_element_list[$id] = $cb_id;
      }
      $group->setValue($to_select);
      $this->addElements(
        array($cb_id=>$group)
          ); 
      
      $controller_items[] = $cb_id;
    }
    $this->addDisplayGroup($controller_items, $module, array('legend' => $module));
    $this->setDisplayGroupDecorators(array(
      'FormElements',
      'Fieldset'
    ));
  }

  public function getRuleToElement() {
    return $this->_rule_to_element_list;
  }
} 