-- ============================================================
-- CampusCare: Seed Data (Temporary Test Data)
-- Run this AFTER importing campuscare.sql
-- ============================================================

USE `campuscare`;

-- ============================================================
-- Seed: Programs
-- ============================================================
INSERT INTO `programs` (`code`, `name`) VALUES
-- College of Business and Accountancy
('BSA', 'Bachelor of Science in Accountancy'),
('BSAIS', 'Bachelor of Science in Accounting Information System'),
('BSBAFM', 'Bachelor of Science in Business Administration, Major in Financial Management'),
('BSBAHRM', 'Bachelor of Science in Business Administration, Major in Human Resource Management'),
('BSBAMM', 'Bachelor of Science in Business Administration, Major in Marketing Management'),
('BSENTREP', 'Bachelor of Science in Entrepreneurship'),
('BSHM', 'Bachelor of Science in Hospitality Management'),
('BSOA', 'Bachelor of Science in Office Administration'),
('BSTM', 'Bachelor of Science in Tourism Management'),

-- College of Criminal Justice Education
('BSCRIM', 'Bachelor of Science in Criminology'),
('BSISM', 'Bachelor of Science in Industrial Security Management'),

-- College of Education
('BSEENG', 'Bachelor in Secondary Education Major in English'),
('BSEEC', 'Bachelor in Secondary Education Major in English - Chinese'),
('BSESCI', 'Bachelor in Secondary Education Major in Science'),
('BSETLE', 'Bachelor in Secondary Education Major in Technology and Livelihood Education'),
('BECE', 'Bachelor of Early Childhood Education'),

-- College of Engineering
('BSCPE', 'Bachelor of Science in Computer Engineering'),
('BSEE', 'Bachelor of Science in Electrical Engineering'),
('BSECE', 'Bachelor of Science in Electronics Engineering'),
('BSIE', 'Bachelor of Science in Industrial Engineering'),

-- College of Liberal Arts and Sciences
('ABPOLSCI', 'AB Political Science'),
('BACOMM', 'BA Communication'),
('BPA', 'Bachelor of Public Administration'),
('BSCS', 'Bachelor of Science in Computer Science'),
('BSEMC', 'Bachelor of Science in Entertainment and Multimedia Computing'),
('BSIS', 'Bachelor of Science in Information System'),
('BSIT', 'Bachelor of Science in Information Technology'),
('BSMATH', 'Bachelor of Science in Mathematics'),
('BSPSYCH', 'Bachelor of Science in Psychology'),
('BSSW', 'Bachelor of Science in Social Work');
-- ============================================================
-- Seed: Year Levels
-- ============================================================
INSERT INTO `year_levels` (`name`, `order_num`) VALUES
('1st Year', 1),
('2nd Year', 2),
('3rd Year', 3),
('4th Year', 4),

-- ============================================================
-- Seed: Users
-- Passwords are hashed using password_hash('password', PASSWORD_DEFAULT)
-- Default passwords listed in comments for testing
-- ============================================================

-- Admin: username=admin, password=admin123
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`) VALUES
('admin', '$2y$10$jveCvwI5ZK9yhTQhFGYdxODOrrSSWzUadQVCN4RIKMC811lkc1kh2', 'System', 'Administrator', 'admin@campuscare.edu', 'admin', 'active');

-- Nurse: username=nurse.garcia, password=nurse123
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`) VALUES
('nurse_garcia', '$2y$10$uADeHOF3FgZjC5pqZy4cQ.HtweLn5h8qSqY.FvHPgV3DOwH.i9hdC', 'Maria', 'Garcia', 'maria.garcia@campuscare.edu', 'nurse', 'active');

-- Nurse: username=nurse.santos, password=nurse123
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`) VALUES
('nurse_santos', '$2y$10$uADeHOF3FgZjC5pqZy4cQ.HtweLn5h8qSqY.FvHPgV3DOwH.i9hdC', 'Jose', 'Santos', 'jose.santos@campuscare.edu', 'nurse', 'active');

-- Class Rep: username=rep.dela_cruz, password=rep123 (assigned to BSIT 2nd Year Section A)
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `assigned_program_id`, `assigned_year_level_id`, `assigned_section`, `status`) VALUES
('rep_delacruz', '$2y$10$XWPlsJYECdHMmgMnTBF9S.h8.peWBr.ZX/5v4P0ibli/gfhowZB9G', 'Ana', 'Dela Cruz', 'ana.delacruz@student.edu', 'rep', 1, 2, 'A', 'active');

-- Class Rep: username=rep.reyes, password=rep123 (assigned to BSN 1st Year Section B)
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `assigned_program_id`, `assigned_year_level_id`, `assigned_section`, `status`) VALUES
('rep_reyes', '$2y$10$XWPlsJYECdHMmgMnTBF9S.h8.peWBr.ZX/5v4P0ibli/gfhowZB9G', 'Carlos', 'Reyes', 'carlos.reyes@student.edu', 'rep', 3, 1, 'B', 'active');

-- Inactive user for testing deactivation
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`) VALUES
('inactive_user', '$2y$10$uADeHOF3FgZjC5pqZy4cQ.HtweLn5h8qSqY.FvHPgV3DOwH.i9hdC', 'Deactivated', 'User', 'inactive@campuscare.edu', 'nurse', 'inactive');

-- ============================================================
-- Seed: Students (20 sample students)
-- ============================================================
INSERT INTO `students` (`student_id`, `first_name`, `last_name`, `middle_name`, `date_of_birth`, `gender`, `program_id`, `year_level_id`, `section`, `contact_number`, `email`, `address`, `blood_type`, `status`, `created_by`) VALUES
('2024-00001', 'Juan', 'Dela Cruz', 'Santos', '2003-05-15', 'Male', 1, 2, 'A', '09171234567', 'juan.delacruz@student.edu', '123 Rizal St, Manila', 'O+', 'active', 4),
('2024-00002', 'Maria', 'Santos', 'Lopez', '2004-02-20', 'Female', 1, 2, 'A', '09181234567', 'maria.santos@student.edu', '456 Mabini St, Quezon City', 'A+', 'active', 4),
('2024-00003', 'Pedro', 'Reyes', NULL, '2003-11-10', 'Male', 2, 3, 'B', '09191234567', 'pedro.reyes@student.edu', '789 Bonifacio Ave, Makati', 'B+', 'active', 2),
('2024-00004', 'Angela', 'Garcia', 'Mendoza', '2004-08-03', 'Female', 3, 1, 'B', '09201234567', 'angela.garcia@student.edu', '321 Luna St, Pasig', 'AB+', 'active', 5),
('2024-00005', 'Mark', 'Lopez', 'Rivera', '2003-01-25', 'Male', 4, 4, 'A', '09211234567', 'mark.lopez@student.edu', '654 Aguinaldo St, Taguig', 'O-', 'active', 2),
('2024-00006', 'Sofia', 'Rivera', 'Cruz', '2004-07-12', 'Female', 1, 2, 'A', '09221234567', 'sofia.rivera@student.edu', '987 Roxas Blvd, Manila', 'A-', 'active', 4),
('2024-00007', 'Luis', 'Mendoza', NULL, '2003-09-08', 'Male', 5, 3, 'A', '09231234567', 'luis.mendoza@student.edu', '147 Quirino Ave, Paranaque', 'B-', 'active', 2),
('2024-00008', 'Camille', 'Torres', 'Bautista', '2004-04-16', 'Female', 2, 2, 'B', '09241234567', 'camille.torres@student.edu', '258 Osmeña St, Caloocan', 'O+', 'active', 2),
('2024-00009', 'Rafael', 'Bautista', 'Villanueva', '2003-12-01', 'Male', 6, 3, 'A', '09251234567', 'rafael.bautista@student.edu', '369 Quezon Ave, Manila', 'A+', 'active', 2),
('2024-00010', 'Isabelle', 'Villanueva', NULL, '2004-06-22', 'Female', 3, 1, 'B', '09261234567', 'isabelle.villanueva@student.edu', '741 Magsaysay Blvd, Sta. Mesa', 'AB-', 'active', 5),
('2024-00011', 'Kenneth', 'Aquino', 'Pascual', '2003-03-14', 'Male', 7, 4, 'A', '09271234567', 'kenneth.aquino@student.edu', '852 Taft Ave, Manila', 'O+', 'active', 2),
('2024-00012', 'Patricia', 'Pascual', 'Ramos', '2004-10-30', 'Female', 8, 2, 'A', '09281234567', 'patricia.pascual@student.edu', '963 España Blvd, Manila', 'B+', 'active', 2),
('2024-00013', 'Jerome', 'Ramos', NULL, '2003-07-19', 'Male', 1, 2, 'A', '09291234567', 'jerome.ramos@student.edu', '111 P. Noval St, Manila', 'A+', 'active', 4),
('2024-00014', 'Christine', 'Flores', 'Gonzales', '2004-01-08', 'Female', 4, 3, 'B', '09301234567', 'christine.flores@student.edu', '222 Lepanto St, Manila', 'O-', 'active', 2),
('2024-00015', 'Bryan', 'Gonzales', 'Torres', '2003-06-27', 'Male', 5, 1, 'A', '09311234567', 'bryan.gonzales@student.edu', '333 G. Tuazon St, Manila', 'AB+', 'active', 2),
('2024-00016', 'Nicole', 'Cruz', NULL, '2004-03-11', 'Female', 6, 2, 'B', '09321234567', 'nicole.cruz@student.edu', '444 Dapitan St, Manila', 'B-', 'active', 2),
('2024-00017', 'Daniel', 'Soriano', 'Aguilar', '2003-08-05', 'Male', 2, 4, 'A', '09331234567', 'daniel.soriano@student.edu', '555 Laong Laan St, Manila', 'O+', 'active', 2),
('2024-00018', 'Rachel', 'Aguilar', 'Navarro', '2004-12-18', 'Female', 7, 1, 'B', '09341234567', 'rachel.aguilar@student.edu', '666 V. Concepcion St, Manila', 'A-', 'active', 2),
('2024-00019', 'Francis', 'Navarro', NULL, '2003-04-20', 'Male', 3, 3, 'A', '09351234567', 'francis.navarro@student.edu', '777 Algeciras St, Manila', 'B+', 'active', 2),
('2024-00020', 'Samantha', 'Castro', 'Lim', '2004-09-14', 'Female', 8, 2, 'A', '09361234567', 'samantha.castro@student.edu', '888 Morayta St, Manila', 'AB+', 'active', 2);

-- ============================================================
-- Seed: Allergies
-- ============================================================
INSERT INTO `allergies` (`student_id`, `allergen`, `reaction`, `severity`, `notes`) VALUES
(1, 'Penicillin', 'Rash and hives', 'Severe', 'Verified by Dr. Reyes on enrollment medical exam'),
(1, 'Peanuts', 'Swelling, difficulty breathing', 'Severe', 'Carries epinephrine auto-injector'),
(2, 'Dust mites', 'Sneezing, itchy eyes', 'Mild', NULL),
(4, 'Sulfa drugs', 'Skin rash', 'Moderate', 'Use alternative antibiotics'),
(6, 'Latex', 'Contact dermatitis', 'Mild', 'Use non-latex gloves during procedures'),
(10, 'Shellfish', 'Hives, stomach cramps', 'Moderate', NULL),
(15, 'Aspirin', 'Stomach upset, nausea', 'Mild', 'Use paracetamol instead');

-- ============================================================
-- Seed: Chronic Conditions
-- ============================================================
INSERT INTO `chronic_conditions` (`student_id`, `condition_name`, `diagnosis_date`, `status`, `notes`) VALUES
(1, 'Asthma', '2015-06-10', 'Managed', 'Uses inhaler as needed, last asthma attack was 6 months ago'),
(3, 'Hypertension (Stage 1)', '2022-03-15', 'Active', 'On daily medication, needs BP monitoring at each visit'),
(5, 'Type 1 Diabetes', '2010-09-01', 'Active', 'Insulin-dependent, carries glucose monitor'),
(8, 'Migraine', '2021-07-20', 'Active', 'Triggered by stress and bright lights'),
(12, 'Scoliosis', '2019-01-05', 'Managed', 'Mild curvature, annual monitoring required'),
(17, 'Epilepsy', '2018-11-12', 'Managed', 'Controlled with medication, no seizures in 2 years');

-- ============================================================
-- Seed: Medications
-- ============================================================
INSERT INTO `medications` (`student_id`, `medication_name`, `dosage`, `frequency`, `prescribing_doctor`, `start_date`, `end_date`, `notes`) VALUES
(1, 'Salbutamol Inhaler', '100mcg/puff', 'As needed', 'Dr. Reyes', '2020-01-01', NULL, 'For asthma attacks, max 4 puffs daily'),
(3, 'Losartan', '50mg', 'Once daily', 'Dr. Lopez', '2022-04-01', NULL, 'Take in the morning with food'),
(5, 'Insulin Glargine', '20 units', 'Once daily at bedtime', 'Dr. Santos', '2010-09-15', NULL, 'Long-acting insulin'),
(5, 'Insulin Lispro', 'Variable dose', 'Before meals', 'Dr. Santos', '2010-09-15', NULL, 'Rapid-acting, dose based on carb counting'),
(8, 'Sumatriptan', '50mg', 'As needed at migraine onset', 'Dr. Garcia', '2022-01-10', NULL, 'Max 2 doses per 24 hours'),
(17, 'Levetiracetam', '500mg', 'Twice daily', 'Dr. Mendoza', '2019-01-01', NULL, 'Anti-epileptic, do not discontinue abruptly');

-- ============================================================
-- Seed: Immunizations
-- ============================================================
INSERT INTO `immunizations` (`student_id`, `vaccine_name`, `date_administered`, `dose_number`, `administered_by`, `next_dose_date`, `notes`) VALUES
(1, 'Hepatitis B', '2003-06-01', '1st Dose', 'City Health Center', '2003-07-01', NULL),
(1, 'Hepatitis B', '2003-07-01', '2nd Dose', 'City Health Center', '2003-12-01', NULL),
(1, 'Hepatitis B', '2003-12-01', '3rd Dose', 'City Health Center', NULL, 'Series complete'),
(1, 'COVID-19 (Pfizer)', '2021-11-15', '1st Dose', 'LGU Vaccination Site', '2021-12-15', NULL),
(1, 'COVID-19 (Pfizer)', '2021-12-15', '2nd Dose', 'LGU Vaccination Site', NULL, 'Primary series complete'),
(2, 'COVID-19 (Moderna)', '2022-01-10', '1st Dose', 'School Vaccination Drive', '2022-02-10', NULL),
(2, 'COVID-19 (Moderna)', '2022-02-10', '2nd Dose', 'School Vaccination Drive', NULL, 'Primary series complete'),
(4, 'Influenza', '2025-10-01', 'Annual', 'Campus Clinic', NULL, 'Annual flu shot'),
(10, 'Tetanus Toxoid', '2023-05-20', 'Booster', 'City Health Center', '2033-05-20', '10-year booster');

-- ============================================================
-- Seed: Emergency Contacts
-- ============================================================
INSERT INTO `emergency_contacts` (`student_id`, `contact_name`, `relationship`, `phone_number`, `email`, `is_primary`) VALUES
(1, 'Roberto Dela Cruz', 'Father', '09171111111', 'roberto.dc@email.com', 1),
(1, 'Elena Dela Cruz', 'Mother', '09172222222', 'elena.dc@email.com', 0),
(2, 'Carmen Santos', 'Mother', '09183333333', 'carmen.santos@email.com', 1),
(3, 'Manuel Reyes', 'Father', '09194444444', NULL, 1),
(4, 'Diana Garcia', 'Mother', '09205555555', 'diana.garcia@email.com', 1),
(5, 'Eduardo Lopez', 'Father', '09216666666', NULL, 1),
(5, 'Linda Lopez', 'Mother', '09217777777', 'linda.lopez@email.com', 0),
(6, 'Antonio Rivera', 'Father', '09228888888', NULL, 1),
(8, 'Sandra Torres', 'Mother', '09249999999', 'sandra.torres@email.com', 1),
(10, 'Fernando Villanueva', 'Father', '09261010101', NULL, 1),
(12, 'Gloria Pascual', 'Mother', '09281212121', 'gloria.p@email.com', 1),
(15, 'Roberto Gonzales', 'Father', '09311515151', NULL, 1),
(17, 'Teresa Soriano', 'Mother', '09331717171', 'teresa.s@email.com', 1),
(20, 'Michael Castro', 'Father', '09362020202', 'michael.c@email.com', 1);

-- ============================================================
-- Seed: Visits (30 sample visits spread across different dates)
-- ============================================================
INSERT INTO `visits` (`student_id`, `attended_by`, `visit_date`, `blood_pressure`, `temperature`, `pulse_rate`, `respiratory_rate`, `weight`, `height`, `complaint`, `assessment`, `treatment`, `follow_up_notes`, `follow_up_date`, `status`) VALUES
-- January 2026
(1, 2, '2026-01-06 08:30:00', '120/80', 36.5, 72, 18, 65.0, 170.0, 'Headache and dizziness', 'Mild tension headache, likely due to stress', 'Paracetamol 500mg given. Advised rest and hydration.', NULL, NULL, 'Completed'),
(3, 2, '2026-01-06 09:15:00', '150/95', 36.8, 88, 20, 80.0, 175.0, 'Feeling lightheaded, elevated BP', 'Hypertension flare-up, BP above baseline', 'BP monitored. Advised to take prescribed Losartan. Referred to physician if persists.', 'Follow up in 3 days for BP check', '2026-01-09', 'Follow-up'),
(5, 3, '2026-01-08 10:00:00', '118/75', 36.4, 70, 16, 58.0, 165.0, 'Low blood sugar episode in class', 'Hypoglycemia, blood glucose at 60mg/dL', 'Glucose tablets given. Monitored until glucose stabilized at 95mg/dL.', 'Reminded to carry snacks. Notify instructor.', NULL, 'Completed'),
(2, 2, '2026-01-10 11:30:00', '110/70', 37.2, 76, 18, 55.0, 160.0, 'Sore throat and runny nose', 'Upper respiratory tract infection', 'Vitamin C and throat lozenges provided. Advised warm fluids.', NULL, NULL, 'Completed'),
(8, 3, '2026-01-13 14:00:00', '115/72', 36.6, 68, 17, 52.0, 158.0, 'Severe headache with visual disturbance', 'Migraine episode with aura', 'Sumatriptan 50mg taken by patient. Rested in clinic for 1 hour. Pain subsided.', NULL, NULL, 'Completed'),
(4, 2, '2026-01-15 08:45:00', '108/68', 38.2, 90, 22, 50.0, 155.0, 'Fever, body aches, and fatigue', 'Possible viral infection, flu-like symptoms', 'Paracetamol 500mg given. Advised to go home and rest. Return if fever persists beyond 3 days.', 'Excuse letter issued for classes', NULL, 'Completed'),
(6, 2, '2026-01-17 10:30:00', '112/74', 36.5, 74, 18, 48.0, 157.0, 'Allergic reaction: skin rash on arms', 'Contact dermatitis, likely from latex exposure in lab', 'Antihistamine (Cetirizine 10mg) given. Topical hydrocortisone applied.', 'Advised to use non-latex gloves henceforth', NULL, 'Completed'),
(9, 3, '2026-01-20 09:00:00', '125/82', 36.7, 78, 19, 72.0, 178.0, 'Sprained ankle from PE class', 'Grade 1 ankle sprain, mild swelling', 'RICE method applied. Cold compress. Elastic bandage wrap.', 'Follow up in 1 week if no improvement', '2026-01-27', 'Follow-up'),
(12, 2, '2026-01-22 13:30:00', '105/65', 36.4, 66, 16, 58.0, 162.0, 'Back pain during prolonged sitting', 'Mild lower back strain, related to scoliosis', 'Hot compress applied. Stretching exercises demonstrated.', 'Recommended ergonomic assessment', NULL, 'Completed'),

-- February 2026
(7, 2, '2026-02-03 08:30:00', '118/76', 36.6, 72, 18, 68.0, 172.0, 'Stomach ache and nausea', 'Acute gastritis, skipped breakfast', 'Antacid given. Advised regular meals.', NULL, NULL, 'Completed'),
(1, 3, '2026-02-05 09:45:00', '120/78', 36.8, 75, 19, 65.0, 170.0, 'Asthma attack triggered by dust in classroom', 'Mild bronchospasm, wheezing on auscultation', 'Salbutamol 2 puffs administered via inhaler. Monitored for 30 mins until symptoms resolved.', 'Reminded to carry inhaler at all times', NULL, 'Completed'),
(10, 2, '2026-02-07 11:00:00', '115/72', 36.5, 70, 17, 62.0, 168.0, 'Eye irritation and redness', 'Allergic conjunctivitis', 'Artificial tears administered. Advised to avoid rubbing eyes.', NULL, NULL, 'Completed'),
(14, 3, '2026-02-10 14:15:00', '110/70', 36.6, 73, 18, 56.0, 160.0, 'Menstrual cramps, unable to focus in class', 'Dysmenorrhea', 'Mefenamic acid 250mg given. Hot compress on abdomen. Advised rest.', NULL, NULL, 'Completed'),
(15, 2, '2026-02-12 08:00:00', '122/80', 37.0, 80, 20, 70.0, 175.0, 'Cough and cold, 3 days duration', 'Common cold, no signs of pneumonia', 'Vitamin C, decongestant provided. Advised steam inhalation at home.', 'Return if symptoms worsen or fever develops', NULL, 'Completed'),
(17, 3, '2026-02-14 10:30:00', '116/74', 36.5, 68, 17, 74.0, 176.0, 'Regular check: seizure medication compliance', 'Stable, no recent seizure episodes. Medication compliant.', 'No treatment needed. Documented medication adherence.', 'Next check-up in 1 month', '2026-03-14', 'Follow-up'),
(3, 2, '2026-02-17 09:00:00', '140/88', 36.7, 82, 19, 80.5, 175.0, 'Follow-up BP monitoring', 'BP improved but still slightly elevated', 'Continued medication. Lifestyle counseling on diet and exercise.', 'Follow up in 2 weeks', '2026-03-03', 'Follow-up'),
(11, 2, '2026-02-18 13:00:00', '120/78', 36.4, 70, 16, 76.0, 180.0, 'Minor cut on hand from paper', 'Superficial laceration, no deep tissue involvement', 'Wound cleaned with antiseptic. Bandage applied. Tetanus status verified (up to date).', NULL, NULL, 'Completed'),
(18, 3, '2026-02-19 15:00:00', '105/68', 36.5, 64, 17, 50.0, 155.0, 'Feeling faint and weak', 'Possible dehydration and low blood sugar', 'Oral rehydration solution given. Biscuits provided. Monitored for 30 mins.', 'Advised proper hydration and regular meals', NULL, 'Completed'),
(20, 2, '2026-02-20 08:45:00', '108/72', 36.6, 72, 18, 54.0, 160.0, 'Anxiety and chest tightness before exam', 'Anxiety-related symptoms, no cardiac concerns', 'Deep breathing exercises guided. Counseling referral suggested.', 'Recommended counseling center visit', NULL, 'Referred'),
(13, 3, '2026-02-21 11:00:00', '118/76', 37.5, 84, 20, 67.0, 170.0, 'Fever and sore muscles after field activity', 'Heat exhaustion, mild dehydration', 'Cool compress, electrolyte drink, rest in clinic for 2 hours.', 'Cleared to go home, advised 1 day rest', NULL, 'Completed'),

-- More February scattered visits
(2, 2, '2026-02-06 09:00:00', '112/70', 36.6, 72, 17, 55.0, 160.0, 'Follow up for sore throat', 'Symptoms improving, throat less inflamed', 'Continued lozenges. Cleared for classes.', NULL, NULL, 'Completed'),
(4, 3, '2026-02-11 10:00:00', '110/68', 36.5, 74, 18, 50.0, 155.0, 'Skin rash on forearm', 'Allergic dermatitis, possible sulfa reaction from topical cream', 'Antihistamine given. Area cleaned. Advised to stop using cream.', NULL, NULL, 'Completed'),
(16, 2, '2026-02-13 14:00:00', '120/80', 36.7, 76, 18, 60.0, 163.0, 'Toothache causing difficulty concentrating', 'Dental issue, referred to dentist', 'Mefenamic acid for pain relief. Dental referral letter issued.', 'Send to university dental clinic', NULL, 'Referred'),
(19, 3, '2026-02-15 08:30:00', '115/74', 36.5, 70, 17, 72.0, 178.0, 'Knee pain after basketball', 'Mild patellar strain', 'Ice pack applied. Knee support bandage. Advised rest from sports for 1 week.', 'Follow up if pain persists', '2026-02-22', 'Follow-up'),

-- Today's visits (for dashboard display)
(1, 2, '2026-02-23 08:00:00', '118/78', 36.5, 72, 18, 65.0, 170.0, 'Headache', 'Tension headache', 'Paracetamol given, rest advised', NULL, NULL, 'Completed'),
(6, 2, '2026-02-23 09:30:00', '110/70', 36.8, 74, 18, 48.0, 157.0, 'Sore throat', 'Mild pharyngitis', 'Lozenges and warm fluids advised', NULL, NULL, 'Completed'),
(8, 3, '2026-02-23 10:15:00', '112/72', 36.6, 70, 17, 52.0, 158.0, 'Migraine', 'Migraine with aura', 'Sumatriptan taken, resting in clinic', 'Scheduled follow-up', '2026-02-26', 'Follow-up'),
(13, 2, '2026-02-23 11:00:00', '120/76', 37.0, 78, 19, 67.0, 170.0, 'Cough and cold', 'Upper respiratory infection', 'Decongestant and Vitamin C provided', NULL, NULL, 'Completed'),
(15, 3, '2026-02-23 13:30:00', '116/74', 36.7, 72, 18, 70.0, 175.0, 'Stomach ache', 'Gastritis', 'Antacid given, advised regular meals', NULL, NULL, 'Completed'),
(20, 2, '2026-02-23 14:45:00', '108/70', 36.5, 68, 16, 54.0, 160.0, 'Dizziness and fatigue', 'Low blood pressure, dehydration', 'ORS given, monitored for 1 hour', 'Advised to increase fluid intake', NULL, 'Completed');

-- ============================================================
-- Seed: Announcements
-- ============================================================
INSERT INTO `announcements` (`title`, `content`, `posted_by`, `status`) VALUES
('Annual Medical Checkup Schedule', 'The annual medical checkup for all students will be held from March 10-14, 2026. Please bring your medical clearance forms and a valid ID. Schedules per department will be posted next week.', 2, 'published'),
('Flu Vaccination Drive', 'The university clinic, in partnership with the City Health Office, will conduct a free flu vaccination drive on March 20, 2026, from 8:00 AM to 4:00 PM at the university gymnasium. All students and staff are welcome.', 2, 'published'),
('Clinic Closed for Holiday', 'Please be advised that the campus clinic will be closed on February 25, 2026 (EDSA People Power Anniversary). For emergencies, please call the emergency hotline posted on this page.', 3, 'published'),
('Mental Health Awareness Week', 'Join us for Mental Health Awareness Week activities from March 3-7, 2026. Free psychological first aid sessions and stress management workshops will be available at the clinic. Sign up at the clinic front desk.', 2, 'published'),
('Updated COVID-19 Protocols', 'The university has updated its COVID-19 health protocols effective February 2026. Mask-wearing remains optional but recommended in enclosed spaces. Hand sanitizers are available at all building entrances.', 2, 'draft');

-- ============================================================
-- Seed: FAQs
-- ============================================================
INSERT INTO `faqs` (`question`, `answer`, `sort_order`, `status`) VALUES
('What are the clinic operating hours?', 'The campus clinic is open Monday to Friday, 7:00 AM to 6:00 PM. Saturday hours are 8:00 AM to 12:00 PM. The clinic is closed on Sundays and holidays.', 1, 'active'),
('Do I need to bring my student ID when visiting the clinic?', 'Yes, you must present your valid student ID for identification and record-keeping purposes every time you visit the clinic.', 2, 'active'),
('Are clinic services free for students?', 'Yes, all basic clinic services including consultations, first aid, vital signs monitoring, and basic medications are free for enrolled students.', 3, 'active'),
('Can the clinic provide medical certificates?', 'The clinic can issue medical certificates for minor ailments treated at the clinic. For conditions requiring external treatment, you will need documentation from your attending physician.', 4, 'active'),
('What should I do in case of a medical emergency on campus?', 'Call the campus emergency hotline immediately. If you are with someone who needs help, keep them calm, do not move them if they have a potential spinal injury, and wait for trained responders. You can also go directly to the clinic during operating hours.', 5, 'active'),
('Can I request my medical records from the clinic?', 'Yes, you can request a copy of your medical records by visiting the clinic and filling out a records request form. Allow 3-5 working days for processing.', 6, 'active'),
('Does the clinic handle dental or eye problems?', 'The clinic provides basic first aid for dental pain and eye irritation. For specialized dental or optical care, we will provide a referral to the university dental clinic or an external specialist.', 7, 'active');

-- ============================================================
-- Seed: First Aid Guidelines
-- ============================================================
INSERT INTO `first_aid_guidelines` (`title`, `icon`, `content`, `sort_order`, `status`) VALUES
('Cuts and Wounds', 'cuts-and-wounds', '<strong>Step 1:</strong> Wash your hands or wear disposable gloves if available.<br><strong>Step 2:</strong> Apply gentle pressure with a clean cloth to stop bleeding.<br><strong>Step 3:</strong> Clean the wound under running water.<br><strong>Step 4:</strong> Apply antiseptic and cover with a sterile bandage.<br><strong>Step 5:</strong> Seek medical attention if the wound is deep, won''t stop bleeding, or shows signs of infection.', 1, 'active'),
('Burns', 'burns', '<strong>For minor burns:</strong><br>1. Cool the burn under cool (not cold) running water for at least 10 minutes.<br>2. Do NOT apply ice, butter, or toothpaste.<br>3. Cover with a sterile, non-fluffy dressing.<br>4. Take over-the-counter pain relief if needed.<br><br><strong>For severe burns:</strong> Call emergency services immediately. Do not remove clothing stuck to the burn. Cover with a clean sheet and keep the person warm.', 2, 'active'),
('Fainting', 'fainting-dizziness', '<strong>If someone feels faint:</strong><br>1. Have them lie down and elevate their legs above heart level.<br>2. Loosen any tight clothing.<br>3. Ensure fresh air circulation.<br>4. If they don''t recover within 1 minute, call for medical help.<br><br><strong>If someone has fainted:</strong> Check breathing, place them in the recovery position, and call for help if they don''t regain consciousness quickly.', 3, 'active'),
('Nosebleed', 'nosebleed', '1. Sit upright and lean slightly forward (NOT backward).<br>2. Pinch the soft part of the nose firmly for 10-15 minutes.<br>3. Breathe through your mouth.<br>4. Apply a cold compress to the bridge of the nose.<br>5. Do NOT blow your nose for several hours afterward.<br>6. Seek medical attention if bleeding persists beyond 20 minutes.', 4, 'active'),
('Seizures', 'head-injury', '1. Stay calm and time the seizure.<br>2. Clear the area of hazardous objects.<br>3. Place something soft under the person''s head.<br>4. Turn the person on their side (recovery position).<br>5. Do NOT restrain the person or put anything in their mouth.<br>6. Call emergency services if the seizure lasts more than 5 minutes, if it''s their first seizure, or if they are injured.', 5, 'active'),
('Sprains and Strains', 'fracture-and-sprains', 'Follow the <strong>RICE Method</strong>:<br><br><strong>R</strong>est — Stop activity and rest the injured area.<br><strong>I</strong>ce — Apply ice wrapped in a cloth for 15-20 minutes every 2-3 hours.<br><strong>C</strong>ompression — Wrap the area with an elastic bandage (not too tight).<br><strong>E</strong>levation — Keep the injured area elevated above heart level when possible.<br><br>Seek medical attention if there is severe pain, inability to bear weight, or significant swelling.', 6, 'active');

-- ============================================================
-- Seed: Clinic Emergency Contacts
-- ============================================================
INSERT INTO `clinic_emergency_contacts` (`name`, `role`, `phone_number`, `email`, `sort_order`) VALUES
('Campus Clinic Main Line', 'General Clinic', '(02) 8123-4567', 'clinic@university.edu', 1),
('Nurse Maria Garcia', 'Head Nurse', '0917-111-2222', 'maria.garcia@campuscare.edu', 2),
('Nurse Jose Santos', 'Staff Nurse', '0918-333-4444', 'jose.santos@campuscare.edu', 3),
('Campus Security', 'Emergency Response', '(02) 8123-9999', 'security@university.edu', 4),
('Philippine Red Cross', 'External Emergency', '143', NULL, 5),
('National Emergency Hotline', 'External Emergency', '911', NULL, 6),
('Poison Control Center', 'External Emergency', '(02) 8524-1078', NULL, 7);

-- ============================================================
-- Seed: Clinic Hours
-- ============================================================
INSERT INTO `clinic_hours` (`day_of_week`, `opening_time`, `closing_time`, `is_closed`, `notes`) VALUES
('Monday', '07:00:00', '18:00:00', 0, NULL),
('Tuesday', '07:00:00', '18:00:00', 0, NULL),
('Wednesday', '07:00:00', '18:00:00', 0, NULL),
('Thursday', '07:00:00', '18:00:00', 0, NULL),
('Friday', '07:00:00', '18:00:00', 0, NULL),
('Saturday', '08:00:00', '12:00:00', 0, 'Half day only'),
('Sunday', NULL, NULL, 1, 'Closed');

-- ============================================================
-- Seed: Access Logs (sample log entries)
-- ============================================================
INSERT INTO `access_logs` (`user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 'login', 'Admin logged in successfully', '127.0.0.1', '2026-02-23 07:30:00'),
(2, 'login', 'Nurse Garcia logged in', '127.0.0.1', '2026-02-23 07:45:00'),
(3, 'login', 'Nurse Santos logged in', '127.0.0.1', '2026-02-23 07:50:00'),
(2, 'create_visit', 'Recorded visit for student 2024-00001', '127.0.0.1', '2026-02-23 08:00:00'),
(2, 'create_visit', 'Recorded visit for student 2024-00006', '127.0.0.1', '2026-02-23 09:30:00'),
(3, 'create_visit', 'Recorded visit for student 2024-00008', '127.0.0.1', '2026-02-23 10:15:00'),
(1, 'create_user', 'Created new user account: rep.reyes', '127.0.0.1', '2026-02-22 10:00:00'),
(4, 'login', 'Class Rep Dela Cruz logged in', '127.0.0.1', '2026-02-22 14:00:00'),
(4, 'create_student', 'Added student record: 2024-00013 Jerome Ramos', '127.0.0.1', '2026-02-22 14:05:00'),
(1, 'deactivate_user', 'Deactivated user account: inactive.user', '127.0.0.1', '2026-02-21 09:00:00');
