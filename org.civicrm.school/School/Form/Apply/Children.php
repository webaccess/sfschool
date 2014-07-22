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

class School_Form_Apply_Children extends School_Form_Apply {
    
    const
        NUM_CHILDREN = 3;
    
    protected $_defaults = array();

    protected $_detailMapper = array();

    protected $_detailGroupTree = array();

    function preProcess( ) {
        parent::preProcess();

        $this->_childCustomTable = 
            CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                         School_Form_Apply::CUSTOM_OTHER_CHILDREN_TABLE, 'id', 'table_name' );

        $childGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                                                             $this,
                                                             $this->_applicantId,
                                                             $this->_childCustomTable,
                                                             'Applicant');
        for ( $count = 1 ; $count <= self::NUM_CHILDREN ; $count++ ) {
            $this->_detailGroupTree[$count] = 
                CRM_Core_BAO_CustomGroup::formatGroupTree( $childGroupTree, $count, $this );
            foreach(  $this->_detailGroupTree[$count] as $customID => $customValue ) {
                foreach ( $customValue['fields'] as $fid => $fieldTree ) {
                    $this->_detailMapper[$fieldTree['column_name'] . '_' . $count] = $fieldTree["element_name"];
                    if ( in_array($fieldTree['column_name'] , array('dob') ) ) {
                      if(!empty($fieldTree['element_value'])){
                        $this->_defaults[$fieldTree['column_name'] . '_' . $count] = 
                          CRM_Utils_Date::customFormat($fieldTree['element_value'],'%Y-%m-%d');}
                    } else {
                      if(!empty($fieldTree['element_value'])){
                        $this->_defaults[$fieldTree['column_name'] . '_' . $count] = $fieldTree['element_value'];
                      }
                        
                    }
                }
            }
        }
    }
    
    function setDefaultValues( ) {
        return $this->_defaults;
    }
    
    function buildQuickForm( ) {
        for ( $count = 1; $count <= self::NUM_CHILDREN; $count++ ) {
            foreach ( $this->_detailGroupTree[$count] as $gId => $groupTree ) {
                foreach ( $groupTree['fields'] as $fId => $fieldTree ) {
                    CRM_Core_BAO_CustomField::addQuickFormElement($this, $fieldTree['column_name'] . '_' . $count, 
                                                                  $fieldTree['id'], false, $fieldTree['is_required']);
                    $fieldsNames[$count][] = $fieldTree['column_name'] . '_' . $count;
                }
            }
        }
        $this->assign( 'fieldNames', $fieldsNames );  
        
        parent::buildQuickForm( );
    }
    
    function postProcess() {
        $params = $this->controller->exportValues( $this->_name );

        $params['dob_1'] = CRM_Utils_Date::processDate($params['dob_1'],null,false,'Ymd'); 
        $params['dob_2'] = CRM_Utils_Date::processDate($params['dob_2'],null,false,'Ymd'); 
        $params['dob_3'] = CRM_Utils_Date::processDate($params['dob_3'],null,false,'Ymd');
        $customParams = $customFields = array();
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