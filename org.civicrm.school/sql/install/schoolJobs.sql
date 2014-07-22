INSERT INTO `civicrm_job` (`domain_id`, `run_frequency`, `last_run`, `name`, `description`, `api_prefix`, `api_entity`, `api_action`, `parameters`, `is_active`) VALUES
(1, 'Always', '2012-07-20 08:24:38', 'Check Parent Login', 'Bulk creation of relationships for parent teacher', 'civicrm_api3', 'Schooljob', 'check_parent_login', '', 0),
(1, 'Always', '2012-07-20 08:12:44', 'Yearly Job Export', 'Upload class list and change the school term', 'civicrm_api3', 'Schooljob', 'yearly_export', 'start_date = 01-09-2010\r\nend_date = 31-08-2011\r\nsem_year = 2010-2011', 0),
(1, 'Always', '2012-07-20 08:25:29', 'Add Report', '', 'civicrm_api3', 'Schooljob', 'add_report', 'inputDir = /your/input/directory/here\r\nyear = 2011-2012-ms\r\nterm = S2', 0),
(1, 'Always', '2012-07-20 08:25:13', 'Check App Complete', '', 'civicrm_api3', 'Schooljob', 'check_app_complete', 'ec = 1', 0),
(1, 'Always', '2012-07-20 00:28:30', 'Generate Online Form PDF', '', 'civicrm_api3', 'Schooljob', 'gen_online_form_pdf', 'powerschool = 1', 0),
(1, 'Always', '2012-07-20 08:12:30', 'Generate SIS file', '', 'civicrm_api3', 'Schooljob', 'gen_sis_file', 'all = 0\r\nmonth = Jan\r\nweek = Mon', 0),
(1, 'Always', '2012-07-20 08:23:48', 'Generate Yearly Balance', '', 'civicrm_api3', 'Schooljob', 'gen_yearly_balance', 'start_date = 09-01-2010\r\nend_date = 31-08-2011\r\nglobal_id = 1784\r\nacademic_year = 2010-2011', 0),
(1, 'Always', '2012-07-20 08:22:49', 'Send Balance Invoice Email', '', 'civicrm_api3', 'Schooljob', 'send_bal_invoice_email', 'balance_overdue = 10', 0),
(1, 'Always', '2012-07-20 00:23:00', 'Create Conference Schedule', '', 'civicrm_api3', 'Schooljob', 'create_conference_schedule', '', 0),
(1, 'Always', '2012-07-20 08:21:37', 'Send Conference Reminder', '', 'civicrm_api3', 'Schooljob', 'send_conf_reminder', 'days = 7\r\noffset = 7', 0),
(1, 'Always', '2012-07-20 08:18:58', 'Send EConsent Reminder', '', 'civicrm_api3', 'Schooljob', 'send_econsent_reminder', '', 0),
(1, 'Always', '2012-07-20 08:18:10', 'Send not scheduled reminder', '', 'civicrm_api3', 'Schooljob', 'send_not_scheduled_reminder', '', 0),
(1, 'Always', '2012-07-20 08:27:29', 'Send Online Form Email', '', 'civicrm_api3', 'Schooljob', 'send_online_form_email', '', 0),
(1, 'Always', '2012-07-20 08:16:13', 'Send Sign Out Reminder', '', 'civicrm_api3', 'Schooljob', 'send_sign_out_reminder', 'start_date = 01-12-2009\r\nend_date = 10-12-2009', 0);

