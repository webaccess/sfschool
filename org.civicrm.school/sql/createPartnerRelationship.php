<?php

function initialize( ) {
  require_once '../bin/Utils.php';

  global $civicrm_root;

  require_once "$civicrm_root/civicrm.config.php";
  require_once 'CRM/Core/Config.php';
  $config =& CRM_Core_Config::singleton( );

  require_once 'CRM/Core/Error.php';
}

function getRelationshipType( $name ) {
  $partnerRelationshipType = null;
  $query = "
SELECT id
FROM   civicrm_relationship_type
WHERE  name_a_b = %1
";
  $params = array( 1 => array( $name, 'String' ) );
  $type = CRM_Core_DAO::singleValueQuery( $query, $params );
  if ( ! $type ) {
    CRM_Core_Error::fatal( );
  }

  return $type;
}

function createPartnerRelationship( $contactID_a,
  $contactID_b,
  $relationshipTypeID ) {
  $query = "
SELECT id
FROM   civicrm_relationship
WHERE  relationship_type_id = %1
AND    ( ( contact_id_a = %2 AND contact_id_b = %3 )
OR       ( contact_id_a = %3 AND contact_id_b = %2 ) )
";
  $params = array( 1 => array( $relationshipTypeID, 'Integer' ),
            2 => array( $contactID_a       , 'Integer' ),
            3 => array( $contactID_b       , 'Integer' ) );
  $id = CRM_Core_DAO::singleValueQuery( $query, $params );
  if ( $id ) {
    return $id;
  }

  $dao = new CRM_Contact_DAO_Relationship( );
  $dao->relationship_type_id = $relationshipTypeID;
  $dao->contact_id_a = $contactID_a;
  $dao->contact_id_b = $contactID_b;
  $dao->is_active    = 1;
  $dao->is_permission_a_b = 1;
  $dao->is_permission_b_a = 1;
  $dao->save( );

  return $dao->id;
}

function createPartnerRelationships( ) {
  $parentRelationshipType  = getRelationshipType( 'Child of' );
  $partnerRelationshipType = getRelationshipType( 'Spouse of' );

  $query = "
SELECT     p.id as parent_id, p.display_name as parent_name, c.id as child_id
FROM       civicrm_contact p
INNER JOIN civicrm_relationship r ON r.contact_id_b = p.id
INNER JOIN civicrm_contact c ON r.contact_id_a = c.id
INNER JOIN civicrm_value_school_information s ON s.entity_id = c.id
WHERE      r.relationship_type_id = $parentRelationshipType
AND        r.is_active    = 1
AND        s.is_currently_enrolled = 1
ORDER BY c.id
";

  $dao = CRM_Core_DAO::executeQuery( $query );

  $children = $name = array( );
  while ( $dao->fetch( ) ) {
    if ( ! array_key_exists( $dao->child_id, $children ) ) {
      $children[$dao->child_id] = array( 'parents' => array( ) );
    }
    $children[$dao->child_id]['parents'][$dao->parent_id] = $dao->parent_name;
    $name[$dao->parent_id] = $dao->parent_name;
  }

  require_once 'CRM/Contact/DAO/Relationship.php';

  $alreadyCreated = array( );
  foreach ( $children as $id => $info ) {
    if ( count( $children[$id]['parents'] ) <= 1 ) {
      continue;
    }

    $parentIDs = array_keys( $children[$id]['parents'] );
    foreach ( $parentIDs as $lowerParentID ) {
      foreach ( $parentIDs as $higherParentID ) {
        if ( $lowerParentID == $higherParentID ) {
          continue;
        }

        if ( array_key_exists( "{$lowerParentID}_{$higherParentID}", $alreadyCreated ) ) {
          continue;
        }

        $alreadyCreated["{$lowerParentID}_{$higherParentID}"] = 1;
        $alreadyCreated["{$higherParentID}_{$lowerParentID}"] = 1;
        echo "Creating a partner relationship between {$name[$lowerParentID]} and {$name[$higherParentID]}\n";
        createPartnerRelationship( $lowerParentID,
          $higherParentID,
          $partnerRelationshipType );
      }
    }
  }

}

function run( ) {
  initialize( );

  createPartnerRelationships( );
}

run( );