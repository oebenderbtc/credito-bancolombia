-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2025 at 04:22 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `solicitudes`
--

-- --------------------------------------------------------

--
-- Table structure for table `solicitudes`
--

CREATE TABLE `solicitudes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'pendiente',
  `request_id` varchar(255) DEFAULT NULL,
  `key` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `solicitudes`
--

INSERT INTO `solicitudes` (`id`, `nombre`, `telefono`, `estado`, `request_id`, `key`) VALUES
(1, 'hola1234', '2580', 'opcion_1', 'req_683812c35a4b2', NULL),
(2, 'hola1234', '2580', 'opcion_1', 'req_68381309b90a3', NULL),
(3, 'HOLA1234', '1234', 'pendiente', 'req_68388103528e5', NULL),
(4, 'Pablo67c', '2534', 'opcion_1', 'req_68388aa1357ac', NULL),
(5, '256845', 'Desconocido', 'pendiente', 'req_68388ab20c986', NULL),
(6, '256845', 'Desconocido', 'pendiente', 'req_68388ab793073', NULL),
(7, 'hola1234', '2580', 'opcion_1', 'req_68388f2b4cd30', NULL),
(8, '813484', 'Desconocido', 'pendiente', 'req_68388f3e90636', NULL),
(9, '11055654M', '5611', 'opcion_1', 'req_683893cc9c5a7', NULL),
(10, '096308', 'Desconocido', 'opcion_1', 'req_68389400a4c3c', NULL),
(11, 'Gayepes15', '1079', 'opcion_1', 'req_683894078557a', NULL),
(12, '169107', 'Desconocido', 'opcion_1', 'req_6838942eb131d', NULL),
(13, '886028', 'Desconocido', 'pendiente', 'req_6838945a4137d', NULL),
(14, 'diegorojasf75', '1031', 'opcion_1', 'req_683894616183f', NULL),
(15, NULL, 'Desconocido', 'opcion_1', 'req_683894b981bb4', NULL),
(16, '846817', 'Desconocido', 'opcion_3', 'req_683894d79f96b', NULL),
(17, '036750', 'Desconocido', 'opcion_3', 'req_683895009e3d9', NULL),
(18, '685700', 'Desconocido', 'opcion_3', 'req_68389578a3d04', NULL),
(19, '685700', 'Desconocido', 'opcion_3', 'req_683895ac62a84', NULL),
(20, '756745', 'Desconocido', 'opcion_3', 'req_683895ce1fba8', NULL),
(21, 'Cascadia278', '4569', 'opcion_1', 'req_683898d4a8d86', NULL),
(22, '747286', 'Desconocido', 'opcion_6', 'req_6838992bc0f87', NULL),
(23, 'Juanmemo70', '2580', 'opcion_1', 'req_68389a8f57219', NULL),
(24, '416350', 'Desconocido', 'opcion_3', 'req_68389ac1cc2cc', NULL),
(25, 'prueba01', '1043', 'opcion_1', 'req_68389f691522e', NULL),
(26, 'Prueba899', '3106', 'opcion_1', 'req_6838a35d4ffeb', NULL),
(27, 'Prueba899', '3488', 'opcion_4', 'req_6838a8e22c521', NULL),
(28, 'Pruwua78', '3048', 'pendiente', 'req_6838a9cce38ac', NULL),
(29, 'Pruwua78', '3048', 'opcion_6', 'req_6838a9cd622b0', NULL),
(30, 'Proeiiw78', '6764', 'opcion_1', 'req_6838aaa13cb2e', NULL),
(31, 'Peueui89', '6468', 'opcion_3', 'req_6838ab03c722d', NULL),
(32, 'Pruebao99', '3480', 'opcion_5', 'req_6838ab736beeb', NULL),
(33, 'Pedrotorresp65', '6534', 'opcion_1', 'req_6838b9270da00', NULL),
(34, 'Prusba89', '3130', 'opcion_1', 'req_6838b9f31126a', NULL),
(35, 'Patoace1962', '1962', 'opcion_1', 'req_6838bd9e9dcf7', NULL),
(36, 'patoace1962', '1962', 'opcion_7', 'req_6838be1551412', NULL),
(37, 'isanpat0423', '1962', 'opcion_7', 'req_6838be3ca52c2', NULL),
(38, 'Patoace1962', '1962', 'opcion_7', 'req_6838be92da372', NULL),
(39, 'Jesuseduardo54', '1791', 'opcion_1', 'req_6838c35fd76cc', NULL),
(40, 'Luisergara08', '2309', 'opcion_1', 'req_6838c613a1079', NULL),
(41, 'pruebab89', '3819', 'pendiente', 'req_6838c8df0348d', NULL),
(42, 'RAFAELENRIQUE20', '2020', 'opcion_1', 'req_6838c932d98a9', NULL),
(43, 'prueba90', '3215', 'opcion_1', 'req_6838ca12946b7', NULL),
(44, 'prueba8998', '8998', 'pendiente', 'req_6838cb10080b1', '2115eff6916c0a0e4fa286d183f4aa18'),
(45, 'cfdff24', '43141413', 'pendiente', '341311', 'cdfffdc'),
(46, 'prueba7888', '3214', 'opcion_1', 'req_6838cdafe00e5', '2df552efeda4cd9988e441367f0271af'),
(47, 'dsa32c43', '3256', 'opcion_1', 'req_6838cdfb4a0f0', 'bb921646fa5d027a114a9a3e4093e925'),
(48, 'prueba7888', '4325', 'opcion_1', 'req_6838cf1beedd7', '1c36b39952fab1ff9a403a955240a034'),
(49, 'prueba01', '1043', 'opcion_1', 'req_6838cf74bce1f', '19ba8bea6ae61847cd4b12c77eda13b6'),
(50, 'fd43vsv54', '3245', 'opcion_1', 'req_6838cffd6c523', '3fdd9af6edb5c9650b34e4ea85003151'),
(51, 'prueba90', '3214', 'opcion_6', 'req_6838d025df07d', 'afe32871ac75323d34885923104cc295'),
(52, 'prueba90', '3214', 'opcion_1', 'req_6838d0d0c6fae', 'cc0727c20b87c210f675b3194965a25c'),
(53, 'pureba91', '3956', 'opcion_55', 'req_6838d158c0ae2', '3831c049b39a945c8973b89f801e8b7b'),
(54, '1eduardogaviria2', '7022', 'opcion_1', 'req_6838d16c237bc', 'e0d3c9fa294fc50cbd6916510597b09c'),
(55, 'prueba90', '9023', 'opcion_4', 'req_6838d21c37872', 'd673f3a7abb3e382b78e35648297a7f8'),
(56, 'JAIMESCOBAR87', '8751', 'opcion_1', 'req_6838d34451ae7', '88f007d5688def73f503d88ca489c536'),
(57, 'JAIMESCOBAR87', '8751', 'pendiente', 'req_6838d37547882', '64e09158cb7312476e726f26f77dca19'),
(58, 'prubab89', '3291', 'opcion_1', 'req_6838d4a195d15', '82106d4ab5345a68d537b5bc44b06320'),
(59, 'Jjsosa26', '8625', 'opcion_1', 'req_6838d4d6482ed', '147484a0de3d76d1ba24b80eb42dc63c'),
(60, 'prueba9090', '0349', 'opcion_2', 'req_6838d59d2e592', 'a079e019d406441a660edffca707fff5'),
(61, 'prueba9090', '0349', 'opcion_1', 'req_6838d6eb225cc', '6d401debab1486473396735d5351af9e'),
(62, 'prueba9090', '0349', 'opcion_1', 'req_6838d757be346', '19dec3c6a66f909858d78322e3e5228c'),
(63, '1309CRISTIAN', '1980', 'opcion_1', 'req_6838d7d3aa76e', 'dc60a199ba1b49c65696ab280adadc8e'),
(64, 'prueba89982', '3214', 'pendiente', 'req_6838da3853d9a', 'f7db6ba26d95eb7b662c1f7cbf6b4e85'),
(65, 'pruedba9090', '0349', 'opcion_1', 'req_6838dac98ba3f', '0759ee0fce96de1b9f853f2f2c927726'),
(66, 'Gordoalf20', '2502', 'opcion_1', 'req_6838da98a6a9b', '00c69fc2abb847d21319b1f4fffca768'),
(67, 'prueba9090', '3425', 'opcion_1', 'req_6838dbee52761', 'f16f5db7b5ba582513bbf5a11c8f4cb5'),
(68, 'priebia23', '3214', 'opcion_4', 'req_6838dd609c199', 'ad1e2db851497a8348923dffe097122a'),
(69, 'lumasior0708', '2903', 'opcion_1', 'req_6838df111b1be', '5530a094e6d4d98bb468cc3d956c37a7'),
(70, 'Marinilla22', '1120', 'opcion_1', 'req_6838df7927a45', 'f43830711cbe58012cba82d0de8d55b7'),
(71, 'Vila1962', '1962', 'opcion_1', 'req_6838dfbf08068', 'beb27a70928102d456ab4f67e44a11ab'),
(72, 'Br41665149', '7430', 'opcion_3', 'req_6838e059943c7', '3d6cf2e62fbf2fbd1ba9eed7acfa684c'),
(73, 'Marinilla22', '1120', 'opcion_1', 'req_6838e0a8ba6b4', '54942ef28156462e1c119ec5bdaaff33'),
(74, 'RafaelQDR27', '4381', 'pendiente', 'req_6838e57e45cca', 'd69425054125b70ff27edf3f12afa7ca'),
(75, 'prueba900', '3242', 'opcion_4', 'req_6838e5b4f095a', '123991e513141aa893dc85111ebdbb10'),
(76, 'prueba8998', '3426', 'pendiente', 'req_6838e68f3a47a', 'b9c7d52c2719b87760ede2df843c8ea9'),
(77, 'Juan45ospina', '2356', 'opcion_5', 'req_6838e7dd10a4c', '520c39c63af0072b7d0bbb4191b72051'),
(78, 'Peoeiih78', '3488', 'pendiente', 'req_6838eca26a0c3', '46636091b36cec7c405a3d205689e57a'),
(79, 'Shamell04', '0927', 'opcion_7', 'req_6838ed2edee84', 'a22f3497b12b98ff527aa6b4d1999826'),
(80, 'Wilmer08moreno', '1536', 'opcion_7', 'req_6838ee22067f7', '3cc989e88d75d4a45f87c15a224b2684'),
(81, 'edgardito66', '1717', 'opcion_1', 'req_6838ee768dfff', '5a1950638d8b8d56cd861427f29299cc'),
(82, 'Wilmer08moreno', '1536', 'opcion_7', 'req_6838eeb872daa', '496e1f2e79bbd8f987b59c2716752c04'),
(83, 'edgardito66', '1717', 'opcion_3', 'req_6838f06ad0be3', '17831ca21bb42e5a3c4cdb639ea82fd1'),
(84, 'FabioNelsonmartine82', '1982', 'opcion_6', 'req_6838f0bf74f60', '93984387680a465671a7ca8262f47d02'),
(85, 'Mariovargas18', '3010', 'pendiente', 'req_6838f08b48e2f', '5fd3955d4cf6703594f9f24cabb8a139'),
(86, 'JUROMO533', '1753', 'pendiente', 'req_6838f0ca5276a', '63f5b24f79d018af11b355b72d330384'),
(87, 'JUROMO53', '1753', 'opcion_1', 'req_6838f0dabe5a6', '6795c22fcd94ab317502aaf2b77c3232'),
(88, 'Carrlosmendezlopez16', '2195', 'opcion_3', 'req_6838f1ac98d78', '8b31fdc34737e93178c6afacecfbb252'),
(89, 'Brunogarcia47', '0081', 'opcion_6', 'req_6838f1b76a4fb', '4f0dfd8bba8511c2285a488b19dff4f3'),
(90, 'Rafaelmena8', '1157', 'opcion_7', 'req_6838f2e707cb5', '6bb961da55aa94e7c0a32b3fa57ed622'),
(91, 'LDLR1963', '0136', 'opcion_8', 'req_6838f2ecf3eac', '684e533bbb63b8c570b20969dfbad7ed'),
(92, 'Rafaelmena8', '1157', 'pendiente', 'req_6838f312a489a', '32060fe125c56b693a10ec5008c03f50'),
(93, 'JUVWNALCAMPO80', '8626', 'opcion_1', 'req_6838f472a8573', '4b39ddd7d60a99a99162f6939fe39e3b'),
(94, 'JUVENALCAMPO80', '8626', 'pendiente', 'req_6838f5bc833b0', '32b5cd68fdaf991fc471040a10614198'),
(95, 'horaciocordoba04', '4326', 'pendiente', 'req_6838f81a9740e', '3097c26883fe99c9efad45d235554429'),
(96, 'hola1245', '2580', 'pendiente', 'req_6838f8cd7b7be', '5d1f9c21d2509f1155c11a12dd9ccd46'),
(97, 'nomina897634', '8536', 'opcion_1', 'req_6838fa62e45e9', 'f00a264c69bb93fd1140dc2f50debe30'),
(98, 'Karlamichell12', '2631', 'opcion_1', 'req_6838fb4a83fc7', '6d3100e52e5e9af3a63bc40cc0fe6b8b'),
(99, '19rodrigo63', '0228', 'opcion_2', 'req_6838fb6307985', '31a2f4eefc3cc23aaa81c1b0654f9aa6'),
(100, 'Juandas79', '4663', 'opcion_2', 'req_6838fc0373094', 'a5eae80affb042d9c765e1705fd300c7'),
(101, 'Prieii89', '3480', 'pendiente', 'req_6838fc7dda016', '1db3315786a68d6ad0e395e3d2424a2e'),
(102, 'Jhonnairo01', '0105', 'opcion_7', 'req_6838fd28b5dbe', '21393ab398c8899d0e6c3711b812d9a9'),
(103, 'mauramairarogers59', '1104', 'opcion_7', 'req_6838fd32194b8', '865eaa482438a2f73407905b6ead8f5a'),
(104, 'Chicha8118', '0107', 'opcion_2', 'req_6838fe14b6ebf', '1aa5029fde2c8b362874f21eea267e9c'),
(105, 'Nelsyalgarin11', '2758', 'opcion_2', 'req_6838fec854ae5', '900f9e7fb6f0f9d4112728f2a9cc91bc'),
(106, 'mauramairarogers59', '1104', 'opcion_2', 'req_6838fecedb099', 'f81e9619b299853cca212fd83a02d4b3'),
(107, 'mauramairarogers59', '1104', 'pendiente', 'req_6838ff6366d6d', '1f748890c05fa0d80c8e2b162b90be65'),
(108, 'Karlamichell12', '2631', 'opcion_2', 'req_6838ffe64edf5', '71b62008462fab6f5e2d5477b14d5dde'),
(109, 'Joher68ve', '3004', 'opcion_55', 'req_683900159826b', '8e05acdba49291c28b0d5987b6e7a910'),
(110, 'Nelsyalgarin11', '2758', 'pendiente', 'req_683900682004e', 'ae0f731dffe813cb5d5df5f0ad6cfbed'),
(111, 'Nelsyalgarin11', '2758', 'pendiente', 'req_683900692f388', 'af8ede11b78cfef11c63ba2fb9f63eab'),
(112, 'Nelsyalgarin11', '2758', 'pendiente', 'req_68390069bea46', '19bed2ebd94e0dabc8d60d9cfb83ded6'),
(113, 'Nelsyalgarin11', '2758', 'pendiente', 'req_6839006a0df8f', 'fc3bfd256ae0c110df466c07b1703113'),
(114, '991234as', '0208', 'pendiente', 'req_683901cd688bf', 'efb42e8124070b6d8345b7e87ea4973b'),
(115, '11055654M', '5611', 'pendiente', 'req_6839040075459', '6499265879b223eac1c5742055c09472'),
(116, 'prueba56', '2154', 'pendiente', 'req_683904255b755', '97dde5e040bdb0fd6367e3307a28f5ec'),
(117, 'emar03vaquero', '1302', 'opcion_2', 'req_68390437cd747', '9d4dd3168c0b75493269d5c664b92fbb'),
(118, 'alcala58', '1023', 'opcion_3', 'req_6839054ea0783', '9568bf62bb95d4f2c5de1b3f6e9319f6'),
(119, 'Nefraca1', '1448', 'opcion_3', 'req_6839072e92865', '9b3f40791b5ccbcbae917ab506e84677'),
(120, 'armando453', '2369', 'opcion_2', 'req_683906a9587c6', '2a7c97033bfa36b426d68666defa5d69'),
(121, 'Pitillo26', '1623', 'pendiente', 'req_683907410727e', '80291b2b2f87f9197fc8e980277a8faf'),
(122, 'Javierguisao1', '1120', 'opcion_2', 'req_6839081ca78b6', '79d1fd46c4037cdd8f58034b0ed2da29'),
(123, 'holaqere2', '2580', 'opcion_2', 'req_68390926e4eac', 'b6c144381f18beae586d05964d13afaa'),
(124, 'hola1234', '2550', 'opcion_2', 'req_68390995cac1a', '96c5a9a2d16d36cbe8836909d1577cd5'),
(125, 'Jameshenao1971', '1379', 'opcion_2', 'req_68390a41c7ad0', 'b7fb0e529729f1650e1cfe2fe433efcc'),
(126, 'OVERALVIS73', '1017', 'pendiente', 'req_68390ba36da07', '50e5857f70ab187b9c5cff7b7a8a8ce4'),
(127, 'denisrosabuelvaspe45', '3624', 'opcion_2', 'req_68390bbdbf42a', 'cd3f23e7434ab26bd215e2a9b5bdac77'),
(128, 'Jimmysanchez72', '4413', 'opcion_2', 'req_68390c6e07906', '5f9dc7f7000f9edce575a448b8719c9d'),
(129, 'Eliecerpadilla1', '1122', 'opcion_2', 'req_68390d8a618cd', '440f02a6397d0eb7ac0180dfe2a174c4'),
(130, 'Mariovargas18', '3010', 'opcion_2', 'req_68390d3cbd6bb', 'd396fe3130cd5edffdcd57f6b03bfa95'),
(131, 'Jeronimo2928', '1997', 'opcion_2', 'req_68390f067be46', '5a2bc17ac0da33759d02df3ba05cd230'),
(132, 'Cobaorozco20', '4508', 'pendiente', 'req_68391092959b4', '8c289ab04d39c646caaba0eb3511172d'),
(133, 'Reinal24', '1985', 'opcion_2', 'req_683910e6d8c5f', '4f52edf4c3b42d8234841e3a6b1db57e'),
(134, 'joficol19', '6084', 'opcion_2', 'req_683911cae17f9', '0e1120c9585b03daa64af6ffa61a2077'),
(135, 'jjch28co', '2010', 'opcion_2', 'req_683912310944c', '19a8d3ebf9cbc4213e1beabdca78afb7'),
(136, 'Luisarrigui33', '2383', 'opcion_2', 'req_6839142aa66b3', 'db0b7aa1d5c9bc2e36a518c38db9e018');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
