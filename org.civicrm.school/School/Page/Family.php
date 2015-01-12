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
require_once 'CRM/Contact/BAO/Contact.php';
require_once 'CRM/Core/Page.php';

class School_Page_Family extends CRM_Core_Page {

  public $_values;
  public $_phoneTypes;
  public $_locationTypes;

  function commonRun( $studentID ) {
    require_once 'School/Form/Family.php';
    require_once 'api/v3/Relationship.php';

    $this->_phoneTypes    = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $this->_locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

    $this->_studentId = $studentID;

    if ( $this->_studentId ) {
      // make sure _studentId is a student
      require_once 'School/Utils/Query.php';
      $subType = CRM_Contact_BAO_Contact::getContactSubType($this->_studentId);

      // if subType is not student then hide the extended care tab
      if(!in_array('Student',$subType)){
        CRM_Core_Error::fatal( ts( 'The family form is for a Contact of type Student.' ) );
      }
    }

    require_once 'CRM/Contact/BAO/Contact/Permission.php';
    require_once 'CRM/Contact/BAO/Contact.php';

    // check that the current user has permission to see student information
    if ( ! CRM_Contact_BAO_Contact_Permission::allow( $this->_studentId ) ) {
      CRM_Core_Error::fatal( ts( 'Specified user does not have permission to access student record.' ) );
    }

    $this->assign( 'cid', $this->_studentId );
    require_once 'CRM/Core/BAO/CustomGroup.php';

    $values = array( 'household' => array( ),
              'emergency' => array( ),
              'medical'   => array( ),
              'release'   => array( ),
              'diversity' => array( ),
    );

    // parent information
    //get "child of" relationship type id
    $childOfRelTypeId = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_RelationshipType',
                        'Child Of', 'id', 'name_a_b' );
    require_once 'api/api.php';
    $getRelParams = array(
      'contact_id_a' => $this->_studentId,
      'relationship_type_id' => $childOfRelTypeId,
      'version' => 3,
    );
    $relationships = civicrm_api( 'relationship', 'get', $getRelParams );
    $detailFields  = array( 'phone', 'email', 'address' );

    if ( !$relationships['is_error'] && is_array( $relationships['values'] ) ) {
      $this->_relDataId = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup',
                          School_Form_Family::RELATION_TABLE,
                          'id',
                          'table_name' );
      $this->_relTypeId = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_RelationshipType',
                          'Child Of', 'id', 'name_a_b' );
      foreach ( $relationships['values'] as $rid => $relationship ) {
        // get the names of parents
        $getNameParams = array(
          'contact_id' => $relationship['contact_id_b'],
          'version' => 3,
        );
        $getName = civicrm_api( 'contact', 'get', $getNameParams );
        // rebuild relationship array
        $relationship['display_name'] = $getName['values'][$getName['id']]['display_name'];
        $relationship['sort_name'] = $getName['values'][$getName['id']]['sort_name'];
        $values['household'][$rid] = $relationship;
        $params['id'] = $params['contact_id'] = $relationship['contact_id_b'];
        $params['noRelationships'] = $params['noNotes'] = $params['noGroups'] = true;
        CRM_Contact_BAO_Contact::retrieve( $params, $data );

        // collapse the email , phone and address
        foreach ( $detailFields as $fieldName ) {
          $values['household'][$rid][$fieldName] = CRM_Utils_Array::value( $fieldName, $data, array( ) );
          if ( $fieldName == 'email' ) {
            $values['household'][$rid]['email_display'] = $values['household'][$rid]['email'][1]['email'];
          } if ( $fieldName == 'address' ) {
            $values['household'][$rid]['address_display'] = $values['household'][$rid]['address'][1]['display'];
          } else {
            $phones = array( );
            foreach ( $values['household'][$rid]['phone'] as $dontCare => $phoneInfo ) {
              $phone = $phoneInfo['phone'];
              if ( ! empty( $phone ) ) {
                if ( ! empty( $phoneInfo['phone_type_id'] ) ) {
                  $phone .= "&nbsp;({$this->_phoneTypes[$phoneInfo['phone_type_id']]})";
                }
                $phones[] = $phone;
              }
            }
            $values['household'][$rid]['phone_display'] = implode( ',', $phones );
          }
        }

        $relGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Relationship',
                        $this,
                        $rid,
                        $this->_relDataId,
                        $this->_relTypeId );

        $relCustData = CRM_Core_BAO_CustomGroup::buildCustomDataView( $this, $relGroupTree );
        if ( ! empty( $relCustData[$this->_relDataId] ) ) {
          $relInfo = array_pop( $relCustData[$this->_relDataId] );
        }
        foreach ( $relInfo['fields'] as $fieldID => $fieldInfo ) {
          if ( $fieldInfo['field_title'] == 'Counselor Authorization' ) {
            $values['household'][$rid]['counselor_authorization'] =
              $fieldInfo['field_value'] ? 'Yes' : 'No';
          } else if ( $fieldInfo['field_title'] == 'Parent Index' ) {
            $values['household'][$rid]['parent_index'] = $fieldInfo['field_value'];
          }
        }
      }

      // now sort values based on parent_index
      $values['household_new'] = array( );
      foreach ( $values['household'] as $rid => $info ) {
        $values['household_new'][$info['parent_index']] = $info;
      }
      ksort( $values['household_new'] );
      $values['household'] = $values['household_new'];
      unset( $values['household_new'] );
    }

    // emergency information
    $emergencyRelTypeId = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_RelationshipType',
                          'Emergency Contact Of', 'id', 'name_a_b' );
    $getEmergencyRelParams = array(
      'contact_id_a' => $this->_studentId,
      'relationship_type_id' => $emergencyRelTypeId,
      'version' => 3,
    );
    $emergencyRel = civicrm_api( 'relationship', 'get', $getEmergencyRelParams );

    if ( !$emergencyRel['is_error'] && is_array($emergencyRel['values']) && !empty($emergencyRel['values'])) {
      $count = 4;
      $lookup = $cLookup = array( );

      foreach ( $emergencyRel['values'] as $rid => $rFields ) {
        $index = (int ) trim($rFields['description']);
        if ( ! $index ) {
          $index = $count++;
        }
        $lookup[$rid] = $cLookup[$rFields['contact_id_b']] = $index;
        // get the names of emergency contacts
        $getEmergencyNameParams = array(
          'contact_id' => $rFields['contact_id_b'],
          'version' => 3,
        );
        $getEmergencyName = civicrm_api( 'contact', 'get', $getEmergencyNameParams );
        // rebuild rFields array
        $rFields['display_name'] = $getEmergencyName['values'][$getEmergencyName['id']]['display_name'];
        $rFields['sort_name'] = $getEmergencyName['values'][$getEmergencyName['id']]['sort_name'];
        $values['emergency'][$index] = $rFields;
      }
      ksort( $values['emergency'] );
      foreach( $values['emergency'] as $key => $info ) {
        $displayUsers[] = $info['contact_id_b'];
      }

      $contactIDString = implode( ', ', array_keys( $cLookup ) );
      // an emergency contact can have more than one phone
      // lets get all of them
      $query = "
SELECT contact_id, phone, phone_type_id
FROM   civicrm_phone
WHERE  contact_id IN ( $contactIDString )
ORDER BY contact_id, is_primary desc
";
      $dao = CRM_Core_DAO::executeQuery( $query );
      while ( $dao->fetch( ) ) {
        if( in_array( $dao->contact_id, $displayUsers ) ){
          if ( ! isset($values['emergency'][$cLookup[$dao->contact_id]]['phones'] )) {
            $values['emergency'][$cLookup[$dao->contact_id]]['phones'] = array( );
            $values['emergency'][$cLookup[$dao->contact_id]]['phone_display'] = null;
          }
          $values['emergency'][$cLookup[$dao->contact_id]]['phones'][] =
            array( 'phone'         => $dao->phone,
              'phone_type_id' => $dao->phone_type_id );
          if ( ! empty( $dao->phone ) ) {
            if ( empty( $values['emergency'][$cLookup[$dao->contact_id]]['phone_display'] ) ) {
              $values['emergency'][$cLookup[$dao->contact_id]]['phone_display'] = $dao->phone;
            } else {
              $values['emergency'][$cLookup[$dao->contact_id]]['phone_display'] .= ", {$dao->phone}";
            }
            if ( $dao->phone_type_id ) {
              $values['emergency'][$cLookup[$dao->contact_id]]['phone_display'] .=
                "&nbsp;({$this->_phoneTypes[$dao->phone_type_id]})";
            }
          }
        }
      }

      $entityIDString = implode( ',', array_keys( $emergencyRel['values'] ) );
      $query = "
SELECT entity_id, relationship_name
FROM " . School_Form_Family::EMERGENCY_REL_TABLE . "
WHERE entity_id IN ( $entityIDString )
";
      $dao   = CRM_Core_DAO::executeQuery( $query );
      while( $dao->fetch( ) ) {
        $values['emergency'][$lookup[$dao->entity_id]]['relationship_name'] = $dao->relationship_name;
      }
    }

    // medical information
    $medDetailId = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup',
                   School_Form_Family::MEDICAL_DETAILS_TABLE, 'id', 'table_name' );
    $medInfoId   = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup',
                   School_Form_Family::MEDICAL_INFO_TABLE, 'id', 'table_name' );
    $relTypeId   = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_RelationshipType',
                   'Child Of', 'id', 'name_a_b' );
    $relDataId   = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup',
                   School_Form_Family::RELATION_TABLE, 'id', 'table_name' );
    $detailGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Contact',
                       $this,
                       $this->_studentId,
                       $medDetailId );
    $medValues = CRM_Core_BAO_CustomGroup::buildCustomDataView( $this, $detailGroupTree );
    if ( ! empty( $medValues[$medDetailId] ) ) {
      // format it for easy printing
      foreach ( $medValues[$medDetailId] as $medID => $medValue ) {
        $detailName = $detailDesc = null;
        foreach ( $medValue['fields'] as $fieldID => $fieldValue ) {
          if ( $fieldValue['field_title'] == 'Medical Type' ) {
            if( isset( $fieldValue['field_value'] ) ) {
              $detailName .= "{$fieldValue['field_value']}: ";
            }
          } else if ( $fieldValue['field_title'] == 'Name' ) {
            if( isset( $fieldValue['field_value'] ) ) {
              $detailName .= $fieldValue['field_value'];
            }
          } else {
            if( isset( $fieldValue['field_value'] ) ) {
              $detailDesc = $fieldValue['field_value'];
            }
          }
        }
        $medValues[$medDetailId][$medID]['medical_type'] = $detailName;
        $medValues[$medDetailId][$medID]['description' ] = $detailDesc;
      }
      $values['medical']['details'] = $medValues[$medDetailId];
    }
    $infoGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                     $this,
                     $this->_studentId,
                     $medInfoId );

    $infoValues = CRM_Core_BAO_CustomGroup::buildCustomDataView( $this, $infoGroupTree );
    if ( ! empty( $infoValues[$medInfoId] ) ) {
      // format it for easy printing
      foreach ( $infoValues[$medInfoId] as $infoID => $infoValue ) {
        $details = array( );
        foreach ( $infoValue['fields'] as $fieldID => $fieldValue ) {
          if ( ! empty( $fieldValue['field_value'] ) ) {
            $details[] = array( 'title' => $fieldValue['field_title'],
                         'value' => $fieldValue['field_value'] );
          }
        }
        if ( ! empty( $details ) ) {
          $infoValues[$medInfoId][$infoID]['details'] = $details;
        }
        $values['medical']['info'] = $infoValues[$medInfoId][$infoID];
      }
    }

    // releases
    $this->_schoolInfoId = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup',
                           School_Form_Family::SCHOOL_INFO_TABLE, 'id', 'table_name' );
    $groupTree = CRM_Core_BAO_CustomGroup::getTree( 'contact',
                 $this,
                 $this->_studentId,
                 $this->_schoolInfoId );
    $values['release'] = CRM_Core_BAO_CustomGroup::buildCustomDataView( $this, $groupTree );

    // student diversity
    $groupID  = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup',
                School_Form_Family::RACE_ETHNICITY_TABLE,
                'id',
                'table_name' );
    $groupTree = CRM_Core_BAO_CustomGroup::getTree( 'individual',
                 $this,
                 $this->_studentId,
                 $groupID
    );
    $diversity = CRM_Core_BAO_CustomGroup::buildCustomDataView( $this, $groupTree );
    if ( ! empty( $diversity[$groupID] ) ) {
      $values['diversity'] = array_pop( $diversity[$groupID] );
    }

    $race = $family = array( );
    foreach ( $values['diversity']['fields'] as $fieldID => $fieldInfo ) {
      if ( ! empty( $fieldInfo['field_value'] ) &&
        is_array( $fieldInfo['field_value'] ) ) {
        if ( $fieldInfo['field_title'] == 'Family Structure' ) {
          foreach ( $fieldInfo['field_value']  as $value ) {
            $family[] = $value;
          }
        } else {
          foreach ( $fieldInfo['field_value']  as $value ) {
            $race[] = $value;
          }
        }
      }
    }

    $values['race'] = implode( ', ', $race );
    $values['family_structure'] = implode( ', ', $family );

    $this->_values = $values;

    $this->assign('values'       , $this->_values        );
    $this->assign('locationTypes', $this->_locationTypes );
    $this->assign('phoneTypes'   , $this->_phoneTypes    );
  }

  function run( ) {
    $studentID = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this, true, 0, 'REQUEST' );
    $this->commonRun( $studentID );
    return parent::run( );
  }

}
