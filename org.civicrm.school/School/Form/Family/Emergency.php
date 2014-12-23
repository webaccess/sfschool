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

require_once 'School/Form/Family.php';
require_once 'api/api.php';

class School_Form_Family_Emergency extends School_Form_Family {

  protected $_relationIds = array( 'contact'      => array(),
                                   'relationship' => array(),
                                   'custom'       => array() );
  const
    BLOCK_NUM = 3;

  function preProcess( ) {
    parent::preProcess();

    $this->_emergencyRelTypeId = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_RelationshipType', 
                                                              'Emergency Contact Of', 'id', 'name_a_b' );
    $this->_emergencyTableCol  = 'relationship_name';
  }

  function setDefaultValues( ) 
  {
      
    $defaults = array( );
    $blockId  = 1;
    $hasSibling = false;
    $locationTypeIds = array_flip(CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id'));
    $phoneTypeIds    = array_flip(CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id'));

    $relTypeParams = array(
                           'name_a_b' => 'Emergency Contact Of',
                           );
    $default = null;
    $relType = CRM_Contact_BAO_RelationshipType::retrieve($relTypeParams, $default);
	
		$relationParams = array(
                            'version' => 3,
                            'contact_id_a' => $this->_studentId,
                            'relationship_type_id' => $relType->id,
                            );
		$relationships = civicrm_api( 'relationship','get',$relationParams );
    if ( $relationships['is_error'] ) {	  
      
      $relTypeParams = array(
                             'name_a_b' => 'Emergency Contact Of',
                             );
      $default = null;
      $relType = CRM_Contact_BAO_RelationshipType::retrieve($relTypeParams, $default);
      $relationParams = array(
                              'version' => 3,
                              'contact_id_a' => $this->_studentId,
                              'relationship_type_id' => $relType->id,
                              );
      $siblings = civicrm_api( 'relationship','get',$relationParams );
            
      if ( !$siblings['is_error'] ) {
        foreach ( $siblings['values'] as $sibling ) {
		  
          $relTypeParams = array(
                                 'name_a_b' => 'Employee of',
                                 );
          $default = null;
          $relType = CRM_Contact_BAO_RelationshipType::retrieve($relTypeParams, $default);
		  
          $siblingParams = array(
                                 'version' => 3,
                                 'contact_id_a' => $sibling['contact_id_b'],
                                 'relationship_type_id' => $relType->id,	 
                                 );
          $emergencyRel = civicrm_api( 'relationship','get',$siblingParams );
          if ( !$emergencyRel['is_error'] ) {
            $relationships = $emergencyRel;
            $hasSibling    = true;
            break;
          }
        }
      }
    }

    $blockIdSpots = array( );
    for ( $i = 1 ; $i <= self::BLOCK_NUM ; $i++ ) {
      $blockIdSpots[$i] = 0;
    }

    $dataFields = array('first_name', 'last_name', 'email', 'phone');
    if ( is_array($relationships['values']) ) {
      foreach ( $relationships['values'] as $relationship ) {

        // use the description if available and numeric, else use lowest blockId
        if ( is_numeric( $relationship['description'] ) &&
             (int)$relationship['description'] <= self::BLOCK_NUM ) {
          $blockId = (int) $relationship['description'];
        } else {
          $blockId = self::BLOCK_NUM + 1;
          for ( $i = 1 ; $i <= self::BLOCK_NUM ; $i++ ) {
            if ( ! $blockIdSpots[$i] ) {
              $blockId = $i;
              break;
            }
          }
        }
                    
        if ( $blockId > self::BLOCK_NUM ) {
          break;
        }
            
        $blockIdSpots[$blockId] = 1;

        $this->_relationIds['ec_contact'][$blockId]   = $relationship['contact_id_b'];
        if ( !$hasSibling ) {
          $this->_relationIds['relationship'][$blockId] = $relationship['id'];
        }

        $params['id'] = $params['contact_id'] = $relationship['contact_id_b'];
        $params['noRelationships'] = $params['noNotes'] = $params['noGroups'] = true;
        CRM_Contact_BAO_Contact::retrieve( $params, $data );
                
        foreach ( $data as $dataKey => $dataVal ) {
          if ( ! in_array($dataKey, $dataFields) ) {
            unset($data[$dataKey]);
          }
        }

        // fix phone sequence
        $phone = array();
        foreach ( $data['phone'] as $phoneFields ) {
          if ( $phoneFields['location_type_id'] == $locationTypeIds['Home'] ) {
            if ( $phoneFields['phone_type_id'] == $phoneTypeIds['Mobile'] ) {
              $phone[1] = $phoneFields;
            }
            if ( $phoneFields['phone_type_id'] == $phoneTypeIds['Phone'] ) {
              $phone[2] = $phoneFields;
            }
            if ( $phoneFields['phone_type_id'] == $phoneTypeIds['Work'] ) {
              $phone[3] = $phoneFields;
            }
          }
        }
        $data['phone'] = $phone;

        // fix email sequence
        $email = array();
        foreach ( $data['email'] as $emailFields ) {
          if ( $emailFields['location_type_id'] == $locationTypeIds['Home'] ) {
            $email[1] = $emailFields;
          }
        }
        $data['email'] = $email;

        $defaults['ec_contact'][$blockId] = $data;
                
        $groupTree = CRM_Core_BAO_CustomGroup::getTree( 'Relationship', $this, $relationship['id'], 
                                                        -1, $this->_emergencyRelTypeId );

        foreach ( $groupTree as $gId => $gFields ) {
          if ( array_key_exists('fields', $gFields) ) {
            foreach ( $gFields['fields'] as $fId => $fFields ) {
              if ( $fFields['column_name'] == $this->_emergencyTableCol ) {
                $defaults['ec_contact'][$blockId]['relationship'] = $fFields['customValue'][1]['data'];
                if ( !empty($fFields['customValue'][1]['id']) && !$hasSibling ) {
                  $this->_relationIds['custom'][$blockId] = $fFields['customValue'][1]['id'];
                }
                break;
              }
            }
          }
        }
      }
    } 

    return $defaults;
  }

  function formRule( $params, $files, $form ) {
    $errors   = array( );  
    $countFilled = 0;
    for ( $blockId = 1; $blockId <= self::BLOCK_NUM; $blockId++ ) {
      if ( !empty($params['ec_contact'][$blockId]['first_name']) ||
           !empty($params['ec_contact'][$blockId]['last_name']) ||
           !empty($params['ec_contact'][$blockId]['email'][1]['email']) ) {
        $countFilled ++; 
      }
    }
         
    if ( $countFilled < 2 ) {
      $errors['ec_contact[2][first_name]'] = ts("Please fill at least 2 contacts details.");
    }

    return $errors;
  }

  function buildQuickForm( ) {

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact');

    require_once 'CRM/Contact/Form/Edit/Address.php';
    for ( $blockId = 1; $blockId <= self::BLOCK_NUM; $blockId++ ) {
      $this->addElement('text', "ec_contact[$blockId][first_name]"  ,
                        ts('First Name'), $attributes['first_name'] );
      $this->addElement('text', "ec_contact[$blockId][last_name]"   , 
                        ts('Last Name'), $attributes['last_name' ] );

      $this->addElement('text', "ec_contact[$blockId][email][1][email]"   , 
                        ts('Email'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email') );
      $this->addRule( "ec_contact[$blockId][email][1][email]", ts('Email is not valid.'), 'email' );

      $this->addElement('text', "ec_contact[$blockId][relationship]", 
                        ts('Relationship'), $attributes['last_name' ] );
      $this->addElement('text', "ec_contact[$blockId][phone][1][phone]"  ,  
                        ts('Cell Phone'), $attributes['last_name' ] );

      $this->addElement('text', "ec_contact[$blockId][phone][2][phone]"  ,  
                        ts('Home Phone'), $attributes['last_name' ] );

      $this->addElement('text', "ec_contact[$blockId][phone][3][phone]"  ,  
                        ts('Work Phone'), $attributes['last_name' ] );
    }
    parent::buildQuickForm( );

    $this->addFormRule( array( 'School_Form_Family_Emergency', 'formRule' ) );
  }

  function postProcess() 
  {
    $params = $this->controller->exportValues( $this->_name );
    require_once 'CRM/Contact/BAO/Contact.php';
    require_once 'CRM/Core/BAO/CustomValueTable.php';
    require_once 'CRM/Dedupe/Finder.php';

    $sql = "
SELECT     f.id
FROM       civicrm_custom_field f
INNER JOIN civicrm_custom_group g ON f.custom_group_id = g.id
WHERE      g.table_name  = '" . School_FORM_FAMILY::EMERGENCY_REL_TABLE . "'
AND        f.column_name = %1
";
    $qParams  = array( 1 => array( $this->_emergencyTableCol, 'String' ) );
    $fieldId  = CRM_Core_DAO::singleValueQuery( $sql, $qParams );

    $locationTypeIds = array_flip(CRM_Core_PseudoConstant::locationType());
    $phoneTypeIds    = array_flip(CRM_Core_PseudoConstant::phoneType());

    for ( $blockId = 1; $blockId <= self::BLOCK_NUM; $blockId++ ) {
      if ( !empty($params['ec_contact'][$blockId]['first_name']) ||
           !empty($params['ec_contact'][$blockId]['last_name']) ||
           !empty($params['email'][$blockId]['email']) ) {

        $params['ec_contact'][$blockId]['email'][1]['location_type_id'] = $locationTypeIds['Home'];
        $params['ec_contact'][$blockId]['phone'][1]['location_type_id'] = $locationTypeIds['Home'];
        $params['ec_contact'][$blockId]['phone'][1]['phone_type_id']    = $phoneTypeIds['Mobile'];
        $params['ec_contact'][$blockId]['phone'][2]['location_type_id'] = $locationTypeIds['Home'];
        $params['ec_contact'][$blockId]['phone'][2]['phone_type_id']    = $phoneTypeIds['Phone'];
        $params['ec_contact'][$blockId]['phone'][3]['location_type_id'] = $locationTypeIds['Home'];
        $params['ec_contact'][$blockId]['phone'][3]['phone_type_id']    = $phoneTypeIds['Work'];

        $dropContactId = 0;
        $dedupeParams  = CRM_Dedupe_Finder::formatParams( $params['ec_contact'][$blockId], 'Individual' );
        $dedupeParams['civicrm_relationship'] = array( 'contact_id_a'         => $this->_studentId,
                                                       'relationship_type_id' => $this->_emergencyRelTypeId );

        $params['ec_contact'][$blockId]['contact_id'] = NULL;
        if ( $dupeId = $this->findDupe( $dedupeParams ) ) {
          $params['ec_contact'][$blockId]['contact_id'] = $dupeId;
        }
        if ( isset($this->_relationIds['ec_contact'][$blockId]) && 
             !in_array($dupeId, $this->_relationIds['ec_contact']) ) {
          // drop old relationship
          $dropContactId = $this->_relationIds['ec_contact'][$blockId];
        } 

        $contactId = CRM_Contact_BAO_Contact::createProfileContact( $params['ec_contact'][$blockId],
                                                                    CRM_Core_DAO::$_nullArray,
                                                                    $params['ec_contact'][$blockId]['contact_id'] );

        // create relationship if doesn't already exist
		
        $relParams = array(
                           'version'  => '3',
                           'contact_id_a' => $this->_studentId , 
                           'contact_id_b' => $contactId ,
                           'relationship_type_id' => $this->_emergencyRelTypeId,
                           );
        $relationships =  civicrm_api( 'relationship','get',$relParams );
        $relationshipId = null;
        if ( $relationships['count'] == 0 ) {
                 
          $relParams = array(				     
                             'contact_id_a'         => $this->_studentId,
                             'contact_id_b'         => $contactId,
                             'relationship_type_id' => $this->_emergencyRelTypeId,
                             'start_date'           => date('Ymd'),
                             'is_active'            => 1,
                             'description'          => $blockId,
                             'version'              => 3,
                                         );


          $relationship = civicrm_api( 'relationship','create',$relParams );
          $relationshipId = $relationship['id'];
        } else {
          foreach ( $relationships['values'] as $relId => $dontCare ) {
            $relationshipId = $relId;
          }
        }

        if ( $dropContactId ) {
          $oldRelation = new CRM_Contact_DAO_Relationship( );
          $oldRelation->contact_id_a = $this->_studentId;
          $oldRelation->contact_id_b = $dropContactId;
          $oldRelation->relationship_type_id = $this->_emergencyRelTypeId;
          $oldRelation->delete();
        }

        $fieldParams = $customFields = array();
        if ( $relationshipId ) {
          $fieldParams['custom_' . $fieldId . (isset($this->_relationIds['custom'][$blockId]) ? 
                                               "_{$this->_relationIds['custom'][$blockId]}" : '_-1')] = 
            trim($params['ec_contact'][$blockId]['relationship']);
          require_once 'CRM/Core/BAO/CustomValueTable.php';
          CRM_Core_BAO_CustomValueTable::postProcess( $fieldParams,
                                                      $customFields,
                                                      'civicrm_relationship',
                                                      $relationshipId,
                                                      'Relationship' );
        }
      }
    }

    parent::endPostProcess( );
  }
}
