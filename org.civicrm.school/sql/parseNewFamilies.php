<?php

define( 'HOME_LOCATION_TYPE_ID', 1 );

function parsePSFile( &$studentInfo ) {

  $fdRead  = fopen( '/home/lobo/SFS/PowerSchool/export/NewFamilies_2013.csv', 'r' );

  if ( ! $fdRead ) {
    echo "Could not read file\n";
    exit( );
  }

  // read first line
  $fields = fgetcsv( $fdRead );

  $studentInfo = array( );

  $count = 0;
  while ( $fields = fgetcsv( $fdRead ) ) {
    $count++;
    parseRow( $fields, $studentInfo );
  }

  fclose( $fdRead  );
}

function parseRow( &$fields, &$studentInfo ) {
  if ( array_key_exists( $fields[5], $studentInfo ) ) {
    CRM_Core_Error::fatal( );
  }

  if ( empty( $fields[6] ) ) {
    CRM_Core_Error::fatal( 'First parent name in household 1 cannot be empty' );
  }

  $studentInfo[$fields[5]] =
    array( 'student_id'  => $fields[5],
      'first_name'  => $fields[0],
      'last_name'   => $fields[1] );

  $studentInfo[$fields[5]]['grade']     =
    $studentInfo[$fields[5]]['grade_sis'] = $fields[2];

  // fix for pre K to include north / south
  if ( $fields[2] < 1 ) {
    $grade = null;
    if ( $fields[2] == -2 ) {
      $grade = 'PK3 ';
    } else if ( $fields[2] == -1 ) {
      $grade = 'PK4 ';
    } else {
      $grade = 'K ';
    }

    $grade .= strtoupper( trim( $fields[3] ) );
    $studentInfo[$fields[5]]['grade'] = $grade;
  }


  // first check if easy case
  // 2 parents, 1 household
  if ( empty( $fields[22] ) ) {
    parseOneHousehold( $fields, $studentInfo );
  } else {
    parseTwoHousehold( $fields, $studentInfo );
  }
}

function parseOneHousehold( &$fields, &$studentInfo ) {
  $studentInfo[$fields[5]]['parents'] = array( );

  $studentInfo[$fields[5]]['parents'][1] =
    array( 'name' => $fields[6],
      'email'      => $fields[7],
      'parent_index' => 1 );

  if ( ! empty( $fields[8] ) ) {
    $studentInfo[$fields[5]]['parents'][2] =
      array( 'name' => $fields[8],
        'email'      => $fields[9],
        'parent_index' => 2 );
  }
}

function parseTwoHousehold( &$fields, &$studentInfo ) {
  $studentInfo[$fields[5]]['parents'] = array( );

  $studentInfo[$fields[5]]['parents'][1] =
    array( 'name' => $fields[6],
      'street_address' => $fields[13],
      'city' => $fields[14],
      'state' => $fields[15],
      'postal_code' => $fields[16],
      'phone_home' => $fields[17],
      'phone_work' => $fields[18],
      'phone_cell' => $fields[19],
      'email'      => $fields[20],
      'parent_index' => 1 );

  if ( ! empty( $fields[12] ) ) {
    $studentInfo[$fields[5]]['parents'][2] =
      array( 'name' => $fields[9],
        'parent_index' => 2 );
  }

  if ( empty( $fields[21] ) ) {
    CRM_Core_Error::fatal( 'First parent name in household 2 cannot be empty' );
  }

  $studentInfo[$fields[5]]['parents'][3] =
    array( 'name' => $fields[21],
      'street_address' => $fields[23],
      'city' => $fields[24],
      'state' => $fields[25],
      'postal_code' => $fields[26],
      'phone_home' => $fields[27],
      'phone_work' => $fields[28],
      'phone_cell' => $fields[29],
      'email'      => $fields[30],
      'parent_index' => 3 );

  if ( ! empty( $fields[22] ) ) {
    $studentInfo[$fields[5]]['parents'][4] =
      array( 'name' => $fields[22],
        'parent_index' => 4 );
  }

}

function initialize( ) {
  require_once '../bin/Utils.php';

  global $civicrm_root;

  require_once "$civicrm_root/civicrm.config.php";

  require_once 'CRM/Core/Config.php';
  $config =& CRM_Core_Config::singleton( );

  require_once 'CRM/Core/Error.php';
}

function findContactIDs( &$studentInfo, &$errors ) {
  foreach ( $studentInfo as $studentID => &$student ) {
    // first get student id
    $query = "
SELECT     c.id, s.grade
FROM       civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE      c.external_identifier = %1
";
    $params = array( 1 => array( "Student-{$studentID}", 'String' ) );
    $dao = CRM_Core_DAO::executeQuery( $query, $params );
    $dao->fetch( );
    if ( ! isset( $dao->id ) ) {
      $errors['No Contact Info Student'][] = "$studentID, {$student['first_name']}, {$student['last_name']}";
      createStudent( $student );

      $dao = CRM_Core_DAO::executeQuery( $query, $params );
      $dao->fetch( );
      if ( ! $dao->id ) {
        CRM_Core_Error::fatal( );
      }
    }

    $student['contact_id'] = $dao->id;

    // next get all parents id
    foreach ( $student['parents'] as $parentIndex =>& $parent ) {
      list( $parent['last_name' ] ,
        $parent['first_name'] ) = splitName( $parent['name'] );

      $query = "
SELECT     p.id as parent_id, r.id as relationship_id
FROM       civicrm_contact p
INNER JOIN civicrm_relationship r ON r.contact_id_b = p.id
INNER JOIN civicrm_contact c      ON r.contact_id_a = c.id
WHERE      r.is_active = 1
AND        r.relationship_type_id = 1
AND        c.id = %1
AND        ( ( p.first_name = %2 AND p.last_name = %3 ) OR
             ( p.sort_name LIKE %4 ) )
";
      $params = array( 1 => array( $student['contact_id'], 'Integer' ),
                2 => array( $parent['first_name'] , 'String' ),
                3 => array( $parent['last_name']  , 'String' ),
                4 => array( "{$parent['name']}%"  , 'String' ) );
      $dao = CRM_Core_DAO::executeQuery( $query, $params );
      $dao->fetch( );
      if ( ! isset( $dao->parent_id ) ) {
        $errors['No Contact Info Parent'][] =
          "$studentID, {$student['first_name']}, {$student['last_name']}, {$parent['first_name']}, {$parent['last_name']}, {$parent['name']}, {$parent['email']}";
        createParent( $student, $parent );
        $dao = CRM_Core_DAO::executeQuery( $query, $params );
        $dao->fetch( );
        if ( ! $dao->parent_id ) {
          CRM_Core_Error::fatal( );
        }
      }
      $parent['contact_id']      = $dao->parent_id;
      $parent['relationship_id'] = $dao->relationship_id;

      // save the parent id info and also check email address
      checkAndSaveParentInfo( $student, $parent, $errors );
    }
  }
}

function checkAndSaveParentInfo( &$student, &$parent, &$errors ) {
  checkAndSaveParentEmail  ( $student, $parent, $errors );
  updateRelationshipCustomData( $parent );
}

function updateRelationshipCustomData( &$parent ) {
  $params = array( 1 => array( $parent['relationship_id'], 'Integer' ),
            2 => array( $parent['parent_index']   , 'Integer' ) );

  $query = "
SELECT id
FROM   civicrm_value_parent_relationship_data
WHERE  entity_id = %1
";
  $customID = CRM_Core_DAO::singleValueQuery( $query, $params );

  if ( $customID ) {
    $query = "
UPDATE civicrm_value_parent_relationship_data
SET    parent_index = %2
WHERE  entity_id = %1
";
  } else {
    $query = "
INSERT INTO civicrm_value_parent_relationship_data
  ( entity_id, parent_index )
VALUES
  ( %1, %2 )
";
  }

  CRM_Core_DAO::executeQuery( $query, $params );
}

function checkAndSaveParentAddress( &$student, &$parent ) {
  $params = array( 1 => array( $parent['contact_id'], 'Integer' ),
            2 => array( HOME_LOCATION_TYPE_ID, 'Integer' ) );

  if ( ! CRM_Utils_Array::value( 'street_address', $parent ) ) {
    // delete existing address
    $query = "
DELETE
FROM   civicrm_address
WHERE  contact_id = %1
AND    location_type_id = %2
";
    CRM_Core_DAO::executeQuery( $query, $params );
    return;
  }

  // check if address already exists, if so reuse that
  $query = "
SELECT id
FROM   civicrm_address
WHERE  contact_id = %1
AND    location_type_id = %2
";
  $addressID = CRM_Core_DAO::singleValueQuery( $query, $params );

  $params[3] = array( trim( $parent['street_address'] ), 'String' );
  $city = CRM_Utils_Array::value( 'city', $parent, 'San Francisco' );
  $params[4] = array( trim( $city ), 'String' );

  $postalCode = CRM_Utils_Array::value( 'postal_code', $parent, 'San Francisco' );
  if ( $postalCode ) {
    $params[5] = array( $postalCode, 'String' );
  }

  $stateProvinceID = 1004;
  $params[6] = array( $stateProvinceID, 'Integer' );

  $countryID = 1228;
  $params[7] = array( $countryID, 'Integer' );

  if ( $addressID ) {
    $params[8] = array( $addressID, 'Integer' );
    $query = "
UPDATE civicrm_address
SET    street_address    = %3,
       city              = %4,
       postal_code       = %5,
       state_province_id = %6,
       country_id        = %7,
       is_primary        = 1
WHERE  id = %8
";
  } else {
    $query = "
INSERT INTO civicrm_address
  ( contact_id, location_type_id, street_address, city, postal_code, state_province_id, country_id, is_primary )
VALUES
  ( %1, %2, %3, %4, %5, %6, %7, 1 )
";
  }

  CRM_Core_DAO::executeQuery( $query, $params );
}

function checkAndSaveParentEmail( &$student, &$parent, &$errors ) {
  if ( ! CRM_Utils_Array::value( 'email', $parent ) ) {
    return;
  }

  $newEmail = strtolower( trim( $parent['email'] ) );

  // first check if email exists and matches
  $query = "
SELECT email
FROM   civicrm_email
WHERE  contact_id = %1
";
  $params = array( 1 => array( $parent['contact_id'], 'Integer' ) );
  $email = CRM_Core_DAO::singleValueQuery( $query, $params );
  $email = strtolower( trim($email) );

  if ( ! empty( $email ) ) {
    // check if user has logged into drupal account
    $query = "
SELECT uid, login
FROM   drupal_sfs.users
WHERE  ( name = %1 OR mail = %1 )
";
    $params = array( 1 => array( $email, 'String' ) );
    $user = CRM_Core_DAO::executeQuery( $query, $params );
    $user->fetch( );
    if ( ! isset( $user->uid ) ) {
      $errors['Drupal Account does not exist'][] =
        "{$student['first_name']}, {$student['last_name']}, {$parent['first_name']}, {$parent['last_name']}, {$parent['name']}, {$parent['email']}, CiviCRM Email: $email";
    }
  }

  if ( $email != $newEmail ) {
    if ( empty( $email ) ) {
      $errors['No Email in CiviCRM'][] =
        "{$student['first_name']}, {$student['last_name']}, {$parent['first_name']}, {$parent['last_name']}, {$parent['name']}, {$parent['email']}";
    } else if ( strpos( $newEmail, '/' ) !== false ) {
      $errors['Multiple Emails in PowerSchool'][] =
        "Email does not match for parent of {$student['contact_id']}, {$student['first_name']}, {$student['last_name']}, {$parent['name']}: Drupal Email: $email, PowerSchool Email: $newEmail";
    } else {
      $loginMessage =
        isset( $user->login ) && $user->login ?
        'User has logged in' :
        'User has not logged in';
      $errors['Email MisMatch'][] =
        "Email does not match for parent of {$student['contact_id']}, {$student['first_name']}, {$student['last_name']}, {$parent['name']}: Drupal Email: $email, PowerSchool Email: $newEmail. {$loginMessage}";
    }
  }
}

function checkAndSaveParentPhone( &$student, &$parent ) {
  $primary = 1;
  // add phone numbers
  addPhone( $parent['contact_id'],
    HOME_LOCATION_TYPE_ID,
    CRM_Utils_Array::value( 'phone_home', $parent ),
    HOME_PHONE_TYPE_ID,
    $primary );
  addPhone( $parent['contact_id'],
    HOME_LOCATION_TYPE_ID,
    CRM_Utils_Array::value( 'phone_cell', $parent ),
    CELL_PHONE_TYPE_ID,
    $primary );
  addPhone( $parent['contact_id'],
    HOME_LOCATION_TYPE_ID,
    CRM_Utils_Array::value( 'phone_work', $parent ),
    WORK_PHONE_TYPE_ID,
    $primary );
}

function addPhone( $contactID,
  $locationTypeID,
  $phone,
  $phoneTypeID,
  &$primary ) {
  $params = array( 1 => array( $contactID     , 'Integer' ),
            2 => array( $locationTypeID, 'Integer' ),
            3 => array( $phoneTypeID   , 'Integer' ),
            4 => array( trim($phone)   , 'String'  ),
            5 => array( $primary       , 'Integer' ) );

  if ( empty( $phone ) ) {
    $query = "
DELETE
FROM   civicrm_phone
WHERE  contact_id = %1
AND    location_type_id = %2
AND    phone_type_id = %3
";
    CRM_Core_DAO::executeQuery( $query, $params );
  } else {
    $query = "
SELECT id
FROM   civicrm_phone
WHERE  contact_id = %1
AND    location_type_id = %2
AND    phone_type_id = %3
";
    $phoneID = CRM_Core_DAO::singleValueQuery( $query, $params );
    if ( $phoneID ) {
      $query = "
UPDATE civicrm_phone
SET    phone = %4,
       is_primary = %5
WHERE  id = %6
";
      $params[6] = array( $phoneID, 'Integer' );
    } else {
      $query = "
INSERT INTO civicrm_phone ( contact_id, location_type_id, phone_type_id, phone, is_primary )
VALUES ( %1, %2, %3, %4, %5 )
";
    }
    CRM_Core_DAO::executeQuery( $query, $params );
    $primary = 0;
  }
}

function matchName( $name ) {
  static $names = array(
    'Lee, Mike' => 'Lee, Michael',
  );

  $name = trim( $name );
  return CRM_Utils_Array::value( $name, $names, $name );
}

function splitName( $name, $separator = ' ' ) {
  $name = matchName( $name );

  $names = explode( $separator, $name, 2 );
  return array(
    trim($names[1]),
    trim($names[0])
  );
}

function createStudent( &$student ) {
  // create student contact record
  $contactData = array( 'first_name'          => $student['first_name'],
                 'last_name'           => $student['last_name'] ,
                 'external_identifier' => "Student-{$student['student_id']}",
                 'contact_type'        => 'Individual',
                 'contact_sub_type'    => 'Student',
                 'custom_2'            => $student['grade'],
                 'custom_14'           => $student['grade_sis'],
                 'custom_24'           => 'Regular',
                 'version'             => 3,
  );

  require_once 'api/api.php';
  $result = civicrm_api('contact', 'create', $contactData);
}

function createParent( &$student, &$parent ) {
  // first checka and create parent record
  $query = "
SELECT     p.id as parent_id
FROM       civicrm_contact p
INNER JOIN civicrm_value_school_information s ON p.id = s.entity_id
WHERE      ( ( p.first_name = %1 AND p.last_name = %2 ) OR
             ( p.sort_name LIKE %3 ) )
AND        ( ( p.contact_sub_type LIKE '%Parent%' ) OR ( p.contact_sub_type LIKE '%Staff%' ) )
";
  $params = array( 1 => array( $parent['first_name'], 'String' ),
            2 => array( $parent['last_name'], 'String' ),
            3 => array( "{$parent['name']}%", 'String' ) );
  $dao = CRM_Core_DAO::executeQuery( $query, $params );
  $dao->fetch( );
  if ( ! isset( $dao->parent_id ) ) {
    $contactData = array( 'first_name'          => $parent['first_name'],
                   'last_name'           => $parent['last_name'] ,
                   'email'               => $parent['email'],
                   'contact_type'        => 'Individual',
                   'contact_sub_type'    => 'Parent',
                   'version'             => 3,
    );

    require_once 'api/api.php';
    $result = civicrm_api('contact', 'create', $contactData);
    $parentID = $result['id'];
  } else {
    $parentID = $dao->parent_id;
  }

  // next create relationship between parent and student
  $sql = "
INSERT INTO civicrm_relationship
  ( contact_id_a, contact_id_b, relationship_type_id, is_active, is_permission_a_b, is_permission_b_a )
VALUES
  ( %1, %2, 1, 1, 0, 1 )
";
  $params = array( 1 => array( $student['contact_id'], 'Integer' ),
            2 => array( $parentID             , 'Integer' ) );
  CRM_Core_DAO::executeQuery( $sql, $params );
}

function run( ) {
  initialize( );

  $studentInfo = null;
  $errors = array( 'No Contact Info Student'        => array( ),
            'No Contact Info Parent'         => array( ),
            'No Email in CiviCRM'            => array( ),
            'Drupal Account does not exist'  => array( ),
            'Email MisMatch'                 => array( ),
            'Multiple Emails in PowerSchool' => array( ),
  );
  parsePSFile( $studentInfo );

  // print_r( $studentInfo );

  findContactIDs( $studentInfo, $errors );

  print_r( $errors );
}

run( );