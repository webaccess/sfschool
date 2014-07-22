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

class School_Utils_PowerSchool {
  static function export( $time = null, $print = null ) {
    $clause = null;
    if ( $time ) {
      $clause = "AND s.updated_date >= '$time'";
    }

    $sql = "
SELECT     c.id as student_id,
           c.display_name as student_name,
           c.first_name as student_first_name,
           c.last_name as student_last_name,
           c.birth_date as student_birth,
           c.external_identifier as student_identifier,
           s.grade_sis as student_grade
FROM       civicrm_contact c
INNER JOIN civicrm_value_school_information s ON s.entity_id = c.id
WHERE      c.contact_sub_type LIKe '%Student%'
AND        s.is_currently_enrolled = 1
           $clause
ORDER BY   s.grade_sis, c.last_name
";

    $dao      =  CRM_Core_DAO::executeQuery( $sql );
    $template =& CRM_Core_Smarty::singleton( );

    $header = null;
    self::generateCSVHeader( $header );
    $content = array( $header );
    $content = array( );

    $daoFields = array(
      'student_name',
      'student_first_name',
      'student_last_name',
      'student_grade'
    );

    require_once 'School/Page/Family.php';
    require_once 'CRM/Utils/String.php';
    $config =& CRM_Core_Config::singleton( );

    $currentGrade = null;
    while ( $dao->fetch( ) ) {
      $page = new School_Page_Family( );
      $page->commonRun( $dao->student_id );

      $page->_values['birth_date'] = CRM_Utils_Date::customFormat( $dao->student_birth,
                                     '%m/%d/%Y' );
      $page->_values['student_identifier'] = substr( $dao->student_identifier, 8 );

      foreach ( $daoFields as $field ) {
        $page->_values[$field] = $dao->$field;
      }

      self::initRow( $row, $header );
      self::storeStudentInfo     ( $row, $page->_values );

      if ( isset( $page->_values['household'][3] ) ||
        isset( $page->_values['household'][4] ) ) {
        self::storeHouseholdInfoTwo( $row, $page->_values );
      } else {
        self::storeHouseholdInfoOne( $row, $page->_values );
      }

      self::storeEmergency( $row, $page->_values );

      self::storeMedicalInfo( $row, $page->_values );

      self::storeAllergiesInfo( $row, $page->_values );

      self::storeAuthorizations( $row, $page->_values );

      $content[$row[0]] = $row;
    }

    if ( $print ) {
      $fp = fopen("php://output", 'w');
    } else {
      $fp = fopen( $config->configAndLogDir . "PowerSchoolExport.csv", "w" );
    }

    foreach ( $content as $studentID => $row ) {
      fputcsv( $fp, $row, "\t" );
    }

    if ( ! $print ) {
      fclose( $fp );
    }

    // also send out the mail with the changes
    self::compareToLatestData($content);
  }

  static function generateCSVHeader( &$row ) {
    $row =
      array(
        0  => 'Student Number',
        1  => 'First Name',
        2  => 'Last Name',
        3  => 'Grade Level',
        4  => 'Dob',
        5  => 'Ethnicity',
        6  => 'Family Structure',
        7  => 'Father',
        8  => 'Father2',
        9  => 'Street',
        10 => 'City',
        11 => 'State',
        12 => 'Zip',
        13 => 'Home Phone',
        14 => 'Fatherdayphone',
        15 => 'Fathercellphone',
        16 => 'Fatheremail',
        17 => 'Mother',
        18 => 'Mother2',
        19 => 'Mailing Street',
        20 => 'Mailing City',
        21 => 'Mailing State',
        22 => 'Mailing Zip',
        23 => 'Mother Home Phone',
        24 => 'Motherdayphone',
        25 => 'Mothercellphone',
        26 => 'Motheremail',
        27 => 'FatherCounselorAuth',
        28 => 'Father2CounselorAuth',
        29 => 'MotherCounselorAuth',
        30 => 'Mother2CounselorAuth',
        31 => 'EmergencyContact1',
        32 => 'EC1Relationship',
        33 => 'EC1Phone',
        34 => 'EmergencyContact2',
        35 => 'EC2Relationship',
        36 => 'EC2Phone',
        37 => 'EmergencyContact3',
        38 => 'EC3Relationship',
        39 => 'EC3Phone',
        40 => 'InsuranceCompany',
        41 => 'GroupNumber',
        42 => 'PolicyNumber',
        43 => 'PhysicianName',
        44 => 'PhysicianPhone',
        45 => 'MedicalAuthorization',
        46 => 'ActivityAuthorization',
        47 => 'MediaAuthorization',
        48 => 'MiddleSchoolReleaseAuthorization',
        49 => 'HandbookAuthorization',
        50 => 'AllergiesMedical',
      );
  }

  static function initRow( &$row, &$header ) {
    $row = array( );
    for ( $i = 0 ; $i < count($header); $i++ ) {
      $row[$i] = '';
    }
  }

  static function storeStudentInfo( &$row, &$values ) {
    $row[0] = $values['student_identifier'];
    $row[1] = $values['student_first_name'];
    $row[2] = $values['student_last_name'];
    $row[3] = $values['student_grade'];
    $row[4] = $values['birth_date'];
    $row[5] = $values['race'];
    $row[6] = $values['family_structure'];
  }

  static function storeHouseholdInfoOne( &$row, &$values ) {
    // first fill name and address
    $pValues = $values['household'][1];

    $row[7]  = $pValues['display_name'];
    $row[9]  = $pValues['address'][1]['street_address'];
    $row[10] = $pValues['address'][1]['city'];
    $row[11] = 'CA'; // FIXME - hard code for now
    $row[12] = $pValues['address'][1]['postal_code'];
    // phone type: 1 - Home (13), 2 - Mobile (15), 4 - Work (14)
    $row[13] = $row[14] = $row[15] = '';
    $map = array(
      '' => 13,
      1  => 13,
      2  => 15,
      4  => 14
    );
    foreach ($pValues['phone'] as $phoneID => $phoneValue) {
      $phone = trim($phoneValue['phone']);
      if (empty($phone)) {
        continue;
      }
      $row[$map[CRM_Utils_Array::value('phone_type_id', $phoneValue, '')]] = $phoneValue['phone'];
    }
    $row[16] = $pValues['email'][1]['email'];
    $row[27] = $pValues['counselor_authorization'];

    $pValues = $values['household'][2];
    $row[17] = $pValues['display_name'];
    $row[23] = $row[24] = $row[25] = '';
    $map = array(
      '' => 23,
      1  => 23,
      2  => 25,
      4  => 24
    );
    foreach ($pValues['phone'] as $phoneID => $phoneValue) {
      $phone = trim($phoneValue['phone']);
      if (empty($phone)) {
        continue;
      }
      $row[$map[CRM_Utils_Array::value('phone_type_id', $phoneValue, '')]] = $phoneValue['phone'];
    }
    $row[26] = $pValues['email'][1]['email'];
    $row[29] = $pValues['counselor_authorization'];
  }

  static function storeHouseholdInfoTwo( &$row, &$values ) {
    // first fill name and address
    $pValues = $values['household'][1];
    $oValues = $values['household'][2];

    $row[7]  = $pValues['display_name'];
    $row[8]  = $oValues['display_name'];
    $row[9]  = $pValues['address'][1]['street_address'];
    $row[10] = $pValues['address'][1]['city'];
    $row[11] = 'CA'; // FIXME - hard code for now
    $row[12] = $pValues['address'][1]['postal_code'];

    // phone type: 1 - Home (13), 2 - Mobile (15), 4 - Work (14)
    $row[13] = $row[14] = $row[15] = '';
    $map = array(
      '' => 13,
      1  => 13,
      2  => 15,
      4  => 14
    );
    foreach ($pValues['phone'] as $phoneID => $phoneValue) {
      $phone = trim($phoneValue['phone']);
      if (empty($phone)) {
        continue;
      }
      $row[$map[CRM_Utils_Array::value('phone_type_id', $phoneValue, '')]] = $phoneValue['phone'];
    }
    $row[16] = $pValues['email'][1]['email'];
    $row[27] = $pValues['counselor_authorization'];
    $row[28] = $oValues['counselor_authorization'];

    $pValues = $values['household'][3];
    $oValues = $values['household'][4];
    $row[17] = $pValues['display_name'];
    $row[18] = $oValues['display_name'];
    $row[19]  = $pValues['address'][1]['street_address'];
    $row[20] = $pValues['address'][1]['city'];
    $row[21] = 'CA'; // FIXME - hard code for now
    $row[22] = $pValues['address'][1]['postal_code'];

    $row[23] = $row[24] = $row[25] = '';
    $map = array(
      '' => 23,
      1  => 23,
      2  => 25,
      4  => 24
    );
    foreach ($pValues['phone'] as $phoneID => $phoneValue) {
      $phone = trim($phoneValue['phone']);
      if (empty($phone)) {
        continue;
      }
      $row[$map[CRM_Utils_Array::value('phone_type_id', $phoneValue, '')]] = $phoneValue['phone'];
    }

    $row[26] = $pValues['email'][1]['email'];
    $row[29] = $pValues['counselor_authorization'];
    $row[30] = $oValues['counselor_authorization'];
  }

  static function storeEmergency( &$row, &$values ) {
    $count  = 1;
    foreach ( $values['emergency'] as $eid => $eValues ) {
      if ( $count == 1 ) {
        $start = 31;
      } else if ( $count == 2 ) {
        $start = 34;
      } else if ( $count == 3 ) {
        $start = 37;
      }
      $row[$start++] = $eValues['display_name'];
      $row[$start++] = $eValues['relationship_name'];
      $row[$start]   = str_replace( '&nbsp;', ' ', $eValues['phone_display'] );
      $count++;
    }
  }

  static function storeMedicalInfo( &$row, &$values ) {
    $mapping = array( 'Insurance Company'     => 40,
               'Group Number'          => 41,
               'Policy Number'         => 42,
               'Physician Name'        => 43,
               'Physician Phone'       => 44,
               'Medical Authorization' => 45,
               'Child Insured?'        => 51 );

    if ( isset( $values['medical']['info'] ) &&
      isset( $values['medical']['info']['details'] ) ) {
      foreach ( $values['medical']['info']['details'] as $dontCare => $details ) {
        if ( isset( $mapping[$details['title']] ) ) {
          $row[$mapping[$details['title']]] = trim($details['value']);
        } else {
          CRM_Core_Error::fatal( "{$details['title']} does not have a mapping" );
        }
      }
    }
  }

  static function storeAllergiesInfo( &$row, &$values ) {
    $allergies = array( );
    foreach ( $values['medical']['details'] as $dontCare => $details ) {
      if ( ! empty( $details['medical_type'] ) &&
        ! empty( $details['description'] ) ) {
        $allergies[] =
          trim( $details['medical_type'] ) .
          "::" .
          trim( preg_replace( '/\s+/', ' ', $details['description'] ) );
      }
    }
    if ( ! empty( $allergies ) ) {
      $row[50] = implode( ":::", $allergies );
    }
  }

  static function storeAuthorizations( &$row, &$values ) {
    $mapping = array( 'Activity Authorization'              => 46,
               'Media Authorization'                 => 47,
               'Middle School Release Authorization' => 48,
               'Handbook Authorization'              => 49 );

    foreach ( $values['release'] as $customGroupId => $customValues ) {
      foreach ( $customValues as $cvID => $cvValues ) {
        foreach ( $cvValues['fields'] as $fieldID => $fieldValue ) {
          if ( $fieldValue['field_type']  == 'Radio' &&
            $fieldValue['field_title'] != 'Currently Enrolled' ) {
            if ( isset( $mapping[$fieldValue['field_title']] ) ) {
              $row[$mapping[$fieldValue['field_title']]] = trim( $fieldValue['field_value'] );
            }
          }
        }
      }
    }
  }

  static function compareToLatestData($content = null) {
    $config = CRM_Core_Config::singleton();

    $latestFP = fopen( $config->configAndLogDir . "LatestSchoolData.csv", "r" );
    if (! $latestFP) {
      CRM_Core_Error::debug_log_message("Could not open LatestSchoolData.csv");
      return;
    }

    $newFP = fopen( $config->configAndLogDir . "LatestSchoolNew.csv", "w" );
    if (! $newFP) {
      CRM_Core_Error::debug_log_message("Could not open LatestSchoolNew.csv");
      return;
    }

    if (!$content) {
      $hackFP = fopen( $config->configAndLogDir . "HackSchoolData.csv", "r" );
      if (!$hackFP) {
        CRM_Core_Error::debug_log_message("Could not open hack file: {$config->configAndLogDir}");
        return;
      }
      $content = array();
      while ($row = fgetcsv($hackFP, 0, "\t")) {
        $content[$row[0]] = $row;
      }
    }

    $allChanges = null;
    while ($current = fgetcsv($latestFP, 0, "\t")) {
      // check if student exists and is modified
      $studentID = $current[0];
      if (! isset($content[$studentID])) {
        fputcsv($newFP, $current, "\t");
        continue;
      }

      $new = $content[$studentID];
      fputcsv($newFP, $new, "\t");

      // now compare the name and email address of the household info
      // 7 - 26p
      $changeRecord = "Changes for Student: {$current[2]}, {$current[1]}\n";
      $change = FALSE;
      for ($i = 7; $i < 27; $i++ ) {
        if (trim($current[$i]) != trim($new[$i])) {
          $change = TRUE;
          $changeRecord .= "\t{$current[$i]} => {$new[$i]}\n";
        }
      }

      if ($change) {
        $allChanges .= $changeRecord;
        $allChanges .= "\n";
      }
    }

    fclose($latestFP);
    fclose($newFP);

    if ($allChanges) {
      // mail $allChanges to some addresses
      $toDisplayName = 'Update list at SFS';
      $toEmail = 'update@sfschool.org';
      $subject = 'Changes made in Parent Portal';
      $ccEmail = 'lobo@civicrm.org';

      require_once 'School/Utils/Mail.php';
      School_Utils_Mail::sendMail(
        $allChanges,
        $subject,
        $toDisplayName,
        $toEmail,
        $ccEmail
      );

      if (!rename(
          $config->configAndLogDir . "LatestSchoolNew.csv",
          $config->configAndLogDir . "LatestSchoolData.csv"
        )) {
        CRM_Core_Error::debug_log_message("Could not overwrite student record file");
        return;
      }
    }
  }

}