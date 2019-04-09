-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 24, 2017 at 06:20 PM
-- Server version: 5.7.17-0ubuntu0.16.04.1
-- PHP Version: 7.0.8-0ubuntu0.16.04.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `office_desk`
--

--
-- Dumping data for table `meta`
--

INSERT INTO `meta` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(15, 'user_list_perPage', '10', '2017-01-18 08:35:25', '2017-01-19 13:49:39'),
(16, 'attendance_list_perPage', '10', '2017-01-18 08:35:25', '2017-01-19 08:27:52'),
(17, 'leave_list_perPage', '10', '2017-01-18 08:35:25', '2017-01-19 08:27:52');

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `uuid`, `first_name`, `last_name`, `gender`, `email`, `phone`, `role`, `department`, `status`, `password`, `profile_pic`, `email_varified`, `email_varifiyer_token`, `pwd_reset_token`, `date_of_birth`, `joined_date`, `website`, `created_at`, `updated_at`) VALUES
(1, 'eeb7035c-0c9e-48cc-9918-a641c7090a11', 'sharmin', 'shanta', 'male', 'shanta@gmail.com', '01964200363', 1, 'administration', 1, '$2y$10$OfftyW6wxBx/a8FBW2rdO.Bj6S6BCJyursoK7xLSrLjSIe48ltAFa', '', 0, NULL, NULL, '1990-01-02 00:00:00', '2013-01-02 00:00:00', 'http://www.bizuqycyko.info', '2017-01-22 12:29:14', '2017-01-22 12:29:14');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;