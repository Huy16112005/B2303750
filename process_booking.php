<?php
session_start();
require_once __DIR__ . "/db.php";

$selected = $_SESSION["selected"] ?? null;
if (!$selected) { header("Location: index.php"); exit; }

$pax = max(1, (int)$selected["pax"]);
$ma_loai_ve = (int)$selected["ma_loai_ve"];
$ngay_bay = $selected["date"] ?? date("Y-m-d");

function is_valid_phone($s) {
  $s = preg_replace('/\s+/', '', $s);
  return preg_match('/^[0-9]{8,15}$/', $s);
}

$errors = [];
$old = [];

// read
$contact_name  = trim($_POST["contact_name"] ?? "");
$contact_email = trim($_POST["contact_email"] ?? "");
$contact_phone = trim($_POST["contact_phone"] ?? "");

$passenger_name  = $_POST["passenger_name"] ?? [];
$passport_no     = $_POST["passport_no"] ?? [];
$passport_expiry = $_POST["passport_expiry"] ?? [];

$old = [
  "contact_name"=>$contact_name,
  "contact_email"=>$contact_email,
  "contact_phone"=>$contact_phone,
  "passenger_name"=>$passenger_name,
  "passport_no"=>$passport_no,
  "passport_expiry"=>$passport_expiry
];

// E1/E3 contact
if ($contact_name === "") $errors["contact_name"] = "Vui lòng nhập họ tên liên hệ.";
if ($contact_email === "") $errors["contact_email"] = "Vui lòng nhập email.";
elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) $errors["contact_email"] = "Email không đúng định dạng.";

if ($contact_phone === "") $errors["contact_phone"] = "Vui lòng nhập số điện thoại.";
elseif (!is_valid_phone($contact_phone)) $errors["contact_phone"] = "SĐT không đúng định dạng (8–15 số).";

// E2 passenger required
for ($i=0; $i<$pax; $i++) {
  $name = trim($passenger_name[$i] ?? "");
  if ($name === "") $errors["passenger_name"][$i] = "Vui lòng nhập họ tên hành khách.";
}

// E5 duplicate passport (optional)
$seen = [];
for ($i=0; $i<$pax; $i++) {
  $pp = strtoupper(trim($passport_no[$i] ?? ""));
  $exp = trim($passport_expiry[$i] ?? "");
  if ($pp !== "") {
    if (isset($seen[$pp])) $errors["passport_no"][$i] = "Số hộ chiếu bị trùng trong đơn đặt vé.";
    $seen[$pp] = true;

    if ($exp !== "" && $exp < $ngay_bay) $errors["passport_expiry"][$i] = "Hộ chiếu đã hết hạn trước ngày bay.";
  }
}

if (!empty($errors)) {
  $_SESSION["form_errors"] = $errors;
  $_SESSION["form_old"] = $old;
  header("Location: booking.php");
  exit;
}

// ====== DB transaction: check + trừ chỗ + tạo đơn + hành khách ======
$pdo = db();
try {
  $pdo->beginTransaction();

  // Lấy giá và thông tin loại vé
  $stmt = $pdo->prepare("SELECT gia_ve, so_cho_con FROM loai_ve WHERE ma_loai_ve = :id FOR UPDATE");
  $stmt->execute([":id"=>$ma_loai_ve]);
  $fare = $stmt->fetch();

  if (!$fare) {
    $pdo->rollBack();
    $_SESSION["form_errors"] = ["general" => "Không tìm thấy loại vé đã chọn."];
    $_SESSION["form_old"] = $old;
    header("Location: booking.php");
    exit;
  }

  // E7 hết chỗ
  if ((int)$fare["so_cho_con"] < $pax) {
    $pdo->rollBack();
    $_SESSION["form_errors"] = ["general" => "Chuyến bay/hạng vé đã hết chỗ. Vui lòng chọn chuyến bay hoặc hạng vé khác."];
    $_SESSION["form_old"] = $old;
    header("Location: booking.php");
    exit;
  }

  // Trừ chỗ (an toàn): update có điều kiện
  $upd = $pdo->prepare("
    UPDATE loai_ve
    SET so_cho_con = so_cho_con - :pax
    WHERE ma_loai_ve = :id AND so_cho_con >= :pax
  ");
  $upd->execute([":pax"=>$pax, ":id"=>$ma_loai_ve]);

  if ($upd->rowCount() === 0) {
    $pdo->rollBack();
    $_SESSION["form_errors"] = ["general" => "Chuyến bay/hạng vé đã hết chỗ. Vui lòng chọn chuyến bay hoặc hạng vé khác."];
    $_SESSION["form_old"] = $old;
    header("Location: booking.php");
    exit;
  }

  $gia_ve = (int)$fare["gia_ve"];
  $tong_tien = $gia_ve * $pax;

  // Tạo mã đặt chỗ
  $ma_dat_cho = "BK" . date("YmdHis") . random_int(100, 999);

  // Insert đơn đặt vé (CHO_THANH_TOAN)
  $ins = $pdo->prepare("
    INSERT INTO don_dat_ve(ma_dat_cho, ma_loai_ve, so_hanh_khach, tong_tien, trang_thai,
                          ho_ten_lien_he, email_lien_he, sdt_lien_he)
    VALUES(:code, :fare, :pax, :total, 'CHO_THANH_TOAN', :name, :email, :phone)
  ");
  $ins->execute([
    ":code"=>$ma_dat_cho,
    ":fare"=>$ma_loai_ve,
    ":pax"=>$pax,
    ":total"=>$tong_tien,
    ":name"=>$contact_name,
    ":email"=>$contact_email,
    ":phone"=>$contact_phone
  ]);

  $ma_don = (int)$pdo->lastInsertId();

  // Insert hành khách
  $insHK = $pdo->prepare("
    INSERT INTO hanh_khach(ma_don, ho_ten, so_ho_chieu, ngay_het_han_ho_chieu)
    VALUES(:ma_don, :ho_ten, :so_ho_chieu, :ngay_hh)
  ");
  for ($i=0; $i<$pax; $i++) {
    $insHK->execute([
      ":ma_don"=>$ma_don,
      ":ho_ten"=>trim($passenger_name[$i] ?? ""),
      ":so_ho_chieu"=>($passport_no[$i] ?? "") !== "" ? strtoupper(trim($passport_no[$i])) : null,
      ":ngay_hh"=>($passport_expiry[$i] ?? "") !== "" ? $passport_expiry[$i] : null
    ]);
  }

  $pdo->commit();

  // Lưu thông tin đơn vào session để payment dùng
  $_SESSION["ma_don"] = $ma_don;
  $_SESSION["ma_dat_cho"] = $ma_dat_cho;

  header("Location: payment.php");
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  $_SESSION["form_errors"] = ["general" => "Hệ thống đang bận, vui lòng thử lại. (" . $e->getMessage() . ")"];
  $_SESSION["form_old"] = $old;
  header("Location: booking.php");
  exit;
}