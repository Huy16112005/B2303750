<?php
session_start();
require_once __DIR__ . "/db.php";

$ma_don = (int)($_SESSION["ma_don"] ?? 0);
if ($ma_don <= 0) { header("Location: index.php"); exit; }

$pdo = db();

// Lấy đơn
$stmt = $pdo->prepare("SELECT * FROM don_dat_ve WHERE ma_don = :id LIMIT 1");
$stmt->execute([":id"=>$ma_don]);
$don = $stmt->fetch();

if (!$don) { header("Location: index.php"); exit; }

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $phuong_thuc = $_POST["phuong_thuc"] ?? "VI_DIEN_TU";

  $pdo->beginTransaction();
  try {
    // insert thanh toán thành công (demo)
    $ins = $pdo->prepare("
      INSERT INTO thanh_toan(ma_don, phuong_thuc, so_tien, trang_thai)
      VALUES(:ma_don, :pt, :tien, 'THANH_CONG')
    ");
    $ins->execute([
      ":ma_don"=>$ma_don,
      ":pt"=>$phuong_thuc,
      ":tien"=>(int)$don["tong_tien"]
    ]);

    // update trạng thái đơn
    $upd = $pdo->prepare("UPDATE don_dat_ve SET trang_thai='DA_THANH_TOAN' WHERE ma_don=:id");
    $upd->execute([":id"=>$ma_don]);

    $pdo->commit();
    header("Location: success.php");
    exit;
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $err = "Thanh toán thất bại: " . $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Thanh toán</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:#f5f7fb}.card{border-radius:16px}</style>
</head>
<body class="py-4">
<div class="container">

  <?php if (!empty($err)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between gap-2">
        <div>
          <div class="h5 mb-1">Thanh toán</div>
          <div class="text-muted">Mã đặt chỗ: <b><?= htmlspecialchars($don["ma_dat_cho"]) ?></b></div>
          <div class="text-muted small">Trạng thái: <?= htmlspecialchars($don["trang_thai"]) ?></div>
        </div>
        <div class="text-end">
          <div class="fw-bold text-danger"><?= vnd($don["tong_tien"]) ?></div>
          <div class="text-muted small"><?= (int)$don["so_hanh_khach"] ?> hành khách</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <h5>Chọn phương thức thanh toán (demo)</h5>

      <form method="post" class="mt-3">
        <select class="form-select" name="phuong_thuc">
          <option value="VI_DIEN_TU" selected>Ví điện tử</option>
          <option value="THE">Thẻ</option>
          <option value="CHUYEN_KHOAN">Chuyển khoản</option>
          <option value="TIEN_MAT">Tiền mặt</option>
        </select>

        <div class="d-flex justify-content-end mt-3">
          <button class="btn btn-success px-4">Xác nhận thanh toán</button>
        </div>
      </form>
    </div>
  </div>

</div>
</body>
</html>