-- sql/add_consultation_templates.sql

CREATE TABLE IF NOT EXISTS consultation_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL UNIQUE,
    chief_complaint TEXT NOT NULL,
    diagnosis TEXT NOT NULL,
    treatment TEXT NOT NULL,
    prescription TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO consultation_templates (template_name, chief_complaint, diagnosis, treatment, prescription) VALUES 
('Common Cough & Cold', 'Cough and colds for days. Associated with mild fever and runny nose.', 'Acute Upper Respiratory Tract Infection (URTI) / Common Cold', 'Symptomatic care. Increase oral fluid intake. Rest.', 'Paracetamol 500mg - 1 tablet every 4 hours as needed for fever.\nCetirizine 10mg - 1 tablet once daily at bedtime for runny nose.\nAscorbic Acid 500mg - 1 tablet daily.')
ON DUPLICATE KEY UPDATE 
chief_complaint=VALUES(chief_complaint), diagnosis=VALUES(diagnosis), treatment=VALUES(treatment), prescription=VALUES(prescription);

INSERT INTO consultation_templates (template_name, chief_complaint, diagnosis, treatment, prescription) VALUES 
('Routine Prenatal Checkup', 'Routine prenatal checkup. Patient is pregnant at weeks gestation. No active discomforts reported.', 'Pregnancy supervised, routine prenatal check-up', 'Monitor weight, blood pressure, fundal height, and fetal heart tone (FHT). Maternal health counseling on maternal nutrition and warning signs.', 'Ferrous Sulfate + Folic Acid - 1 tablet daily.\nCalcium Carbonate 500mg - 1 tablet daily.')
ON DUPLICATE KEY UPDATE 
chief_complaint=VALUES(chief_complaint), diagnosis=VALUES(diagnosis), treatment=VALUES(treatment), prescription=VALUES(prescription);

INSERT INTO consultation_templates (template_name, chief_complaint, diagnosis, treatment, prescription) VALUES 
('Hypertension Follow-up', 'Regular follow-up for blood pressure monitoring. Patient reports medication compliance. Denies chest pain, headache, or visual blurriness.', 'Essential (Primary) Hypertension', 'Advise low-salt, low-fat diet. Encourage daily moderate physical activity. Monitor blood pressure daily and maintain log.', 'Amlodipine 5mg - 1 tablet once daily in the morning.\n(Continue current medication regimen).')
ON DUPLICATE KEY UPDATE 
chief_complaint=VALUES(chief_complaint), diagnosis=VALUES(diagnosis), treatment=VALUES(treatment), prescription=VALUES(prescription);

INSERT INTO consultation_templates (template_name, chief_complaint, diagnosis, treatment, prescription) VALUES 
('Pediatric Immunization', 'Scheduled pediatric routine immunization for age-appropriate vaccines.', 'Routine child health examination and immunization follow-up', 'Administered routine vaccine. Inspected for immediate post-vaccine reaction. Counseled caregiver on post-vaccination fever management.', 'Paracetamol drops (100mg/mL) or syrup (120mg/5mL) - dosage based on weight, every 4 hours as needed for fever.')
ON DUPLICATE KEY UPDATE 
chief_complaint=VALUES(chief_complaint), diagnosis=VALUES(diagnosis), treatment=VALUES(treatment), prescription=VALUES(prescription);
