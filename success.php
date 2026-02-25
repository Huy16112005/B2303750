<?php
session_start();
require_once __DIR__ . "/db.php";

$ma_don = (int)($_SESSION["ma_don"] ?? 0);
if ($ma_don <= 0) { header("Location: index.php"); exit; }

$stmt = db()->prepare("SELECT ma_dat_cho, tong_tien, trang_thai FROM don_dat_ve WHERE ma_don=:id");
$stmt->execute([":id"=>$ma_don]);
$don = $stmt->fetch();
if (!$don) { header("Location: index.php"); exit; }
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Thành công</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:#f5f7fb}.card{border-radius:16px}</style>
</head>
<body class="py-5">
<div class="container">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h4 class="text-success">Thanh toán thành công ✅</h4>
      <p class="mb-1">Mã đặt chỗ: <b><?= htmlspecialchars($don["ma_dat_cho"]) ?></b></p>
      <p class="mb-1">Trạng thái: <b><?= htmlspecialchars($don["trang_thai"]) ?></b></p>
      <p class="mb-3">Tổng tiền: <b><?= vnd($don["tong_tien"]) ?></b></p>

      <a class="btn btn-primary" href="index.php">Về trang chủ</a>
    </div>
  </div>
</div>
</body>
</html>