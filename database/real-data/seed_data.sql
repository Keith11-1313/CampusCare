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
('4th Year', 4);

-- ============================================================
-- Seed: Users
-- Passwords are hashed using password_hash('password', PASSWORD_DEFAULT)
-- Security answers are hashed using password_hash(strtolower(answer), PASSWORD_DEFAULT)
-- Default passwords and security answers listed in comments for testing
-- Password policy: min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special character
-- ============================================================

-- Admin: username=admin, password=Admin@123, security_answer=manila
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`, `security_question`, `security_answer`) VALUES
('admin', '$2y$10$VAJFIi9PI6/rPDlzpEVAp.QVvXvLuEQFms3CXEU/Jp1SoldOOdN.W', 'System', 'Administrator', 'admin@campuscare.edu', 'admin', 'active', 'What city were you born in?', '$2y$10$QY3KUcuI7OJa74X6aXpOp.teSFivu1.fI9vPOKUHN4zySDO23sdvC');

-- Nurse: username=nurse_garcia, password=Nurse@123, security_answer=brownie
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`, `security_question`, `security_answer`) VALUES
('nurse_garcia', '$2y$10$YwzBdn7GJqJW5cWyGmDEUefUXTWAM2rWx5aoPVFdPfNf8irzQU.0W', 'Maria', 'Garcia', 'maria.garcia@campuscare.edu', 'nurse', 'active', 'What is the name of your first pet?', '$2y$10$JeHAii7DPfBmNBAIU6epWuRYjTP/xxJQ/zow0n1dowPKI6nuImuo6');

-- Nurse: username=nurse_santos, password=Nurse@123, security_answer=adobo
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`, `security_question`, `security_answer`) VALUES
('nurse_santos', '$2y$10$YwzBdn7GJqJW5cWyGmDEUefUXTWAM2rWx5aoPVFdPfNf8irzQU.0W', 'Jose', 'Santos', 'jose.santos@campuscare.edu', 'nurse', 'active', 'What is your favorite food?', '$2y$10$FuoY2jRohdWRA24.FrMWUu7DLQ6PF9y6V43/nGU6zTAmvj58l/tgu');

-- Nurse: username=nurse_reyes, password=Nurse@123, security_answer=quezon city
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`, `security_question`, `security_answer`) VALUES
('nurse_reyes', '$2y$10$YwzBdn7GJqJW5cWyGmDEUefUXTWAM2rWx5aoPVFdPfNf8irzQU.0W', 'Ana', 'Reyes', 'ana.reyes@campuscare.edu', 'nurse', 'active', 'What city were you born in?', '$2y$10$wsM7F.cT/Lg7FmlUPnKQeefbrlyupqFsS2EvKC1L9pz0JkN8p.wdi');

-- Nurse: username=nurse_cruz, password=Nurse@123, security_answer=carlo
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`, `security_question`, `security_answer`) VALUES
('nurse_cruz', '$2y$10$YwzBdn7GJqJW5cWyGmDEUefUXTWAM2rWx5aoPVFdPfNf8irzQU.0W', 'Rosa', 'Cruz', 'rosa.cruz@campuscare.edu', 'nurse', 'active', 'What is the name of your best friend?', '$2y$10$oFKGK7tzL//bDmbmMo5c3OX1.9S.1MioHzJ2lx/6MWouRJb2A.vdW');

-- Nurse: username=nurse_mendoza, password=Nurse@123, security_answer=blue
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`, `security_question`, `security_answer`) VALUES
('nurse_mendoza', '$2y$10$YwzBdn7GJqJW5cWyGmDEUefUXTWAM2rWx5aoPVFdPfNf8irzQU.0W', 'Elena', 'Mendoza', 'elena.mendoza@campuscare.edu', 'nurse', 'active', 'What is your favorite color?', '$2y$10$Oq3yFRSqBnjuo9JfmhsVFOpwJ/BhGHHq1FOH.Tvr.4WN7GZdfZM9e');

-- Nurse: username=nurse_villanueva, password=Nurse@123, security_answer=sinigang
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`, `security_question`, `security_answer`) VALUES
('nurse_villanueva', '$2y$10$YwzBdn7GJqJW5cWyGmDEUefUXTWAM2rWx5aoPVFdPfNf8irzQU.0W', 'Carmen', 'Villanueva', 'carmen.villanueva@campuscare.edu', 'nurse', 'active', 'What is your favorite food?', '$2y$10$FgkUrOz0.eDbkl0CKbI/s.f1ZzJOTgauxQIhkiuAIswo.mQRQtqJm');

-- Nurse: username=nurse_torres, password=Nurse@123, security_answer=bantay
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`, `security_question`, `security_answer`) VALUES
('nurse_torres', '$2y$10$YwzBdn7GJqJW5cWyGmDEUefUXTWAM2rWx5aoPVFdPfNf8irzQU.0W', 'Patricia', 'Torres', 'patricia.torres@campuscare.edu', 'nurse', 'active', 'What is the name of your first pet?', '$2y$10$CtMLzI3Mmxue8I.UrwZzbe8bkBnJLWdNwichsqErSyIjFwBvBfrlS');

-- Nurse: username=nurse_bautista, password=Nurse@123, security_answer=cebu
INSERT INTO `users` (`username`, `password`, `first_name`, `last_name`, `email`, `role`, `status`, `security_question`, `security_answer`) VALUES
('nurse_bautista', '$2y$10$YwzBdn7GJqJW5cWyGmDEUefUXTWAM2rWx5aoPVFdPfNf8irzQU.0W', 'Lucia', 'Bautista', 'lucia.bautista@campuscare.edu', 'nurse', 'active', 'What city were you born in?', '$2y$10$DgpW9fESSrN0tWbK/JcAresqthJqaPN6piFspMEddVEpHYDPJF61a');

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
INSERT INTO `first_aid_guidelines` (`title`, `icon`, `content`, `status`) VALUES
('Cuts and Wounds', 'cuts-and-wounds', '<strong>Step 1:</strong> Wash your hands or wear disposable gloves if available.<br><strong>Step 2:</strong> Apply gentle pressure with a clean cloth to stop bleeding.<br><strong>Step 3:</strong> Clean the wound under running water.<br><strong>Step 4:</strong> Apply antiseptic and cover with a sterile bandage.<br><strong>Step 5:</strong> Seek medical attention if the wound is deep, won''t stop bleeding, or shows signs of infection.', 'active'),
('Burns', 'burns', '<strong>For minor burns:</strong><br>1. Cool the burn under cool (not cold) running water for at least 10 minutes.<br>2. Do NOT apply ice, butter, or toothpaste.<br>3. Cover with a sterile, non-fluffy dressing.<br>4. Take over-the-counter pain relief if needed.<br><br><strong>For severe burns:</strong> Call emergency services immediately. Do not remove clothing stuck to the burn. Cover with a clean sheet and keep the person warm.', 'active'),
('Fainting', 'fainting-dizziness', '<strong>If someone feels faint:</strong><br>1. Have them lie down and elevate their legs above heart level.<br>2. Loosen any tight clothing.<br>3. Ensure fresh air circulation.<br>4. If they don''t recover within 1 minute, call for medical help.<br><br><strong>If someone has fainted:</strong> Check breathing, place them in the recovery position, and call for help if they don''t regain consciousness quickly.', 'active'),
('Nosebleed', 'nosebleed', '1. Sit upright and lean slightly forward (NOT backward).<br>2. Pinch the soft part of the nose firmly for 10-15 minutes.<br>3. Breathe through your mouth.<br>4. Apply a cold compress to the bridge of the nose.<br>5. Do NOT blow your nose for several hours afterward.<br>6. Seek medical attention if bleeding persists beyond 20 minutes.', 'active'),
('Seizures', 'head-injury', '1. Stay calm and time the seizure.<br>2. Clear the area of hazardous objects.<br>3. Place something soft under the person''s head.<br>4. Turn the person on their side (recovery position).<br>5. Do NOT restrain the person or put anything in their mouth.<br>6. Call emergency services if the seizure lasts more than 5 minutes, if it''s their first seizure, or if they are injured.', 'active'),
('Sprains and Strains', 'fracture-and-sprains', 'Follow the <strong>RICE Method</strong>:<br><br><strong>R</strong>est — Stop activity and rest the injured area.<br><strong>I</strong>ce — Apply ice wrapped in a cloth for 15-20 minutes every 2-3 hours.<br><strong>C</strong>ompression — Wrap the area with an elastic bandage (not too tight).<br><strong>E</strong>levation — Keep the injured area elevated above heart level when possible.<br><br>Seek medical attention if there is severe pain, inability to bear weight, or significant swelling.', 'active');

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
('Saturday', '08:00:00', '12:00:00', 0, NULL),
('Sunday', NULL, NULL, 1, 'Closed');


