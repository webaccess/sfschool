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

class School_Utils_ParentStudentInfo {
  /**
   * Get parent student info
   */
  static function getParentStudentInfo( ) {  
    $sql = "
SELECT SQL_CALC_FOUND_ROWS cs_civireport.sort_name as civicrm_contact_sort_name,
cs_civireport.id as civicrm_contact_id,
cs_civireport.id as civicrm_contact_exposed_id,
school_civireport.grade as civicrm_value_school_information_grade,
cp1_civireport.display_name as civicrm_contact_parent1_display_name,
cp2_civireport.display_name as civicrm_contact_parent2_display_name,
cp1add_civireport.street_address as civicrm_address_parent1_street_address,
cp1add_civireport.city as civicrm_address_parent1_city,
cp1add_civireport.state_province_id as civicrm_address_parent1_state_province_id,
cp1add_civireport.postal_code as civicrm_address_parent1_postal_code,
cp1add_civireport.geo_code_1 civicrm_address_parent1_geo_code_1,
cp1add_civireport.geo_code_2 civicrm_address_parent1_geo_code_2, 
cp2add_civireport.street_address as civicrm_address_parent2_street_address,
cp2add_civireport.city as civicrm_address_parent2_city,
cp2add_civireport.state_province_id as civicrm_address_parent2_state_province_id,
cp2add_civireport.postal_code as civicrm_address_parent2_postal_code, 
cp2add_civireport.geo_code_1 civicrm_address_parent2_geo_code_1,
cp2add_civireport.geo_code_2 civicrm_address_parent2_geo_code_2 

 FROM 
                         civicrm_contact cs_civireport

                         INNER JOIN civicrm_value_school_information school_civireport ON 
                                cs_civireport.id = school_civireport.entity_id

                         INNER JOIN civicrm_relationship r1 ON 
                                r1.contact_id_a = cs_civireport.id

                         INNER JOIN civicrm_relationship r2 ON 
                                r2.contact_id_a = cs_civireport.id

                         LEFT  JOIN civicrm_contact cp1_civireport ON 
                               cp1_civireport.id = r1.contact_id_b

                         LEFT  JOIN civicrm_address cp1add_civireport ON
                                cp1add_civireport.contact_id = cp1_civireport.id AND cp1add_civireport.is_primary=1

                         LEFT  JOIN civicrm_contact cp2_civireport ON 
                               cp2_civireport.id = r2.contact_id_b

                         LEFT  JOIN civicrm_address cp2add_civireport ON
                                cp2add_civireport.contact_id = cp2_civireport.id AND cp2add_civireport.is_primary=1

  WHERE school_civireport.is_currently_enrolled = 1 AND r1.relationship_type_id = 1 AND r2.relationship_type_id = 1 AND ( cp1_civireport.id < cp2_civireport.id OR cp2_civireport.id IS NULL ) AND  cs_civireport.contact_sub_type like '%Student%'    ORDER BY school_civireport.grade_sis, school_civireport.grade, cs_civireport.sort_name

";
    $dao = CRM_Core_DAO::executeQuery( $sql );
    $parentStudentInfo = array();
    while ( $dao->fetch( ) ) {
      $address = array();
      $address[] = array(
			 'Street Address' => $dao->civicrm_address_parent1_street_address,
			 'City' => $dao->civicrm_address_parent1_city,
			 'State Province' => $dao->civicrm_address_parent1_state_province_id,
			 'Postal Code' => $dao->civicrm_address_parent1_postal_code,
			 'Address geocode1' => $dao->civicrm_address_parent1_geo_code_1,
			 'Address geocode2' => $dao->civicrm_address_parent1_geo_code_1
			 );
      
      if (!empty($dao->civicrm_address_parent2_street_address) 
	  && $dao->civicrm_address_parent1_street_address != $dao->civicrm_address_parent2_street_address) {
	$address[] = array(
			   'Street Address' => $dao->civicrm_address_parent2_street_address,
			   'City' => $dao->civicrm_address_parent2_city,
			   'State Province' => $dao->civicrm_address_parent2_state_province_id,
			   'Postal Code' => $dao->civicrm_address_parent2_postal_code,
			   'Address geocode1' => $dao->civicrm_address_parent2_geo_code_1,
			   'Address geocode2' => $dao->civicrm_address_parent2_geo_code_1
			   );
      }

      $parentStudentInfo[] = array(
				   'Parent Name 1' => $dao->civicrm_contact_parent1_display_name,
				   'Parent Name 2' => $dao->civicrm_contact_parent2_display_name,
				   'Parent Address' => $address,
				   'Student Name'  => $dao->civicrm_contact_sort_name,
				   );
    }
    return $parentStudentInfo; 
  }
}
