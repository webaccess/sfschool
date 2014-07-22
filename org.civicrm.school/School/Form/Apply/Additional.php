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
require_once 'CRM/Core/BAO/CustomField.php';

class School_Form_Apply_Additional extends School_Form_Apply {
    
    protected $_defaults = array();

    protected $_detailMapper = array();

    protected $_groupTree    = array();

    function preProcess() {
        parent::preProcess();
        
        require_once 'CRM/Core/BAO/CustomGroup.php';
        $this->_additionalInformation =  
            CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                         School_Form_Apply::CUSTOM_APPLICANT_TABLE, 'id', 'table_name' );

        $groupTree = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                                                        $this,
                                                        $this->_applicantId,
                                                        $this->_additionalInformation,
                                                        'Applicant');
        $this->_groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree( $groupTree , 1 , $this);
        foreach ( $this->_groupTree as $gid => $groupTree ) {
            foreach ( $groupTree['fields'] as $fid => $fieldTree ) {
                if ( in_array($fieldTree['column_name'], array('about',
                                                               'reference',
                                                               'financial_aid',
                                                               'is_agree',
                                                               'child_character',
                                                               'educ_env',
                                                               'professional_support',
                                                               'needs_of_child',
                                                               'is_app_frozen')) ) {            
                    $this->_detailMapper[$fieldTree['column_name']] = $fieldTree["element_name"];
                    $this->_defaults[$fieldTree['column_name']] = $fieldTree['element_value'];
                }
            }
        } 
    }
    
    function setDefaultValues() {
        return $this->_defaults; 
    }
    
    function buildQuickForm( ) {
        //1. build custom data
        foreach ( $this->_groupTree as $gId => $groupTree ) {
            foreach ( $groupTree['fields'] as $fId => $fieldTree ) {
                if ( in_array($fieldTree['column_name'], array('about', 'reference', 'financial_aid',
                                                               'child_character','educ_env','professional_support','needs_of_child')) ) {
                    if ($fieldTree['column_name'] =='child_character' || $fieldTree['column_name'] =='educ_env'  ||
                        $fieldTree['column_name'] =='professional_support' || $fieldTree['column_name'] =='needs_of_child'  ) {
                        
                        $fieldTree['column_name'] ==  'child_character' ? $maxCharacterCount=1000 : $maxCharacterCount = 500;
                        if(empty($circumstances['attributes'] )){
                          $circumstances['attributes'] = '';
                        }
                          $circumstances['attributes'] .= " onkeyup=wordcount(\"{$fieldTree['column_name']}\",$maxCharacterCount);
                                                          oninput=wordcount(\"{$fieldTree['column_name']}\",$maxCharacterCount);
                                                          cols=40, rows=5;";
                      
                        
                        $this->add( 'textarea', $fieldTree['column_name'], $fieldTree['column_name'],
                                    $circumstances['attributes'] , "true" );
                        $this->add('text', "counter_{$fieldTree['column_name']}", ts( 'Characters Left:' ), 
                                   array('readonly','class="four"'));
                    } else {
                        CRM_Core_BAO_CustomField::addQuickFormElement($this, $fieldTree['column_name'], 
                                                                      $fieldTree['id'], false, $fieldTree['is_required']);
                    }
                    $fieldNames[] = $fieldTree['column_name'];
                }
            }
        }
        $this->assign( 'fieldNames', $fieldNames );
          
        $this->add( 'checkbox','is_app_frozen',ts(''));
        $this->addFormRule( array( 'School_Form_Apply_Additional', 'formRule' ), $this );
        parent::buildQuickForm( );
    }
    
     function formRule( $fields, $files, $form ) {         
        $errors = array( );
        if ( strlen($fields['child_character']) > 1000 ) {
            $errors['child_character'] = ts('Child Character:Please enter only 1000 charcters');
        } else if ( strlen($fields['educ_env']) > 500 ) {
            $errors['educ_env'] = ts('Educational Environment:Please enter only 500 charcters');
        } else if ( strlen($fields['professional_support']) > 500 ) {
            $errors['professional_support'] = ts('professional support:Please enter only 500 charcters');
        } else if ( strlen($fields['needs_of_child']) > 500 ) {
            $errors['needs_of_child'] = ts('Other Information:Please enter only 500 charcters');
        }

        return $errors;
    }


    function postProcess() {
        $params = $this->controller->exportValues($this->_name);
        
        require_once 'CRM/Core/BAO/CustomValueTable.php';
        
        $customParams = $customFields = array( );
        foreach( $this->_detailMapper as $colName => $elementName ) {
            if ( array_key_exists( $colName , $params ) ) {
                $customParams[$elementName] = $params[$colName];
             } 
        }

        CRM_Core_BAO_CustomValueTable::postProcess( $customParams,
                                                    $customFields,
                                                    'civicrm_contact',
                                                    $this->_applicantId,
                                                    'Applicant' );
        
        //Redirect user to dashboard
        if ( $this->_applicantId ) {
            $isAppComplete = School_Form_Apply::checkApplicantStatus( $this->_applicantId, $this->_parentId );
            $applicantDisplayName = CRM_Contact_BAO_Contact::displayName( $this->_applicantId );
            $editUrl = CRM_Utils_System::url("civicrm/school/apply/applicant", "reset=1&cid={$this->_applicantId}");
            $applicantEdit = "<a href =$editUrl>$applicantDisplayName</a>";
            if ( $isAppComplete && School_Form_Apply::isPaymentRequired( $this->_applicantId ) ) {
                $payment = School_Form_Apply::getPaymentDetails( $this->_applicantId );
                if ( empty( $payment ) ) {
                    $contributionUrl = CRM_Utils_System::url( "civicrm/contribute/transact",
                                                              "reset=1&id=" . School_APPLICATION_CONTRIBUTION_PAGE_ID . "&appid=$this->_applicantId") ;
                    $makeContribution = "<a href =$contributionUrl>submit payment</a>";
                    CRM_Core_Error::statusBounce( ts(' Congratulations! You\'ve successfully completed your application for %1.
                                                       At any time, you can edit your Application for %2 from your Dashboard.
                                                       You can %3 anytime from your dashboard.', array( 1 =>  $applicantDisplayName,
                                                                                                        2 =>  $applicantEdit,
                                                                                                        3 =>  $makeContribution ) ) );
                } 
            } else if ( ! $isAppComplete ) {
                CRM_Core_Session::setStatus(ts(' Please make sure all sections of your application are complete for %1.', 
                                               array ( 1 => $applicantEdit ) ) );
            } else {
                CRM_Core_Session::setStatus(ts('Congratulations! You\'ve successfully completed your application for %1.
                                                At any time, you can edit your Application for %2 from your Dashboard.', 
                                               array ( 1 => $applicantDisplayName, 2 => $applicantEdit )));
            }
        }
        $redirectUrl = CRM_Utils_System::url( 'civicrm/school/apply', "reset=1" );
        CRM_Utils_System::redirect( $redirectUrl );
        
        parent::endPostProcess( );
    }
}
