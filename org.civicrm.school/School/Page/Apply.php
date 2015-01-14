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


class School_Page_Apply extends CRM_Core_Page {

    function run( ) {
        require_once 'School/Form/Apply.php';
        require_once 'School/Form/Family.php';
        require_once 'api/v3/Relationship.php';
        

        $phoneTypes         = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
        $locationTypes      = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
        $gender             = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
        $this->_applicantId = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this, true, 0, 'REQUEST' );

        $session = CRM_Core_Session::singleton();
        
        $this->_parentId    = CRM_Utils_Request::retrieve( 'pid', 'Positive', $this, false, 
                                                           $session->get( 'userID'), 'REQUEST' );
  $relTypeParams = array(
                               'name_a_b' => 'Child of',
                               );
        $default = null;
        $relType = CRM_Contact_BAO_RelationshipType::retrieve($relTypeParams,$default);
        $relationParams = array(
				'version' => 3,
				'contact_id_a' => $this->_applicantId,
				'relationship_type_id' => $relType->id,
				);
        $relationships = civicrm_api( 'relationship','get',$relationParams ) ;    
        $familyFields  = array( 'phone', 'email', 'address' ,'display_name');

        if ( !$relationships['is_error'] && is_array($relationships['values']) ) {

            $this->_relDataId = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                                             School_Form_Apply::CUSTOM_FAMILY_TABLE, 'id', 'table_name' );
            
            $this->_relTypeId = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_RelationshipType', 
                                                             'Child Of', 'id', 'name_a_b' );
            
            foreach ( $relationships['values'] as $rid => $relationship ) {
                
                $contactParams['id'] = $contactParams['contact_id'] = $relationship['contact_id_b'];
                $contactParams['noRelationships'] = $contactParams['noNotes'] = $contactParams['noGroups'] = true;
                CRM_Contact_BAO_Contact::retrieve( $contactParams, $contactData );
                
                 foreach ( $familyFields as $fieldName ) {
                    $values['family'][$rid][$fieldName] = CRM_Utils_Array::value( $fieldName, $contactData, array( ) );
                    if ( $fieldName == 'display_name' ) {
                        $values['family'][$rid]['displayname'] = $values['family'][$rid]['display_name'];
                    }
                    
                    if ( $fieldName == 'email' ) {
                        $values['family'][$rid]['email_display'] = $values['family'][$rid]['email'][1]['email'];
                    } if ( $fieldName == 'address' ) {
                        $values['family'][$rid]['address_display'] = $values['family'][$rid]['address'][1]['display'];
                    } else {
                        $phones = array( );
                        foreach ( $values['family'][$rid]['phone'] as $dontCare => $phoneInfo ) {
                            $phone = $phoneInfo['phone'];
                            if ( ! empty( $phone ) ) {
                                if ( ! empty( $phoneInfo['phone_type_id'] ) ) {
                                    $phone .= "&nbsp;({$phoneTypes[$phoneInfo['phone_type_id']]})";
                                }
                                $phones[] = $phone;
                            }
                        }   
                        $values['family'][$rid]['phone_display'] = implode( ',', $phones );
                    }
                }

                 $relGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                                                                    $this,
                                                                    $contactParams['contact_id'],
                                                                    $this->_relDataId,
                                                                    'Applicant_Parent');
                 
                 $familyValues     = CRM_Core_BAO_CustomGroup::buildCustomDataView( $this, $relGroupTree );                 
                 foreach ( $familyValues[$this->_relDataId] as $familyID => $familyValue ) {
                     
                     $familyDetails = array( );
                     
                     foreach ( $familyValue['fields'] as $fkey => $fvalue ) {                
                         if ( ! empty( $fvalue['field_value'] ) ) {
                             $familyDetails[] = array( 'title' => $fvalue['field_title'],
                                                       'value' => $fvalue['field_value'] );
                         }
                     }
                     
                     if ( ! empty( $familyDetails ) ) {
                         $familyValues[$this->_relDataId][$familyID]['fdetails']    = $familyDetails;
                         
                     }
                     
                     $values['family'][$rid]['info']  = $familyValues[$this->_relDataId][$familyID];
                 }
            }
        }
        
        $status = School_Form_Apply::checkApplicantStatus( $this->_applicantId , $this->_parentId );
        
        $values['status'] =  ( $status ? 'Completed':'Incomplete' );

        $applicantID  = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                                     School_Form_Apply::CUSTOM_APPLICANT_TABLE, 'id', 'table_name' );

        $schoolID     = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                                     School_Form_Apply::CUSTOM_SCHOOL_TABLE, 'id', 'table_name' );

        
        $childrenID   = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                                     School_Form_Apply::CUSTOM_OTHER_CHILDREN_TABLE, 'id', 'table_name' );


        //applicant group tree
        $applicantGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                                                                 $this,
                                                                 $this->_applicantId,
                                                                 $applicantID,
                                                                 'Applicant');
        //school group tree        
        $schoolGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                                                              $this,
                                                              $this->_applicantId,
                                                              $schoolID,
                                                              'Applicant');
        //other children group tree
        $childrenGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                                                                $this,
                                                                $this->_applicantId,
                                                                $childrenID,
                                                                'Applicant');
        
        
        $infoValues     = CRM_Core_BAO_CustomGroup::buildCustomDataView( $this, $applicantGroupTree );
        
        $schoolValues   = CRM_Core_BAO_CustomGroup::buildCustomDataView( $this, $schoolGroupTree );
        
        $childrenValues = CRM_Core_BAO_CustomGroup::buildCustomDataView( $this, $childrenGroupTree );
        
        
        $applicantFields  = array('Appying for Year','Applying for Grade');
        $addtionalFields  = array(
                                  'Professional Support',
                                  'Needs of Child',
                                  'Educational Environment',
                                  'Child Character',
                                  'Are you requesting Financial Aid?',
                                  'How do you hear about IDEAL?',
                                  );
        
        $detailFields     = array( 'first_name', 'middle_name', 'last_name','nick_name','birth_date','gender_id');
        $params['id']     =  $this->_applicantId ;
        
        CRM_Contact_BAO_Contact::retrieve( $params, $data ); 
        
        //applicant info       
        foreach ( $infoValues[$applicantID] as $infoID => $infoValue ) {
            $applicantDetails = array( );
            foreach ( $infoValue['fields'] as $fieldID => $fieldValue ) {
                if ( in_array($fieldValue['field_title'], 
                              $applicantFields ) ) {
                    if ( ! empty( $fieldValue['field_value'] ) ) {
                        $applicantDetails[] = array( 'title' => $fieldValue['field_title'],
                                                     'value' => $fieldValue['field_value'] );
                    }
                }
                
                if ( in_array($fieldValue['field_title'], 
                              $addtionalFields ) ) {
                    if ( ! empty( $fieldValue['field_value'] ) ) {
                        $additionalDetails[] = array( 'title' => $fieldValue['field_title'],
                                                      'value' => $fieldValue['field_value'] );
                    }
                }
            }
            if ( ! empty( $applicantDetails ) || ! empty( $additionalDetails )) {
                $infoValues[$applicantID][$infoID]['details']    = $applicantDetails;
                $infoValues[$applicantID][$infoID]['adddetails'] = $additionalDetails;
            }
            
            $values['applicant']['info']  = $infoValues[$applicantID][$infoID];
            $values['additional']['info'] = $infoValues[$applicantID][$infoID];
        }
        
        foreach( $detailFields as $fieldName ) {
            if ( $fieldName == 'gender_id') {
                $values['applicant']['info'][$fieldName] = $gender[CRM_Utils_Array::value( $fieldName, $data ,array())];
            } else {
                $values['applicant']['info'][$fieldName] = CRM_Utils_Array::value( $fieldName, $data ,array());
            }
        }
        
        // schoool info        
        foreach ( $schoolValues[$schoolID] as $schID => $schoolValue ) {
            $schoolDetails = array( );
            foreach ( $schoolValue['fields'] as $key => $value ) {
                if ( ! empty( $value['field_value'] ) ) {
                    if ( $value['field_title'] == 'Country' ) {
                        $schoolDetails[] = array( 'title' => $value['field_title'],
                                                  'value' => CRM_Core_PseudoConstant::country($value['field_value']) ); 
                    } elseif ( $value['field_title'] == 'State') {
                        $schoolDetails[] = array( 'title' => $value['field_title'],
                                                  'value' => CRM_Core_PseudoConstant::stateProvince($value['field_value']) ); 
                    } else {
                        $schoolDetails[] = array( 'title' => $value['field_title'],
                                                  'value' => $value['field_value'] );
                    }
                }
            }
            if ( ! empty( $applicantDetails ) ) {
                $schoolValues[$schoolID][$schID]['details'] = $schoolDetails;               
            }
            
            $values['school']['info'] = $schoolValues[$schoolID][$schID];
        }
        
        
        foreach ( $childrenValues[$childrenID] as $childID => $childValue ) {
            $childrenDetails = null;
            foreach ( $childValue['fields'] as $chldkey => $chldvalue ) {                
                if ( ! empty( $value['field_value'] ) ) {
                    $childrenDetails[] = array( 'title' => $chldvalue['field_title'],
                                                'value' => $chldvalue['field_value'] );
                }
            }
            if ( ! empty( $childrenDetails ) ) {
                $childrenValues[$childrenID][$childID]['cdetails'] = $childrenDetails;
                
            }
            $values['otherchildren']['info'] = $childrenValues[$childrenID][$childID];
            
        }
        $this->assign('values',$values);
        
        return parent::run( );
    }
}
