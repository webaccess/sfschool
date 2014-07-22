DELETE FROM civicrm_relationship WHERE relationship_type_id = 10 AND contact_id_a = 209;

INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 209, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = 'PK4 N')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;