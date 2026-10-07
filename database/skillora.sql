-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 07, 2026 at 04:28 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skillora`
--

-- --------------------------------------------------------

--
-- Table structure for table `t_assessment`
--

CREATE TABLE `t_assessment` (
  `ID_Assessment` int NOT NULL,
  `ID_User` int NOT NULL,
  `Tanggal` date DEFAULT NULL,
  `Status` varchar(30) NOT NULL DEFAULT 'In Progress',
  `Started_at` datetime DEFAULT NULL,
  `Completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_assessment_answer`
--

CREATE TABLE `t_assessment_answer` (
  `ID_Answer` int NOT NULL,
  `ID_Assessment` int NOT NULL,
  `ID_Assessment_Question` int NOT NULL,
  `ID_Option` int NOT NULL,
  `Answered_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_assessment_question`
--

CREATE TABLE `t_assessment_question` (
  `ID_Assessment_Question` int NOT NULL,
  `ID_Assessment` int NOT NULL,
  `Question_Text` text NOT NULL,
  `Question_Order` int NOT NULL,
  `Generated_By_AI` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_hasil_assessment`
--

CREATE TABLE `t_hasil_assessment` (
  `ID_Hasil` int NOT NULL,
  `ID_Assessment` int NOT NULL,
  `ID_Skill` int NOT NULL,
  `Score` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_karir`
--

CREATE TABLE `t_karir` (
  `ID_Karir` int NOT NULL,
  `Nama_Karier` varchar(150) NOT NULL,
  `Bidang` varchar(100) DEFAULT NULL,
  `Deskripsi` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_karir_skill`
--

CREATE TABLE `t_karir_skill` (
  `ID_Karir_Skill` int NOT NULL,
  `ID_Karir` int NOT NULL,
  `ID_Skill` int NOT NULL,
  `Tingkat_Kebutuhan` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_option_skill_weight`
--

CREATE TABLE `t_option_skill_weight` (
  `ID_Option_Skill_Weight` int NOT NULL,
  `ID_Option` int NOT NULL,
  `ID_Skill` int NOT NULL,
  `Weight` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_profile`
--

CREATE TABLE `t_profile` (
  `ID_Profile` int NOT NULL,
  `ID_User` int NOT NULL,
  `Universitas` varchar(150) DEFAULT NULL,
  `Jurusan` varchar(150) DEFAULT NULL,
  `Semester` int DEFAULT NULL,
  `Bio` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_question_option`
--

CREATE TABLE `t_question_option` (
  `ID_Option` int NOT NULL,
  `ID_Assessment_Question` int NOT NULL,
  `Option_Label` varchar(10) NOT NULL,
  `Option_Text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_rekomendasi_karier`
--

CREATE TABLE `t_rekomendasi_karier` (
  `ID_Rekomendasi` int NOT NULL,
  `ID_Assessment` int NOT NULL,
  `ID_Karir` int NOT NULL,
  `Match_Score` decimal(5,2) NOT NULL,
  `Ranking` int NOT NULL,
  `Tanggal_Rekomendasi` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_skill`
--

CREATE TABLE `t_skill` (
  `ID_Skill` int NOT NULL,
  `Nama_Skill` varchar(100) NOT NULL,
  `Kategori` varchar(100) DEFAULT NULL,
  `Deskripsi` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_user`
--

CREATE TABLE `t_user` (
  `ID_User` int NOT NULL,
  `Nama` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `t_assessment`
--
ALTER TABLE `t_assessment`
  ADD PRIMARY KEY (`ID_Assessment`),
  ADD KEY `ID_User` (`ID_User`);

--
-- Indexes for table `t_assessment_answer`
--
ALTER TABLE `t_assessment_answer`
  ADD PRIMARY KEY (`ID_Answer`),
  ADD KEY `ID_Assessment` (`ID_Assessment`),
  ADD KEY `ID_Assessment_Question` (`ID_Assessment_Question`),
  ADD KEY `ID_Option` (`ID_Option`);

--
-- Indexes for table `t_assessment_question`
--
ALTER TABLE `t_assessment_question`
  ADD PRIMARY KEY (`ID_Assessment_Question`),
  ADD KEY `ID_Assessment` (`ID_Assessment`);

--
-- Indexes for table `t_hasil_assessment`
--
ALTER TABLE `t_hasil_assessment`
  ADD PRIMARY KEY (`ID_Hasil`),
  ADD UNIQUE KEY `ID_Assessment` (`ID_Assessment`,`ID_Skill`),
  ADD KEY `ID_Skill` (`ID_Skill`);

--
-- Indexes for table `t_karir`
--
ALTER TABLE `t_karir`
  ADD PRIMARY KEY (`ID_Karir`);

--
-- Indexes for table `t_karir_skill`
--
ALTER TABLE `t_karir_skill`
  ADD PRIMARY KEY (`ID_Karir_Skill`),
  ADD UNIQUE KEY `ID_Karir` (`ID_Karir`,`ID_Skill`),
  ADD KEY `ID_Skill` (`ID_Skill`);

--
-- Indexes for table `t_option_skill_weight`
--
ALTER TABLE `t_option_skill_weight`
  ADD PRIMARY KEY (`ID_Option_Skill_Weight`),
  ADD UNIQUE KEY `ID_Option` (`ID_Option`,`ID_Skill`),
  ADD KEY `ID_Skill` (`ID_Skill`);

--
-- Indexes for table `t_profile`
--
ALTER TABLE `t_profile`
  ADD PRIMARY KEY (`ID_Profile`),
  ADD UNIQUE KEY `ID_User` (`ID_User`);

--
-- Indexes for table `t_question_option`
--
ALTER TABLE `t_question_option`
  ADD PRIMARY KEY (`ID_Option`),
  ADD KEY `ID_Assessment_Question` (`ID_Assessment_Question`);

--
-- Indexes for table `t_rekomendasi_karier`
--
ALTER TABLE `t_rekomendasi_karier`
  ADD PRIMARY KEY (`ID_Rekomendasi`),
  ADD UNIQUE KEY `ID_Assessment` (`ID_Assessment`,`ID_Karir`),
  ADD KEY `ID_Karir` (`ID_Karir`);

--
-- Indexes for table `t_skill`
--
ALTER TABLE `t_skill`
  ADD PRIMARY KEY (`ID_Skill`);

--
-- Indexes for table `t_user`
--
ALTER TABLE `t_user`
  ADD PRIMARY KEY (`ID_User`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `t_assessment`
--
ALTER TABLE `t_assessment`
  MODIFY `ID_Assessment` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_assessment_answer`
--
ALTER TABLE `t_assessment_answer`
  MODIFY `ID_Answer` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_assessment_question`
--
ALTER TABLE `t_assessment_question`
  MODIFY `ID_Assessment_Question` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_hasil_assessment`
--
ALTER TABLE `t_hasil_assessment`
  MODIFY `ID_Hasil` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_karir`
--
ALTER TABLE `t_karir`
  MODIFY `ID_Karir` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_karir_skill`
--
ALTER TABLE `t_karir_skill`
  MODIFY `ID_Karir_Skill` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_option_skill_weight`
--
ALTER TABLE `t_option_skill_weight`
  MODIFY `ID_Option_Skill_Weight` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_profile`
--
ALTER TABLE `t_profile`
  MODIFY `ID_Profile` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_question_option`
--
ALTER TABLE `t_question_option`
  MODIFY `ID_Option` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_rekomendasi_karier`
--
ALTER TABLE `t_rekomendasi_karier`
  MODIFY `ID_Rekomendasi` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_skill`
--
ALTER TABLE `t_skill`
  MODIFY `ID_Skill` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_user`
--
ALTER TABLE `t_user`
  MODIFY `ID_User` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `t_assessment`
--
ALTER TABLE `t_assessment`
  ADD CONSTRAINT `t_assessment_ibfk_1` FOREIGN KEY (`ID_User`) REFERENCES `t_user` (`ID_User`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_assessment_answer`
--
ALTER TABLE `t_assessment_answer`
  ADD CONSTRAINT `t_assessment_answer_ibfk_1` FOREIGN KEY (`ID_Assessment`) REFERENCES `t_assessment` (`ID_Assessment`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `t_assessment_answer_ibfk_2` FOREIGN KEY (`ID_Assessment_Question`) REFERENCES `t_assessment_question` (`ID_Assessment_Question`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `t_assessment_answer_ibfk_3` FOREIGN KEY (`ID_Option`) REFERENCES `t_question_option` (`ID_Option`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_assessment_question`
--
ALTER TABLE `t_assessment_question`
  ADD CONSTRAINT `t_assessment_question_ibfk_1` FOREIGN KEY (`ID_Assessment`) REFERENCES `t_assessment` (`ID_Assessment`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_hasil_assessment`
--
ALTER TABLE `t_hasil_assessment`
  ADD CONSTRAINT `t_hasil_assessment_ibfk_1` FOREIGN KEY (`ID_Assessment`) REFERENCES `t_assessment` (`ID_Assessment`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `t_hasil_assessment_ibfk_2` FOREIGN KEY (`ID_Skill`) REFERENCES `t_skill` (`ID_Skill`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_karir_skill`
--
ALTER TABLE `t_karir_skill`
  ADD CONSTRAINT `t_karir_skill_ibfk_1` FOREIGN KEY (`ID_Karir`) REFERENCES `t_karir` (`ID_Karir`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `t_karir_skill_ibfk_2` FOREIGN KEY (`ID_Skill`) REFERENCES `t_skill` (`ID_Skill`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_option_skill_weight`
--
ALTER TABLE `t_option_skill_weight`
  ADD CONSTRAINT `t_option_skill_weight_ibfk_1` FOREIGN KEY (`ID_Option`) REFERENCES `t_question_option` (`ID_Option`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `t_option_skill_weight_ibfk_2` FOREIGN KEY (`ID_Skill`) REFERENCES `t_skill` (`ID_Skill`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_profile`
--
ALTER TABLE `t_profile`
  ADD CONSTRAINT `t_profile_ibfk_1` FOREIGN KEY (`ID_User`) REFERENCES `t_user` (`ID_User`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_question_option`
--
ALTER TABLE `t_question_option`
  ADD CONSTRAINT `t_question_option_ibfk_1` FOREIGN KEY (`ID_Assessment_Question`) REFERENCES `t_assessment_question` (`ID_Assessment_Question`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `t_rekomendasi_karier`
--
ALTER TABLE `t_rekomendasi_karier`
  ADD CONSTRAINT `t_rekomendasi_karier_ibfk_1` FOREIGN KEY (`ID_Assessment`) REFERENCES `t_assessment` (`ID_Assessment`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `t_rekomendasi_karier_ibfk_2` FOREIGN KEY (`ID_Karir`) REFERENCES `t_karir` (`ID_Karir`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
