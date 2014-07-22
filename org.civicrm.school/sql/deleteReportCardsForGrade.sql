DELETE f.*, ef.*, r.*
FROM   civicrm_value_report_cards r, civicrm_contact c, civicrm_file f, civicrm_entity_file ef
WHERE  c.id = r.entity_id
AND    c.contact_sub_type LIKE '%Student%'
AND    r.report_year = '2013-2014'
AND    r.report_term = 'S2'
AND    r.report_grade = 3
AND    r.report_pdf_76 = ef.id
AND    ef.entity_id = c.id
AND    ef.entity_table = 'civicrm_value_report_cards_13'
AND    f.id = ef.file_id

