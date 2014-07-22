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

class School_Form_Apply_Schedule extends CRM_Core_Form {
    
    const        
        LOCATION = 'The IDEAL School',
        STATUS   = 1,
        SLOTS_PER_ACTIVITY = 1;

    protected $_multipleDay   = false;

    protected $_numberOfSlots = 35;

    protected $_slotsPerActivity = false;
    
    protected $_slotCount = 1;

    function preProcess( ) {
        parent::preProcess( );

        $this->_multipleDay = CRM_Utils_Request::retrieve( 'multipleDay', 'Boolean', $this, false );
        $this->_slotsPerActivity = CRM_Utils_Request::retrieve( 'perSlot', 'Boolean', $this, false );
        $this->assign( 'multipleDay'  , $this->_multipleDay   );
        $this->assign( 'numberOfSlots', $this->_numberOfSlots );
    }

    function buildQuickForm( ) {
        if ( ! $this->_multipleDay ) {
            $this->addDate('sch_date', ts( 'Activity Date' ), true );
        }
        $this->add( 'text', 'sch_duration', ts( 'Duration' ), true );
        
        require_once 'CRM/Core/OptionGroup.php';
        require_once 'CRM/Core/DAO/OptionGroup.php';
        $activityOptionGroupID = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_OptionGroup', 'activity_type', 'id', 'name');
        $activitiesList = CRM_Core_OptionGroup::valuesByID($activityOptionGroupID);
        $activitiesOptions = array( "Visit", "Tour", "Interview");
        foreach( $activitiesList as $optionID => $optionValue ) {
            if (! in_array( $optionValue, $activitiesOptions )  ) {
                unset($activitiesList["{$optionID}"]);
            }
        }

        $this->add('select', 'activity_id', 'Select Activity',array(''=>ts( '- select -' )) +  $activitiesList, true );
        
        for ( $i = 1; $i < $this->_numberOfSlots; $i++ ) {
            $this->addDateTime("sch_date_$i", ts( 'Start Time' ), false );
            if ( $this->_slotsPerActivity  ) {
                $this->add("text", "activity_slot_$i", ts( 'Slots' ), array( 'size' => 5 ) );
                $defaults["activity_slot_{$i}"] = School_Form_Apply_Schedule::SLOTS_PER_ACTIVITY;
                $this->setDefaults($defaults);
            }
        }

        $this->addButtons(array( 
                                array ( 'type'      => 'refresh', 
                                        'name'      => ts( 'Process' ),
                                        'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', 
                                        'isDefault' => true   ), 
                                array ( 'type'      => 'cancel', 
                                        'name'      => ts('Cancel') ), 
                                 )
                          );
        $this->addFormRule( array( 'School_Form_Apply_Schedule', 'formRule' ), $this );
    }

    static function formRule( $fields, $files, $form ) 
    {  
        $errors = array( );

        if  ( ! CRM_Utils_Array::value( 'sch_date_1_time',$fields) ) {
            $errors['sch_date_1_time'] = ts('Activity Start Time is a required field.');
        }

        if ( $form->_multipleDay &&
             ! CRM_Utils_Array::value( 'sch_date_1' ,$fields ) ) {
            $errors['sch_date_1'] = ts('Activity Start Day is a required field.');
        }
        return $errors;
    }

    function setDefaultValues( ) {
        $defaults = array( );

        list($defaults['sch_date'], $defaults['sch_date_time'])
            = CRM_Utils_Date::setDateDefaults(date("Y-m-d", time( ) + 14 * 24 * 60 * 60 ));
        $defaults['sch_duration'] = 25;

        for ( $i = 1; $i < 10; $i++ ) {
            $defaults["sch_date_{$i}"] = $defaults['sch_date'];
            $time = (int ) ( $i + 1 ) / 2;
            $defaults["sch_date_{$i}_time"] = "$time:00 PM";
            $i++;
            $defaults["sch_date_{$i}"] = $defaults['sch_date'];
            $defaults["sch_date_{$i}_time"] = "$time:30 PM";
        }
        return $defaults;
    }

    function postProcess( ) {
        $params  = $this->controller->exportValues( $this->_name );
        $actType = CRM_Core_OptionGroup::getLabel( 'activity_type', $params['activity_id'] );
                
        require_once 'School/Utils/Conference.php';

        $session =& CRM_Core_Session::singleton( );
        $userID = $session->get( 'userID' );
        
        for ( $i = 1 ; $i < $this->_numberOfSlots; $i++ ) {
            if ( empty( $params["sch_date_{$i}_time"] ) ) {
                continue;
            }
            
            if ( $this->_multipleDay ) {
                $mysqlDate = CRM_Utils_Date::processDate( $params["sch_date_$i"], $params["sch_date_{$i}_time"] );
            } else {
                $mysqlDate = CRM_Utils_Date::processDate( $params['sch_date'], $params["sch_date_{$i}_time"] );
            }

			if ( isset( $params["activity_slot_{$i}"] ) ){
                $this->_slotCount = $params["activity_slot_{$i}"];
			}
            for ( $j = 1; $j <= $this->_slotCount; $j++ ) {
                $activityResult = School_Utils_Conference::createConference( $userID,
                                                                          School_APPLICATION_SCHOOL_CONTACT_ID,
                                                                          $params['activity_id'],
                                                                          $mysqlDate,                                                                       
                                                                          $actType,
                                                                          School_Form_Apply_Schedule::LOCATION,
                                                                          School_Form_Apply_Schedule::STATUS,
                                                                          $params['sch_duration'] );
            }
        }
        
        if ( $activityResult ) {
            require_once 'CRM/Core/PseudoConstant.php';
			require_once 'CRM/Contact/BAO/Contact.php';
            $activityName = CRM_Utils_Array::value( $params['activity_id'], CRM_Core_PseudoConstant::activityType( 'name' ) );
            $cid = School_APPLICATION_SCHOOL_CONTACT_ID;
            $url = CRM_Utils_System::url( 'civicrm/contact/view', "action=browse&reset=1&cid={$cid}&selectedChild=activity");
            $displayName = CRM_Contact_BAO_Contact::displayName( $cid );
            $contactURl = "<a href = $url>$displayName</a>";
            CRM_Core_Session::setStatus( ts(' Slots have been added for %1 You may view these slots in Activity Tab of %2', 
                                            array( 1 => $activityName, 2 => $contactURl ) ) );
        }
    }
}