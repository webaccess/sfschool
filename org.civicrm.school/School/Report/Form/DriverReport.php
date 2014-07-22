<?php

require_once 'CRM/Report/Form.php';

class School_Report_Form_DriverReport extends CRM_Report_Form {
    
    // set custom table name
    protected $_schoolInfo = 'civicrm_value_school_information';
    protected $_driverInfo = 'civicrm_value_vehicle_use_agreement';

    function __construct( ) {
      
        
        $fields = array( );
        $query  = "
SELECT column_name, label , option_group_id 
FROM   civicrm_custom_field 
WHERE  is_active = 1 
AND    column_name='grade' 
AND    custom_group_id = (
  SELECT id FROM civicrm_custom_group WHERE table_name='{$this->_schoolInfo}'
 )
";
        $dao_column = CRM_Core_DAO::executeQuery( $query );
        
        while ( $dao_column->fetch( ) ) {
            $fields[$dao_column->column_name] = array('required'   => true, 
                                                      'title'      => $dao_column->label,
                                                      'no_display' => true,
                                                      'no_repeat' => true,
                                                      );
            $op_group_id = $dao_column->option_group_id;
        }
        
        $filters = array( );
        // filter for Grade
        $options = array( );
        $query   = "SELECT label , value FROM civicrm_option_value WHERE option_group_id =".$op_group_id."  AND is_active=1";
        $dao     = CRM_Core_DAO::executeQuery( $query );
        
        while( $dao->fetch( ) ) {
            $options[$dao->value] = $dao->label; 
        }

        $filters['grade'] = array( 'title'        => ts('Grade'),
                                   'operatorType' => CRM_Report_Form::OP_SELECT,
                                   'options'      => array( '' => '-select-' ) + $options ,
                                   'type'         => CRM_Utils_Type::T_STRING
                                   );
        
        // filter for parent name
        $parentFilter['sort_name'] = array( 'title'        => ts('Parent Name'),
                                            'operatorType' => CRM_Report_Form::OP_STRING,
                                            'type'         => CRM_Utils_Type::T_STRING
                                            );
        
        $this->_columns = array( 
                                'civicrm_contact' =>
                                array( 'dao'       => 'CRM_Contact_DAO_Contact',
                                       'fields'    => 
                                       array( 'sort_name' =>
                                              array(
                                                    'required'   => true,
                                                    'title'      => ts('Parent Name'),
                                                    'no_repeat'  => true,
                                                    ),
                                              'id' =>
                                              array(
                                                    'no_display' => true,
                                                    'required'   => false,
                                                    'no_repeat'  => true,
                                                    ),
                                              ), 
                                       'filters' => $parentFilter,
                                       'alias' => 'cs'
                                       ),
                                
                                'civicrm_email'   =>
                                array( 'dao'       => 'CRM_Core_DAO_Email',
                                       'fields'    =>
                                       array( 'email' => 
                                              array( 
                                                    'title'   => ts( 'Email' ), 
                                                    'default' => true
                                                     ),
                                              ),
                                       ),

                                $this->_schoolInfo =>
                                array( 'dao'     => 'CRM_Contact_DAO_Contact',
                                       'fields'  => $fields ,
                                       'filters' => $filters,
                                       'alias'   => 'school',
                                       ),
                                
                                $this->_driverInfo =>
                                array( 'dao'     => 'CRM_Contact_DAO_Contact',
                                       'fields'  => 
                                       array( 'employee_volunteer_name' =>
                                              array(
                                                    'required'   => true,
                                                    'title'      => ts('Emp Name'),
                                                    'default'    => true,
                                                    ),
                                              'license_number' =>
                                              array(
                                                    'required'   => true,
                                                    'title'      => ts('License No'),
                                                    'default'    => true,
                                                    ),
                                              'state_issued' =>
                                              array(
                                                    'title'      => ts('State Issued'),
                                                    'default'    => true,
                                                    ),
                                              'birth_date' =>
                                              array(
                                                    'title'      => ts('Birth Date'),
                                                    'default'    => true,
                                                    ),
                                              'license_plate' =>
                                              array(
                                                    'required'   => true,
                                                    'title'      => ts('License Plate'),
                                                    'default'    => true,
                                                    ),
                                              'policy_number' =>
                                              array(
                                                    'required'   => true,
                                                    'title'      => ts('Policy Number'),
                                                    'default'    => true,
                                                    ),
                                              'insurance_carrier_name' =>
                                              array(
                                                    'title'      => ts('Insurance Carrier Name'),
                                                    'default'    => true,
                                                    ),
                                              ),
                                       'alias'   => 'driver',
                                       ),
                                
                                 );
        parent::__construct( );
    }
    
    function preProcess( ) {
        $this->_csvSupported = false;
        parent::preProcess( );
    }
    
    function select(  ) {
    
      //$fieldArray = array( 'civicrm_contact',$this->_customTable );
        $select = $this->_columnHeaders =  array( );

        foreach ( $this->_columns as $tableName => $table ) {
 
           if ( array_key_exists('fields', $table) ) {
                foreach ( $table['fields'] as $fieldName => $field ) {
                    if ( CRM_Utils_Array::value( 'required', $field ) ||
                         CRM_Utils_Array::value( $fieldName, $this->_params['fields'] ) ) {
                            
                            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']  = CRM_Utils_Array::value( 'type', $field );
                            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
                        
                    }
                }
            }
        }
        $this->_select = "SELECT " . implode( ",\n", $select ) . " ";
    }
    
    
    function from( ) {

      $agreement = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomField','Agreement','column_name','name' );
      $relTypeId = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_RelationshipType', 'Child Of', 'id', 'name_a_b' );
      $gradeAlias = $this->_aliases[$this->_schoolInfo];
      $driverAlias = $this->_aliases[$this->_driverInfo];
      $this->_from = 
        "FROM civicrm_contact {$this->_aliases['civicrm_contact']}

                    INNER JOIN  civicrm_value_vehicle_use_agreement $driverAlias
                    ON ({$this->_aliases['civicrm_contact']}.id = $driverAlias.entity_id
                    AND $driverAlias.".$agreement." = '1' AND {$this->_aliases['civicrm_contact']}.contact_sub_type LIKE '%Parent%')

                    LEFT JOIN  civicrm_relationship
                    ON {$this->_aliases['civicrm_contact']}.id = civicrm_relationship.contact_id_b and civicrm_relationship.relationship_type_id = ".$relTypeId."
                    
                    LEFT JOIN civicrm_value_school_information $gradeAlias 
                    ON $gradeAlias.entity_id = civicrm_relationship.contact_id_a

                    LEFT JOIN  civicrm_email {$this->_aliases['civicrm_email']} 
                    ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id 
                    AND {$this->_aliases['civicrm_email']}.is_primary = 1) " ;
    }

    function where( ) {
      $clauses = array( );
      foreach ( $this->_columns as $tableName => $table ) {
        if ( array_key_exists('filters', $table) ) {
          foreach ( $table['filters'] as $fieldName => $field ) {
            $clause = null;
            if ( CRM_Utils_Array::value( 'operatorType', $field )  & CRM_Report_Form::OP_DATE ) {
              $relative = CRM_Utils_Array::value( "{$fieldName}_relative", $this->_params );
              $from     = CRM_Utils_Array::value( "{$fieldName}_from"    , $this->_params );
              $to       = CRM_Utils_Array::value( "{$fieldName}_to"      , $this->_params );
                        
              $clause = $this->dateClause( $field['name'], $relative, $from, $to, $field['type'] );
            } else {
              $op = CRM_Utils_Array::value( "{$fieldName}_op", $this->_params );
              if ( $op ) {
                $clause = 
                  $this->whereClause( $field,
                                      $op,
                                      CRM_Utils_Array::value( "{$fieldName}_value", $this->_params ),
                                      CRM_Utils_Array::value( "{$fieldName}_min", $this->_params ),
                                      CRM_Utils_Array::value( "{$fieldName}_max", $this->_params ) );
              }
            }
                    
            if ( ! empty( $clause ) ) {
              $clauses[$fieldName] = $clause;
            }
          }
        }
      }
            
      if(empty( $clauses )){
        $this->_where = "WHERE (1)";
      }
      else{
        $this->_where = "WHERE ".implode( ' AND ', $clauses );
      }
    }

    function orderBy( ) {
        $alias = $this->_aliases[$this->_schoolInfo];
        $this->_orderBy = " ORDER BY $alias.grade, {$this->_aliases['civicrm_contact']}.sort_name";
    }
    
    function postProcess( ) {
      $rows = array();
      $this->beginPostProcess( );
      $sql = $this->buildQuery( );
      $dao  = CRM_Core_DAO::executeQuery( $sql );
      while ( $dao->fetch( ) ) {
        $row = array( );
        foreach ( $this->_columnHeaders as $key => $value ) {
          if ( property_exists( $dao, $key ) ) {
            $row[$key] = $dao->$key;
          }
        }
        $rows[] = $row;
      }
      $this->setPager( );
      $this->alterDisplay( $rows );
      $this->doTemplateAssignment( $rows );
      $this->endPostProcess( $rows );
    }

}
