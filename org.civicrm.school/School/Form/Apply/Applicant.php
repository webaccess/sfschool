<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.2                                                |
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

require_once 'api/api.php';
require_once 'School/Form/Apply.php';
class School_Form_Apply_Applicant extends School_Form_Apply {
    
    protected $_detailMapper = array( );
   
    function preProcess( ) { 
       parent::preProcess();
        //set startoffset and endoffset for year field
        $prefer['name'] = 'custom';
        CRM_Core_BAO_PreferencesDate::retrieve( $prefer, $defaults );
        $this->_startOffset = $defaults['start'];
        $this->_endOffset   = $defaults['end'];

        $this->_parentRelDataId = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                                               self::RELATION_TABLE, 'id', 'table_name' );
        $this->_relTypeId       = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_RelationshipType', 
                                                               'Child Of', 'id', 'name_a_b'     );
        // applicant custom
        $customTableId = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                                      School_Form_Apply::CUSTOM_APPLICANT_TABLE, 'id', 'table_name' );
        
        $groupTree     = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                                                            $this,
                                                            $this->_applicantId,
                                                            $customTableId,
                                                            'Applicant' );
     
        $this->_detailGroupTree = CRM_Core_BAO_CustomGroup::formatGroupTree( $groupTree, 1, $this );
        foreach ( $this->_detailGroupTree as $gid => $groupTree ) {
            foreach ( $groupTree['fields'] as $fid => $fieldTree ) {
                if ( in_array($fieldTree['column_name'], array('applying_grade',
                                                               'year' )) ) {
                    $this->_detailMapper[$fieldTree['column_name']] = $fieldTree["element_name"];
                }
            }
        }

        // relationship custom
        $relGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Relationship',
                                                           $this,
                                                           null,
                                                           $this->_parentRelDataId,
                                                           $this->_relTypeId );
        $this->_relGroupTree = CRM_Core_BAO_CustomGroup::formatGroupTree( $relGroupTree, 1, $this );
        foreach ( $this->_relGroupTree as $gid => $groupTree ) {
            foreach ( $groupTree['fields'] as $fid => $fieldTree ) {
                $this->_relationMapper[$fieldTree['column_name']] = $fieldTree["element_name"];
            }
        }
    }
    
    function setDefaultValues( ) {
        $defaults = array( );
        
        if ( isset( $this->_applicantId ) ) {
            $params = array(
                            'version' => 3,
                            'contact_id' => $this->_applicantId );   
                   $result = civicrm_api( 'contact','get',$params );
                   $result['values'][$result['id']]['birth_date']  =  CRM_Utils_Date::setDateDefaults($result['values'][$result['id']]['birth_date'],null,'m/d/Y');
                   $result['values'][$result['id']]['birth_date'] =  $result['values'][$result['id']]['birth_date'][0];   
                   $dataFields = array('first_name', 'last_name' , 'middle_name' , 'nick_name','gender_id','birth_date');
                   foreach ( $result['values'][$result['id']] as $dataKey => $dataVal ) {
                     if ( ! in_array($dataKey, $dataFields) ) {
                       unset($result['values'][$result['id']][$dataKey]);
                     }
                   }
                   
                $defaults = $result['values'][$result['id']];

               foreach ( $this->_detailGroupTree as $groupId => $groupValue ) {
              if ( array_key_exists('fields',$groupValue  ) ) {
                foreach ( $groupValue['fields'] as $key => $value ) {
                  if( !empty($value['element_value']) ) {
                    if ( in_array($value['column_name'], array('year')) ) {
                      $value['element_value'] = date("Y", strtotime($value['element_value']));
                      $defaults[$value['column_name']]  =   $value['element_value'];
                      CRM_Utils_Date::setDateDefaults($value['element_value'],null,'yy');     
                    } else {
                       $defaults[$value['column_name']] = $value['element_value'];
                      
                    }
                    
                  }
                }   
              }
            }
        } 
        return $defaults;
    }

    function buildQuickForm( ) {
        $this->add( 'text', 'first_name', ts('Applicants First Name:') ,'',true);
        $this->add( 'text', 'middle_name', ts('Applicants Middle Name:') );
        $this->add( 'text', 'last_name', ts('Applicants Last Name:') ,'',true);
        $this->add( 'text', 'nick_name', ts('Prefered Name/Nickname:') );
        $this->add( 'select', 'gender_id', ts('Gender:') ,array( '' => ts( '- select -' ) ) +
                    CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id'), true );

        //get name/value pair for grades
        $params = 'grade_20090727222519';
        require_once 'CRM/Core/OptionGroup.php';
        $grades = CRM_Core_OptionGroup::values( $params );
        asort($grades);
        // remove unwanted grades
        unset($grades['PK4 S'],$grades['PK4 N'],$grades['PK3 S'],$grades['PK3 N'],$grades['K S'],$grades['K N']);
        $this->add('select', 'applying_grade', ts('Applying for Grade:'), array('' => '- Select -') +  $grades, true );
        
        // build year range $this
        $years = range( date('Y') - $this->_endOffset  ,date('Y') + $this->_endOffset );
        foreach ( $years as $k => $v ) {
            $forYears[$v] = $v;
        }
                
        $this->add('select', 'year', ts('For Year:'), array('' => '- Select -') +  $forYears, true );
        $this->addDate('birth_date', ts('Date of Birth:') );
        $this->addRule('birth_date', ts('Date of Birth is Required field'), 'required');
        $this->add( 'text', 'current_school', ts('Current School:') );
 
        parent::buildQuickForm( );
    }

    function postProcess() { 
            
        $session =& CRM_Core_Session::singleton( );
        $params  = $this->controller->exportValues( $this->_name );
        
        //process birth date
        $params['birth_date'] = CRM_Utils_Date::processDate( $params['birth_date'] );

        // 1. check for duplicate contacts 
        require_once 'CRM/Dedupe/Finder.php';
        $duplicateParams = CRM_Dedupe_Finder::formatParams( $params, 'Individual' );
        $duplicateParams['civicrm_relationship'] = array( 'contact_id_b'         => $session->get('userID'),
                                                          'relationship_type_id' => $this->_relTypeId );
        if ( $duplicateId = $this->findDupe( $duplicateParams  ) ) {
            $params['contact_id'] = $duplicateId;
        } 
        
        // 2. create applicant contact
        require_once 'CRM/Contact/BAO/Contact.php';
        $params['contact_sub_type'] = 'Applicant';
        $this->_applicantId = CRM_Contact_BAO_Contact::createProfileContact( $params, CRM_Core_DAO::$_nullArray );
        $this->set( 'cid',    $this->_applicantId );

        // 3. create applicant-parent relationship if doesn't already exist        
        
        $relTypeParams = array(
                               'name_a_b' => 'Child of',
                               );
        $default = null;
        $relType = CRM_Contact_BAO_RelationshipType::retrieve($relTypeParams,$default);
        $relationParams = array(
				'version' => 3,
				'contact_id_a' => $this->_applicantId,
        'contact_id_b' => $session->get('userID'),
				'relationship_type_id' => $relType->id,
				);
        $relationships = civicrm_api( 'relationship','get',$relationParams );
        
        if ( empty($relationships['values']) ) { 
       
            $relParams = array( 
                               'version' => '3',
                               'contact_id_a'         => $this->_applicantId,
                               'contact_id_b'         => $session->get('userID'),
                               'relationship_type_id' => $this->_relTypeId,
                               'start_date'           => date('Ymd'),
                               'is_permission_b_a'    => 1,
                               'is_active'            => 1,
                                );
            $relationship = civicrm_api( 'relationship','create',$relParams);

            
            // create indexes for every new relationship
            $key = CRM_Core_BAO_CustomField::getKeyID($this->_relationMapper['parent_index']);
            $customParams = array( "custom_{$key}-1" => 1 );
            // set index to 1
            $customFields = CRM_Core_BAO_CustomField::getFields( 'Relationship', false, false, $this->_relTypeId );
       
          
            CRM_Core_BAO_CustomValueTable::postProcess( $customParams,
                                                        $customFields,
                                                        'civicrm_relationship',
                                                        $relationship['id'],
                                                        'Relationship' );   
        }

        // 4. Add / Update any custom data
        $customParams = $customFields = array( );
        foreach( $this->_detailMapper as $colName => $elementName ) {
            if ( CRM_Utils_Array::value( $colName, $params ) ) {
                $customParams[$elementName] = $params[$colName];
            }
        }        
        CRM_Core_BAO_CustomValueTable::postProcess( $customParams,
                                                    $customFields,
                                                    'civicrm_contact',
                                                    $this->_applicantId,
                                                    'Individual' );
        parent::endPostProcess( );
    }
}
