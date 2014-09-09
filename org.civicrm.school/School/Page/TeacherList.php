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

require_once 'CRM/Core/Page.php';
require_once 'School/Utils/Conference.php';

class School_Page_TeacherList extends CRM_Core_Page {
  function run( ) {
    // get the advisorType id
    $advisorRelTypeId = School_Utils_Conference::getAdvisorRelTypeId();
    // get all the potential advisors
    $sql = "SELECT DISTINCT(c.id), c.display_name FROM civicrm_contact c INNER JOIN civicrm_relationship r ON r.contact_id_a = c.id WHERE r.relationship_type_id = {$advisorRelTypeId} ORDER BY   c.display_name ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $teacherList = array();    
    while ($dao->fetch( )) {
      $url = CRM_Utils_System::url( "civicrm/school/conferenceview",'tid=' .$dao->id , FALSE);
      $teacherList[$dao->display_name] = $url;
    }
    $conferenceUrl = CRM_Utils_System::url("civicrm/school/conference", 'reset=1');
    $this->assign('row', $teacherList);
    $this->assign('url', $conferenceUrl);
    parent::run( );	
  }
}