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

class School_Form_Apply_Family extends School_Form_Apply {
    
    const
        BLOCK_NUM = 2;

    protected $_parentIds = array( );

    protected $_defaults  = array( );

    function preProcess( ) {
        parent::preProcess();
        
        $this->_parentRelDataId   = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                                                 self::RELATION_TABLE, 'id', 'table_name' );
        $this->_relTypeId         = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_RelationshipType', 
                                                                 'Child Of', 'id', 'name_a_b'     );
        
        $this->_familyCustomId    = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                                                 School_Form_Apply::CUSTOM_FAMILY_TABLE, 'id', 'table_name' );

        // family custom data
        $familyGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                                                              $this,
                                                              null,
                                                              $this->_familyCustomId,
                                                              'Applicant_Parent');
        $familyGroupTree = CRM_Core_BAO_CustomGroup::formatGroupTree( $familyGroupTree, 1, $this );
        foreach ( $familyGroupTree as $gid => $groupTree ) {
            foreach ( $groupTree['fields'] as $fid => $fieldTree ) {
                $this->_familyCustomMapper[0][$fieldTree['column_name']] = $fieldTree['element_name'];
            }
        }

        //  applicant custom data
        $this->_applicantCustomId = 
            CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup', 
                                         School_Form_Apply::CUSTOM_APPLICANT_TABLE, 'id', 'table_name' );
        $applicantGroupTree       = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                                                                       $this,
                                                                       $this->_applicantId,
                                                                       $this->_applicantCustomId,
                                                                       'Applicant' );        
        $this->_applicantGroupTree = CRM_Core_BAO_CustomGroup::formatGroupTree( $applicantGroupTree, 1, $this );
        foreach ( $this->_applicantGroupTree as $gid => $groupTree ) {
            foreach ( $groupTree['fields'] as $fid => $fieldTree ) {
                $this->_applicantCustomMapper[$fieldTree['column_name']] = $fieldTree["element_name"];
                $this->_defaults[$fieldTree['column_name']] = $fieldTree["element_value"];
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
                $this->_relationMapper[0][$fieldTree['column_name']] = $fieldTree["element_name"];
            }
        }
    }
    
    function setDefaultValues( ) {
        $defaults = array();
        $defaults = $this->_defaults;
       
        $locationTypeIds = array_flip(CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id'));
        $phoneTypeIds    = array_flip(CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id'));

        $blockId = 0;    
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
        $relationships = civicrm_api( 'relationship','get',$relationParams );
        
        $dataFields    = array('first_name', 'last_name', 'email', 'phone', 'address', 'prefix_id');
        if ( is_array($relationships['values']) ) {
            foreach ( $relationships['values'] as $relationship ) {
                $subType = CRM_Contact_BAO_Contact::getContactSubType( $relationship['contact_id_b'] );

                if( in_array('Applicant_Parent',$subType) ) {
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
                    
                    // parent indexing
                    $relGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Relationship',
                                                                       $this,
                                                                       $relationship['id'],
                                                                       $this->_parentRelDataId,
                                                                       $this->_relTypeId );
                    $this->_relGroupTree = CRM_Core_BAO_CustomGroup::formatGroupTree( $relGroupTree, 1, $this );
                    foreach ( $this->_relGroupTree as $gid => $groupTree ) {
                        foreach ( $groupTree['fields'] as $fid => $fieldTree ) {
                            $this->_relationMapper[$relationship['contact_id_b']][$fieldTree['column_name']] = 
                                $fieldTree["element_name"];
                        }
                    }
                    
                    $relCustomDefaults = array( );
                    CRM_Core_BAO_CustomGroup::setDefaults( $this->_relGroupTree, $relCustomDefaults);
                    $parentIndex = $relCustomDefaults[$this->_relationMapper[$relationship['contact_id_b']]['parent_index']];
                    $this->_parentIds[$parentIndex] = $relationship['contact_id_b'];
                    
                    if ( !$parentIndex ) {
                        CRM_Core_Error::fatal( "Parent index missing for rid {$relationship['id']}" );
                    }
                    
                    // fix phone sequence
                    $phone = array();
                    foreach ( $data['phone'] as $phoneFields ) {
                        if ( $phoneFields['location_type_id'] == $locationTypeIds['Home'] ) {
                            if ( $phoneFields['phone_type_id'] == $phoneTypeIds['Phone'] ) {
                                $phone[1] = $phoneFields;
                            } else if ( $phoneFields['phone_type_id'] == $phoneTypeIds['Mobile'] ) {
                                $phone[2] = $phoneFields;
                            }
                        }
                        if ( $phoneFields['location_type_id'] == $locationTypeIds['Work'] && 
                             $phoneFields['phone_type_id'] == $phoneTypeIds['Phone'] ) {
                            $phone[3] = $phoneFields;
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
                    
                    // fix address sequence
                    $address = array();
                    foreach ( $data['address'] as $addressFields ) {
                        if ( $addressFields['location_type_id'] == $locationTypeIds['Home'] ) {
                            $address[1] = $addressFields;
                        } else if ( $addressFields['location_type_id'] == $locationTypeIds['Work'] ) {
                            $address[2] = $addressFields;
                        }
                    }
                    
                    // set contact & address defaults
                    $defaults["contact"][$parentIndex] = $data;
                    if(!empty($address[1])){
                      $defaults["address"][$parentIndex] = $address[1];
                    }
                    if(!empty($address[2])){ 
                      $defaults["address"][$parentIndex+2] = $address[2];
                    }                   
                    
                    // family custom data
                    $familyGroupTree = CRM_Core_BAO_CustomGroup::getTree( 'Individual',
                                                                          $this,
                                                                          $relationship['contact_id_b'],
                                                                          $this->_familyCustomId,
                                                                          'Applicant_Parent');
                    $familyGroupTree = CRM_Core_BAO_CustomGroup::formatGroupTree( $familyGroupTree, 1, $this );
                    foreach ( $familyGroupTree as $gid => $groupTree ) {
                        foreach ( $groupTree['fields'] as $fid => $fieldTree ) {
                          if(!empty($fieldTree['element_value'])){  
                            $defaults["contact"][$parentIndex][$fieldTree['column_name']] = $fieldTree['element_value'];
                          }                          
                            $this->_familyCustomMapper[$relationship['contact_id_b']][$fieldTree['column_name']] = 
                                $fieldTree['element_name'];
                        }
                    }
                    
                    $blockId++;
                }
            }
        }    
                    
   
        return $defaults;
    }
    
    
    function buildQuickForm( ) {
        $prefix = CRM_Core_PseudoConstant::individualPrefix( );
        
        require_once 'CRM/Contact/Form/Edit/Address.php';
        for ( $blockId = 1 ; $blockId <= self::BLOCK_NUM ; $blockId++ ) {
            CRM_Contact_Form_Edit_Address::buildQuickForm( $this, $blockId );
            CRM_Contact_Form_Edit_Address::buildQuickForm( $this, $blockId + 2 );
            if ( !empty( $prefix ) ) {
                $this->add('select',"contact[$blockId][prefix_id]", ts('Prefix'), array('' => '') + $prefix );
            }
            $isRequired  = ($blockId == 2 ? false : true);
            $this->add('text', "contact[$blockId][first_name]", ts('Parent\'s Name'),'',$isRequired);
            $this->add('text', "contact[$blockId][last_name]", ts('Last Name'),'',$isRequired);
            $this->add('text', "contact[$blockId][relationship_name]", ts('Relationship'), null, $isRequired );

            // email
            $this->add('text', "contact[$blockId][email][1][email]", 
                              ts('Email'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'), $isRequired);
            $this->addRule( "contact[$blockId][email][1][email]", ts('Email is not valid.'), 'email' );

            // phone
            $this->addElement('text', "contact[$blockId][phone][1][phone]", 
                              ts('Home Phone'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));
            $this->addRule("contact[$blockId][phone][1][phone]", ts('Phone number is not valid.'), 'regex', "/^\d{3}[-.]?\d{3}[-.]?\d{4}$/");

            $this->addElement('text', "contact[$blockId][phone][2][phone]", 
                              ts('Cell Phone'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));
            $this->addRule("contact[$blockId][phone][2][phone]", ts('Phone number is not valid.'), 'regex', "/^\d{3}[-.]?\d{3}[-.]?\d{4}$/");

            $this->addElement('text', "contact[$blockId][phone][3][phone]", 
                              ts('Business Phone'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'));
            $this->addRule("contact[$blockId][phone][3][phone]", ts('Phone number is not valid.'), 'regex', "/^\d{3}[-.]?\d{3}[-.]?\d{4}$/");          

            $this->addRule( "address[$blockId][postal_code]", ts('Please enter valid zip'), 'positiveInteger' );
            $this->add('text', "contact[$blockId][employer]", ts('Employer'), null, $isRequired);
            $this->add('text', "contact[$blockId][occupation]", ts('Occupation'), null, $isRequired);
            $this->add('text', "contact[$blockId][position]", ts('Position'), null, $isRequired);
            
        }
              
        foreach ( $this->_applicantGroupTree as $gId => $groupTree ) {
            foreach ( $groupTree['fields'] as $fId => $fieldTree ) {
                if ( in_array($fieldTree['column_name'], 
                              array('language', 'marital_status', 'living_status', 
                                    'correspondence', 'billing', 'circumstances')) ) {
                    if ( $fieldTree['column_name'] =='circumstances' ) {
                        $maxCharacterCount = 500;
                        if(empty( $circumstances['attributes'])){
                          $circumstances['attributes'] = '';
                        }
                           $circumstances['attributes'] .= " onkeyup=wordcount(\"{$fieldTree['column_name']}\",$maxCharacterCount);
                                                          oninput=wordcount(\"{$fieldTree['column_name']}\",$maxCharacterCount);
                                                          cols=40, rows=5;";
                                            
                       
                        $this->add( 'textarea', $fieldTree['column_name'],
                                    "Are there any additional family circumstances that you believe are important to share with us?". 
                                    " ( $maxCharacterCount character limit )",
                                    $circumstances['attributes'] , "true" );
                        $this->add('text', "counter_{$fieldTree['column_name']}", ts( 'Characters Left:' ), 
                                   array('readonly','class="two"'));
                    } else {
                        CRM_Core_BAO_CustomField::addQuickFormElement( $this, $fieldTree['column_name'], 
                                                                       $fieldTree['id'], false, $fieldTree['is_required'] );
                    }
                    $fieldNames[] = $fieldTree['column_name'];
                }
            }
        }
        $this->assign( 'fieldNames', $fieldNames );
        
        parent::buildQuickForm( );
    }
    
    function postProcess( ) {
        $session = CRM_Core_Session::singleton();
        $params  = $this->controller->exportValues( $this->_name );
        
        require_once 'CRM/Dedupe/Finder.php';
        
        $locationTypeIds = array_flip(CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id'));
        $phoneTypeIds    = array_flip(CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id'));
        
        for ( $blockId = 1; $blockId <= self::BLOCK_NUM; $blockId++ ) {
            if ( !empty($params['contact'][$blockId]['first_name']) ||
                 !empty($params['contact'][$blockId]['last_name']) ||
                 !empty($params['email'][$blockId]['email']) ) {
                
                $dropContactId = 0;
                $dedupeParams  = CRM_Dedupe_Finder::formatParams( $params['contact'][$blockId], 'Individual' );
                $dedupeParams['civicrm_relationship'] = array( 'contact_id_a'         => $this->_applicantId,
                                                               'relationship_type_id' => $this->_relTypeId );
                if ( $dupeId = $this->findDupe( $dedupeParams ) ) {
                    $params['contact'][$blockId]['contact_id'] = $dupeId;
                } 
                
                if ( isset($this->_parentIds[$blockId]) && !in_array($dupeId, $this->_parentIds) ) {
                    // drop old relationship
                    $dropContactId = $this->_parentIds[$blockId];
                    if ( $dropContactId == $session->get( 'userID') ) {
                        CRM_Core_Error::fatal( 'Logged-in / specified parent has to remain as one of the parent for application to be accessible.' );
                    }
                } 
                
                if ( isset( $params['address'][$blockId] ) ) {
                    $params['contact'][$blockId]['address'][1] = $params['address'][$blockId];
                    $params['contact'][$blockId]['address'][1]['location_type_id'] = $locationTypeIds['Home'];
                    $params['contact'][$blockId]['address'][2] = $params['address'][$blockId + 2];
                    $params['contact'][$blockId]['address'][2]['location_type_id'] = $locationTypeIds['Work'];
                }
                
                if ( empty( $params['contact'][$blockId]['email'][1]['email'] ) ) {
                    $params['contact'][$blockId]['email'][1]['email'] = 'null';
                }

                for ( $i = 1; $i <= 3; $i++ ) {
                    if ( empty( $params['contact'][$blockId]['phone'][$i]['phone'] ) ) {
                        $params['contact'][$blockId]['phone'][$i]['phone'] = 'null';
                    }
                }
                
                $params['contact'][$blockId]['email'][1]['location_type_id'] = $locationTypeIds['Home'];
                $params['contact'][$blockId]['phone'][1]['location_type_id'] = $locationTypeIds['Home'];
                $params['contact'][$blockId]['phone'][1]['phone_type_id']    = $phoneTypeIds['Phone'];
                $params['contact'][$blockId]['phone'][2]['location_type_id'] = $locationTypeIds['Home'];
                $params['contact'][$blockId]['phone'][2]['phone_type_id']    = $phoneTypeIds['Mobile'];
                $params['contact'][$blockId]['phone'][3]['location_type_id'] = $locationTypeIds['Work'];
                $params['contact'][$blockId]['phone'][3]['phone_type_id']    = $phoneTypeIds['Phone'];
                
                $params['contact'][$blockId]['contact_sub_type'] = 'Applicant_Parent';
                $contactId = CRM_Contact_BAO_Contact::createProfileContact( $params['contact'][$blockId],
                                                                            CRM_Core_DAO::$_nullArray );
                // create relationship if doesn't already exist
                $relTypeParams = array(
                                       'name_a_b' => 'Child of',
                                       );
                $default = null;
                $relType = CRM_Contact_BAO_RelationshipType::retrieve($relTypeParams,$default);
                $relationParams = array(
                                        'version' => 3,
                                        'contact_id_a' => $this->_applicantId,
                                        'contact_id_b' => $contactId,
                                        'relationship_type_id' => $relType->id,
                                );
                $relationships = civicrm_api( 'relationship','get',$relationParams );
                if ( empty($relationships['values']) ) {
                  $relParams = array(
                                     'version' => '3',
                                     'contact_id_a'         => $this->_applicantId,
                                     'contact_id_b'         => $contactId,
                                     'relationship_type_id' => $this->_relTypeId,
                                     'start_date'           => date('Ymd'),
                                     'is_permission_b_a'    => 1,
                                     'is_active'            => 1,
                                        );
                  $relationships = civicrm_api( 'relationship','create',$relParams);

                }
                                   
                // update relationship indexes
                $elementName  = isset($this->_relationMapper[$contactId]['parent_index']) ? 
                    $this->_relationMapper[$contactId]['parent_index'] : $this->_relationMapper[0]['parent_index'];
                $relName      = isset($this->_relationMapper[$contactId]['relationship_name']) ? 
                    $this->_relationMapper[$contactId]['relationship_name'] : $this->_relationMapper[0]['relationship_name'];
                
                $customParams = array( $elementName => $blockId ,
                                       $relName=> $params['contact'][$blockId]['relationship_name'] 
                                       );
                
                $customFields = array( );
                CRM_Core_BAO_CustomValueTable::postProcess( $customParams,
                                                            $customFields,
                                                            'civicrm_relationship',
                                                            $relationships['id'],
                                                            'Relationship' );
                
                // drop any old relationship if needed
                if ( $dropContactId ) {
                    $oldRelation = new CRM_Contact_DAO_Relationship( );
                    $oldRelation->contact_id_a = $this->_applicantId;
                    $oldRelation->contact_id_b = $dropContactId;
                    $oldRelation->relationship_type_id = $this->_relTypeId;
                    $oldRelation->delete();
                }

                //  Add or update family custom data
                $customParams = $customFields = array( );
                $familyCustomMapper = array_key_exists($contactId, $this->_familyCustomMapper) ? 
                    $this->_familyCustomMapper[$contactId] : $this->_familyCustomMapper[0];
            
                foreach( $familyCustomMapper as $colName => $elementName ) {
                    if ( CRM_Utils_Array::value( $colName, $params['contact'][$blockId] ) ) {
                        $customParams[$elementName] = $params['contact'][$blockId][$colName];
                    }
                }
                CRM_Core_BAO_CustomValueTable::postProcess( $customParams,
                                                            $customFields,
                                                            'civicrm_contact',
                                                            $contactId,
                                                            'Applicant_Parent' );
            }
        }
        
        //  Add or update applicant custom data like - applicant lives with, language ..etc
        $customParams = $customFields = array( );
        foreach( $this->_applicantGroupTree as $gid => $groupTree ) {
            foreach ( $groupTree['fields'] as $fid => $fieldTree ) {
                if( CRM_Utils_Array::value( $fieldTree['column_name'], $params ) ) {
                    $customParams[$fieldTree['element_name']] = $params[$fieldTree['column_name']];
                }
            }
        }
        CRM_Core_BAO_CustomValueTable::postProcess( $customParams,
                                                    $customFields,
                                                    'civicrm_contact',
                                                    $this->_applicantId,
                                                    'Applicant' ); 
        parent::endPostProcess( );
    }
}
