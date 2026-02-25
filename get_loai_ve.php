<?php
require_once __DIR__ . "/db.php";

$ma_chuyen_bay = (int)($_GET["ma_chuyen_bay"] ?? 0);
if ($ma_chuyen_bay <= 0) {
  http_response_code(400);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode([]);
  exit;
}

$sql = "SELECT ma_loai_ve, ten_loai_ve, gia_ve, hanh_ly_xach_tay_kg, hanh_ly_ky_gui_kg,
               chinh_sach_doi, chinh_sach_hoan, so_cho_con
        FROM loai_ve
        WHERE ma_chuyen_bay = :id
        ORDER BY gia_ve ASC";
$stmt = db()->prepare($sql);
$stmt->execute([":id"=>$ma_chuyen_bay]);

header("Content-Type: application/json; charset=utf-8");
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);