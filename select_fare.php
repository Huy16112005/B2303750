<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: index.php");
  exit;
}

$ma_chuyen_bay = (int)($_POST["ma_chuyen_bay"] ?? 0);
$ma_loai_ve    = (int)($_POST["ma_loai_ve"] ?? 0);
$date          = trim($_POST["date"] ?? "");
$pax           = max(1, (int)($_POST["pax"] ?? 1));

if ($ma_chuyen_bay <= 0 || $ma_loai_ve <= 0) {
  $_SESSION["flash_error"] = "Thiếu thông tin chuyến bay/loại vé.";
  header("Location: flights.php");
  exit;
}

$_SESSION["selected"] = [
  "ma_chuyen_bay" => $ma_chuyen_bay,
  "ma_loai_ve" => $ma_loai_ve,
  "date" => $date,
  "pax" => $pax
];

header("Location: booking.php");
exit;