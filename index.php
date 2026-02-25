<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
$from = $_GET["from"] ?? "SGN";
$to   = $_GET["to"] ?? "BKK";
$date = $_GET["date"] ?? date("Y-m-d");
$pax  = max(1, (int)($_GET["pax"] ?? 1));
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Trang chủ - Tìm vé</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:#f5f7fb}.card{border-radius:16px}</style>
</head>
<body class="py-5">
<div class="container">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h4 class="mb-3">Tìm kiếm vé máy bay</h4>

      <form method="get" action="flights.php" class="row g-3">
        <div class="col-md-3">
  <label class="form-label">Điểm đi</label>
  <select class="form-select" name="from">
    <option value="SGN" <?= $from=="SGN"?"selected":"" ?>>SGN - TP.HCM</option>
    <option value="HAN" <?= $from=="HAN"?"selected":"" ?>>HAN - Hà Nội</option>
    <option value="DAD" <?= $from=="DAD"?"selected":"" ?>>DAD - Đà Nẵng</option>
    <option value="BKK" <?= $from=="BKK"?"selected":"" ?>>BKK - Bangkok</option>
    <option value="DMK" <?= $from=="DMK"?"selected":"" ?>>DMK - Bangkok (Don Mueang)</option>
  </select>
</div>

<div class="col-md-3">
  <label class="form-label">Điểm đến</label>
  <select class="form-select" name="to">
    <option value="SGN" <?= $to=="SGN"?"selected":"" ?>>SGN - TP.HCM</option>
    <option value="HAN" <?= $to=="HAN"?"selected":"" ?>>HAN - Hà Nội</option>
    <option value="DAD" <?= $to=="DAD"?"selected":"" ?>>DAD - Đà Nẵng</option>
    <option value="BKK" <?= $to=="BKK"?"selected":"" ?>>BKK - Bangkok</option>
    <option value="DMK" <?= $to=="DMK"?"selected":"" ?>>DMK - Bangkok (Don Mueang)</option>
  </select>
</div>
        <div class="col-md-3">
          <label class="form-label">Ngày bay</label>
          <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($date) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Số hành khách</label>
          <input type="number" min="1" class="form-control" name="pax" value="<?= (int)$pax ?>">
        </div>

        <div class="col-12 d-flex justify-content-end">
          <button class="btn btn-primary px-4">Tìm</button>
        </div>
      </form>

      <div class="text-muted small mt-3">
        Nhập đúng mã IATA (ví dụ: SGN, BKK). Dữ liệu lấy từ MySQL.
      </div>
    </div>
  </div>
</div>
</body>
</html>