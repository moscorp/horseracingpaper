-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- 主機： localhost:3306
-- 產生時間： 2026 年 09 月 25 日 02:12
-- 伺服器版本： 11.4.13-MariaDB-cll-lve
-- PHP 版本： 8.4.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `fengrmkw_moosay`
--

-- --------------------------------------------------------

--
-- 資料表結構 `00m6`
--

CREATE TABLE `00m6` (
  `id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `nos` int(4) NOT NULL,
  `no1` int(2) NOT NULL,
  `no2` int(2) NOT NULL,
  `no3` int(2) NOT NULL,
  `no4` int(2) NOT NULL,
  `no5` int(2) NOT NULL,
  `no6` int(2) NOT NULL,
  `no7` int(2) NOT NULL,
  `memo` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `00m6estpos`
--

CREATE TABLE `00m6estpos` (
  `id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `nos` int(4) NOT NULL,
  `no1` int(2) NOT NULL,
  `no2` int(2) NOT NULL,
  `no3` int(2) NOT NULL,
  `no4` int(2) NOT NULL,
  `no5` int(2) NOT NULL,
  `no6` int(2) NOT NULL,
  `no7` int(2) NOT NULL,
  `memo` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `barriercomment`
--

CREATE TABLE `barriercomment` (
  `id` int(12) NOT NULL,
  `comment` varchar(50) NOT NULL,
  `rank` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `barrierday`
--

CREATE TABLE `barrierday` (
  `id` int(11) NOT NULL,
  `barrierday` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `barrierresult`
--

CREATE TABLE `barrierresult` (
  `id` int(11) NOT NULL,
  `barrierday` date DEFAULT NULL,
  `Going` varchar(100) DEFAULT NULL,
  `Horse` varchar(50) DEFAULT NULL,
  `Jockey` varchar(50) DEFAULT NULL,
  `Trainer` varchar(100) DEFAULT NULL COMMENT '练马师',
  `Draw` varchar(10) DEFAULT NULL COMMENT '排位',
  `Gear` varchar(50) DEFAULT NULL COMMENT '马匹配备',
  `Margin` varchar(20) DEFAULT NULL COMMENT '头马距离',
  `FinishTime` varchar(20) DEFAULT NULL COMMENT '完成时间',
  `SectionalTime` text DEFAULT NULL COMMENT '分段时间',
  `GoingCondition` varchar(50) DEFAULT NULL COMMENT '场地状况',
  `OverallTime` varchar(20) DEFAULT NULL COMMENT '整体时间',
  `RunningPosition` varchar(50) NOT NULL,
  `Timex` varchar(50) DEFAULT NULL,
  `Result` varchar(50) DEFAULT NULL,
  `Comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `funds`
--

CREATE TABLE `funds` (
  `id` int(12) NOT NULL,
  `UNIT_PRICE_DATE` date NOT NULL,
  `FUND_CODE` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `FUND_NAME` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `FUND_CURRENCY` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `PRICE` decimal(10,4) NOT NULL,
  `rectime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `funds_hsi`
--

CREATE TABLE `funds_hsi` (
  `id` int(12) NOT NULL,
  `tradeday` date DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `symbol` varchar(10) DEFAULT NULL,
  `exchangeName` varchar(50) DEFAULT NULL,
  `fullExchangeName` varchar(100) DEFAULT NULL,
  `instrumentType` varchar(50) DEFAULT NULL,
  `firstTradeDate` date DEFAULT NULL,
  `regularMarketTime` datetime DEFAULT NULL,
  `hasPrePostMarketData` tinyint(1) DEFAULT NULL,
  `gmtoffset` int(11) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL,
  `exchangeTimezoneName` varchar(50) DEFAULT NULL,
  `regularMarketPrice` decimal(10,4) DEFAULT NULL,
  `fiftyTwoWeekHigh` decimal(10,4) DEFAULT NULL,
  `fiftyTwoWeekLow` decimal(10,4) DEFAULT NULL,
  `regularMarketDayHigh` decimal(10,4) DEFAULT NULL,
  `regularMarketDayLow` decimal(10,4) DEFAULT NULL,
  `regularMarketVolume` bigint(20) DEFAULT NULL,
  `chartPreviousClose` decimal(10,4) DEFAULT NULL,
  `priceHint` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `funds_hsi_indicators`
--

CREATE TABLE `funds_hsi_indicators` (
  `id` int(12) NOT NULL,
  `tradeday` date DEFAULT NULL,
  `high` decimal(10,2) DEFAULT NULL,
  `low` decimal(10,2) DEFAULT NULL,
  `close` decimal(10,2) DEFAULT NULL,
  `volume` bigint(20) DEFAULT NULL,
  `open` decimal(10,2) DEFAULT NULL,
  `adjclose` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `historyodds`
--

CREATE TABLE `historyodds` (
  `id` int(11) NOT NULL,
  `venue` varchar(5) NOT NULL,
  `raceno` int(2) NOT NULL,
  `horseid` varchar(4) NOT NULL,
  `historyodds` text NOT NULL,
  `rank` int(1) NOT NULL,
  `rectime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `historyodds_pattern`
--

CREATE TABLE `historyodds_pattern` (
  `id` int(11) NOT NULL,
  `pattern` varchar(5) NOT NULL,
  `pla` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkjc_marksix_draws`
--

CREATE TABLE `hkjc_marksix_draws` (
  `id` int(10) UNSIGNED NOT NULL,
  `year` int(11) NOT NULL,
  `draw_no` varchar(32) NOT NULL,
  `status` varchar(64) DEFAULT NULL,
  `draw_date` datetime DEFAULT NULL,
  `drawn_order_nos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`drawn_order_nos_json`)),
  `drawn_order_nos_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkms_gann`
--

CREATE TABLE `hkms_gann` (
  `id` int(12) NOT NULL,
  `laterest_drawdate` date NOT NULL,
  `jpgfilename` text NOT NULL,
  `rectime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_active_meetings`
--

CREATE TABLE `hkracing_active_meetings` (
  `meeting_id` varchar(50) NOT NULL,
  `activated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_blog_posts`
--

CREATE TABLE `hkracing_blog_posts` (
  `id` int(11) NOT NULL,
  `race_date` date NOT NULL,
  `venue_code` varchar(10) NOT NULL,
  `wp_post_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `score_version` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_cache`
--

CREATE TABLE `hkracing_cache` (
  `cache_key` varchar(255) NOT NULL,
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`response_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_history_queue`
--

CREATE TABLE `hkracing_history_queue` (
  `id` int(11) NOT NULL,
  `horse_code` varchar(10) NOT NULL,
  `horse_name_ch` varchar(100) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `priority` int(11) DEFAULT 0,
  `retry_count` int(11) DEFAULT 0,
  `last_attempt` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_horses`
--

CREATE TABLE `hkracing_horses` (
  `horse_code` varchar(20) NOT NULL COMMENT '马匹代码，如 J071',
  `horse_id` varchar(50) DEFAULT NULL COMMENT '马匹ID，如 HK_2023_J071',
  `name_en` varchar(100) DEFAULT NULL COMMENT '英文名',
  `name_ch` varchar(100) DEFAULT NULL COMMENT '中文名',
  `age` int(11) DEFAULT NULL COMMENT '年龄',
  `sex` varchar(10) DEFAULT NULL COMMENT '性别：阉/雄/雌',
  `colour` varchar(20) DEFAULT NULL COMMENT '毛色',
  `import_type` varchar(50) DEFAULT NULL COMMENT '进口类别',
  `country_of_origin` varchar(50) DEFAULT NULL COMMENT '出生地',
  `trainer_name_en` varchar(100) DEFAULT NULL COMMENT '练马师英文',
  `trainer_name_ch` varchar(100) DEFAULT NULL COMMENT '练马师中文',
  `owner_name_en` varchar(255) DEFAULT NULL COMMENT '马主英文',
  `owner_name_ch` varchar(255) DEFAULT NULL COMMENT '马主中文',
  `current_rating` int(11) DEFAULT NULL COMMENT '现时评分',
  `season_start_rating` int(11) DEFAULT NULL COMMENT '季初评分',
  `sire` varchar(100) DEFAULT NULL COMMENT '父系',
  `dam` varchar(100) DEFAULT NULL COMMENT '母系',
  `maternal_sire` varchar(100) DEFAULT NULL COMMENT '外祖父',
  `total_starts` int(11) DEFAULT 0 COMMENT '总出赛次数',
  `total_wins` int(11) DEFAULT 0 COMMENT '总胜场',
  `total_seconds` int(11) DEFAULT 0 COMMENT '总亚军',
  `total_thirds` int(11) DEFAULT 0 COMMENT '总季军',
  `total_prize_money` decimal(12,2) DEFAULT NULL COMMENT '总奖金',
  `season_prize_money` decimal(12,2) DEFAULT NULL COMMENT '今季奖金',
  `import_date` date DEFAULT NULL COMMENT '进口日期',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_sync_date` date DEFAULT NULL,
  `last_performance_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_horse_fetch_log`
--

CREATE TABLE `hkracing_horse_fetch_log` (
  `id` int(11) NOT NULL,
  `horse_code` varchar(20) NOT NULL,
  `status` enum('success','failed') NOT NULL,
  `error_message` text DEFAULT NULL,
  `performances_count` int(11) DEFAULT 0,
  `duration_ms` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_horse_performances`
--

CREATE TABLE `hkracing_horse_performances` (
  `id` int(11) NOT NULL,
  `horse_code` varchar(20) NOT NULL,
  `season` varchar(10) DEFAULT NULL COMMENT '马季，如 25/26',
  `race_no` int(11) DEFAULT NULL COMMENT '场次编号',
  `finishing_position` int(11) DEFAULT NULL COMMENT '名次',
  `race_date` date DEFAULT NULL COMMENT '赛事日期',
  `venue_code` varchar(10) DEFAULT NULL COMMENT '马场代码 ST/HV',
  `course` varchar(50) DEFAULT NULL COMMENT '跑道/赛道',
  `distance` int(11) DEFAULT NULL COMMENT '途程(米)',
  `going` varchar(20) DEFAULT NULL COMMENT '场地状况',
  `class` varchar(20) DEFAULT NULL COMMENT '赛事班次',
  `barrier_draw` int(11) DEFAULT NULL COMMENT '档位',
  `rating_before` int(11) DEFAULT NULL COMMENT '赛前评分',
  `trainer_name_ch` varchar(100) DEFAULT NULL COMMENT '练马师',
  `jockey_name_ch` varchar(100) DEFAULT NULL COMMENT '骑师',
  `margin` varchar(50) DEFAULT NULL COMMENT '头马距离',
  `win_odds` decimal(6,2) DEFAULT NULL COMMENT '独赢赔率',
  `actual_weight` decimal(5,1) DEFAULT NULL COMMENT '实际负磅',
  `running_position` varchar(20) DEFAULT NULL COMMENT '沿途走位',
  `finishing_time` varchar(20) DEFAULT NULL COMMENT '完成时间',
  `horse_weight` int(11) DEFAULT NULL COMMENT '排位体重',
  `gear` varchar(50) DEFAULT NULL COMMENT '配備',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_incremental_queue`
--

CREATE TABLE `hkracing_incremental_queue` (
  `id` int(11) NOT NULL,
  `horse_code` varchar(10) NOT NULL,
  `horse_name_ch` varchar(100) DEFAULT NULL,
  `reason` varchar(50) DEFAULT 'new_race',
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `retry_count` int(11) DEFAULT 0,
  `last_attempt` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_meetings`
--

CREATE TABLE `hkracing_meetings` (
  `id` varchar(50) NOT NULL,
  `venue_code` varchar(10) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(20) DEFAULT NULL,
  `total_number_of_race` int(11) DEFAULT 0,
  `current_number_of_race` int(11) DEFAULT 0,
  `date_of_week` varchar(3) DEFAULT NULL,
  `meeting_type` varchar(5) DEFAULT NULL,
  `total_investment` decimal(15,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `result_status` enum('pending','updated') DEFAULT 'pending' COMMENT '结果更新状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_odds_cwin_selections`
--

CREATE TABLE `hkracing_odds_cwin_selections` (
  `id` int(11) NOT NULL,
  `race_id` varchar(50) NOT NULL,
  `odds_type` varchar(20) NOT NULL,
  `pool_type` enum('Pre','Curr') DEFAULT 'Curr',
  `composite` varchar(50) DEFAULT NULL,
  `name_ch` varchar(100) DEFAULT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `starters` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_odds_data`
--

CREATE TABLE `hkracing_odds_data` (
  `id` int(11) NOT NULL,
  `race_date` date NOT NULL,
  `venue_code` varchar(10) NOT NULL,
  `race_no` int(11) NOT NULL,
  `odds_type` enum('Pre','Curr') NOT NULL,
  `data_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data_snapshot`)),
  `horse_odds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`horse_odds`)),
  `captured_at` datetime DEFAULT current_timestamp(),
  `is_final` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_odds_horse_details`
--

CREATE TABLE `hkracing_odds_horse_details` (
  `id` int(11) NOT NULL,
  `odds_data_id` int(11) NOT NULL,
  `race_date` date NOT NULL,
  `venue_code` varchar(10) NOT NULL,
  `race_no` int(11) NOT NULL,
  `horse_code` varchar(20) DEFAULT NULL,
  `horse_name_ch` varchar(100) DEFAULT NULL,
  `horse_name_en` varchar(100) DEFAULT NULL,
  `runner_no` varchar(5) DEFAULT NULL,
  `win_pre_odds` decimal(10,2) DEFAULT NULL,
  `win_odds` decimal(10,2) DEFAULT NULL,
  `place_pre_odds` decimal(10,2) DEFAULT NULL,
  `place_odds` decimal(10,2) DEFAULT NULL,
  `quinella_pre_odds` decimal(10,2) DEFAULT NULL,
  `quinella_odds` decimal(10,2) DEFAULT NULL,
  `quinella_place_pre_odds` decimal(10,2) DEFAULT NULL,
  `quinella_place_odds` decimal(10,2) DEFAULT NULL,
  `is_hot_favourite_pre` tinyint(4) DEFAULT 0,
  `is_hot_favourite` tinyint(4) DEFAULT 0,
  `odds_type` enum('Pre','Curr') DEFAULT 'Curr',
  `captured_at` datetime DEFAULT current_timestamp(),
  `win_odds_drop` decimal(10,2) DEFAULT NULL COMMENT '独赢赔率变化值',
  `place_odds_drop` decimal(10,2) DEFAULT NULL COMMENT '位置赔率变化值',
  `final_position` tinyint(4) DEFAULT NULL COMMENT '最终名次'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_odds_nodes`
--

CREATE TABLE `hkracing_odds_nodes` (
  `id` int(11) NOT NULL,
  `race_id` varchar(50) NOT NULL,
  `odds_type` varchar(20) NOT NULL,
  `pool_type` enum('Pre','Curr') DEFAULT 'Curr',
  `comb_string` varchar(50) DEFAULT NULL COMMENT '组合字符串，如 "1" 或 "1,2"',
  `odds_value` decimal(10,2) DEFAULT NULL COMMENT '赔率值',
  `hot_favourite` tinyint(4) DEFAULT 0 COMMENT '是否热门',
  `odds_drop_value` decimal(10,2) DEFAULT NULL COMMENT '赔率跌幅',
  `banker_odds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '银行马赔率' CHECK (json_valid(`banker_odds`)),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_odds_pools`
--

CREATE TABLE `hkracing_odds_pools` (
  `id` int(11) NOT NULL,
  `race_id` varchar(50) NOT NULL,
  `odds_type` varchar(20) NOT NULL COMMENT '赔率类型: WIN, PLA, QIN, WINPre等',
  `pool_type` enum('Pre','Curr') DEFAULT 'Curr' COMMENT '赛前/当前',
  `status` varchar(20) DEFAULT NULL,
  `sell_status` varchar(20) DEFAULT NULL,
  `last_update_time` datetime DEFAULT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `name_ch` varchar(100) DEFAULT NULL,
  `guarantee` varchar(50) DEFAULT NULL,
  `min_ticket_cost` varchar(20) DEFAULT NULL,
  `data_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_snapshot`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_pending_horses`
--

CREATE TABLE `hkracing_pending_horses` (
  `id` int(11) NOT NULL,
  `horse_code` varchar(20) NOT NULL,
  `horse_name_ch` varchar(100) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `priority` int(11) DEFAULT 0,
  `sync_reason` varchar(100) DEFAULT NULL COMMENT '同步原因',
  `retry_count` int(11) DEFAULT 0,
  `last_attempt` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_poster_log`
--

CREATE TABLE `hkracing_poster_log` (
  `id` int(11) NOT NULL,
  `race_date` date NOT NULL,
  `venue_code` varchar(10) NOT NULL,
  `race_no` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `image_filename` varchar(200) NOT NULL,
  `image_size` int(11) DEFAULT 0,
  `facebook_post_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','generated','posted','failed') DEFAULT 'pending',
  `posted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_races`
--

CREATE TABLE `hkracing_races` (
  `id` varchar(50) NOT NULL,
  `meeting_id` varchar(50) NOT NULL,
  `race_no` int(11) NOT NULL,
  `status` varchar(20) DEFAULT NULL,
  `race_name_en` varchar(255) DEFAULT NULL,
  `race_name_ch` varchar(255) DEFAULT NULL,
  `post_time` datetime DEFAULT NULL,
  `country_en` varchar(100) DEFAULT NULL,
  `country_ch` varchar(100) DEFAULT NULL,
  `distance` int(11) DEFAULT NULL,
  `wagering_field_size` int(11) DEFAULT NULL,
  `go_en` varchar(50) DEFAULT NULL,
  `go_ch` varchar(50) DEFAULT NULL,
  `rating_type` varchar(10) DEFAULT NULL,
  `track_en` varchar(50) DEFAULT NULL,
  `track_ch` varchar(50) DEFAULT NULL,
  `course_en` varchar(100) DEFAULT NULL,
  `course_ch` varchar(100) DEFAULT NULL,
  `course_display_code` varchar(20) DEFAULT NULL,
  `class_code` varchar(10) DEFAULT NULL,
  `race_class_en` varchar(50) DEFAULT NULL,
  `race_class_ch` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_review_posts`
--

CREATE TABLE `hkracing_review_posts` (
  `id` int(11) NOT NULL,
  `race_date` date NOT NULL,
  `venue_code` varchar(10) NOT NULL,
  `wp_post_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_runners`
--

CREATE TABLE `hkracing_runners` (
  `id` varchar(50) NOT NULL,
  `race_id` varchar(50) NOT NULL,
  `horse_code` varchar(20) DEFAULT NULL,
  `horse_id` varchar(50) DEFAULT NULL,
  `runner_no` varchar(5) DEFAULT NULL,
  `standby_no` varchar(5) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `name_ch` varchar(100) DEFAULT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `color` varchar(10) DEFAULT NULL,
  `barrier_draw_number` varchar(5) DEFAULT NULL,
  `handicap_weight` varchar(10) DEFAULT NULL,
  `current_weight` varchar(10) DEFAULT NULL,
  `current_rating` varchar(10) DEFAULT NULL,
  `international_rating` varchar(10) DEFAULT NULL,
  `gear_info` varchar(50) DEFAULT NULL,
  `jockey_code` varchar(10) DEFAULT NULL,
  `jockey_name_en` varchar(100) DEFAULT NULL,
  `jockey_name_ch` varchar(100) DEFAULT NULL,
  `trainer_code` varchar(10) DEFAULT NULL,
  `trainer_name_en` varchar(100) DEFAULT NULL,
  `trainer_name_ch` varchar(100) DEFAULT NULL,
  `final_position` int(11) DEFAULT 0,
  `win_odds` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_screenshot_tasks`
--

CREATE TABLE `hkracing_screenshot_tasks` (
  `id` int(11) NOT NULL,
  `race_date` date NOT NULL,
  `venue_code` varchar(10) NOT NULL,
  `race_no` int(11) NOT NULL,
  `share_url` varchar(500) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `image_size` int(11) DEFAULT 0,
  `status` enum('pending','processing','success','failed','retry') DEFAULT 'pending',
  `retry_count` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `api_response` text DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hkracing_v2_score`
--

CREATE TABLE `hkracing_v2_score` (
  `id` int(11) NOT NULL,
  `race_id` varchar(18) NOT NULL COMMENT '賽事ID',
  `horse_code` varchar(20) NOT NULL COMMENT '馬匹編號',
  `runner_no` varchar(10) DEFAULT NULL COMMENT '馬匹號碼',
  `score_win_rate` decimal(5,2) DEFAULT 0.00 COMMENT '勝率評分 (最高30分)',
  `score_recent_form` decimal(5,2) DEFAULT 0.00 COMMENT '近三場表現 (最高30分)',
  `score_draw_history` decimal(5,2) DEFAULT 0.00 COMMENT '檔位歷史 (最高15分)',
  `score_trainer_jockey` decimal(5,2) DEFAULT 0.00 COMMENT '騎練合作 (最高15分)',
  `score_distance_venue` decimal(5,2) DEFAULT 0.00 COMMENT '同程同場 (最高15分)',
  `score_odds` decimal(5,2) DEFAULT 0.00 COMMENT '賠率評分 (最高15分)',
  `score_class_change` decimal(5,2) DEFAULT 0.00 COMMENT '班次變動 (±12分)',
  `score_trainer_venue` decimal(5,2) DEFAULT 0.00 COMMENT '練馬師場地 (最高10分)',
  `score_trainer_form` decimal(5,2) DEFAULT 0.00 COMMENT '練馬師近態 (最高10分)',
  `total_score` decimal(5,2) DEFAULT 0.00 COMMENT '綜合評分總分 (最高120分)',
  `prediction_tag` varchar(50) DEFAULT NULL COMMENT '預測標籤',
  `prediction_class` varchar(30) DEFAULT NULL COMMENT '預測類別 (hot/equal/cold)',
  `class_change_text` text DEFAULT NULL COMMENT '班次變動文字顯示',
  `win_odds_snapshot` decimal(10,2) DEFAULT NULL COMMENT '賠率快照值',
  `place_odds` decimal(10,2) DEFAULT NULL COMMENT '位置賠率',
  `has_odds` tinyint(1) DEFAULT 0 COMMENT '是否有賠率數據',
  `score_version` int(11) DEFAULT 1 COMMENT '評分版本 (1=無賠率, 2=有賠率)',
  `priority` int(11) DEFAULT NULL,
  `blog_score` decimal(5,2) DEFAULT NULL,
  `blog_priority_score` decimal(5,2) DEFAULT NULL,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '最後更新時間',
  `race_finished` tinyint(1) DEFAULT 0 COMMENT '賽事是否已結束',
  `final_position` int(11) DEFAULT NULL,
  `final_win_odds` decimal(10,2) DEFAULT NULL,
  `barrier_trial_signal` int(11) DEFAULT 0 COMMENT '試閘訊號分 0-20',
  `barrier_trial_level` char(1) DEFAULT NULL COMMENT '訊號等級 A/B/C/D',
  `barrier_trial_keyword` varchar(100) DEFAULT NULL COMMENT '訊號關鍵字',
  `barrier_trial_date` date DEFAULT NULL COMMENT '試閘日期',
  `barrier_trial_comment` text DEFAULT NULL COMMENT '原始評語'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='HKJC V2 綜合評分表';

-- --------------------------------------------------------

--
-- 資料表結構 `horseinfo`
--

CREATE TABLE `horseinfo` (
  `id` int(11) NOT NULL,
  `horseid` varchar(50) NOT NULL,
  `horsename` varchar(50) NOT NULL,
  `RaceIndex` varchar(5) NOT NULL,
  `Pla` int(5) NOT NULL,
  `Date` date NOT NULL,
  `RC_Track_Course` varchar(20) NOT NULL,
  `Dist` int(4) NOT NULL,
  `G` varchar(20) NOT NULL,
  `RaceClass` int(1) NOT NULL,
  `Dr` int(2) NOT NULL,
  `Rtg` int(3) NOT NULL,
  `Trainer` varchar(20) NOT NULL,
  `Jockey` varchar(20) NOT NULL,
  `LBW` varchar(20) NOT NULL,
  `Win_Odds` int(3) NOT NULL,
  `Act_Wt` int(3) NOT NULL,
  `Running_Position` varchar(20) NOT NULL,
  `Finish_Time` varchar(10) NOT NULL,
  `Declar_Wt` int(4) NOT NULL,
  `Gear` varchar(10) NOT NULL,
  `rectime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `horseinfo240320`
--

CREATE TABLE `horseinfo240320` (
  `id` int(11) NOT NULL,
  `horseid` varchar(5) NOT NULL,
  `horsename` varchar(50) NOT NULL,
  `RaceIndex` varchar(5) NOT NULL,
  `Pla` int(5) NOT NULL,
  `Date` date NOT NULL,
  `RC_Track_Course` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Dist` int(4) NOT NULL,
  `G` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `RaceClass` int(1) NOT NULL,
  `Dr` int(2) NOT NULL,
  `Rtg` int(3) NOT NULL,
  `Trainer` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Jockey` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `LBW` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Win_Odds` int(3) NOT NULL,
  `Act_Wt` int(3) NOT NULL,
  `Running_Position` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Finish_Time` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Declar_Wt` int(4) NOT NULL,
  `Gear` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `horseinfo_hints`
--

CREATE TABLE `horseinfo_hints` (
  `id` int(11) NOT NULL,
  `racingdate` date NOT NULL,
  `venue` varchar(4) NOT NULL,
  `mtgTotalRace` int(2) NOT NULL,
  `raceno` int(2) NOT NULL,
  `horseno` int(2) NOT NULL,
  `ai2pos` int(2) NOT NULL,
  `trackpla` varchar(20) NOT NULL,
  `jcpla` varchar(10) NOT NULL,
  `jcex` varchar(5) NOT NULL,
  `dist` varchar(1) NOT NULL,
  `rectime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `horseinfo_old`
--

CREATE TABLE `horseinfo_old` (
  `id` int(11) NOT NULL,
  `horseid` varchar(5) NOT NULL,
  `RaceIndex` varchar(5) NOT NULL,
  `Pla` int(5) NOT NULL,
  `Date` date NOT NULL,
  `RC_Track_Course` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Dist` int(4) NOT NULL,
  `G` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `RaceClass` int(1) NOT NULL,
  `Dr` int(2) NOT NULL,
  `Rtg` int(3) NOT NULL,
  `Trainer` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Jockey` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `LBW` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Win_Odds` int(3) NOT NULL,
  `Act_Wt` int(3) NOT NULL,
  `Running_Position` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Finish_Time` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Declar_Wt` int(4) NOT NULL,
  `Gear` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `horseinfo_old2`
--

CREATE TABLE `horseinfo_old2` (
  `id` int(11) NOT NULL,
  `horseid` varchar(5) NOT NULL,
  `horsename` varchar(50) NOT NULL,
  `RaceIndex` varchar(5) NOT NULL,
  `Pla` int(5) NOT NULL,
  `Date` date NOT NULL,
  `RC_Track_Course` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Dist` int(4) NOT NULL,
  `G` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `RaceClass` int(1) NOT NULL,
  `Dr` int(2) NOT NULL,
  `Rtg` int(3) NOT NULL,
  `Trainer` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Jockey` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `LBW` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Win_Odds` int(3) NOT NULL,
  `Act_Wt` int(3) NOT NULL,
  `Running_Position` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Finish_Time` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Declar_Wt` int(4) NOT NULL,
  `Gear` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `horseinfo_summary`
--

CREATE TABLE `horseinfo_summary` (
  `id` int(11) NOT NULL,
  `Trainer` varchar(20) NOT NULL,
  `Jockey` varchar(20) NOT NULL,
  `day_racecnt` int(3) NOT NULL,
  `day_ispla` int(3) NOT NULL,
  `day_iswin` int(3) NOT NULL,
  `night_racecnt` int(3) NOT NULL,
  `night_ispla` int(3) NOT NULL,
  `night_iswin` int(3) NOT NULL,
  `day_racecnt1yr` int(3) NOT NULL,
  `day_ispla1yr` int(3) NOT NULL,
  `day_iswin1yr` int(3) NOT NULL,
  `night_racecnt1yr` int(3) NOT NULL,
  `night_ispla1yr` int(3) NOT NULL,
  `night_iswin1yr` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hrp_pick`
--

CREATE TABLE `hrp_pick` (
  `id` int(12) NOT NULL,
  `racingdate` date NOT NULL,
  `venue` varchar(5) NOT NULL,
  `raceno` int(2) NOT NULL,
  `horseno` int(2) NOT NULL,
  `horsename` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `signalx` varchar(10) NOT NULL,
  `pick` varchar(30) NOT NULL,
  `result` int(1) NOT NULL,
  `rectime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hrp_pick2`
--

CREATE TABLE `hrp_pick2` (
  `id` int(12) NOT NULL,
  `racingdate` date NOT NULL,
  `venue` varchar(5) NOT NULL,
  `raceno` int(2) NOT NULL,
  `pick1` text DEFAULT NULL,
  `pick2` text DEFAULT NULL,
  `pick3` text DEFAULT NULL,
  `pick4` text DEFAULT NULL,
  `rectime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hrp_propanalysis`
--

CREATE TABLE `hrp_propanalysis` (
  `id` int(12) NOT NULL,
  `venue` varchar(2) NOT NULL,
  `proptop` int(1) NOT NULL,
  `prop` varchar(2) NOT NULL,
  `samecnt` int(1) NOT NULL,
  `isbigger` tinyint(1) NOT NULL,
  `issmaller` tinyint(1) NOT NULL,
  `pos` enum('t','m','b') NOT NULL,
  `same1x` tinyint(1) NOT NULL,
  `same2x` tinyint(1) NOT NULL,
  `same3x` tinyint(1) NOT NULL,
  `predict` varchar(20) NOT NULL,
  `pick` varchar(20) NOT NULL,
  `nopick` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hrp_prophist`
--

CREATE TABLE `hrp_prophist` (
  `id` int(12) NOT NULL,
  `racingdate` date NOT NULL,
  `venue` varchar(3) NOT NULL,
  `raceno` int(2) NOT NULL,
  `horseno` int(2) NOT NULL,
  `proptime` time NOT NULL,
  `prop` int(3) NOT NULL,
  `prop_curr` int(3) NOT NULL,
  `prex_wpratio_maxi_save` int(3) DEFAULT NULL,
  `trihint` varchar(4) DEFAULT NULL,
  `trihint_c` varchar(4) DEFAULT NULL,
  `wpmaxhistory` int(1) DEFAULT NULL,
  `tri20` int(2) NOT NULL,
  `ttt20` int(2) NOT NULL,
  `fft20` int(2) NOT NULL,
  `qtt20` int(2) NOT NULL,
  `rectime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hrp_prophist2`
--

CREATE TABLE `hrp_prophist2` (
  `id` int(11) NOT NULL,
  `racingdate` date NOT NULL,
  `venue` varchar(10) NOT NULL,
  `raceno` int(11) NOT NULL,
  `horseno` int(11) NOT NULL,
  `horsecode` int(11) NOT NULL,
  `postTime` datetime NOT NULL,
  `pre_WIN` decimal(10,2) DEFAULT NULL,
  `pre_PLA` decimal(10,2) DEFAULT NULL,
  `pre_oddsDropValue` int(11) DEFAULT NULL,
  `pre_hotFavourite` varchar(1) DEFAULT NULL,
  `pre_prop` int(11) DEFAULT NULL,
  `pre_prop1` int(11) DEFAULT NULL,
  `pre_issmaller` int(11) DEFAULT NULL,
  `curr_WIN` decimal(10,2) DEFAULT NULL,
  `curr_PLA` decimal(10,2) DEFAULT NULL,
  `curr_oddsDropValue` int(11) DEFAULT NULL,
  `curr_hotFavourite` varchar(1) DEFAULT NULL,
  `curr_prop` int(11) DEFAULT NULL,
  `tri20` int(11) DEFAULT NULL,
  `ttt20` int(11) DEFAULT NULL,
  `fft20` int(11) DEFAULT NULL,
  `qtt20` int(11) DEFAULT NULL,
  `finalPosition` int(11) DEFAULT NULL,
  `rectime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `type` varchar(250) NOT NULL,
  `time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `logs.251129`
--

CREATE TABLE `logs.251129` (
  `id` int(11) NOT NULL,
  `type` varchar(250) NOT NULL,
  `time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `m6_all_data_accuracy`
--

CREATE TABLE `m6_all_data_accuracy` (
  `id` int(11) NOT NULL,
  `method` varchar(30) NOT NULL,
  `correct` int(11) DEFAULT 0,
  `total` int(11) DEFAULT 0,
  `accuracy` decimal(5,2) DEFAULT 0.00,
  `no1_accuracy` decimal(5,2) DEFAULT 0.00,
  `no2_accuracy` decimal(5,2) DEFAULT 0.00,
  `no3_accuracy` decimal(5,2) DEFAULT 0.00,
  `no4_accuracy` decimal(5,2) DEFAULT 0.00,
  `no5_accuracy` decimal(5,2) DEFAULT 0.00,
  `no6_accuracy` decimal(5,2) DEFAULT 0.00,
  `no7_accuracy` decimal(5,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `m6_backtest_accuracy`
--

CREATE TABLE `m6_backtest_accuracy` (
  `id` int(11) NOT NULL,
  `method` varchar(30) NOT NULL,
  `training_rows` int(11) NOT NULL,
  `target_column` varchar(5) NOT NULL COMMENT 'no1,no2,no3,no4,no5,no6,no7',
  `mae` decimal(6,2) DEFAULT NULL COMMENT 'Mean Absolute Error',
  `hit_rate` decimal(5,4) DEFAULT NULL COMMENT 'Exact match rate',
  `near_hit_rate` decimal(5,4) DEFAULT NULL COMMENT 'Within ±2',
  `tested_draws` int(11) DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `m6_blog_posts`
--

CREATE TABLE `m6_blog_posts` (
  `id` int(11) NOT NULL,
  `draw_id` int(11) NOT NULL,
  `draw_year` int(11) NOT NULL,
  `draw_nos` int(11) NOT NULL,
  `post_type` varchar(20) DEFAULT 'prediction',
  `wp_post_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `prediction_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `m6_current_year_accuracy`
--

CREATE TABLE `m6_current_year_accuracy` (
  `id` int(11) NOT NULL,
  `method` varchar(30) NOT NULL,
  `correct` int(11) DEFAULT 0,
  `total` int(11) DEFAULT 0,
  `accuracy` decimal(5,2) DEFAULT 0.00,
  `no1_accuracy` decimal(5,2) DEFAULT 0.00,
  `no2_accuracy` decimal(5,2) DEFAULT 0.00,
  `no3_accuracy` decimal(5,2) DEFAULT 0.00,
  `no4_accuracy` decimal(5,2) DEFAULT 0.00,
  `no5_accuracy` decimal(5,2) DEFAULT 0.00,
  `no6_accuracy` decimal(5,2) DEFAULT 0.00,
  `no7_accuracy` decimal(5,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `m6_draw_analysis`
--

CREATE TABLE `m6_draw_analysis` (
  `id` int(11) NOT NULL,
  `draw_id` int(11) NOT NULL,
  `method` varchar(30) NOT NULL,
  `correct_count` int(11) DEFAULT 0,
  `accuracy` decimal(5,2) DEFAULT 0.00,
  `rank_position` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `m6_position_predictions`
--

CREATE TABLE `m6_position_predictions` (
  `id` int(11) NOT NULL,
  `target_draw_id` int(11) NOT NULL COMMENT 'The draw we are predicting',
  `draw_year` int(11) NOT NULL,
  `draw_nos` int(11) NOT NULL,
  `method` varchar(30) NOT NULL COMMENT 'Prediction method name',
  `training_rows` int(11) DEFAULT 30,
  `pred_no1` int(11) DEFAULT NULL,
  `pred_no2` int(11) DEFAULT NULL,
  `pred_no3` int(11) DEFAULT NULL,
  `pred_no4` int(11) DEFAULT NULL,
  `pred_no5` int(11) DEFAULT NULL,
  `pred_no6` int(11) DEFAULT NULL,
  `pred_no7` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `m6_simulated_accuracy`
--

CREATE TABLE `m6_simulated_accuracy` (
  `id` int(11) NOT NULL,
  `method` varchar(30) NOT NULL,
  `total_correct` int(11) DEFAULT 0,
  `total_predictions` int(11) DEFAULT 0,
  `overall_accuracy` decimal(5,2) DEFAULT 0.00,
  `no1_accuracy` decimal(5,2) DEFAULT 0.00,
  `no2_accuracy` decimal(5,2) DEFAULT 0.00,
  `no3_accuracy` decimal(5,2) DEFAULT 0.00,
  `no4_accuracy` decimal(5,2) DEFAULT 0.00,
  `no5_accuracy` decimal(5,2) DEFAULT 0.00,
  `no6_accuracy` decimal(5,2) DEFAULT 0.00,
  `no7_accuracy` decimal(5,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `message_campaigns`
--

CREATE TABLE `message_campaigns` (
  `id` int(11) NOT NULL,
  `campaign_name` varchar(100) NOT NULL,
  `campaign_description` text DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `message_type` enum('text','image','video','document') DEFAULT 'text',
  `message_content` text DEFAULT NULL,
  `media_url` text DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `schedule_type` enum('once','daily','weekly','monthly') DEFAULT 'once',
  `schedule_time` time DEFAULT NULL,
  `schedule_days` varchar(20) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `status` enum('active','paused','completed','draft') DEFAULT 'draft',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `message_groups`
--

CREATE TABLE `message_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `group_description` text DEFAULT NULL,
  `group_type` enum('broadcast','segment','campaign') DEFAULT 'broadcast',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `message_group_users`
--

CREATE TABLE `message_group_users` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `added_at` timestamp NULL DEFAULT current_timestamp(),
  `added_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `message_logs`
--

CREATE TABLE `message_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `queue_id` int(11) DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `chat_id` varchar(100) DEFAULT NULL,
  `message_type` varchar(20) DEFAULT NULL,
  `content_preview` varchar(255) DEFAULT NULL,
  `status` enum('sent','delivered','read','failed','pending') DEFAULT 'pending',
  `whatsapp_status` tinyint(1) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `message_queue`
--

CREATE TABLE `message_queue` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `message_type` enum('text','image','video','document','audio') DEFAULT 'text',
  `message_content` text DEFAULT NULL,
  `media_url` text DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `priority` tinyint(1) DEFAULT 0,
  `status` enum('pending','processing','sent','failed','cancelled') DEFAULT 'pending',
  `retry_count` int(11) DEFAULT 0,
  `max_retries` int(11) DEFAULT 3,
  `scheduled_for` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `message_target_user`
--

CREATE TABLE `message_target_user` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `country_code` varchar(5) DEFAULT '852',
  `status` enum('active','inactive','unsubscribed','blocked') DEFAULT 'active',
  `opt_in` tinyint(1) DEFAULT 1,
  `opt_in_date` datetime DEFAULT NULL,
  `opt_out_date` datetime DEFAULT NULL,
  `last_message_sent` datetime DEFAULT NULL,
  `last_message_status` varchar(20) DEFAULT NULL,
  `total_messages_sent` int(11) DEFAULT 0,
  `total_messages_delivered` int(11) DEFAULT 0,
  `total_messages_read` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `mmkb`
--

CREATE TABLE `mmkb` (
  `id` int(11) NOT NULL,
  `symptoms` text NOT NULL,
  `solution` text NOT NULL,
  `tag` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `oddsDrop`
--

CREATE TABLE `oddsDrop` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `venue` varchar(3) NOT NULL,
  `horsecode` varchar(4) NOT NULL,
  `horsename` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pre_prop` int(3) NOT NULL,
  `curr_prop` int(3) NOT NULL,
  `pre_win` decimal(4,1) NOT NULL,
  `curr_win` decimal(4,1) NOT NULL,
  `oddsDropValue` int(3) NOT NULL,
  `finalPosition` int(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `oddshistory`
--

CREATE TABLE `oddshistory` (
  `id` int(11) NOT NULL,
  `type` varchar(4) NOT NULL,
  `racingdate` date NOT NULL,
  `venue` varchar(4) NOT NULL,
  `mtgTotalRace` int(2) NOT NULL,
  `odds` text NOT NULL,
  `rectime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `prop_manual_rule`
--

CREATE TABLE `prop_manual_rule` (
  `id` bigint(20) NOT NULL,
  `code` varchar(32) NOT NULL,
  `name` varchar(120) NOT NULL,
  `venue_scope` enum('ST','HV','Sx','ALL') NOT NULL DEFAULT 'Sx',
  `priority` int(11) NOT NULL DEFAULT 100,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `mark_weight` decimal(4,2) NOT NULL DEFAULT 1.00,
  `condition_json` longtext NOT NULL,
  `pick_json` longtext NOT NULL,
  `note_zh` text DEFAULT NULL,
  `created_by` varchar(40) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `prop_manual_rule_evidence`
--

CREATE TABLE `prop_manual_rule_evidence` (
  `id` bigint(20) NOT NULL,
  `rule_id` bigint(20) NOT NULL,
  `racingdate` date NOT NULL,
  `venue` varchar(10) NOT NULL,
  `raceno` int(11) NOT NULL,
  `prop_string` varchar(255) NOT NULL,
  `result_json` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `prop_manual_rule_stat`
--

CREATE TABLE `prop_manual_rule_stat` (
  `rule_id` bigint(20) NOT NULL,
  `venue_scope` varchar(10) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `fp_max` tinyint(4) NOT NULL DEFAULT 4,
  `races_matched` int(11) NOT NULL,
  `picks_total` int(11) NOT NULL,
  `picks_placed` int(11) NOT NULL,
  `race_hit` int(11) NOT NULL,
  `hit_rate` decimal(6,4) NOT NULL,
  `pick_hit_rate` decimal(6,4) NOT NULL,
  `computed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `prop_race_fingerprint`
--

CREATE TABLE `prop_race_fingerprint` (
  `id` bigint(20) NOT NULL,
  `racingdate` date NOT NULL,
  `venue` varchar(10) NOT NULL,
  `venue_bucket` enum('ST','HV','Sx') NOT NULL,
  `raceno` int(11) NOT NULL,
  `distance` int(11) DEFAULT NULL,
  `go_ch` varchar(50) DEFAULT NULL,
  `n_horses` tinyint(4) NOT NULL,
  `prop_string` varchar(255) NOT NULL,
  `bands_json` longtext NOT NULL,
  `cells_json` longtext NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `RaceCard`
--

CREATE TABLE `RaceCard` (
  `id` int(11) NOT NULL,
  `racingdate` date DEFAULT NULL,
  `venue` varchar(5) DEFAULT NULL,
  `raceno` varchar(5) DEFAULT NULL,
  `HorseNo` varchar(50) DEFAULT NULL,
  `Last6Runs` varchar(50) DEFAULT NULL,
  `Colour` varchar(50) DEFAULT NULL,
  `horsename` varchar(50) DEFAULT NULL,
  `BrandNo` varchar(50) DEFAULT NULL,
  `Wt` varchar(50) DEFAULT NULL,
  `Jockey` varchar(50) DEFAULT NULL,
  `OverWt` varchar(50) DEFAULT NULL,
  `Draw` varchar(50) DEFAULT NULL,
  `Trainer` varchar(50) DEFAULT NULL,
  `IntlRtg` varchar(50) DEFAULT NULL,
  `Rtg` varchar(50) DEFAULT NULL,
  `Rtgdiff` varchar(50) DEFAULT NULL,
  `HorseWt` varchar(50) DEFAULT NULL,
  `HorseWtDiff` varchar(50) DEFAULT NULL,
  `BestTime` varchar(50) DEFAULT NULL,
  `Age` varchar(50) DEFAULT NULL,
  `WFA` varchar(50) DEFAULT NULL,
  `Sex` varchar(50) DEFAULT NULL,
  `SeasonStakes` varchar(50) DEFAULT NULL,
  `Priority` varchar(50) DEFAULT NULL,
  `Gear` varchar(50) DEFAULT NULL,
  `Owner` varchar(50) DEFAULT NULL,
  `Sire` varchar(50) DEFAULT NULL,
  `Dam` varchar(50) DEFAULT NULL,
  `ImportCat` varchar(50) DEFAULT NULL,
  `result` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `RaceCard_sub`
--

CREATE TABLE `RaceCard_sub` (
  `id` int(11) NOT NULL,
  `racingdate` date DEFAULT NULL,
  `venue` varchar(5) DEFAULT NULL,
  `raceno` varchar(5) DEFAULT NULL,
  `HorseNo` varchar(50) DEFAULT NULL,
  `Last6Runs` varchar(50) DEFAULT NULL,
  `Colour` varchar(50) DEFAULT NULL,
  `horsename` varchar(50) DEFAULT NULL,
  `BrandNo` varchar(50) DEFAULT NULL,
  `Wt` varchar(50) DEFAULT NULL,
  `Jockey` varchar(50) DEFAULT NULL,
  `OverWt` varchar(50) DEFAULT NULL,
  `Draw` varchar(50) DEFAULT NULL,
  `Trainer` varchar(50) DEFAULT NULL,
  `IntlRtg` varchar(50) DEFAULT NULL,
  `Rtg` varchar(50) DEFAULT NULL,
  `Rtgdiff` varchar(50) DEFAULT NULL,
  `HorseWt` varchar(50) DEFAULT NULL,
  `HorseWtDiff` varchar(50) DEFAULT NULL,
  `BestTime` varchar(50) DEFAULT NULL,
  `Age` varchar(50) DEFAULT NULL,
  `WFA` varchar(50) DEFAULT NULL,
  `Sex` varchar(50) DEFAULT NULL,
  `SeasonStakes` varchar(50) DEFAULT NULL,
  `Priority` varchar(50) DEFAULT NULL,
  `Gear` varchar(50) DEFAULT NULL,
  `Owner` varchar(50) DEFAULT NULL,
  `Sire` varchar(50) DEFAULT NULL,
  `Dam` varchar(50) DEFAULT NULL,
  `ImportCat` varchar(50) DEFAULT NULL,
  `result` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `racedata_stheadline`
--

CREATE TABLE `racedata_stheadline` (
  `id` int(11) NOT NULL,
  `race_date` date DEFAULT NULL,
  `startTime` date NOT NULL,
  `venue` varchar(2) NOT NULL,
  `race_no` int(11) DEFAULT NULL,
  `horse_no` int(11) DEFAULT NULL,
  `win_value` decimal(10,2) DEFAULT NULL,
  `win_investment` decimal(10,2) DEFAULT NULL,
  `place_value` decimal(10,2) DEFAULT NULL,
  `place_investment` decimal(10,2) DEFAULT NULL,
  `sourcetimefull` datetime NOT NULL,
  `source_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `racepropresult`
--

CREATE TABLE `racepropresult` (
  `id` int(11) NOT NULL,
  `racingdate` date DEFAULT NULL,
  `venue` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `go_ch` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `horsecode` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `horsename` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `raceno` int(2) DEFAULT NULL,
  `Distance` int(4) DEFAULT NULL,
  `horseno` int(2) DEFAULT NULL,
  `prewin` decimal(4,1) NOT NULL,
  `prepla` decimal(4,1) NOT NULL,
  `proppre` int(3) DEFAULT NULL,
  `finalPosition` int(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `racepropresult_bak`
--

CREATE TABLE `racepropresult_bak` (
  `id` int(11) NOT NULL DEFAULT 0,
  `racingdate` date DEFAULT NULL,
  `venue` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `go_ch` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `horsecode` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `horsename` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `raceno` int(2) DEFAULT NULL,
  `Distance` int(4) DEFAULT NULL,
  `horseno` int(2) DEFAULT NULL,
  `prewin` decimal(4,1) NOT NULL,
  `prepla` decimal(4,1) NOT NULL,
  `proppre` int(3) DEFAULT NULL,
  `finalPosition` int(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `raceresult_pattern`
--

CREATE TABLE `raceresult_pattern` (
  `id` int(11) NOT NULL,
  `racingday` date DEFAULT NULL,
  `raceno` varchar(2) DEFAULT NULL,
  `wpratio_pattern` varchar(11) DEFAULT NULL,
  `result_orderbyprew` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `racingrecord`
--

CREATE TABLE `racingrecord` (
  `id` int(11) NOT NULL,
  `racingdate` date DEFAULT NULL,
  `venueLong` varchar(10) DEFAULT NULL,
  `venue` varchar(10) DEFAULT NULL,
  `raceno` int(2) DEFAULT NULL,
  `Distance` varchar(10) DEFAULT NULL,
  `Track` varchar(20) DEFAULT NULL,
  `Horseno` int(2) DEFAULT NULL,
  `Result` varchar(2) DEFAULT NULL,
  `barrier` varchar(10) DEFAULT NULL,
  `prop1` int(1) DEFAULT NULL,
  `prop2` int(1) DEFAULT NULL,
  `prop3` int(1) DEFAULT NULL,
  `prop4` int(1) DEFAULT NULL,
  `horsename` varchar(50) DEFAULT NULL,
  `Draw` varchar(3) NOT NULL,
  `HandicapWeight` int(3) DEFAULT NULL,
  `WeightAllowance` varchar(3) DEFAULT NULL,
  `RatingRange` varchar(3) DEFAULT NULL,
  `trainer` varchar(50) DEFAULT NULL,
  `trainercnt` varchar(10) DEFAULT NULL,
  `trainer_style` text DEFAULT NULL,
  `trainerresultcnt` varchar(2) DEFAULT NULL,
  `jockey` varchar(50) DEFAULT NULL,
  `jockeyresultcnt` varchar(2) DEFAULT NULL,
  `trainerjockey` varchar(10) DEFAULT NULL,
  `trainerjockeycnt` varchar(2) DEFAULT NULL,
  `OddsInRange` varchar(1) DEFAULT NULL,
  `prewin` decimal(5,1) DEFAULT NULL,
  `prewin_style` text DEFAULT NULL,
  `win` decimal(5,1) DEFAULT NULL,
  `win_style` text DEFAULT NULL,
  `pla` decimal(5,1) DEFAULT NULL,
  `prepla` decimal(5,1) DEFAULT NULL,
  `prepla_style` text DEFAULT NULL,
  `prop` int(2) DEFAULT NULL,
  `prop_style` text DEFAULT NULL,
  `besttime` varchar(10) DEFAULT NULL,
  `beattime_style` text DEFAULT NULL,
  `StakesWon` int(10) DEFAULT NULL,
  `LastSixRuns` varchar(20) DEFAULT NULL,
  `barrierday` date DEFAULT NULL,
  `barrierday_style` text DEFAULT NULL,
  `barrierGoing` varchar(100) DEFAULT NULL,
  `barrierHorse` varchar(50) DEFAULT NULL,
  `barrierJockey` varchar(50) DEFAULT NULL,
  `barrierJockey_style` text DEFAULT NULL,
  `barrierTimex` varchar(50) DEFAULT NULL,
  `barrierResult` varchar(50) DEFAULT NULL,
  `barrierComment` text DEFAULT NULL,
  `rectime` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `racingrecord_summary`
--

CREATE TABLE `racingrecord_summary` (
  `id` int(11) NOT NULL,
  `racingdate` date DEFAULT NULL,
  `venueLong` varchar(10) DEFAULT NULL,
  `venue` varchar(10) DEFAULT NULL,
  `raceno` int(2) DEFAULT NULL,
  `Distance` varchar(10) DEFAULT NULL,
  `Track` varchar(20) DEFAULT NULL,
  `Horseno` int(2) DEFAULT NULL,
  `Result` varchar(2) DEFAULT NULL,
  `barrier` varchar(10) DEFAULT NULL,
  `prop1` int(1) DEFAULT NULL,
  `prop2` int(1) DEFAULT NULL,
  `prop3` int(1) DEFAULT NULL,
  `prop4` int(1) DEFAULT NULL,
  `horsename` varchar(50) DEFAULT NULL,
  `Draw` varchar(3) NOT NULL,
  `HandicapWeight` int(3) DEFAULT NULL,
  `WeightAllowance` varchar(3) DEFAULT NULL,
  `RatingRange` varchar(3) DEFAULT NULL,
  `trainer` varchar(50) DEFAULT NULL,
  `trainercnt` varchar(10) DEFAULT NULL,
  `trainer_style` text DEFAULT NULL,
  `trainerresultcnt` varchar(2) DEFAULT NULL,
  `jockey` varchar(50) DEFAULT NULL,
  `jockeyresultcnt` varchar(2) DEFAULT NULL,
  `trainerjockey` varchar(10) DEFAULT NULL,
  `trainerjockeycnt` varchar(2) DEFAULT NULL,
  `OddsInRange` varchar(1) DEFAULT NULL,
  `prewin` decimal(5,1) DEFAULT NULL,
  `prewin_style` text DEFAULT NULL,
  `win` decimal(5,1) DEFAULT NULL,
  `win_style` text DEFAULT NULL,
  `pla` decimal(5,1) DEFAULT NULL,
  `prepla` decimal(5,1) DEFAULT NULL,
  `prepla_style` text DEFAULT NULL,
  `prop` int(2) DEFAULT NULL,
  `prop_style` text DEFAULT NULL,
  `besttime` varchar(10) DEFAULT NULL,
  `beattime_style` text DEFAULT NULL,
  `StakesWon` int(10) DEFAULT NULL,
  `LastSixRuns` varchar(20) DEFAULT NULL,
  `barrierday` date DEFAULT NULL,
  `barrierday_style` text DEFAULT NULL,
  `barrierGoing` varchar(100) DEFAULT NULL,
  `barrierHorse` varchar(50) DEFAULT NULL,
  `barrierJockey` varchar(50) DEFAULT NULL,
  `barrierJockey_style` text DEFAULT NULL,
  `barrierTimex` varchar(50) DEFAULT NULL,
  `barrierResult` varchar(50) DEFAULT NULL,
  `barrierComment` text DEFAULT NULL,
  `rectime` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `rsdata`
--

CREATE TABLE `rsdata` (
  `id` int(11) NOT NULL,
  `top5Jockey` text NOT NULL,
  `top5Trainer` text NOT NULL,
  `mtgDate` date NOT NULL,
  `mtgVenue` varchar(3) NOT NULL,
  `dayShort` varchar(10) NOT NULL,
  `venueShort` varchar(50) NOT NULL,
  `dayLong` varchar(20) NOT NULL,
  `venueLongCh` varchar(20) NOT NULL,
  `reserveList` text NOT NULL,
  `scratchList` text NOT NULL,
  `meetingIdKey` varchar(50) NOT NULL,
  `foKey` varchar(50) NOT NULL,
  `raceHeaderInfo` text NOT NULL,
  `mtgCurRace` varchar(20) NOT NULL,
  `mtgTotalRace` varchar(20) NOT NULL,
  `mtgRanRace` varchar(30) NOT NULL,
  `isLastRaceRan` varchar(20) NOT NULL,
  `isOverseaMeeting` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `stripe_price_id` varchar(255) DEFAULT NULL,
  `subscription_status` enum('trialing','active','past_due','canceled','unsubscribed') NOT NULL DEFAULT 'trialing',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `messages_used_this_month` int(11) NOT NULL DEFAULT 0,
  `messages_limit` int(11) NOT NULL DEFAULT 50,
  `max_sessions` int(11) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wapi_campaigns`
--

CREATE TABLE `wapi_campaigns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `wa_session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message_type` enum('text','image','document','template') NOT NULL DEFAULT 'text',
  `message_body` text NOT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `media_caption` text DEFAULT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`variables`)),
  `schedule_type` enum('now','specific','recurring') NOT NULL DEFAULT 'now',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `recurring_pattern` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recurring_pattern`)),
  `status` enum('draft','scheduled','running','completed','failed','paused') NOT NULL DEFAULT 'draft',
  `total_recipients` int(11) NOT NULL DEFAULT 0,
  `messages_sent` int(11) NOT NULL DEFAULT 0,
  `messages_failed` int(11) NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wapi_campaign_messages`
--

CREATE TABLE `wapi_campaign_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','queued','sent','delivered','read','failed','bounced') NOT NULL DEFAULT 'pending',
  `whatsapp_message_id` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `rendered_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wapi_contacts`
--

CREATE TABLE `wapi_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `tags` text NOT NULL DEFAULT '[]',
  `consent_status` enum('unknown','granted','denied') NOT NULL DEFAULT 'unknown',
  `consent_source` varchar(255) DEFAULT NULL,
  `consent_timestamp` timestamp NULL DEFAULT NULL,
  `opted_out_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wapi_message_logs`
--

CREATE TABLE `wapi_message_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` bigint(20) UNSIGNED DEFAULT NULL,
  `campaign_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `wa_session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `direction` enum('inbound','outbound') NOT NULL,
  `from_number` varchar(20) NOT NULL,
  `to_number` varchar(20) NOT NULL,
  `message_type` varchar(50) NOT NULL,
  `content_summary` varchar(255) NOT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `raw_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_response`)),
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wapi_migrations`
--

CREATE TABLE `wapi_migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wapi_personal_access_tokens`
--

CREATE TABLE `wapi_personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wapi_subscriptions`
--

CREATE TABLE `wapi_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `stripe_subscription_id` varchar(255) NOT NULL,
  `stripe_price_id` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `current_period_start` timestamp NULL DEFAULT NULL,
  `current_period_end` timestamp NULL DEFAULT NULL,
  `cancel_at_period_end` tinyint(1) NOT NULL DEFAULT 0,
  `canceled_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wapi_users`
--

CREATE TABLE `wapi_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `stripe_price_id` varchar(255) DEFAULT NULL,
  `subscription_status` enum('trialing','active','past_due','canceled','unsubscribed') NOT NULL DEFAULT 'trialing',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `messages_used_this_month` int(11) NOT NULL DEFAULT 0,
  `messages_limit` int(11) NOT NULL DEFAULT 50,
  `max_sessions` int(11) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wapi_wa_sessions`
--

CREATE TABLE `wapi_wa_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `status` enum('creating','qr_ready','connected','disconnected','banned','error') NOT NULL DEFAULT 'creating',
  `qr_code_path` varchar(500) DEFAULT NULL,
  `last_connected_at` timestamp NULL DEFAULT NULL,
  `last_disconnected_at` timestamp NULL DEFAULT NULL,
  `ban_reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wa_campaigns`
--

CREATE TABLE `wa_campaigns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `wa_session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message_type` enum('text','image','document','template') NOT NULL DEFAULT 'text',
  `message_body` text NOT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `media_caption` text DEFAULT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`variables`)),
  `schedule_type` enum('now','specific','recurring') NOT NULL DEFAULT 'now',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `recurring_pattern` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recurring_pattern`)),
  `status` enum('draft','scheduled','running','completed','failed','paused') NOT NULL DEFAULT 'draft',
  `total_recipients` int(11) NOT NULL DEFAULT 0,
  `messages_sent` int(11) NOT NULL DEFAULT 0,
  `messages_failed` int(11) NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wa_campaign_messages`
--

CREATE TABLE `wa_campaign_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','queued','sent','delivered','read','failed','bounced') NOT NULL DEFAULT 'pending',
  `whatsapp_message_id` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `rendered_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wa_contacts`
--

CREATE TABLE `wa_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `tags` text NOT NULL DEFAULT '[]',
  `consent_status` enum('unknown','granted','denied') NOT NULL DEFAULT 'unknown',
  `consent_source` varchar(255) DEFAULT NULL,
  `consent_timestamp` timestamp NULL DEFAULT NULL,
  `opted_out_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wa_sessions`
--

CREATE TABLE `wa_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `status` enum('creating','qr_ready','connected','disconnected','banned','error') NOT NULL DEFAULT 'creating',
  `qr_code_path` varchar(500) DEFAULT NULL,
  `last_connected_at` timestamp NULL DEFAULT NULL,
  `last_disconnected_at` timestamp NULL DEFAULT NULL,
  `ban_reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `whatsapp_logs`
--

CREATE TABLE `whatsapp_logs` (
  `id` int(11) NOT NULL,
  `log_type` enum('info','success','warning','error') DEFAULT 'info',
  `action` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `00m6`
--
ALTER TABLE `00m6`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `00m6estpos`
--
ALTER TABLE `00m6estpos`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `barriercomment`
--
ALTER TABLE `barriercomment`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `barrierday`
--
ALTER TABLE `barrierday`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `barrierresult`
--
ALTER TABLE `barrierresult`
  ADD PRIMARY KEY (`id`),
  ADD KEY `Horse` (`Horse`),
  ADD KEY `barrierday` (`barrierday`),
  ADD KEY `idx_horse_barrierday` (`Horse`,`barrierday`),
  ADD KEY `idx_trainer` (`Trainer`),
  ADD KEY `idx_draw` (`Draw`);

--
-- 資料表索引 `funds`
--
ALTER TABLE `funds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_fund` (`UNIT_PRICE_DATE`,`FUND_CODE`),
  ADD KEY `FUND_CODE` (`FUND_CODE`);

--
-- 資料表索引 `funds_hsi`
--
ALTER TABLE `funds_hsi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tradeday` (`tradeday`);

--
-- 資料表索引 `funds_hsi_indicators`
--
ALTER TABLE `funds_hsi_indicators`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tradeday` (`tradeday`);

--
-- 資料表索引 `historyodds`
--
ALTER TABLE `historyodds`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `historyodds_pattern`
--
ALTER TABLE `historyodds_pattern`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `hkjc_marksix_draws`
--
ALTER TABLE `hkjc_marksix_draws`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_year_drawno` (`year`,`draw_no`);

--
-- 資料表索引 `hkms_gann`
--
ALTER TABLE `hkms_gann`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `hkracing_active_meetings`
--
ALTER TABLE `hkracing_active_meetings`
  ADD PRIMARY KEY (`meeting_id`);

--
-- 資料表索引 `hkracing_blog_posts`
--
ALTER TABLE `hkracing_blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_race_venue_version` (`race_date`,`venue_code`,`score_version`),
  ADD KEY `idx_race_date` (`race_date`);

--
-- 資料表索引 `hkracing_cache`
--
ALTER TABLE `hkracing_cache`
  ADD PRIMARY KEY (`cache_key`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- 資料表索引 `hkracing_history_queue`
--
ALTER TABLE `hkracing_history_queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_horse_code` (`horse_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_horse_code` (`horse_code`),
  ADD KEY `idx_priority` (`priority`,`status`);

--
-- 資料表索引 `hkracing_horses`
--
ALTER TABLE `hkracing_horses`
  ADD PRIMARY KEY (`horse_code`),
  ADD KEY `idx_name_ch` (`name_ch`),
  ADD KEY `idx_trainer` (`trainer_name_ch`),
  ADD KEY `idx_last_sync_date` (`last_sync_date`);

--
-- 資料表索引 `hkracing_horse_fetch_log`
--
ALTER TABLE `hkracing_horse_fetch_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_horse_code` (`horse_code`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 資料表索引 `hkracing_horse_performances`
--
ALTER TABLE `hkracing_horse_performances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_horse_race` (`horse_code`,`race_date`,`venue_code`,`race_no`),
  ADD KEY `idx_horse_code` (`horse_code`),
  ADD KEY `idx_race_date` (`race_date`);

--
-- 資料表索引 `hkracing_incremental_queue`
--
ALTER TABLE `hkracing_incremental_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_horse_code` (`horse_code`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 資料表索引 `hkracing_meetings`
--
ALTER TABLE `hkracing_meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_venue_code` (`venue_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_result_status` (`result_status`);

--
-- 資料表索引 `hkracing_odds_cwin_selections`
--
ALTER TABLE `hkracing_odds_cwin_selections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_race_odds` (`race_id`,`odds_type`,`pool_type`);

--
-- 資料表索引 `hkracing_odds_data`
--
ALTER TABLE `hkracing_odds_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_race_odds_time` (`race_date`,`venue_code`,`race_no`,`odds_type`,`captured_at`),
  ADD KEY `idx_race` (`race_date`,`venue_code`,`race_no`),
  ADD KEY `idx_captured_at` (`captured_at`);

--
-- 資料表索引 `hkracing_odds_horse_details`
--
ALTER TABLE `hkracing_odds_horse_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_horse` (`horse_code`),
  ADD KEY `idx_race` (`race_date`,`venue_code`,`race_no`),
  ADD KEY `odds_data_id` (`odds_data_id`);

--
-- 資料表索引 `hkracing_odds_nodes`
--
ALTER TABLE `hkracing_odds_nodes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_race_odds` (`race_id`,`odds_type`,`pool_type`),
  ADD KEY `idx_odds_value` (`odds_value`);

--
-- 資料表索引 `hkracing_odds_pools`
--
ALTER TABLE `hkracing_odds_pools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_race_odds_type` (`race_id`,`odds_type`,`pool_type`),
  ADD KEY `idx_race_id` (`race_id`),
  ADD KEY `idx_odds_type` (`odds_type`),
  ADD KEY `idx_pool_type` (`pool_type`),
  ADD KEY `idx_last_update` (`last_update_time`);

--
-- 資料表索引 `hkracing_pending_horses`
--
ALTER TABLE `hkracing_pending_horses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_horse_code` (`horse_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`);

--
-- 資料表索引 `hkracing_poster_log`
--
ALTER TABLE `hkracing_poster_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_race_poster` (`race_date`,`venue_code`,`race_no`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_race_date` (`race_date`);

--
-- 資料表索引 `hkracing_races`
--
ALTER TABLE `hkracing_races`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_meeting_race` (`meeting_id`,`race_no`),
  ADD KEY `idx_post_time` (`post_time`),
  ADD KEY `idx_race_no` (`race_no`),
  ADD KEY `idx_race_lookup` (`meeting_id`,`race_no`),
  ADD KEY `idx_race_post_time` (`post_time`);

--
-- 資料表索引 `hkracing_review_posts`
--
ALTER TABLE `hkracing_review_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_race_venue` (`race_date`,`venue_code`),
  ADD KEY `idx_race_date` (`race_date`);

--
-- 資料表索引 `hkracing_runners`
--
ALTER TABLE `hkracing_runners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_race_runner` (`race_id`,`runner_no`),
  ADD KEY `idx_horse_code` (`horse_code`),
  ADD KEY `idx_runner_no` (`runner_no`);

--
-- 資料表索引 `hkracing_screenshot_tasks`
--
ALTER TABLE `hkracing_screenshot_tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_race` (`race_date`,`venue_code`,`race_no`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_retry` (`retry_count`);

--
-- 資料表索引 `hkracing_v2_score`
--
ALTER TABLE `hkracing_v2_score`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_race_horse_version` (`race_id`,`horse_code`,`score_version`),
  ADD KEY `idx_race_id` (`race_id`),
  ADD KEY `idx_horse_code` (`horse_code`),
  ADD KEY `idx_total_score` (`total_score`),
  ADD KEY `idx_last_updated` (`last_updated`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_final_position` (`final_position`);

--
-- 資料表索引 `horseinfo`
--
ALTER TABLE `horseinfo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `horseid` (`horseid`,`RaceIndex`),
  ADD KEY `horsename` (`horsename`),
  ADD KEY `horseid_2` (`horseid`,`Date`),
  ADD KEY `Date` (`Date`),
  ADD KEY `idx_horseinfo` (`horseid`,`Date`,`RaceIndex`);

--
-- 資料表索引 `horseinfo240320`
--
ALTER TABLE `horseinfo240320`
  ADD PRIMARY KEY (`id`),
  ADD KEY `horseid` (`horseid`,`RaceIndex`),
  ADD KEY `horsename` (`horsename`);

--
-- 資料表索引 `horseinfo_hints`
--
ALTER TABLE `horseinfo_hints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`racingdate`,`venue`),
  ADD KEY `racingdate` (`racingdate`,`venue`);

--
-- 資料表索引 `horseinfo_old`
--
ALTER TABLE `horseinfo_old`
  ADD PRIMARY KEY (`id`),
  ADD KEY `horseid` (`horseid`,`RaceIndex`);

--
-- 資料表索引 `horseinfo_old2`
--
ALTER TABLE `horseinfo_old2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `horseid` (`horseid`,`RaceIndex`),
  ADD KEY `horsename` (`horsename`);

--
-- 資料表索引 `horseinfo_summary`
--
ALTER TABLE `horseinfo_summary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `Trainer` (`Trainer`),
  ADD KEY `Jockey` (`Jockey`);

--
-- 資料表索引 `hrp_pick`
--
ALTER TABLE `hrp_pick`
  ADD PRIMARY KEY (`id`),
  ADD KEY `racingdate` (`racingdate`),
  ADD KEY `raceno` (`raceno`),
  ADD KEY `horseno` (`horseno`);

--
-- 資料表索引 `hrp_pick2`
--
ALTER TABLE `hrp_pick2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `racingdate` (`racingdate`),
  ADD KEY `raceno` (`raceno`),
  ADD KEY `racingdate_2` (`racingdate`,`venue`,`raceno`);

--
-- 資料表索引 `hrp_propanalysis`
--
ALTER TABLE `hrp_propanalysis`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `hrp_prophist`
--
ALTER TABLE `hrp_prophist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `racingdate` (`racingdate`),
  ADD KEY `raceno` (`raceno`),
  ADD KEY `horseno` (`horseno`),
  ADD KEY `wpmaxhistory` (`wpmaxhistory`);

--
-- 資料表索引 `hrp_prophist2`
--
ALTER TABLE `hrp_prophist2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `racingdate` (`racingdate`,`venue`,`raceno`,`horseno`,`horsecode`);

--
-- 資料表索引 `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `logs.251129`
--
ALTER TABLE `logs.251129`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `m6_all_data_accuracy`
--
ALTER TABLE `m6_all_data_accuracy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_method` (`method`);

--
-- 資料表索引 `m6_backtest_accuracy`
--
ALTER TABLE `m6_backtest_accuracy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_method_train_col` (`method`,`training_rows`,`target_column`),
  ADD KEY `idx_best` (`method`,`target_column`,`mae`);

--
-- 資料表索引 `m6_blog_posts`
--
ALTER TABLE `m6_blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_draw_year_nos_type` (`draw_year`,`draw_nos`,`post_type`),
  ADD KEY `idx_draw` (`draw_id`),
  ADD KEY `idx_date` (`prediction_date`);

--
-- 資料表索引 `m6_current_year_accuracy`
--
ALTER TABLE `m6_current_year_accuracy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_method` (`method`);

--
-- 資料表索引 `m6_draw_analysis`
--
ALTER TABLE `m6_draw_analysis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_draw_method` (`draw_id`,`method`),
  ADD KEY `idx_accuracy` (`accuracy`),
  ADD KEY `idx_draw` (`draw_id`);

--
-- 資料表索引 `m6_position_predictions`
--
ALTER TABLE `m6_position_predictions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_draw_method` (`target_draw_id`,`method`),
  ADD KEY `idx_method` (`method`);

--
-- 資料表索引 `m6_simulated_accuracy`
--
ALTER TABLE `m6_simulated_accuracy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_method` (`method`);

--
-- 資料表索引 `message_campaigns`
--
ALTER TABLE `message_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_next_run` (`next_run`),
  ADD KEY `idx_schedule_type` (`schedule_type`);

--
-- 資料表索引 `message_groups`
--
ALTER TABLE `message_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_group_name` (`group_name`);

--
-- 資料表索引 `message_group_users`
--
ALTER TABLE `message_group_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_group_user` (`group_id`,`user_id`),
  ADD KEY `idx_group` (`group_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- 資料表索引 `message_logs`
--
ALTER TABLE `message_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_phone` (`phone_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_message_id` (`message_id`);

--
-- 資料表索引 `message_queue`
--
ALTER TABLE `message_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled` (`scheduled_for`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_user` (`user_id`);

--
-- 資料表索引 `message_target_user`
--
ALTER TABLE `message_target_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_phone` (`phone_number`),
  ADD KEY `idx_phone` (`phone_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_opt_in` (`opt_in`),
  ADD KEY `idx_last_message` (`last_message_sent`),
  ADD KEY `idx_opt_in_status` (`opt_in`,`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 資料表索引 `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `mmkb`
--
ALTER TABLE `mmkb`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `oddsDrop`
--
ALTER TABLE `oddsDrop`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`,`horsecode`),
  ADD KEY `date_2` (`date`),
  ADD KEY `idx_horsecode_date` (`horsecode`,`date`),
  ADD KEY `idx_date_venue` (`date`,`venue`),
  ADD KEY `idx_oddsDropValue` (`oddsDropValue`),
  ADD KEY `idx_horsecode` (`horsecode`);

--
-- 資料表索引 `oddshistory`
--
ALTER TABLE `oddshistory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`,`racingdate`,`venue`);

--
-- 資料表索引 `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- 資料表索引 `prop_manual_rule`
--
ALTER TABLE `prop_manual_rule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_code` (`code`),
  ADD KEY `idx_scope_en` (`venue_scope`,`enabled`);

--
-- 資料表索引 `prop_manual_rule_evidence`
--
ALTER TABLE `prop_manual_rule_evidence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_rule_race` (`rule_id`,`racingdate`,`venue`,`raceno`),
  ADD KEY `idx_race` (`racingdate`,`venue`,`raceno`);

--
-- 資料表索引 `prop_manual_rule_stat`
--
ALTER TABLE `prop_manual_rule_stat`
  ADD PRIMARY KEY (`rule_id`,`venue_scope`,`date_from`,`date_to`,`fp_max`);

--
-- 資料表索引 `prop_race_fingerprint`
--
ALTER TABLE `prop_race_fingerprint`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_race` (`racingdate`,`venue`,`raceno`),
  ADD KEY `idx_bucket_date` (`venue_bucket`,`racingdate`);

--
-- 資料表索引 `RaceCard`
--
ALTER TABLE `RaceCard`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barrierday` (`racingdate`),
  ADD KEY `venue` (`venue`),
  ADD KEY `horsename` (`horsename`),
  ADD KEY `idx_racingdate_venue` (`racingdate`,`venue`),
  ADD KEY `racingdate` (`racingdate`);

--
-- 資料表索引 `RaceCard_sub`
--
ALTER TABLE `RaceCard_sub`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barrierday` (`racingdate`),
  ADD KEY `venue` (`venue`),
  ADD KEY `horsename` (`horsename`),
  ADD KEY `idx_racingdate_venue` (`racingdate`,`venue`),
  ADD KEY `racingdate` (`racingdate`);

--
-- 資料表索引 `racedata_stheadline`
--
ALTER TABLE `racedata_stheadline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_race_date_source_time` (`race_date`,`source_time`),
  ADD KEY `idx_racedata` (`venue`,`race_no`,`horse_no`,`sourcetimefull`,`race_date`,`startTime`),
  ADD KEY `idx_starttime` (`startTime`);

--
-- 資料表索引 `racepropresult`
--
ALTER TABLE `racepropresult`
  ADD PRIMARY KEY (`id`),
  ADD KEY `racingdate` (`racingdate`,`venue`,`raceno`,`horseno`);

--
-- 資料表索引 `raceresult_pattern`
--
ALTER TABLE `raceresult_pattern`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `racingrecord`
--
ALTER TABLE `racingrecord`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `racingrecord_summary`
--
ALTER TABLE `racingrecord_summary`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `rsdata`
--
ALTER TABLE `rsdata`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_stripe_customer_id_unique` (`stripe_customer_id`),
  ADD UNIQUE KEY `users_stripe_subscription_id_unique` (`stripe_subscription_id`),
  ADD KEY `users_email_index` (`email`),
  ADD KEY `users_stripe_customer_id_index` (`stripe_customer_id`),
  ADD KEY `users_stripe_subscription_id_index` (`stripe_subscription_id`);

--
-- 資料表索引 `wapi_campaigns`
--
ALTER TABLE `wapi_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wapi_campaigns_uuid_unique` (`uuid`),
  ADD KEY `wapi_campaigns_user_id_status_index` (`user_id`,`status`),
  ADD KEY `wapi_campaigns_scheduled_at_index` (`scheduled_at`),
  ADD KEY `wapi_campaigns_wa_session_id_index` (`wa_session_id`);

--
-- 資料表索引 `wapi_campaign_messages`
--
ALTER TABLE `wapi_campaign_messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wapi_campaign_messages_campaign_id_contact_id_unique` (`campaign_id`,`contact_id`),
  ADD KEY `wapi_campaign_messages_campaign_id_status_index` (`campaign_id`,`status`),
  ADD KEY `wapi_campaign_messages_contact_id_index` (`contact_id`),
  ADD KEY `wapi_campaign_messages_status_index` (`status`);

--
-- 資料表索引 `wapi_contacts`
--
ALTER TABLE `wapi_contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wapi_contacts_user_id_phone_unique` (`user_id`,`phone`),
  ADD UNIQUE KEY `wapi_contacts_uuid_unique` (`uuid`),
  ADD KEY `wapi_contacts_consent_status_index` (`consent_status`);

--
-- 資料表索引 `wapi_message_logs`
--
ALTER TABLE `wapi_message_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wapi_message_logs_campaign_id_foreign` (`campaign_id`),
  ADD KEY `wapi_message_logs_campaign_message_id_foreign` (`campaign_message_id`),
  ADD KEY `wapi_message_logs_wa_session_id_foreign` (`wa_session_id`),
  ADD KEY `wapi_message_logs_user_id_logged_at_index` (`user_id`,`logged_at`),
  ADD KEY `wapi_message_logs_to_number_index` (`to_number`),
  ADD KEY `wapi_message_logs_from_number_index` (`from_number`);

--
-- 資料表索引 `wapi_migrations`
--
ALTER TABLE `wapi_migrations`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wapi_personal_access_tokens`
--
ALTER TABLE `wapi_personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wapi_personal_access_tokens_token_unique` (`token`),
  ADD KEY `wapi_personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- 資料表索引 `wapi_subscriptions`
--
ALTER TABLE `wapi_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wapi_subscriptions_stripe_subscription_id_unique` (`stripe_subscription_id`),
  ADD KEY `wapi_subscriptions_user_id_foreign` (`user_id`),
  ADD KEY `wapi_subscriptions_stripe_subscription_id_index` (`stripe_subscription_id`);

--
-- 資料表索引 `wapi_users`
--
ALTER TABLE `wapi_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wapi_users_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `wapi_users_email_unique` (`email`),
  ADD UNIQUE KEY `wapi_users_stripe_customer_id_unique` (`stripe_customer_id`),
  ADD UNIQUE KEY `wapi_users_stripe_subscription_id_unique` (`stripe_subscription_id`),
  ADD KEY `wapi_users_email_index` (`email`),
  ADD KEY `wapi_users_stripe_customer_id_index` (`stripe_customer_id`),
  ADD KEY `wapi_users_stripe_subscription_id_index` (`stripe_subscription_id`);

--
-- 資料表索引 `wapi_wa_sessions`
--
ALTER TABLE `wapi_wa_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wapi_wa_sessions_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `wapi_wa_sessions_session_id_unique` (`session_id`),
  ADD UNIQUE KEY `wapi_wa_sessions_phone_number_unique` (`phone_number`),
  ADD KEY `wapi_wa_sessions_user_id_index` (`user_id`),
  ADD KEY `wapi_wa_sessions_status_index` (`status`),
  ADD KEY `wapi_wa_sessions_phone_number_index` (`phone_number`);

--
-- 資料表索引 `wa_campaigns`
--
ALTER TABLE `wa_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wa_campaigns_uuid_unique` (`uuid`),
  ADD KEY `wa_campaigns_user_id_status_index` (`user_id`,`status`),
  ADD KEY `wa_campaigns_scheduled_at_index` (`scheduled_at`),
  ADD KEY `wa_campaigns_wa_session_id_index` (`wa_session_id`);

--
-- 資料表索引 `wa_campaign_messages`
--
ALTER TABLE `wa_campaign_messages`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wa_contacts`
--
ALTER TABLE `wa_contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wa_contacts_user_id_phone_unique` (`user_id`,`phone`),
  ADD UNIQUE KEY `wa_contacts_uuid_unique` (`uuid`),
  ADD KEY `wa_contacts_consent_status_index` (`consent_status`);

--
-- 資料表索引 `wa_sessions`
--
ALTER TABLE `wa_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wa_sessions_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `wa_sessions_session_id_unique` (`session_id`),
  ADD UNIQUE KEY `wa_sessions_phone_number_unique` (`phone_number`),
  ADD KEY `wa_sessions_user_id_index` (`user_id`),
  ADD KEY `wa_sessions_status_index` (`status`),
  ADD KEY `wa_sessions_phone_number_index` (`phone_number`);

--
-- 資料表索引 `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`log_type`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_user` (`user_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `00m6`
--
ALTER TABLE `00m6`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `00m6estpos`
--
ALTER TABLE `00m6estpos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `barriercomment`
--
ALTER TABLE `barriercomment`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `barrierday`
--
ALTER TABLE `barrierday`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `barrierresult`
--
ALTER TABLE `barrierresult`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `funds`
--
ALTER TABLE `funds`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `funds_hsi`
--
ALTER TABLE `funds_hsi`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `funds_hsi_indicators`
--
ALTER TABLE `funds_hsi_indicators`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `historyodds`
--
ALTER TABLE `historyodds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `historyodds_pattern`
--
ALTER TABLE `historyodds_pattern`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkjc_marksix_draws`
--
ALTER TABLE `hkjc_marksix_draws`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkms_gann`
--
ALTER TABLE `hkms_gann`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_blog_posts`
--
ALTER TABLE `hkracing_blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_history_queue`
--
ALTER TABLE `hkracing_history_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_horse_fetch_log`
--
ALTER TABLE `hkracing_horse_fetch_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_horse_performances`
--
ALTER TABLE `hkracing_horse_performances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_incremental_queue`
--
ALTER TABLE `hkracing_incremental_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_odds_cwin_selections`
--
ALTER TABLE `hkracing_odds_cwin_selections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_odds_data`
--
ALTER TABLE `hkracing_odds_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_odds_horse_details`
--
ALTER TABLE `hkracing_odds_horse_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_odds_nodes`
--
ALTER TABLE `hkracing_odds_nodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_odds_pools`
--
ALTER TABLE `hkracing_odds_pools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_pending_horses`
--
ALTER TABLE `hkracing_pending_horses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_poster_log`
--
ALTER TABLE `hkracing_poster_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_review_posts`
--
ALTER TABLE `hkracing_review_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_screenshot_tasks`
--
ALTER TABLE `hkracing_screenshot_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hkracing_v2_score`
--
ALTER TABLE `hkracing_v2_score`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `horseinfo`
--
ALTER TABLE `horseinfo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `horseinfo240320`
--
ALTER TABLE `horseinfo240320`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `horseinfo_hints`
--
ALTER TABLE `horseinfo_hints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `horseinfo_old`
--
ALTER TABLE `horseinfo_old`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `horseinfo_old2`
--
ALTER TABLE `horseinfo_old2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `horseinfo_summary`
--
ALTER TABLE `horseinfo_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hrp_pick`
--
ALTER TABLE `hrp_pick`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hrp_pick2`
--
ALTER TABLE `hrp_pick2`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hrp_propanalysis`
--
ALTER TABLE `hrp_propanalysis`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hrp_prophist`
--
ALTER TABLE `hrp_prophist`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hrp_prophist2`
--
ALTER TABLE `hrp_prophist2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `logs.251129`
--
ALTER TABLE `logs.251129`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `m6_all_data_accuracy`
--
ALTER TABLE `m6_all_data_accuracy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `m6_backtest_accuracy`
--
ALTER TABLE `m6_backtest_accuracy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `m6_blog_posts`
--
ALTER TABLE `m6_blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `m6_current_year_accuracy`
--
ALTER TABLE `m6_current_year_accuracy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `m6_draw_analysis`
--
ALTER TABLE `m6_draw_analysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `m6_position_predictions`
--
ALTER TABLE `m6_position_predictions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `m6_simulated_accuracy`
--
ALTER TABLE `m6_simulated_accuracy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `message_campaigns`
--
ALTER TABLE `message_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `message_groups`
--
ALTER TABLE `message_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `message_group_users`
--
ALTER TABLE `message_group_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `message_logs`
--
ALTER TABLE `message_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `message_queue`
--
ALTER TABLE `message_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `message_target_user`
--
ALTER TABLE `message_target_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `mmkb`
--
ALTER TABLE `mmkb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `oddsDrop`
--
ALTER TABLE `oddsDrop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `oddshistory`
--
ALTER TABLE `oddshistory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `prop_manual_rule`
--
ALTER TABLE `prop_manual_rule`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `prop_manual_rule_evidence`
--
ALTER TABLE `prop_manual_rule_evidence`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `prop_race_fingerprint`
--
ALTER TABLE `prop_race_fingerprint`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `RaceCard`
--
ALTER TABLE `RaceCard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `RaceCard_sub`
--
ALTER TABLE `RaceCard_sub`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `racedata_stheadline`
--
ALTER TABLE `racedata_stheadline`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `racepropresult`
--
ALTER TABLE `racepropresult`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `raceresult_pattern`
--
ALTER TABLE `raceresult_pattern`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `racingrecord`
--
ALTER TABLE `racingrecord`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `racingrecord_summary`
--
ALTER TABLE `racingrecord_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `rsdata`
--
ALTER TABLE `rsdata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wapi_campaigns`
--
ALTER TABLE `wapi_campaigns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wapi_campaign_messages`
--
ALTER TABLE `wapi_campaign_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wapi_contacts`
--
ALTER TABLE `wapi_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wapi_message_logs`
--
ALTER TABLE `wapi_message_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wapi_migrations`
--
ALTER TABLE `wapi_migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wapi_personal_access_tokens`
--
ALTER TABLE `wapi_personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wapi_subscriptions`
--
ALTER TABLE `wapi_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wapi_users`
--
ALTER TABLE `wapi_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wapi_wa_sessions`
--
ALTER TABLE `wapi_wa_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wa_campaigns`
--
ALTER TABLE `wa_campaigns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wa_campaign_messages`
--
ALTER TABLE `wa_campaign_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wa_contacts`
--
ALTER TABLE `wa_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wa_sessions`
--
ALTER TABLE `wa_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `hkracing_active_meetings`
--
ALTER TABLE `hkracing_active_meetings`
  ADD CONSTRAINT `hkracing_active_meetings_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `hkracing_meetings` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `hkracing_horse_performances`
--
ALTER TABLE `hkracing_horse_performances`
  ADD CONSTRAINT `hkracing_horse_performances_ibfk_1` FOREIGN KEY (`horse_code`) REFERENCES `hkracing_horses` (`horse_code`) ON DELETE CASCADE;

--
-- 資料表的限制式 `hkracing_odds_cwin_selections`
--
ALTER TABLE `hkracing_odds_cwin_selections`
  ADD CONSTRAINT `hkracing_odds_cwin_selections_ibfk_1` FOREIGN KEY (`race_id`) REFERENCES `hkracing_races` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `hkracing_odds_horse_details`
--
ALTER TABLE `hkracing_odds_horse_details`
  ADD CONSTRAINT `hkracing_odds_horse_details_ibfk_1` FOREIGN KEY (`odds_data_id`) REFERENCES `hkracing_odds_data` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `hkracing_odds_nodes`
--
ALTER TABLE `hkracing_odds_nodes`
  ADD CONSTRAINT `hkracing_odds_nodes_ibfk_1` FOREIGN KEY (`race_id`) REFERENCES `hkracing_races` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `hkracing_odds_pools`
--
ALTER TABLE `hkracing_odds_pools`
  ADD CONSTRAINT `hkracing_odds_pools_ibfk_1` FOREIGN KEY (`race_id`) REFERENCES `hkracing_races` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `hkracing_races`
--
ALTER TABLE `hkracing_races`
  ADD CONSTRAINT `hkracing_races_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `hkracing_meetings` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `hkracing_runners`
--
ALTER TABLE `hkracing_runners`
  ADD CONSTRAINT `hkracing_runners_ibfk_1` FOREIGN KEY (`race_id`) REFERENCES `hkracing_races` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `message_campaigns`
--
ALTER TABLE `message_campaigns`
  ADD CONSTRAINT `message_campaigns_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `message_groups` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `message_group_users`
--
ALTER TABLE `message_group_users`
  ADD CONSTRAINT `message_group_users_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `message_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_group_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `message_target_user` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `message_logs`
--
ALTER TABLE `message_logs`
  ADD CONSTRAINT `message_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `message_target_user` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `message_queue`
--
ALTER TABLE `message_queue`
  ADD CONSTRAINT `message_queue_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `message_target_user` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wapi_campaigns`
--
ALTER TABLE `wapi_campaigns`
  ADD CONSTRAINT `wapi_campaigns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wapi_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wapi_campaigns_wa_session_id_foreign` FOREIGN KEY (`wa_session_id`) REFERENCES `wapi_wa_sessions` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `wapi_campaign_messages`
--
ALTER TABLE `wapi_campaign_messages`
  ADD CONSTRAINT `wapi_campaign_messages_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `wapi_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wapi_campaign_messages_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `wapi_contacts` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wapi_contacts`
--
ALTER TABLE `wapi_contacts`
  ADD CONSTRAINT `wapi_contacts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wapi_users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wapi_message_logs`
--
ALTER TABLE `wapi_message_logs`
  ADD CONSTRAINT `wapi_message_logs_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `wapi_campaigns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `wapi_message_logs_campaign_message_id_foreign` FOREIGN KEY (`campaign_message_id`) REFERENCES `wapi_campaign_messages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `wapi_message_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wapi_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wapi_message_logs_wa_session_id_foreign` FOREIGN KEY (`wa_session_id`) REFERENCES `wapi_wa_sessions` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `wapi_subscriptions`
--
ALTER TABLE `wapi_subscriptions`
  ADD CONSTRAINT `wapi_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wapi_users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wapi_wa_sessions`
--
ALTER TABLE `wapi_wa_sessions`
  ADD CONSTRAINT `wapi_wa_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wapi_users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wa_campaigns`
--
ALTER TABLE `wa_campaigns`
  ADD CONSTRAINT `wa_campaigns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wa_campaigns_wa_session_id_foreign` FOREIGN KEY (`wa_session_id`) REFERENCES `wa_sessions` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `wa_contacts`
--
ALTER TABLE `wa_contacts`
  ADD CONSTRAINT `wa_contacts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wa_sessions`
--
ALTER TABLE `wa_sessions`
  ADD CONSTRAINT `wa_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
