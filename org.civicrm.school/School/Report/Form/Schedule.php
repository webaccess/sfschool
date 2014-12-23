<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 2.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2009                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2009
 * $Id$
 *
 */

require_once 'CRM/Report/Form.php';

class School_Report_Form_Schedule extends CRM_Report_Form {
 
    protected $_customTable_exCare   = 'civicrm_value_extended_care';
    protected $fieldName_exCare = array('name', 'instructor','start_date');
    
    const
        ROW_COUNT_LIMIT = 10;

    function __construct( ) {
        $this->_columns = 
            array( 
                  'civicrm_contact' =>
                  array( 'dao'       => 'CRM_Contact_DAO_Contact',
                         'fields'    => 
                         array( 'display_name' =>
                                array(
                                      'no_display' => true,
                                      'required'   => true,
                                      'title'      => ts('Student')
                                      ),
                                'id' =>
                                array(
                                      'no_display' => true,
                                      'required'   => true,
                                                    ),
                                'contact_sub_type' =>
                                array(
                                      'no_display' => true,
                                      'required'   => true,
                                      ),
                                ),
                         'filters'   =>
                         array( 'sort_name' =>
                                array('title' => ts('Contact Name')
                                      ) ,
                                'contact_sub_type' =>
                                array(
                                      'title'        => ts('Sub Type'),
                                      'default'      => 'Staff',
                                      'operatorType' => CRM_Report_Form::OP_SELECT,
                                      'type'         => CRM_Utils_Type::T_STRING, 
                                      'options'      => CRM_Contact_BAO_ContactType::subTypePairs(),
                                      )
                                ) ),
                  
                  'civicrm_activity'      =>
                  array( 'dao'     => 'CRM_Activity_DAO_Activity',
                         'fields'  =>
                         array(
                               'activity_date_time' => array( 'title'      => ts('Date'),
                                                              'no_display' => true, 
                                                              'required'   => true ),
                               'subject' => array( 'title'      => ts('Activity'),
                                                   'required'   => true,
                                                   'no_display' => true),
                               
                               ),
                         'filters' =>
                         array( 'activity_date_time '=>array( 'title'        => ts('Date'),
                                                              'default'      => 'this.month',
                                                              'operatorType' => CRM_Report_Form::OP_DATE,
                                                              'type'         => CRM_Utils_Type::T_DATE )
                                ), ),

                  'civicrm_contact_other' =>
                  array( 'dao'       => 'CRM_Contact_DAO_Contact',
                         'fields'    => 
                         array( 'display_name' =>
                                array(
                                      'no_display' => true,
                                      'required'   => true,
                                      'title'      => ts('With')
                                      ),
                                ), ),
                    
                   );
        parent::__construct( );
    }

    function preProcess( ) {
        $this->_csvSupported = false;
        parent::preProcess( );
    }
    
    function select(  ) {
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

        if ( $this->_params['contact_sub_type_value'] == 'Staff' ||  $this->_params['contact_sub_type_value'] == 'Teacher' ) { 
            $this->_from = "FROM
                              civicrm_activity_contact activity_assignment
                              INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                                           ON 
                                               {$this->_aliases['civicrm_contact']}.id = activity_assignment.contact_id AND activity_assignment.record_type_id = 1
                                               AND {$this->_aliases['civicrm_contact']}.contact_sub_type LIKE '%".$this->_params['contact_sub_type_value']."%'

                              INNER  JOIN civicrm_activity {$this->_aliases['civicrm_activity']}
                                            ON 
                                              {$this->_aliases['civicrm_activity']}.id = activity_assignment.activity_id 

                              INNER JOIN civicrm_activity_contact activity_target 
                                            ON 
                                              {$this->_aliases['civicrm_activity']}.id = activity_target.activity_id
                              LEFT JOIN civicrm_contact  {$this->_aliases['civicrm_contact_other']}
                                             ON 
                                              {$this->_aliases['civicrm_contact_other']}.id = activity_target.contact_id AND activity_target.record_type_id = 3   ";
        }


        if( $this->_params['contact_sub_type_value'] == 'Student' || $this->_params['contact_sub_type_value'] == 'Parent' ) {
            $this->_from = "FROM 
                                 civicrm_activity_contact activity_target
                                 INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                                           ON 
                                               {$this->_aliases['civicrm_contact']}.id = activity_target.contact_id  AND activity_target.record_type_id = 3
                                               AND {$this->_aliases['civicrm_contact']}.contact_sub_type LIKE '%".$this->_params['contact_sub_type_value']."%'

                                 INNER JOIN civicrm_activity {$this->_aliases['civicrm_activity']}
                                            ON 
                                              {$this->_aliases['civicrm_activity']}.id =  activity_target.activity_id 


                                 INNER JOIN civicrm_activity_contact activity_assignment
                                            ON 
                                              {$this->_aliases['civicrm_activity']}.id = activity_assignment.activity_id

                                  LEFT JOIN civicrm_contact  {$this->_aliases['civicrm_contact_other']}
                                             ON 
                                              {$this->_aliases['civicrm_contact_other']}.id = activity_assignment.contact_id AND activity_assignment.record_type_id = 1

 ";

        }

    }
    
    function where( ) { 
        $clauses    = array( );
        foreach ( $this->_columns as $tableName => $table ) {
            if( $tableName != 'civicrm_contact' ) {
                
                continue;
            }
            if ( array_key_exists('filters', $table) ) {
                foreach ( $table['filters'] as $fieldName => $field ) {
                    $clause = null;

                    //  if ( CRM_Utils_Array::value( 'type', $field ) & CRM_Utils_Type::T_DATE ) {
                     if ( $field['operatorType'] & CRM_Report_Form::OP_DATE ) { 
                        $relative = CRM_Utils_Array::value( "{$fieldName}_relative", $this->_params );
                        $from     = CRM_Utils_Array::value( "{$fieldName}_from"    , $this->_params );
                        $to       = CRM_Utils_Array::value( "{$fieldName}_to"      , $this->_params );
                        $clause = $this->dateClause( $field['name'], $relative, $from, $to );

                    } else {
                        $op = CRM_Utils_Array::value( "{$fieldName}_op", $this->_params );
                        if ( $op ) {
                            
                            // hack for values type string
                            if ( $op == 'in' ) {
                                $value  = CRM_Utils_Array::value( "{$fieldName}_value", $this->_params );
                                if ( $value !== null && count( $value ) > 0 ) {
                                    $clause = "( {$field['dbAlias']} IN ('" . implode( '\',\'', $value ) . "' ) )";
                                }
                            } else {
                                $clause = 
                                    $this->whereClause( $field,
                                                        $op,
                                                        CRM_Utils_Array::value( "{$fieldName}_value", $this->_params ),
                                                        CRM_Utils_Array::value( "{$fieldName}_min", $this->_params ),
                                                        CRM_Utils_Array::value( "{$fieldName}_max", $this->_params ) );
                            }
                        }
                    }
                    
                    if ( ! empty( $clause ) ) {
                        $clauses[] = $clause;
                    }
                }
            }
        }
        
        if ( empty( $clauses ) ) {
            $this->_where = "WHERE ( 1 ) ";
        } else {
            $this->_where = "WHERE " . implode( ' AND ', $clauses );
        }
       

    }
    
    function groupBy( ) {

        $this->_groupBy = "  ";
        
            
        $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_activity']}.id,{$this->_aliases['civicrm_activity']}.id";

        
    }

    function postProcess( ) {
        $this->beginPostProcess( ); 
        // print_r($this->_params);
      
        $this->contact = $contact= array();
        $activity = array();

        $this->select (  );
        $this->from   ( );
        $this->where  (  );
        $this->groupBy( );
        $sql  = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy}";
        
        $dao  = CRM_Core_DAO::executeQuery( $sql );
        $rows = array();

        while( $dao->fetch( ) ) {

            $row = array( );
            foreach ( $this->_columnHeaders as $key => $value ) {
                if ( property_exists( $dao, $key ) ) {
                        $row[$key] = $dao->$key;
                }
            }
            $this->contact[$dao->civicrm_contact_id]['display_name'] = $dao->civicrm_contact_display_name;
            $contact[] = $dao->civicrm_contact_id;
            $rows[$dao->civicrm_contact_id][] = $row;
            
        }   
        
        if( !empty($contact) && ($this->_params['contact_sub_type_value'] == 'Student' OR $this->_params['contact_sub_type_value'] == 'Parent' )) {       
            $this->addRelationData($this->_params['contact_sub_type_value'], $contact);
        }
        
        unset($this->_columnHeaders['civicrm_contact_id']);
        unset($this->_columnHeaders['civicrm_contact_display_name']);     

        $this->assign( 'contactDetails', $this->contact );
        $this->assign( 'activityHeaders' , $this->_columnHeaders );
        $this->assign( 'activityDetails' , $rows );
        




        $this->formatDisplay($this->contact ,false );
        
        $this->doTemplateAssignment($this->contact );
        
        $this->endPostProcess($this->contact );

    }
    

    function addRelationData( $subType ,$contact ) {
        $relationDetails = array();
        $relationHeaders = array();
        
        if( $subType == 'Student') {
            $query ="SELECT contact_id_a,contact_id_b, contact.display_name as rel_contact  FROM civicrm_relationship 
                     LEFT JOIN civicrm_contact contact
                            ON contact_id_b=contact.id
                     WHERE relationship_type_id = 1 AND
                     contact_id_a IN (".implode(',', $contact).") AND is_active=1 GROUP BY contact_id_a,contact_id_b";

            $dao = CRM_Core_DAO::executeQuery( $query );
            while(  $dao->fetch()) {
                $relationDetails[$dao->contact_id_a][] = $dao->rel_contact;
            }
         $this->assign('relationDetails', $relationDetails );
         $this->assign('relHeader', 'Parent');
        }
        
    }
    
}
