-- phpMyAdmin SQL Dump
-- version 4.6.6deb4+deb9u2
-- https://www.phpmyadmin.net/
--
-- Počítač: localhost
-- Vytvořeno: Pon 07. čen 2021, 12:25
-- Verze serveru: 5.5.60-0+deb8u1
-- Verze PHP: 5.6.33-0+deb8u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Databáze: `ups`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `notify`
--

CREATE TABLE `notify` (
  `name` varchar(20) COLLATE utf8_czech_ci NOT NULL COMMENT 'Jméno',
  `email` varchar(100) COLLATE utf8_czech_ci NOT NULL COMMENT 'Email',
  `oneOnBatt` tinyint(1) NOT NULL COMMENT 'Každou 1 UPS',
  `allOnBatt` tinyint(1) NOT NULL COMMENT 'Všechny UPS (elektrika)',
  `coment` varchar(100) COLLATE utf8_czech_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


-- --------------------------------------------------------

--
-- Struktura tabulky `settings`
--

CREATE TABLE `settings` (
  `name` varchar(25) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL,
  `value` varchar(100) CHARACTER SET utf8 COLLATE utf8_czech_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktura tabulky `status`
--

CREATE TABLE `status` (
  `ip` varchar(15) COLLATE utf8_czech_ci NOT NULL,
  `status` varchar(15) COLLATE utf8_czech_ci NOT NULL,
  `lastChange` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

--
-- Vypisuji data pro tabulku `status`
--

INSERT INTO `status` (`ip`, `status`, `lastChange`) VALUES
('0.0.0.0', 'ONLINE', '2020-12-15 07:21:01');

--
-- Klíče pro exportované tabulky
--

--
-- Klíče pro tabulku `notify`
--
ALTER TABLE `notify`
  ADD UNIQUE KEY `name` (`name`);

--
-- Klíče pro tabulku `settings`
--
ALTER TABLE `settings`
  ADD UNIQUE KEY `name` (`name`);

--
-- Klíče pro tabulku `status`
--
ALTER TABLE `status`
  ADD UNIQUE KEY `ip` (`ip`);
