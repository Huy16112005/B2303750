<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . "/db.php";


$selected = $_SESSION["selected"] ?? null;
if (!$selected) { header("Location: index.php"); exit; }

$pax = max(1, (int)$selected["pax"]);

$sql = "
SELECT
  cb.ma_chuyen_bay, hh.ten_hang, cb.so_hieu_chuyen,
  sbdi.ma_iata AS diem_di, sbden.ma_iata AS diem_den,
  cb.ngay_bay, cb.gio_cat_canh, cb.gio_ha_canh, cb.thoi_gian_bay_phut,
  lv.ma_loai_ve, lv.ten_loai_ve, lv.gia_ve, lv.hanh_ly_xach_tay_kg, lv.hanh_ly_ky_gui_kg,
  lv.chinh_sach_doi, lv.chinh_sach_hoan, lv.so_cho_con
FROM loai_ve lv
JOIN chuyen_bay cb ON cb.ma_chuyen_bay = lv.ma_chuyen_bay
JOIN hang_hang_khong hh ON hh.ma_hang = cb.ma_hang
JOIN san_bay sbdi ON sbdi.ma_san_bay = cb.san_bay_di
JOIN san_bay sbden ON sbden.ma_san_bay = cb.san_bay_den
WHERE lv.ma_loai_ve = :ma_loai_ve
LIMIT 1
";
$stmt = db()->prepare($sql);
$stmt->execute([":ma_loai_ve" => (int)$selected["ma_loai_ve"]]);
$info = $stmt->fetch();

if (!$info) {
  $_SESSION["flash_error"] = "Không tìm thấy loại vé đã chọn.";
  header("Location: flights.php");
  exit;
}

$errors = $_SESSION["form_errors"] ?? [];
$old    = $_SESSION["form_old"] ?? [];
unset($_SESSION["form_errors"], $_SESSION["form_old"]);

function phút_thành_giờ($m){
  $h = intdiv((int)$m, 60); $mi = (int)$m % 60;
  return $h . "h " . $mi . "m";
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Đặt vé</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:#f5f7fb}.card{border-radius:16px}</style>
</head>
<body class="py-4">
<div class="container">

  <?php if (!empty($errors["general"])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors["general"]) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between gap-2">
        <div>
          <div class="h5 mb-1"><?= htmlspecialchars($info["diem_di"]) ?> → <?= htmlspecialchars($info["diem_den"]) ?></div>
          <div class="text-muted"><?= htmlspecialchars($info["ngay_bay"]) ?> | <?= (int)$pax ?> hành khách</div>
          <div class="mt-2">
            <span class="badge text-bg-primary"><?= htmlspecialchars($info["ten_loai_ve"]) ?></span>
            <span class="badge text-bg-light border">Ký gửi: <?= (int)$info["hanh_ly_ky_gui_kg"] ?>kg</span>
          </div>
        </div>
        <div class="text-end">
          <div class="fw-semibold"><?= htmlspecialchars(substr($info["gio_cat_canh"],0,5)) ?> → <?= htmlspecialchars(substr($info["gio_ha_canh"],0,5)) ?></div>
          <div class="text-muted small"><?= htmlspecialchars(phút_thành_giờ($info["thoi_gian_bay_phut"])) ?></div>
          <div class="fw-bold text-danger mt-1"><?= vnd($info["gia_ve"]) ?>/người</div>
          <div class="text-muted small">Còn chỗ: <?= (int)$info["so_cho_con"] ?></div>
        </div>
      </div>
    </div>
  </div>

  <form method="post" action="process_booking.php" class="row g-3">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5>Thông tin liên hệ</h5>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Họ tên</label>
              <input class="form-control" name="contact_name" value="<?= htmlspecialchars($old["contact_name"] ?? "") ?>">
              <div class="text-danger small"><?= htmlspecialchars($errors["contact_name"] ?? "") ?></div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Email</label>
              <input class="form-control" name="contact_email" value="<?= htmlspecialchars($old["contact_email"] ?? "") ?>">
              <div class="text-danger small"><?= htmlspecialchars($errors["contact_email"] ?? "") ?></div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Số điện thoại</label>
              <input class="form-control" name="contact_phone" value="<?= htmlspecialchars($old["contact_phone"] ?? "") ?>">
              <div class="text-danger small"><?= htmlspecialchars($errors["contact_phone"] ?? "") ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php for ($i=1; $i<=$pax; $i++): ?>
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5>Hành khách <?= $i ?></h5>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Họ tên</label>
                <input class="form-control" name="passenger_name[]" value="<?= htmlspecialchars(($old["passenger_name"][$i-1] ?? "")) ?>">
                <div class="text-danger small"><?= htmlspecialchars(($errors["passenger_name"][$i-1] ?? "")) ?></div>
              </div>
              <div class="col-md-3">
                <label class="form-label">Số hộ chiếu (tuỳ chọn)</label>
                <input class="form-control" name="passport_no[]" value="<?= htmlspecialchars(($old["passport_no"][$i-1] ?? "")) ?>">
                <div class="text-danger small"><?= htmlspecialchars(($errors["passport_no"][$i-1] ?? "")) ?></div>
              </div>
              <div class="col-md-3">
                <label class="form-label">Hết hạn hộ chiếu (tuỳ chọn)</label>
                <input type="date" class="form-control" name="passport_expiry[]" value="<?= htmlspecialchars(($old["passport_expiry"][$i-1] ?? "")) ?>">
                <div class="text-danger small"><?= htmlspecialchars(($errors["passport_expiry"][$i-1] ?? "")) ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endfor; ?>

    <div class="col-12 d-flex justify-content-between">
      <a class="btn btn-outline-secondary" href="flights.php?from=<?= urlencode($info["diem_di"]) ?>&to=<?= urlencode($info["diem_den"]) ?>&date=<?= urlencode($info["ngay_bay"]) ?>&pax=<?= $pax ?>">Quay lại</a>
      <button class="btn btn-primary px-4">Thanh toán</button>
    </div>
  </form>

</div>
</body>
</html>