-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 06, 2026 at 10:23 AM
-- Server version: 8.0.45-0ubuntu0.22.04.1
-- PHP Version: 7.4.33

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `std660104db`
--

-- --------------------------------------------------------

--
-- Table structure for table `add_caselog`
--

CREATE TABLE `add_caselog` (
  `id` int NOT NULL COMMENT 'รหัสลำดับเคส (Primary Key)',
  `pid` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'รหัสบัตรประชาชนนักเรียน (FK)',
  `case_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ประเภทกรณี (ซึมเศร้า, เครียด, ฯลฯ)',
  `report_date` date DEFAULT NULL COMMENT 'วันที่รายงาน',
  `presenting_symptoms` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'อาการนำ (Presenting Symptoms)',
  `history_personal` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'ประวัติส่วนตัว',
  `history_family` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'ข้อมูลจากครอบครัว',
  `history_school` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'ข้อมูลจากโรงเรียน',
  `personal_habits` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'นิสัยส่วนตัว',
  `history_hospital` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'ข้อมูลจากโรงพยาบาล',
  `consultation_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'รายละเอียดการให้การปรึกษา',
  `event_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'รายละเอียดเหตุการณ์',
  `assist_school` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'แนวทางช่วยเหลือ-โรงเรียน',
  `assist_hospital` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'แนวทางช่วยเหลือ-โรงพยาบาล',
  `assist_parent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'แนวทางช่วยเหลือ-ผู้ปกครอง',
  `assist_other` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'แนวทางช่วยเหลือ-หน่วยงานอื่น',
  `suggestions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'ข้อเสนอแนะ',
  `recorder` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ผู้บันทึกข้อมูล (FK)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'วันเวลาที่บันทึก',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันเวลาที่แก้ไขล่าสุด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางเก็บข้อมูลรายงานการช่วยเหลือรายกรณี';

-- --------------------------------------------------------

--
-- Table structure for table `assessment`
--

CREATE TABLE `assessment` (
  `id` int NOT NULL COMMENT 'idรันaa',
  `pid` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'เลขบัตรประชาขน',
  `date_time` datetime DEFAULT NULL COMMENT 'วันเวลา ณ ที กรอกแบบประเมิน',
  `c1` tinyint(1) DEFAULT NULL COMMENT 'คำถามที่ 1',
  `c2` tinyint(1) DEFAULT NULL COMMENT 'คำถามที่ 2',
  `c3` tinyint(1) DEFAULT NULL COMMENT 'คำถามที่ 3',
  `c4` tinyint(1) DEFAULT NULL COMMENT 'คำถามที่ 4',
  `c5` tinyint(1) DEFAULT NULL COMMENT 'คำถามที่ 5',
  `c6` tinyint(1) DEFAULT NULL COMMENT 'คำถามที่ 6',
  `c7` tinyint(1) DEFAULT NULL COMMENT 'คำถามที่ 7',
  `c8` tinyint(1) DEFAULT NULL COMMENT 'คำถามที่ 8',
  `c9` tinyint(1) DEFAULT NULL COMMENT 'คำถามที่ 9',
  `c10` int DEFAULT NULL COMMENT 'คำถามที่ 10',
  `c11` int DEFAULT NULL COMMENT 'คำถามที่ 11',
  `stress` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'คำถามที่ 12',
  `manage_stress` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'คำถามที่ 13',
  `score` int DEFAULT NULL COMMENT 'คะแนนรวม'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `closure_report`
--

CREATE TABLE `closure_report` (
  `id` int NOT NULL COMMENT 'รหัสรายงาน (Primary Key)',
  `pid` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'รหัสบัตรประชาชนนักเรียน (FK)',
  `case_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ประเภทกรณี (ซึมเศร้า, เครียด, ฯลฯ)',
  `case_count` int DEFAULT NULL COMMENT 'ครั้งที่ (1-10)',
  `report_date` date DEFAULT NULL COMMENT 'วันที่รายงาน',
  `detail_family` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'รายละเอียดการติดตาม-ครอบครัว',
  `detail_school` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'รายละเอียดการติดตาม-โรงเรียน',
  `detail_hospital` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'รายละเอียดการติดตาม-โรงพยาบาล',
  `suggestion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'ข้อเสนอแนะ',
  `recorder` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ผู้บันทึกข้อมูล (Username)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'วันเวลาที่บันทึก',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันเวลาที่แก้ไขล่าสุด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางรายงานการยุติให้การดูแลช่วยเหลือรายกรณี';

-- --------------------------------------------------------

--
-- Table structure for table `forward_case`
--

CREATE TABLE `forward_case` (
  `id` int NOT NULL COMMENT 'รหัสการส่งต่อ (Primary Key)',
  `pid` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'รหัสบัตรประชาชนนักเรียน (FK)',
  `referral_agency` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'หน่วยงานที่ส่งต่อ',
  `referral_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ระบุหน่วยงานอื่น',
  `recorder` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ผู้บันทึกข้อมูล (Username)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'วันเวลาที่บันทึก',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันเวลาที่แก้ไขล่าสุด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางบันทึกการส่งต่อกรณี';

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE `images` (
  `id` int NOT NULL COMMENT 'รหัสรูปภาพ (Primary Key)',
  `case_id` int NOT NULL COMMENT 'เชื่อมโยงกับตาราง add_caselog (FK)',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ชื่อไฟล์รูปภาพที่บันทึกใน Server',
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'เวลาที่อัปโหลด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ตารางเก็บรูปภาพประกอบเคส';

-- --------------------------------------------------------

--
-- Table structure for table `phq_question`
--

CREATE TABLE `phq_question` (
  `id` int NOT NULL,
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prefix`
--

CREATE TABLE `prefix` (
  `prefix_id` int NOT NULL,
  `prefix_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school`
--

CREATE TABLE `school` (
  `school_id` int NOT NULL,
  `school_name` varchar(191) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `sex`
--

CREATE TABLE `sex` (
  `sex_id` int NOT NULL,
  `sex_name` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `student_data`
--

CREATE TABLE `student_data` (
  `school_id` int DEFAULT NULL COMMENT 'รหัสโรงเรียน',
  `pid` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'รหัสบัตรประชาชน',
  `sex` int DEFAULT NULL,
  `prefix_id` int DEFAULT NULL COMMENT 'ไอดีคำนำหน้าชื่อ',
  `fname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ชื่อ',
  `lname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'นามสกุล',
  `age` int DEFAULT NULL COMMENT 'อายุ',
  `class` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ชั้นเรียน',
  `room` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ห้อง',
  `tel` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `date_time` datetime DEFAULT NULL COMMENT 'เวลาที่กรอกแบบฟอร์ม'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `type`
--

CREATE TABLE `type` (
  `type_id` int NOT NULL,
  `type_name` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `username` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `typeuser` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pid` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'รหัสบัตรประชาชน',
  `prefix_id` int DEFAULT NULL COMMENT 'Foreign Key เชื่อมกับตาราง prefix',
  `fname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ชื่อจริง',
  `lname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'นามสกุล',
  `position` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'ตำแหน่ง'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `add_caselog`
--
ALTER TABLE `add_caselog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pid` (`pid`),
  ADD KEY `idx_recorder` (`recorder`);

--
-- Indexes for table `assessment`
--
ALTER TABLE `assessment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pid` (`pid`);

--
-- Indexes for table `closure_report`
--
ALTER TABLE `closure_report`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_closure_pid` (`pid`),
  ADD KEY `idx_closure_recorder` (`recorder`);

--
-- Indexes for table `forward_case`
--
ALTER TABLE `forward_case`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forward_pid` (`pid`),
  ADD KEY `idx_forward_recorder` (`recorder`);

--
-- Indexes for table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_images_caselog` (`case_id`);

--
-- Indexes for table `phq_question`
--
ALTER TABLE `phq_question`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `prefix`
--
ALTER TABLE `prefix`
  ADD PRIMARY KEY (`prefix_id`);

--
-- Indexes for table `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`school_id`) USING BTREE;

--
-- Indexes for table `sex`
--
ALTER TABLE `sex`
  ADD PRIMARY KEY (`sex_id`) USING BTREE;

--
-- Indexes for table `student_data`
--
ALTER TABLE `student_data`
  ADD PRIMARY KEY (`pid`),
  ADD KEY `fk_student_prefix` (`prefix_id`),
  ADD KEY `fk_student_sex` (`sex`),
  ADD KEY `fk_student_school` (`school_id`);

--
-- Indexes for table `type`
--
ALTER TABLE `type`
  ADD PRIMARY KEY (`type_id`) USING BTREE;

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`username`) USING BTREE,
  ADD KEY `fk_users_prefix` (`prefix_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `add_caselog`
--
ALTER TABLE `add_caselog`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'รหัสลำดับเคส (Primary Key)';

--
-- AUTO_INCREMENT for table `assessment`
--
ALTER TABLE `assessment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'idรันaa';

--
-- AUTO_INCREMENT for table `closure_report`
--
ALTER TABLE `closure_report`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'รหัสรายงาน (Primary Key)';

--
-- AUTO_INCREMENT for table `forward_case`
--
ALTER TABLE `forward_case`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'รหัสการส่งต่อ (Primary Key)';

--
-- AUTO_INCREMENT for table `images`
--
ALTER TABLE `images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'รหัสรูปภาพ (Primary Key)';

--
-- Constraints for dumped tables
--

--
-- Constraints for table `add_caselog`
--
ALTER TABLE `add_caselog`
  ADD CONSTRAINT `fk_caselog_student` FOREIGN KEY (`pid`) REFERENCES `student_data` (`pid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `assessment`
--
ALTER TABLE `assessment`
  ADD CONSTRAINT `fk_assessment_student` FOREIGN KEY (`pid`) REFERENCES `student_data` (`pid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `closure_report`
--
ALTER TABLE `closure_report`
  ADD CONSTRAINT `fk_closure_student` FOREIGN KEY (`pid`) REFERENCES `student_data` (`pid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_closure_users` FOREIGN KEY (`recorder`) REFERENCES `users` (`username`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `forward_case`
--
ALTER TABLE `forward_case`
  ADD CONSTRAINT `fk_forward_student` FOREIGN KEY (`pid`) REFERENCES `student_data` (`pid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_forward_users` FOREIGN KEY (`recorder`) REFERENCES `users` (`username`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `fk_images_caselog_constraint` FOREIGN KEY (`case_id`) REFERENCES `add_caselog` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_data`
--
ALTER TABLE `student_data`
  ADD CONSTRAINT `fk_student_prefix` FOREIGN KEY (`prefix_id`) REFERENCES `prefix` (`prefix_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student_school` FOREIGN KEY (`school_id`) REFERENCES `school` (`school_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student_sex` FOREIGN KEY (`sex`) REFERENCES `sex` (`sex_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_prefix` FOREIGN KEY (`prefix_id`) REFERENCES `prefix` (`prefix_id`) ON DELETE SET NULL ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
