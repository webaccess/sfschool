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

require_once 'CRM/Core/Form.php';
require_once 'School/Utils/Conference.php';

class School_Form_ConferenceView extends CRM_Core_Form {

  protected $_multipleDay   = false;

  protected $_numberOfSlots = 35;

  protected $_confDate = NULL;
  
  function preProcess( ) {
    parent::preProcess( );
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/school/teacherlist', 'reset=1');
    $session->pushUserContext($url);
    $this->_multipleDay = CRM_Utils_Request::retrieve('multipleDay', 'Boolean', $this, false);
    $this->_tid = CRM_Utils_Request::retrieve('tid', 'Int', $this, false);      
    if (!$this->_tid) {
      CRM_Utils_System::redirect( CRM_Utils_System::url('civicrm/school/teacherlist', "reset=1"));
    }
    //get activity type id
    $activity_type_id = CRM_Core_OptionGroup::values('activity_type', NULL, NULL, NULL,'AND v.name IN ("Parent Teacher Conference")');
    $param = array('contact_id'=> $this->_tid, 'activity_type_id' => key($activity_type_id), 'sort' =>'tbl.activity_date_time asc');
    
    // get all parent teacher related conference activities
    $conferenceActivity = CRM_Activity_BAO_Activity::getActivities($param);
    
    //assign values
    $this->_numberOfSlots = count($conferenceActivity);
    $this->assign('numberOfSlots', $this->_numberOfSlots);
    if ($this->_numberOfSlots > 0) {
      $this->_conferenceSchedule = array();
      $this->_assigne = array();
      $this->_activityId = array();
      $i = 1;
      foreach ($conferenceActivity as $k=>$v) {
        if(!isset($this->_confDate)) {
          $this->_confDate = date('Y-m-d', strtotime($conferenceActivity[$k]['activity_date_time']));
        }
        $this->_activityId[] = $k;
        $this->_assigne[$i] = str_replace(',', ' ', $v['target_contact_name'][key($v['target_contact_name'])]);
        $this->_conferenceSchedule[$i] = $conferenceActivity[$k]['activity_date_time'];
        $i++;
      }
      if (isset($this->_conferenceSchedule[2])) {
        $this->_duration = round(abs(strtotime($this->_conferenceSchedule[1]) - strtotime($this->_conferenceSchedule[2]))/ 60,2); 
      }
      else {
        $this->_duration = civicrm_api("Activity", "getvalue", array (version => '3', 'sequential' =>'1', 'id' =>$this->_activityId[0], 'return' =>'duration'));	
      }
      $this->assign('assigne', $this->_assigne);
      $this->assign('multipleDay', $this->_multipleDay);
    }
    else {
      $conferenceUrl = CRM_Utils_System::url("civicrm/school/conference", 'reset=1');
      $this->assign('url', $conferenceUrl);
    }
  }
  
  function buildQuickForm( ) {
    $advisorRelTypeId = School_Utils_Conference::getAdvisorRelTypeId();
    
    // get all the potential advisors
    $sql = "SELECT DISTINCT(c.id), c.display_name FROM civicrm_contact c INNER JOIN civicrm_relationship r ON r.contact_id_a = c.id WHERE      r.relationship_type_id = {$advisorRelTypeId} ORDER BY   c.display_name";
    $advisors = array( '' => '- Select a Teacher -' );
    $dao = CRM_Core_DAO::executeQuery( $sql );
    while ($dao->fetch( )) {
      $advisors[$dao->id] = $dao->display_name;
    }
    $this->add( 'select', 'advisor_id', ts('Advisor'), $advisors);
    if (!$this->_multipleDay) {
      $this->addDate('ptc_date', ts( 'Conference Date'), true);
    }
    $this->add('text', 'ptc_subject', ts('Conference Subject'), true);
    $this->add('text', 'ptc_duration', ts('Conference Duration'), true);
    $this->addDate('booking_start_date', ts('Conf. Booking Start Date'), true);
    $this->addDate('booking_end_date', ts('Conf. Booking End Date'), true);
    for ($i = 1; $i <= $this->_numberOfSlots; $i++) {
      $this->addDateTime("ptc_date_$i", ts( 'Conference Start Time' ), false );
    }
    $this->freeze();
    $this->addButtons(array(
      array ('type' => 'upload',
             'name' => ts( 'Delete'),
             'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
             'isDefault' => true),
      array ('type' => 'cancel',
             'name' => ts('Cancel')),
      ));
    $this->add('hidden', 'tid', $this->_tid );
  }
  
  function setDefaultValues( ) {
    $defaults = array( );
    list($defaults['ptc_date'], $defaults['ptc_date_time']) = CRM_Utils_Date::setDateDefaults($this->_confDate);
    $defaults['booking_start_date'] = $defaults['booking_end_date'] = $defaults['ptc_date'];
    $defaults['ptc_duration'] = $this->_duration;
    $defaults['ptc_subject'] = School_Utils_Conference::SUBJECT;
    $defaults['advisor_id'] = $this->_tid;
    for ( $i = 1; $i <= count($this->_conferenceSchedule); $i++ ) {
      list($defaults["ptc_date_{$i}"], $defaults["ptc_date_{$i}_time"]) = CRM_Utils_Date::setDateDefaults($this->_conferenceSchedule[$i]);
    }
    return $defaults;
  }
  
  function postProcess( ) {
    $advisor = civicrm_api("Contact","getvalue", array (version => '3','sequential' =>'1', 'contact_id' =>$this->_tid, 'return' =>'display_name'));
    $sql = "DELETE FROM civicrm_activity WHERE id IN (".implode(", ", $this->_activityId).")";
    CRM_Core_DAO::executeQuery( $sql );    	
    CRM_Core_Session::setStatus( "All conferences deleted for {$advisor}" );
  }
}