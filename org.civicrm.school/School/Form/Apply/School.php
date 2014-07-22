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

require_once 'School/Form/Apply.php';

class School_Form_Apply_School extends School_Form_Apply {

    function preProcess() {
        parent::preProcess();
        
        $schoolTableId = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                                      School_Form_Apply::CUSTOM_SCHOOL_TABLE, 'id', 'table_name' );
        $groupTree     = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                                                            $this,
                                                            $this->_applicantId,
                                                            $schoolTableId,
                                                            'Applicant' );
        $this->_groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree( $groupTree , 1 , $this);
        foreach ( $this->_groupTree as $gid => $groupTree ) {
            foreach ( $groupTree['fields'] as $fid => $fieldTree ) {
                $this->_detailMapper[$fieldTree['column_name']] = $fieldTree["element_name"];
            }
        }
    }

     function setDefaultValues( ) {
        $defaults = array( );

        if ( isset( $this->_applicantId ) ) {
            foreach ( $this->_groupTree as $groupId => $groupValue ) {
                if ( array_key_exists('fields',$groupValue  ) ) {
                    foreach ( $groupValue['fields'] as $key => $value ) {
                        if ( in_array($value['column_name'], array('attended_from','attended_to')) ) { 
                          if( !empty($value['element_value']) ) {
                                list( $defaults[$value['column_name']] ) = 
                                    CRM_Utils_Date::setDateDefaults($value['element_value']);                            }
                        } else {    
                          if( !empty($value['element_value']) ) {
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
        $this->add( 'text', 'current_school', ts('Current School:'), null, true);
        $this->add( 'text', 'current_grade', ts('Current Grade:'), null, true );
        $this->addDate('attended_from', ts('From:'), true);
        $this->addDate('attended_to', ts('To:'), true);
        $this->add('text', 'address', ts('School Address:'),array( 'size' => 40, 'maxlength' => 60 ));
        $this->add( 'text', 'city', ts('City:') );
        $this->add( 'select', 'country_id', ts('Country:'), array('' => '- Select -') +  CRM_Core_PseudoConstant::country( ), true ); 
        $this->add( 'select', 'state_id', ts('State:'), array('' => '- Select -') +  CRM_Core_PseudoConstant::stateProvince( ), true );
        $this->add( 'text', 'zip', ts('Zip:') );
        $this->addRule( 'zip', ts('Please enter valid zip'), 'positiveInteger' );
        
        $this->add( 'text', 'phone', ts('School Phone Number:') );
        $this->addRule("phone", ts('Phone number is not valid.'), 'regex', "/^\d{3}-?\d{3}-?\d{4}$/");

        $this->add( 'text', 'name_of_head', ts('Name of Head of school:') );
        $this->add( 'textarea', 'other', ts('Other School Attended:') ,array('rows'=> 4,'cols' => 50 ));
        $this->addFormRule( array( 'School_Form_Apply_School', 'formRule' ), $this );
        parent::buildQuickForm( );
    }

    function formRule( $fields, $files, $form ) {
        $errors       = array( );
        $attendedFrom = date('Y\-m\-d H\:i\:s', strtotime($fields['attended_from']));
        $attendedTo   = date('Y\-m\-d H\:i\:s', strtotime($fields['attended_to']));
        $currentDate  = date('Y\-m\-d H\:i\:s', strtotime("now"));
        
        if ( $attendedTo <=  $attendedFrom ) {
            $errors['attended_to'] = ts('Attended to date should be greater then Attended from date');
        }
        return $errors;
    }
    
    function postProcess( ) {
        $params = $this->controller->exportValues( $this->_name );
        $params['attended_from'] = CRM_Utils_Date::processDate($params['attended_from'],null,false,'Ymd');
        $params['attended_to']   = CRM_Utils_Date::processDate($params['attended_to'],null,false,'Ymd');
        $customParams = $customFields  = array( );
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
