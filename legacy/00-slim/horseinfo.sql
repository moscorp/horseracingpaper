-- phpMyAdmin SQL Dump
-- version 4.0.4.1
-- http://www.phpmyadmin.net
--
-- 主機: 127.0.0.1
-- 產生日期: 2022 年 11 月 09 日 09:32
-- 伺服器版本: 5.6.11-log
-- PHP 版本: 5.5.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 資料庫: `moosay`
--

-- --------------------------------------------------------

--
-- 表的結構 `horseinfo`
--

CREATE TABLE IF NOT EXISTS `horseinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `horseid` varchar(5) NOT NULL,
  `RaceIndex` varchar(5) NOT NULL,
  `Pla` int(5) NOT NULL,
  `Date` date NOT NULL,
  `RC_Track_Course` varchar(20) CHARACTER SET utf8 NOT NULL,
  `Dist` int(4) NOT NULL,
  `G` varchar(20) CHARACTER SET utf8 NOT NULL,
  `RaceClass` int(1) NOT NULL,
  `Dr` int(2) NOT NULL,
  `Rtg` int(3) NOT NULL,
  `Trainer` varchar(20) CHARACTER SET utf8 NOT NULL,
  `Jockey` varchar(20) CHARACTER SET utf8 NOT NULL,
  `LBW` varchar(20) CHARACTER SET utf8 NOT NULL,
  `Win_Odds` int(3) NOT NULL,
  `Act_Wt` int(3) NOT NULL,
  `Running_Position` varchar(20) CHARACTER SET utf8 NOT NULL,
  `Finish_Time` varchar(10) CHARACTER SET utf8 NOT NULL,
  `Declar_Wt` int(4) NOT NULL,
  `Gear` varchar(10) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
