<?php

function splitName( $name, $separator = ',' ) {
  $names = explode( $separator, $name, 2 );
  return array( trim( $names[0] ),
    trim( $names[1] ) );
}

function getContactID( $lastName, $firstName, $grade = NULL) {
  if ($grade) {
    $sql = "
SELECT     c.id, c.display_name
FROM       civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE      c.first_name = %1
AND        c.last_name  = %2
AND        c.contact_sub_type LIKE '%Student%'
AND        s.grade_sis = %3
";
    $params = array( 1 => array( trim( $firstName ), 'String' ),
              2 => array( trim( $lastName ), 'String' ),
              3 => array( $grade, 'Integer' ) );
  } else {
    $sql = "
SELECT     c.id, c.display_name
FROM       civicrm_contact c
WHERE      c.first_name = %1
AND        c.last_name  = %2
AND        c.contact_sub_type LIKE '%Staff%'
";
    $params = array( 1 => array( trim( $firstName ), 'String' ),
              2 => array( trim( $lastName ), 'String' ) );
  }

  $dao = CRM_Core_DAO::executeQuery( $sql, $params );
  while ( $dao->fetch( ) ) {
    if ( $dao->N > 1 ) {
      print_r( $dao );
      echo "More than one contact ID for $lastName, $firstName\n";
      exit( );
    }
    return $dao->id;
  }

  echo "Could not find contact ID for $lastName, $firstName, $grade\n";
  return null;
}

function createAdvisors( ) {
  $fdRead  = fopen( '/home/lobo/SFS/SFS/MSAdvisors.csv', 'r' );

  if ( ! $fdRead ) {
    echo "Could not read file\n";
    exit( );
  }


  $values = array( );
  while ( $fields = fgetcsv( $fdRead ) ) {
    list( $studentName, $advisorName, $grade) = $fields;

    list($studentLast, $studentFirst) = splitName($studentName);
    list($advisorLast, $advisorFirst) = splitName($advisorName);

    $studentID = getContactID( $studentLast, $studentFirst, $grade);
    if (! $studentID) {
      echo "Could not find Student: $studentLast, $studentFirst, $grade\n";
      continue;
    }

    $advisorID = getContactID($advisorLast, $advisorFirst);
    if (! $advisorID) {
      echo "Could not find advisor: $advisorLast, $advisorFirst\n";
      continue;
    }

    $values[] = "( $advisorID, $studentID, 10, 1 )";
  }

  echo "
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
VALUES
" .
    implode( ",\n", $values ) .
    ";
";

  fclose( $fdRead  );
}

function initialize( ) {
  require_once '/home/lobo/www/d7/sites/school/civicrm.settings.php';

  require_once 'CRM/Core/Config.php';
  $config =& CRM_Core_Config::singleton( );

  require_once 'CRM/Core/Error.php';
}

function run( ) {
  initialize( );

  createAdvisors( );
}

run( );