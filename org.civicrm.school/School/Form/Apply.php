<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'School/Form/Apply/TabHeader.php';

/**
 * This class generates form components for processing Event  
 * 
 */
class School_Form_Apply extends CRM_Core_Form
{
    protected $_applicantId;
    protected $_parentId;

    const          
        CUSTOM_OTHER_CHILDREN_TABLE = 'civicrm_value_app_children',
        CUSTOM_FAMILY_TABLE         = 'civicrm_value_app_family',
        CUSTOM_APPLICANT_TABLE      = 'civicrm_value_app_applicant',
        CUSTOM_SCHOOL_TABLE         = 'civicrm_value_app_school',
        RELATION_TABLE              = 'civicrm_value_parent_relationship_data',
        CC_EMAILID                  = 'admissions@theidealschool.org';

    function preProcess( ) {
       
        $session = CRM_Core_Session::singleton();
        $this->_applicantId = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this, false, 0, 'REQUEST' );
        $this->_parentId    = CRM_Utils_Request::retrieve( 'pid', 'Positive', $this, false, 
                                                           $session->get( 'userID'), 'REQUEST' );

           if ( ! $this->_applicantId ) {
            $this->_applicantId = $this->get( 'cid' );
           }
        if ( $this->_name != 'Applicant' && !$this->_applicantId ) {
            CRM_Core_Error::fatal( ts('Applicant not found.') );
        }
        $this->add( 'hidden', 'cid', $this->_applicantId );

        if ( $this->_applicantId ) {
            $getAppInfo = self::isApplicationFrozen( $this->_applicantId );
            if ( $getAppInfo ) {
                CRM_Core_Error::fatal( ts( 'Your Application form is no longer available to edit.' ) );
            }
        }

        // check if parent has required contact type set  
        $subType = CRM_Contact_BAO_Contact::getContactSubType( $this->_parentId );
        if(!in_array('Applicant_Parent',$subType)){     
          CRM_Core_Error::fatal( ts( 'The application form is accessible only to parents.' ) );
        }

        if( $this->_applicantId ) {
                   
            if (  !CRM_Contact_BAO_Contact_Permission::relationship( $this->_applicantId , $this->_parentId )  ) {
                CRM_Core_Error::fatal( ts( 'You do not have permission to edit specified applicant.' ) );
            }
        }
        // make sure logged in user is either - parent OR admin
        if ( $this->_parentId != $session->get( 'userID' ) && 
             !CRM_Core_Permission::check( 'administer CiviCRM' ) ) {
            CRM_Core_Error::fatal( ts('Not enough permission.') );
        }
        
        // set up tabs
        School_Form_Apply_TabHeader::build( $this );        
    }

    function buildQuickForm( ) {

        $className = CRM_Utils_String::getClassName( $this->_name );
        
        $buttons   = array();
        $buttons[] = array ( 'type'      => 'submit',
                             'name'      => ts('Save'),
                             'isDefault' => true   );

        if ( $className !== 'Additional' ) {
            $buttons[] = array ( 'type'      => 'next',
                                 'name'      => ts('Save and Next'),
                                 'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',  );
        }

        $buttons[] = array ( 'type'      => 'cancel',
                             'name'      => ts('Cancel') );
    
        $this->addButtons( $buttons );
    }

    function getTemplateFileName( ) {
        if ( $this->controller->getPrint( ) == CRM_Core_Smarty::PRINT_NOFORM ||
             ( $this->_action & CRM_Core_Action::DELETE ) ) {
            return parent::getTemplateFileName( );
        } else {
            return 'CRM/common/TabHeader.tpl';
        }
    }

    function endPostProcess( )
    {
        $className = CRM_Utils_String::getClassName( $this->_name );
        if ( $this->controller->getButtonName('submit') == "_qf_{$className}_next" ) {
            $nextTab = School_Form_Apply_TabHeader::getNextSubPage( $this, $className );
            $nextUrl = CRM_Utils_System::url( 'civicrm/school/apply/' . strtolower($nextTab),
                                              "reset=1&cid={$this->_applicantId}&pid={$this->_parentId}" );
            CRM_Utils_System::redirect( $nextUrl );
        } else if ( $className == 'Applicant' ) {
            // This is the case when tab is applicant and user has hit Save button.
            // We want to rebuild tabs for this case so that all tabs get there cid, 
            // since cid is generated in the post process of Applicant.php
            require_once 'School/Form/Apply/TabHeader.php';
            School_Form_Apply_TabHeader::build( $this );
        }
    }

    function findDupe( $params ) {
        $dupeId = false;
        $input  = array();

        foreach ( array('civicrm_contact', 'civicrm_email') as $table ) {
            if ( array_key_exists($table, $params) ) {
                foreach ( $params[$table] as $field => $value ) {
                    $input[$field] = CRM_Utils_Type::escape( $params[$table][$field], 'String' );
                }
            }
        } 
        foreach ( array('civicrm_relationship') as $table ) {
            if ( array_key_exists($table, $params) ) {
                foreach ( $params[$table] as $field => $value ) {
                    $input[$field] = CRM_Utils_Type::escape( $params[$table][$field], 'Integer' );
                }
            }
        }
        
        // 1. make first check based on relationship
        if ( $input['relationship_type_id'] && !empty($input['contact_id_b']) ) {
            $sql    = "
SELECT cc.id FROM civicrm_contact cc 
INNER JOIN civicrm_relationship cr ON cc.id = cr.contact_id_a AND 
           cr.contact_id_b = {$input['contact_id_b']} AND 
           cr.relationship_type_id = {$input['relationship_type_id']} 
WHERE cc.first_name = '{$input['first_name']}' AND 
      cc.last_name  = '{$input['last_name']}'
LIMIT 1";
            $dupeId = CRM_Core_DAO::singleValueQuery($sql);
        } else if ( $input['relationship_type_id'] && $input['contact_id_a'] ) {
            $sql    = "
SELECT cc.id FROM civicrm_contact cc 
INNER JOIN civicrm_relationship cr ON cc.id = cr.contact_id_b AND 
           cr.contact_id_a = {$input['contact_id_a']} AND 
           cr.relationship_type_id = {$input['relationship_type_id']} 
WHERE cc.first_name = '{$input['first_name']}' AND 
      cc.last_name  = '{$input['last_name']}'
LIMIT 1";
            $dupeId = CRM_Core_DAO::singleValueQuery($sql);
        }

        // 2. if no dupe is found based on rel, do another check based on email / birth-date
        if ( !$dupeId && !empty( $input['email'] ) ) {
            $sql    = "
SELECT cc.id FROM civicrm_contact cc 
INNER JOIN civicrm_email ce ON cc.id = ce.contact_id
WHERE cc.first_name = '{$input['first_name']}' AND 
      cc.last_name =  '{$input['last_name']}'  AND 
      ce.email = '{$input['email']}' LIMIT 1";
            $dupeId = CRM_Core_DAO::singleValueQuery($sql);
        } else if ( !$dupeId && !empty( $input['birth_date'] ) ) {
            $sql    = "
SELECT cc.id FROM civicrm_contact cc 
WHERE cc.first_name = '{$input['first_name']}' AND 
      cc.last_name  = '{$input['last_name']}'  AND
      cc.birth_date = '{$input['birth_date']}' LIMIT 1";
            $dupeId = CRM_Core_DAO::singleValueQuery($sql);
        }

        return $dupeId;
    }

    function checkApplicantStatus( $cid , $parentID , $array = false) {
        $taskList = array(
                          'applicant' => true,
                          'school'    => true,
                          'family'    => true,
                          );
        
        $fieldMapper = array( 
                             self::CUSTOM_APPLICANT_TABLE => 
                             array(
                                   'marital_status'       => array( 'op'    => '',
                                                                    'value' => 'IS NOT NULL' ),
                                   'living_status'        => array( 'op'    => '',
                                                                    'value' => 'IS NOT NULL' ),
                                   'correspondence'       => array( 'op'    => '',
                                                                    'value' => 'IS NOT NULL' ),
                                   'billing'              => array( 'op'    => '',
                                                                    'value' => 'IS NOT NULL' ),
                                   'financial_aid '       => array( 'op'    => '',
                                                                    'value' => 'IS NOT NULL' ),
                                   'applying_grade '      => array( 'op'    => '',
                                                                    'value' => 'IS NOT NULL' ),
                                   'year '                => array( 'op'    => '',
                                                                    'value' => 'IS NOT NULL' ),
                                   ),
                             self::CUSTOM_SCHOOL_TABLE    => 
                             array( 
                                   'current_school'      => array( 'op'    => '',
                                                                   'value' => 'IS NOT NULL' ),
                                   'current_grade'       => array( 'op'    => '',
                                                                   'value' => 'IS NOT NULL' ),
                                   'state_id'            => array( 'op'    => '',
                                                                   'value' => 'IS NOT NULL' ),
                                   'country_id'          => array( 'op'    => '',
                                                                   'value' => 'IS NOT NULL' ),
                                   'attended_from'       => array( 'op'    => '',
                                                                   'value' => 'IS NOT NULL' ),
                                   'attended_to'         => array( 'op'    => '',
                                                                   'value' => 'IS NOT NULL' ),
                                   ),
                             self::CUSTOM_FAMILY_TABLE    => 
                             array( 
                                   'relationship_name'    => array( 'op'    => '',
                                                                    'value' => 'IS NOT NULL' ),
                                   'employer'             => array( 'op'    => '',
                                                                    'value' => 'IS NOT NULL' ),
                                   'occupation'           => array( 'op'    => '',
                                                                    'value' => 'IS NOT NULL' ),
                                    ),
                              );
        
        //Applicant
        $query  = "SELECT COUNT(*) FROM ".self::CUSTOM_APPLICANT_TABLE." WHERE entity_id = ".$cid;
        $query  = self::whereClause( $query, $fieldMapper[self::CUSTOM_APPLICANT_TABLE] );
        $result = CRM_Core_DAO::singleValueQuery($query);
        if ( $result < 1) {
            $taskList['applicant'] = false;
        }
        
        // School 
        $query  = "SELECT COUNT(*) FROM ".self::CUSTOM_SCHOOL_TABLE." WHERE entity_id = ".$cid;
        $query  = self::whereClause( $query, $fieldMapper[self::CUSTOM_SCHOOL_TABLE] );
        $result = CRM_Core_DAO::singleValueQuery($query);
        if ( $result < 1 ) {
            $taskList['school'] = false;
        }
        
        // Family
        $query  = "SELECT COUNT(*) FROM ".self::CUSTOM_FAMILY_TABLE." WHERE entity_id = ".$parentID;
        $query  = self::whereClause( $query, $fieldMapper[self::CUSTOM_FAMILY_TABLE] );
        $result = CRM_Core_DAO::singleValueQuery($query);
        if ( $result  < 1 ) {
            $taskList['family'] = false;
        }        
        if ( $array ) {
            return $taskList;
        }
        return ( $taskList['family'] && $taskList['applicant'] && $taskList['school'] ? true : false );
    }
    
    
    function whereClause( $query, $mapper ) {
        $whereClause = array( );
        
        foreach( $mapper as $field => $values ) {
            $whereClause[ ] = "{$field} {$values['op']} {$values['value']}";  
        }
        if ( !empty($whereClause) ) {
            $query .= " AND ". implode(" AND ", $whereClause);   
        }
        
        return $query;
    }

    function isPaymentRequired( $applicantId ) {
        $sql            = "SELECT financial_aid FROM " .self::CUSTOM_APPLICANT_TABLE. " WHERE entity_id = %1";
        $contactParams  = array( 1 => array( $applicantId, 'Integer' ) );
        $financialAid   = CRM_Core_DAO::singleValueQuery( $sql ,$contactParams );
        return !$financialAid;
    }

    function getPaymentDetails( $applicantId ) {
        // get payment id of applicant
        $sql            = "SELECT payment_id FROM " .self::CUSTOM_APPLICANT_TABLE. " WHERE entity_id = %1";
        $contactParams  = array( 1 => array( $applicantId, 'Integer' ) );
        $contributionId = CRM_Core_DAO::singleValueQuery( $sql ,$contactParams );

        $defaults = array();
        if ( $contributionId ) {
            require_once 'CRM/Contribute/PseudoConstant.php';
            $status = CRM_Contribute_PseudoConstant::contributionStatus();

            require_once 'CRM/Contribute/BAO/Contribution.php';
            $params = array( 'id' => $contributionId );
            CRM_Contribute_BAO_Contribution::retrieve( $params , $defaults, $ids );

            $defaults['status'] = $status[$defaults['contribution_status_id']];
        }
        return $defaults;
    }
 
    function getActivityDetails( $actTypeId, $targetContactId , $futureOnly = true ) {
        $sql = "
SELECT at.id, at.activity_id, act.activity_date_time ,op.label
FROM  civicrm_activity_contact as at
INNER JOIN civicrm_activity act ON act.activity_type_id = %1
LEFT JOIN civicrm_option_value as op ON op.value = act.activity_type_id
WHERE at.contact_id = %2 AND at.record_type_id = 3
AND at.activity_id = act.id 
AND op.option_group_id = %3
";
        if ( $futureOnly ) {
            $sql .= "AND act.activity_date_time > NOW()";
        }
        $params = array( 1 => array($actTypeId, 'Integer'),
                         2 => array($targetContactId,'Integer'),
                         3 => array('2','Integer'));

        $dao    = CRM_Core_DAO::executeQuery( $sql, $params );
        $target = array( );

        while( $dao->fetch( ) ) {
            $target['id']                 = $dao->id;
            $target['label']              = $dao->label;
            $target['activity_id']        = $dao->activity_id;
            $target['activity_date_time'] = $dao->activity_date_time;
            
        }          
    
        return $target;
    }

    function isApplicationFrozen( $applicantId ) {
        $sql            = "SELECT is_app_frozen FROM " .self::CUSTOM_APPLICANT_TABLE. " WHERE entity_id = %1";
        $contactParams  = array( 1 => array( $applicantId, 'Integer' ) );
        $appComplete    = CRM_Core_DAO::singleValueQuery( $sql ,$contactParams );
        return  $appComplete ;
    }
}
