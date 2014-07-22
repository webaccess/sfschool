<?php

require_once 'School/Utils/Job.php';


/*** Jobs for School START ***/
function civicrm_api3_schooljob_check_parent_login() {
    School_Utils_Job::checkParentLogin();    
  }

function civicrm_api3_schooljob_yearly_export($params) {
    School_Utils_Job::yearlyExport($params);
}

function civicrm_api3_schooljob_add_report( $params ) {
    School_Utils_Job::addReport($params);
}

function civicrm_api3_schooljob_check_app_complete( $params ) {
    require_once 'School/Utils/EConsent.php';
    if ( $params['ec'] ) {
        School_Utils_EConsent::checkEmergencyContacts( );
    } else {
        School_Utils_EConsent::checkAppCompleted( );
    }
}

function civicrm_api3_schooljob_create_conference_schedule( $params ) {
 require_once 'School/Utils/Conference.php';
    School_Utils_Conference::createConferenceSchedule( $params );
}

function civicrm_api3_schooljob_gen_online_form_pdf( $params ) {
require_once 'School/Utils/PowerSchool.php';
 require_once 'School/Utils/EConsent.php';
    if ( $params['powerschool'] ) {
        // generate PowerSchool Export
        School_Utils_PowerSchool::export( );
    } else {
        // generate PDF
        School_Utils_EConsent::genOnlineFormPDF( );
    }
}

function civicrm_api3_schooljob_gen_sis_file( $params ) {
    School_Utils_Job::genSISFile($params);
}

function civicrm_api3_schooljob_gen_yearly_balance( $params ) {
    School_Utils_Job::genYearlyBalance($params);
}

function civicrm_api3_schooljob_send_bal_invoice_email( $params ) {
    define( 'School_BALANCE_OVERDUE', $params['balance_overdue'] );
    require_once 'School/Utils/ExtendedCare.php';
  
    School_Utils_ExtendedCare::sendBalanceInvoiceEmail( School_BALANCE_OVERDUE );
}

function civicrm_api3_schooljob_send_conf_reminder( $params ) {
    require_once 'School/Utils/Conference.php';
    $days = $params['days'];
    $offset = $params['offset'];
   
    // send reminder email for all
    School_Utils_Conference::sendReminderEmail( $days, $offset );
}

function civicrm_api3_schooljob_send_econsent_reminder() {
    require_once 'School/Utils/EConsent.php';
  
    // send reminder email for all
    School_Utils_EConsent::sendReminderEmail( );
}

function civicrm_api3_schooljob_send_not_scheduled_reminder() {
    require_once 'School/Utils/Conference.php';
   
    // send reminder email for all
    School_Utils_Conference::notScheduledReminder( );
}

function civicrm_api3_schooljob_send_online_form_email() {
    require_once 'School/Utils/EConsent.php';
   
    // send reminder email for all
    School_Utils_EConsent::sendOnlineFormEmail( );
}

function civicrm_api3_schooljob_send_sign_out_reminder( $params ) {
    
    require_once 'School/Utils/ExtendedCare.php';
    $startTimestamp = strtotime($params['start_date']); 
    $endTimestamp = strtotime($params['end_date']); 
    $startDate =  date('Y-m-d', $startTimestamp);
    $endDate   = date('Y-m-d', $endTimestamp);
    // send reminder email for all
   
    School_Utils_ExtendedCare::sendNotSignedOutEmail( $startDate, $endDate );
}
/*** Jobs for School END ***/
