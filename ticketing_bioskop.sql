-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 23 Jun 2026 pada 08.32
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ticketing_bioskop`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `activity_log`
--

CREATE TABLE `activity_log` (
  `id_log` int(11) NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `activity_log`
--

INSERT INTO `activity_log` (`id_log`, `id_pengguna`, `username`, `action`, `details`, `is_admin`, `created_at`) VALUES
(1, 5, 'tama', 'Login', 'User login: tama@gmail.com', 0, '2026-06-23 12:00:27'),
(2, 1, 'Irsyad', 'Login', 'Admin login: ahmad@mail.com', 1, '2026-06-23 12:00:54'),
(3, 1, 'Irsyad', 'Login', 'Admin login: ahmad@mail.com', 1, '2026-06-23 12:24:22'),
(4, 1, 'Irsyad', 'Login', 'Admin login: ahmad@mail.com', 1, '2026-06-23 13:16:55'),
(5, 6, 'okita', 'Login', 'User login: okita@gmail.com', 0, '2026-06-23 13:18:33'),
(6, 6, 'okita', 'Pembelian Tiket', 'User membeli tiket jadwal ID 6, kursi: A4, total: Rp 50.000', 0, '2026-06-23 13:19:01'),
(7, 1, 'Irsyad', 'Login', 'Admin login: ahmad@mail.com', 1, '2026-06-23 13:19:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `film`
--

CREATE TABLE `film` (
  `id_film` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `durasi_menit` int(11) DEFAULT NULL,
  `rating_umur` varchar(10) DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `sinopsis` text DEFAULT NULL,
  `status_tayang` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `film`
--

INSERT INTO `film` (`id_film`, `judul`, `genre`, `durasi_menit`, `rating_umur`, `poster`, `sinopsis`, `status_tayang`, `created_at`) VALUES
(1, 'Avengers Endgame', 'Action', 181, '13+', 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=400', NULL, 1, '2026-06-18 04:50:47'),
(2, 'Interstellar', 'Sci-Fi', 169, '13+', 'uploads/posters/poster_6a3769d4d1d88.jpg', '', 1, '2026-06-18 04:50:47'),
(3, 'Inside Out 2', 'Animation', 96, 'SU', 'uploads/posters/poster_6a3765dde1343.jpg', 'Kartun anak anak', 1, '2026-06-18 04:50:47'),
(4, 'Spongbob', 'comedy', 90, '13+', 'uploads/posters/poster_6a376590882f4.jpeg', 'kartun anak anak', 1, '2026-06-21 04:16:16'),
(6, 'Spongbob', 'comedy', 60, 'SU', 'uploads/posters/poster_6a3a24f85727c.jpeg', 'Kartun anak anak', 1, '2026-06-23 06:17:28');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
--

CREATE TABLE `jadwal` (
  `id_jadwal` int(11) NOT NULL,
  `id_film` int(11) DEFAULT NULL,
  `id_studio` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam` time DEFAULT NULL,
  `status` enum('aktif','dibatalkan','selesai') DEFAULT 'aktif',
  `harga` decimal(10,2) NOT NULL DEFAULT 50000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jadwal`
--

INSERT INTO `jadwal` (`id_jadwal`, `id_film`, `id_studio`, `tanggal`, `jam`, `status`, `harga`) VALUES
(4, 4, 1, '2026-06-22', '11:28:00', 'aktif', 50000.00),
(6, 4, 2, '2026-06-24', '13:00:00', 'aktif', 50000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kursi`
--

CREATE TABLE `kursi` (
  `id_kursi` int(11) NOT NULL,
  `id_studio` int(11) DEFAULT NULL,
  `nomor_kursi` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kursi`
--

INSERT INTO `kursi` (`id_kursi`, `id_studio`, `nomor_kursi`) VALUES
(1, 1, 'A1'),
(2, 1, 'A2'),
(3, 1, 'A3'),
(4, 1, 'A4'),
(5, 1, 'A5'),
(6, 1, 'A6'),
(7, 1, 'A7'),
(8, 1, 'A8'),
(9, 1, 'A9'),
(10, 1, 'A10'),
(11, 1, 'B1'),
(12, 1, 'B2'),
(13, 1, 'B3'),
(14, 1, 'B4'),
(15, 1, 'B5'),
(16, 1, 'B6'),
(17, 1, 'B7'),
(18, 1, 'B8'),
(19, 1, 'B9'),
(20, 1, 'B10'),
(21, 1, 'C1'),
(22, 1, 'C2'),
(23, 1, 'C3'),
(24, 1, 'C4'),
(25, 1, 'C5'),
(26, 1, 'C6'),
(27, 1, 'C7'),
(28, 1, 'C8'),
(29, 1, 'C9'),
(30, 1, 'C10'),
(31, 1, 'D1'),
(32, 1, 'D2'),
(33, 1, 'D3'),
(34, 1, 'D4'),
(35, 1, 'D5'),
(36, 1, 'D6'),
(37, 1, 'D7'),
(38, 1, 'D8'),
(39, 1, 'D9'),
(40, 1, 'D10'),
(41, 1, 'E1'),
(42, 1, 'E2'),
(43, 1, 'E3'),
(44, 1, 'E4'),
(45, 1, 'E5'),
(46, 1, 'E6'),
(47, 1, 'E7'),
(48, 1, 'E8'),
(49, 1, 'E9'),
(50, 1, 'E10'),
(51, 1, 'F1'),
(52, 1, 'F2'),
(53, 1, 'F3'),
(54, 1, 'F4'),
(55, 1, 'F5'),
(56, 1, 'F6'),
(57, 1, 'F7'),
(58, 1, 'F8'),
(59, 1, 'F9'),
(60, 1, 'F10'),
(61, 1, 'G1'),
(62, 1, 'G2'),
(63, 1, 'G3'),
(64, 1, 'G4'),
(65, 1, 'G5'),
(66, 1, 'G6'),
(67, 1, 'G7'),
(68, 1, 'G8'),
(69, 1, 'G9'),
(70, 1, 'G10'),
(71, 1, 'H1'),
(72, 1, 'H2'),
(73, 1, 'H3'),
(74, 1, 'H4'),
(75, 1, 'H5'),
(76, 1, 'H6'),
(77, 1, 'H7'),
(78, 1, 'H8'),
(79, 1, 'H9'),
(80, 1, 'H10'),
(81, 2, 'A1'),
(82, 2, 'A2'),
(83, 2, 'A3'),
(84, 2, 'A4'),
(85, 2, 'A5'),
(86, 2, 'A6'),
(87, 2, 'A7'),
(88, 2, 'A8'),
(89, 2, 'A9'),
(90, 2, 'A10'),
(91, 2, 'B1'),
(92, 2, 'B2'),
(93, 2, 'B3'),
(94, 2, 'B4'),
(95, 2, 'B5'),
(96, 2, 'B6'),
(97, 2, 'B7'),
(98, 2, 'B8'),
(99, 2, 'B9'),
(100, 2, 'B10'),
(101, 2, 'C1'),
(102, 2, 'C2'),
(103, 2, 'C3'),
(104, 2, 'C4'),
(105, 2, 'C5'),
(106, 2, 'C6'),
(107, 2, 'C7'),
(108, 2, 'C8'),
(109, 2, 'C9'),
(110, 2, 'C10'),
(111, 2, 'D1'),
(112, 2, 'D2'),
(113, 2, 'D3'),
(114, 2, 'D4'),
(115, 2, 'D5'),
(116, 2, 'D6'),
(117, 2, 'D7'),
(118, 2, 'D8'),
(119, 2, 'D9'),
(120, 2, 'D10'),
(121, 2, 'E1'),
(122, 2, 'E2'),
(123, 2, 'E3'),
(124, 2, 'E4'),
(125, 2, 'E5'),
(126, 2, 'E6'),
(127, 2, 'E7'),
(128, 2, 'E8'),
(129, 2, 'E9'),
(130, 2, 'E10'),
(131, 2, 'F1'),
(132, 2, 'F2'),
(133, 2, 'F3'),
(134, 2, 'F4'),
(135, 2, 'F5'),
(136, 2, 'F6'),
(137, 2, 'F7'),
(138, 2, 'F8'),
(139, 2, 'F9'),
(140, 2, 'F10'),
(141, 2, 'G1'),
(142, 2, 'G2'),
(143, 2, 'G3'),
(144, 2, 'G4'),
(145, 2, 'G5'),
(146, 2, 'G6'),
(147, 2, 'G7'),
(148, 2, 'G8'),
(149, 2, 'G9'),
(150, 2, 'G10'),
(151, 2, 'H1'),
(152, 2, 'H2'),
(153, 2, 'H3'),
(154, 2, 'H4'),
(155, 2, 'H5'),
(156, 2, 'H6'),
(157, 2, 'H7'),
(158, 2, 'H8'),
(159, 2, 'H9'),
(160, 2, 'H10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_pemesanan` int(11) DEFAULT NULL,
  `metode` varchar(30) DEFAULT NULL,
  `status_bayar` varchar(20) DEFAULT NULL,
  `tanggal_bayar` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pembayaran`
--

INSERT INTO `pembayaran` (`id_pembayaran`, `id_pemesanan`, `metode`, `status_bayar`, `tanggal_bayar`) VALUES
(1, 1, 'QRIS', 'Lunas', NULL),
(2, 2, 'Transfer', 'Lunas', NULL),
(3, 3, 'QRIS', 'Lunas', '2026-06-21 12:28:36'),
(4, 4, 'QRIS', 'Lunas', '2026-06-21 21:24:49'),
(5, 5, 'E-Wallet', 'Lunas', '2026-06-22 08:21:43'),
(6, 6, 'Transfer Bank', 'Lunas', '2026-06-23 13:19:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pemesanan`
--

CREATE TABLE `pemesanan` (
  `id_pemesanan` int(11) NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `tanggal_pesan` datetime DEFAULT NULL,
  `total_harga` decimal(10,2) DEFAULT NULL,
  `kode_booking` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pemesanan`
--

INSERT INTO `pemesanan` (`id_pemesanan`, `id_pengguna`, `tanggal_pesan`, `total_harga`, `kode_booking`) VALUES
(1, 1, '2026-06-18 02:27:27', 100000.00, NULL),
(2, 2, '2026-06-18 02:27:27', 50000.00, NULL),
(3, 1, '2026-06-21 12:28:36', 50000.00, 'CT20260621072836330'),
(4, 1, '2026-06-21 21:24:49', 50000.00, 'CT20260621162449637'),
(5, 4, '2026-06-22 08:21:43', 50000.00, 'CT20260622032143915'),
(6, 6, '2026-06-23 13:19:01', 50000.00, 'CT20260623081901226');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL DEFAULT '',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `nama`, `email`, `no_hp`, `password`, `is_admin`, `created_at`) VALUES
(1, 'Irsyad', 'ahmad@mail.com', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '2026-06-21 14:57:39'),
(2, 'Halimah', 'halimah@mail.com', '081299988877', '', 0, '2026-06-21 14:57:39'),
(3, 'Irsyad', 'irsyad@mail.com', '081233344455', '', 0, '2026-06-21 14:57:39'),
(4, 'taka1', 'taka@gmail.com', '08312308748', '$2y$10$u1j6AtLXYmRL4D6w0Ddeeu214cCEjSYPphwt.VqnTXY1F9I2C7Tbu', 0, '2026-06-22 01:20:13'),
(5, 'tama', 'tama@gmail.com', '081234567891', '$2y$10$55.mOthS7P5wgXtV4pUEH.h8BlOB.PotLYPXCbmZaY6K.htWduI0m', 0, '2026-06-23 05:00:13'),
(6, 'okita', 'okita@gmail.com', '083123087311', '$2y$10$leRiHdFCR7fHIt38FmyWUuwR.NnUeEaBM9jX5Mv61.pZ4swV6b9na', 0, '2026-06-23 06:16:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `studio`
--

CREATE TABLE `studio` (
  `id_studio` int(11) NOT NULL,
  `nama_studio` varchar(20) DEFAULT NULL,
  `kapasitas` int(11) DEFAULT NULL,
  `tipe` varchar(30) DEFAULT 'Reguler'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `studio`
--

INSERT INTO `studio` (`id_studio`, `nama_studio`, `kapasitas`, `tipe`) VALUES
(1, 'Studio 1', 80, 'Reguler'),
(2, 'Studio 2', 80, 'Reguler');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tiket`
--

CREATE TABLE `tiket` (
  `id_tiket` int(11) NOT NULL,
  `id_pemesanan` int(11) DEFAULT NULL,
  `id_jadwal` int(11) DEFAULT NULL,
  `id_kursi` int(11) DEFAULT NULL,
  `harga` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tiket`
--

INSERT INTO `tiket` (`id_tiket`, `id_pemesanan`, `id_jadwal`, `id_kursi`, `harga`) VALUES
(1, 3, 4, 1, 50000.00),
(2, 4, 4, 5, 50000.00),
(3, 5, 4, 2, 50000.00),
(4, 6, 6, 84, 50000.00);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indeks untuk tabel `film`
--
ALTER TABLE `film`
  ADD PRIMARY KEY (`id_film`);

--
-- Indeks untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `id_film` (`id_film`),
  ADD KEY `id_studio` (`id_studio`),
  ADD KEY `idx_jadwal_status` (`status`),
  ADD KEY `idx_jadwal_tanggal` (`tanggal`);

--
-- Indeks untuk tabel `kursi`
--
ALTER TABLE `kursi`
  ADD PRIMARY KEY (`id_kursi`),
  ADD KEY `id_studio` (`id_studio`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `id_pemesanan` (`id_pemesanan`);

--
-- Indeks untuk tabel `pemesanan`
--
ALTER TABLE `pemesanan`
  ADD PRIMARY KEY (`id_pemesanan`),
  ADD UNIQUE KEY `kode_booking` (`kode_booking`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indeks untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `studio`
--
ALTER TABLE `studio`
  ADD PRIMARY KEY (`id_studio`);

--
-- Indeks untuk tabel `tiket`
--
ALTER TABLE `tiket`
  ADD PRIMARY KEY (`id_tiket`),
  ADD KEY `id_pemesanan` (`id_pemesanan`),
  ADD KEY `id_jadwal` (`id_jadwal`),
  ADD KEY `id_kursi` (`id_kursi`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `film`
--
ALTER TABLE `film`
  MODIFY `id_film` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `kursi`
--
ALTER TABLE `kursi`
  MODIFY `id_kursi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `pemesanan`
--
ALTER TABLE `pemesanan`
  MODIFY `id_pemesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `studio`
--
ALTER TABLE `studio`
  MODIFY `id_studio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tiket`
--
ALTER TABLE `tiket`
  MODIFY `id_tiket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`id_film`) REFERENCES `film` (`id_film`),
  ADD CONSTRAINT `jadwal_ibfk_2` FOREIGN KEY (`id_studio`) REFERENCES `studio` (`id_studio`);

--
-- Ketidakleluasaan untuk tabel `kursi`
--
ALTER TABLE `kursi`
  ADD CONSTRAINT `kursi_ibfk_1` FOREIGN KEY (`id_studio`) REFERENCES `studio` (`id_studio`);

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_pemesanan`) REFERENCES `pemesanan` (`id_pemesanan`);

--
-- Ketidakleluasaan untuk tabel `pemesanan`
--
ALTER TABLE `pemesanan`
  ADD CONSTRAINT `pemesanan_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Ketidakleluasaan untuk tabel `tiket`
--
ALTER TABLE `tiket`
  ADD CONSTRAINT `tiket_ibfk_1` FOREIGN KEY (`id_pemesanan`) REFERENCES `pemesanan` (`id_pemesanan`),
  ADD CONSTRAINT `tiket_ibfk_2` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal` (`id_jadwal`),
  ADD CONSTRAINT `tiket_ibfk_3` FOREIGN KEY (`id_kursi`) REFERENCES `kursi` (`id_kursi`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
