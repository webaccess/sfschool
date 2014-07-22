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

class School_Form_Family_Household extends School_Form_Family {
  const
    BLOCK_NUM = 4,
    RELATION_TABLE = 'civicrm_value_parent_relationship_data';

  protected $_parentIds = array( );

  function preProcess( ) {
    parent::preProcess();

    require_once 'CRM/Core/BAO/CustomGroup.php';

    $this->_parentRelDataId = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup',
                              self::RELATION_TABLE, 'id', 'table_name' );

    $this->_relTypeId       = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_RelationshipType',
                              'Child of', 'id', 'name_a_b'     );
  }

  function setDefaultValues( ) {

    $defaults = array( );

    $locationTypeIds = array_flip(CRM_Core_PseudoConstant::locationType());
    $phoneTypeIds    = array_flip(CRM_Core_PseudoConstant::phoneType());

    $blockId = 1;

    $relationParams = array(
      'version' => 3,
      'contact_id_a' => $this->_studentId,
      'relationship_type_id' => $this->_relTypeId,
    );
    $relationships = civicrm_api( 'relationship','get',$relationParams );

    $dataFields    = array('first_name', 'last_name', 'email', 'phone', 'address');
    $this->_indexMapper = array( );

    if ( is_array($relationships['values']) ) {
      foreach ( $relationships['values'] as $relationship ) {
	if ( $blockId > self::BLOCK_NUM ) {
	  continue;
	}


	$params['id'] = $params['contact_id'] = $relationship['contact_id_b'];
	$params['noRelationships'] = $params['noNotes'] = $params['noGroups'] = true;
	CRM_Contact_BAO_Contact::retrieve( $params, $data );

	foreach ( $data as $dataKey => $dataVal ) {
	  if ( ! in_array($dataKey, $dataFields) ) {
	    unset($data[$dataKey]);
	  }
	}


	$relGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Relationship',
                        $this,
                        $relationship['id'],
                        $this->_parentRelDataId,
                        $this->_relTypeId );
	$this->_relGroupTree = CRM_Core_BAO_CustomGroup::formatGroupTree( $relGroupTree, 1, $this );

	foreach ( $this->_relGroupTree as $gid => $groupTree ) {
	  foreach ( $groupTree['fields'] as $fid => $fieldTree ) {
	    $this->_relationMapper[$fieldTree['column_name']] = $fieldTree["element_name"];
	    if ( CRM_Utils_Array::value('column_name', $fieldTree) == 'parent_index'  ) {
	      $this->_indexMapper[$relationship['id']] = $fieldTree["element_name"];
	    }
	  }
	}


	$relCustomDefaults = array( );
	CRM_Core_BAO_CustomGroup::setDefaults( $this->_relGroupTree, $relCustomDefaults);
	$parentIndex = $relCustomDefaults[$this->_relationMapper['parent_index']];
	$this->_parentIds[$parentIndex] = $relationship['contact_id_b'];
	if ( !$parentIndex ) {
	  CRM_Core_Error::fatal( "Parent index missing for rid {$relationship['id']}" );
	}

	// fix phone sequence
	$phone = array();
	foreach ( $data['phone'] as $phoneFields ) {
	  if ( $phoneFields['location_type_id'] == $locationTypeIds['Home'] ) {
            $phoneType = CRM_Utils_Array::value('phone_type_id', $phoneFields);
	    if ( $phoneType == $phoneTypeIds['Phone'] ) {
	      $phone[1] = $phoneFields;
	    }
	    if ( $phoneType == $phoneTypeIds['Work'] ) {
	      $phone[2] = $phoneFields;
	    }
	    if ( $phoneType == $phoneTypeIds['Mobile'] ) {
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

	$defaults["contact"][$parentIndex] = $data;
	if( !empty($data['address'][1])) {
	  $defaults["address"][$parentIndex] = $data['address'][1];
	  $blockId++;
	}
      }
    }


    foreach ( array(1,2) as $blockId ) {
      //copy address from parent 2, if parent 1 is empty
      if ( empty( $defaults["address"][$blockId] ) ) {
	if($blockId == 2){
	  $defaults["address"][$blockId] = $defaults["address"][$blockId - 1];
	  unset($defaults["address"][$blockId]['id'], $defaults["address"][$blockId]['contact_id']);
	}
	else{
	  $defaults["address"][$blockId] = $defaults["address"][$blockId + 1];
	  unset($defaults["address"][$blockId]['id'], $defaults["address"][$blockId]['contact_id']);
	}
      }

      if ( empty( $defaults["contact"][$blockId]['phone'][1] ) ) {
	$defaults["contact"][$blockId]['phone'][1] = $defaults["contact"][$blockId + 1]['phone'][1];
	unset($defaults["contact"][$blockId]['phone'][1]['id'],
          $defaults["contact"][$blockId]['phone'][1]['contact_id']);
      }


      // if still empty, add some address defaults
      if ( empty( $defaults["address"][$blockId] ) ) {
	$defaults["address[$blockId][city]"]              = 'San Francisco';
	$defaults["address[$blockId][country_id]"]        = '1228';
	$defaults["address[$blockId][state_province_id]"] = '1004';
      }
    }

    $this->assign( 'parentIDs', $this->_parentIds );

    return $defaults;
  }

  function buildQuickForm( ) {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact');

    require_once 'CRM/Contact/Form/Edit/Email.php';
    require_once 'CRM/Contact/Form/Edit/Phone.php';
    require_once 'CRM/Contact/Form/Edit/Address.php';
    for ( $blockId = 1; $blockId <= self::BLOCK_NUM; $blockId++ ) {
      $this->addElement('text', "contact[$blockId][first_name]",  ts('First Name'), $attributes['first_name'] );
      $this->addElement('text', "contact[$blockId][last_name]" ,   ts('Last Name'), $attributes['last_name' ] );

      // email
      $this->addElement('text', "contact[$blockId][email][1][email]",
        ts('Email'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'));

      $this->addRule( "contact[$blockId][email][1][email]", ts('Email is not valid.'), 'email' );

      // phone
      $this->addElement('text', "contact[$blockId][phone][1][phone]",
        ts('Home Phone'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));

      $this->addElement('text', "contact[$blockId][phone][2][phone]",
        ts('Work Phone'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));

      $this->addElement('text', "contact[$blockId][phone][3][phone]",
        ts('Cell Phone'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));

      // address
      if ( in_array( $blockId, array(1,2) ) ) {
	CRM_Contact_Form_Edit_Address::buildQuickForm( $this, $blockId, false );
      }
    }
    parent::buildQuickForm( );
  }
  
  function sendMail( $groupName, $valueName, $email ) {
    $templateVars = array( );
    if( School_APPLICATION_SCHOOL_CONTACT_ID != null ) {
      list( $templateVars['schoolName'],
            $templateVars['schoolEmail'] ) = 
        School_Utils_Query::getNameAndEmail( School_APPLICATION_SCHOOL_CONTACT_ID );
      $from = $templateVars['schoolName']." <".$templateVars['schoolEmail'].">";
      $sendTemplateParams = array(
                                  'groupName' => $groupName,
                                  'valueName' => $valueName,
                                  'from' => $from,
                                  'toEmail' => $email,
                                  );
      CRM_Core_BAO_MessageTemplates::sendTemplate( $sendTemplateParams );
    }
  }
  
  function postProcess()
  {
    $params = $this->controller->exportValues( $this->_name );
       
    require_once 'CRM/Dedupe/Finder.php';
    require_once 'School/Utils/Query.php';

    $locationTypeIds = array_flip(CRM_Core_PseudoConstant::locationType());
    $phoneTypeIds    = array_flip(CRM_Core_PseudoConstant::phoneType());

    for ( $blockId = 1; $blockId <= self::BLOCK_NUM; $blockId++ ) {
      if ( !empty($params['contact'][$blockId]['first_name']) ||
        !empty($params['contact'][$blockId]['last_name']) ||
        !empty($params['email'][$blockId]['email']) ) {

	$dropContactId = 0;
	$dedupeParams  = CRM_Dedupe_Finder::formatParams( $params['contact'][$blockId], 'Individual' );
	$dedupeParams['civicrm_relationship'] = array( 'contact_id_a'         => $this->_studentId,
                                                'relationship_type_id' => $this->_relTypeId );
	if ( $dupeId = $this->findDupe( $dedupeParams ) ) {
	  $params['contact'][$blockId]['contact_id'] = $dupeId;
	}

	if ( isset($this->_parentIds[$blockId]) && !in_array($dupeId, $this->_parentIds) ) {
	  // drop old relationship
	  $dropContactId = $this->_parentIds[$blockId];
	}

	if ( $dropContactId == $this->_parentId ) {
	  CRM_Core_Error::fatal( ts( "Current parent can't be changed to some other parent." ) );
	}

	$fromBlockId = ( $blockId % 2 == 0 ) ? ( $blockId - 1 ) : $blockId;

	if ( isset( $params['address'][$blockId] ) ) {
	  $params['contact'][$blockId]['address'][1] = $params['address'][$blockId];
	  $params['contact'][$blockId]['address'][1]['location_type_id'] = $locationTypeIds['Home'];
	}

	if ( empty( $params['contact'][$blockId]['email'][1]['email'] ) ) {
	  $params['contact'][$blockId]['email'][1]['email'] = ' ';
	}

	for ( $i = 1; $i <= 3; $i++ ) {
	  if ( empty( $params['contact'][$blockId]['phone'][$i]['phone'] ) ) {
	    $params['contact'][$blockId]['phone'][$i]['phone'] = ' ';
	  }
	}

	$params['contact'][$blockId]['email'][1]['location_type_id'] = $locationTypeIds['Home'];
	$params['contact'][$blockId]['phone'][1]['location_type_id'] = $locationTypeIds['Home'];
	$params['contact'][$blockId]['phone'][1]['phone_type_id']    = $phoneTypeIds['Phone'];
	$params['contact'][$blockId]['phone'][2]['location_type_id'] = $locationTypeIds['Home'];
	$params['contact'][$blockId]['phone'][2]['phone_type_id']    = $phoneTypeIds['Work'];
	$params['contact'][$blockId]['phone'][3]['location_type_id'] = $locationTypeIds['Home'];
	$params['contact'][$blockId]['phone'][3]['phone_type_id']    = $phoneTypeIds['Mobile'];

	// if phone is null, reset it
	for ($i = 1; $i <= 3; $i++) {
	  if ( empty($params['contact'][$blockId]['phone'][$i]['phone'])) {
	    $params['contact'][$blockId]['phone'][$i]['phone'] = 'null';
	  }
	}
  if ($params['contact'][$blockId]['contact_id']) {
	  $params['contact'][$blockId]['contact_sub_type'] =
	  CRM_Contact_BAO_Contact::getContactSubType($params['contact'][$blockId]['contact_id']);
  }

	$householdId = CRM_Contact_BAO_Contact::createProfileContact( $params['contact'][$blockId], CRM_Core_DAO::$_nullArray );

	$subType = CRM_Contact_BAO_Contact::getContactSubType($householdId);
  $subtypeParams = array();
	if(!in_array('Staff',$subType) && !in_array('Parent',$subType)){
	  array_push($subType,'Parent');
	  $subtypeParams['contact_id'] = $householdId;
	  $subtypeParams['contact_type'] = 'Individual';
	  $subtypeParams['contact_sub_type'] = $subType;
	  CRM_Contact_BAO_Contact::create($subtypeParams);
	  $checkParent = "Select id from ".self::SCHOOL_INFO_TABLE." where entity_id = %1";
	  $dao =  CRM_Core_DAO::executeQuery( $checkParent,
                  array( 1 => array( $householdId, 'Integer' ) ) );
	  if($dao->fetch())
	    {
	      $isPresent =   $dao->id;
	    }
	  if(empty($isPresent)){
	    $createParent =
	      "INSERT INTO " .
	      self::SCHOOL_INFO_TABLE .
	      " SET entity_id = %1 , extended_care_status_2011 = 'Regular', extended_care_status_2010 = 'Regular'";
	    CRM_Core_DAO::executeQuery( $createParent,
              array( 1 => array( $householdId, 'Integer' ) ) );
	  }
	}

	// create relationship if doesn't already exist
	$relParams = array(
          'version'  => '3',
          'contact_id_a' => $this->_studentId ,
          'contact_id_b' => $householdId,
          'relationship_type_id' => $this->_relTypeId,
        );
	$relationships =  civicrm_api( 'relationship','get',$relParams );

	$relationshipId = null;
	if ( $relationships['count'] == 0 ) {
	  $relParams = array( 'contact_id_a'         => $this->_studentId,
                       'contact_id_b'         => $householdId,
                       'relationship_type_id' => $this->_relTypeId,
                       'start_date'           => date('Ymd'),
                       'is_permission_b_a'    => 1,
                       'is_active'            => 1,
                       'version'              => 3
          );

	  $relationship = civicrm_api( 'relationship','create',$relParams );

	  $relationshipId = $relationship['id'];

	} else {
	  foreach ( $relationships['values'] as $relId => $dontCare ) {
	    if ( isset($this->_indexMapper[$relId] ) ) {
	      $relationshipId = $relId;
	    }
	  }
	}

	if ( $relationshipId ) {
	  // create indexes for every new relationship
	  $key = CRM_Core_BAO_CustomField::getKeyID($this->_relationMapper['parent_index']);

	  $indexKey =  isset($this->_indexMapper[$relationshipId]) ? $this->_indexMapper[$relationshipId] : "custom_{$key}-1";

	  $customParams = array( $indexKey => $blockId );

	  $customFields = CRM_Core_BAO_CustomField::getFields( 'Relationship', false, false, $this->_relTypeId );
	  CRM_Core_BAO_CustomValueTable::postProcess( $customParams,
            $customFields,
            'civicrm_relationship',
            $relationshipId,
            'Relationship' );
	}

	// drop any old relationship if needed
	if ( $dropContactId ) {
	  $oldRelation = new CRM_Contact_DAO_Relationship( );
	  $oldRelation->contact_id_a = $this->_studentId;
	  $oldRelation->contact_id_b = $dropContactId;
	  $oldRelation->relationship_type_id = $this->_relTypeId;
	  $oldRelation->delete();

	}
      }
    }    
    
    $defaultValues = $this->_defaultValues;
    $submitValues = $params;
    $groupNameEmail = 'updated_email';
    $valueNameEmail = 'email_update_notification';

    // send email on changing email address
    for ( $blockId = 1; $blockId <= self::BLOCK_NUM; $blockId++ ) {
      if( isset( $defaultValues['contact'][$blockId] ) || isset( $submitValues['contact'][$blockId] ) ) {
        if( strcmp( $defaultValues['contact'][$blockId]['email'][1]['email'], $submitValues['contact'][$blockId]['email'][1]['email'] ) != 0 ) {
          self::sendMail( $groupNameEmail, $valueNameEmail, $submitValues['contact'][$blockId]['email'][1]['email'] );
        }
      }
    }
    
    $groupNameAddr = 'updated_address';
    $valueNameAddr = 'address_update_notification';
    $postAddr = $submitValues['address'];
    $preAddr = $defaultValues['address'];

    foreach( $postAddr as $block => $info ) {
      foreach( $info as $addrKey => $addrValue ) {
        if( empty($addrValue) && !isset($preAddr[$block][$addrKey]) ) {
          unset( $postAddr[$block][$addrKey] );
        }
      }
    }
  
    $contactIds = array();
    $addrs = array();
    foreach( $postAddr as $blockid => $values ) {  
      foreach( $values as $postAddrKey => $postAddrVal ) {
        if( $postAddrVal != $preAddr[$blockid][$postAddrKey] ) {  
          $addrs [] = $postAddr[$blockid];
          $contactIds[] = $preAddr[$blockid]['contact_id'];
          break;
        }
      }
    }

    // send mail on changing address
    require_once 'CRM/Core/BAO/MessageTemplates.php';
    require_once 'CRM/Core/PseudoConstant.php';
    if( !empty( $contactIds ) ) {
      foreach( $contactIds as $key => $id ) {
        if( isset( $addrs[$key]["state_province_id"] ) ) {
          $addrs[$key]["state_province_id"] = CRM_Core_PseudoConstant::stateProvince( $addrs[$key]["state_province_id"] );
        }
        if( isset( $addrs[$key]["country_id"] ) ) {
          $addrs[$key]["country_id"] = CRM_Core_PseudoConstant::country( $addrs[$key]["country_id"] );
        }
        $this->assign( 'address_new', $addrs[$key] );
        $mailId = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_Email', $id, 'email', 'contact_id' );
        self::sendMail( $groupNameAddr, $valueNameAddr, $mailId );
      }
    }
    parent::endPostProcess( );
  }

}
