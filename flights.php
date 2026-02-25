<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . "/db.php"; 

$from = strtoupper(trim($_GET["from"] ?? "SGN")); //diem di
$to   = strtoupper(trim($_GET["to"] ?? "BKK")); //diem den
$date = $_GET["date"] ?? date("Y-m-d"); //ngay bay
$pax  = max(1, (int)($_GET["pax"] ?? 1)); //so hanh khach

$flash = $_SESSION["flash_error"] ?? null;
unset($_SESSION["flash_error"]);

// Query: danh sách chuyến bay + giá từ + số loại vé
$sql = "
SELECT
  cb.ma_chuyen_bay,
  hh.ten_hang,
  cb.so_hieu_chuyen,
  sbdi.ma_iata AS diem_di,
  sbden.ma_iata AS diem_den,
  cb.ngay_bay,
  cb.gio_cat_canh,
  cb.gio_ha_canh,
  cb.thoi_gian_bay_phut,
  cb.so_diem_dung,
  (
    SELECT MIN(lv.gia_ve)
    FROM loai_ve lv
    WHERE lv.ma_chuyen_bay = cb.ma_chuyen_bay
  ) AS gia_tu,
  (
    SELECT COUNT(*)
    FROM loai_ve lv
    WHERE lv.ma_chuyen_bay = cb.ma_chuyen_bay
  ) AS so_loai_ve
FROM chuyen_bay cb
JOIN hang_hang_khong hh ON hh.ma_hang = cb.ma_hang
JOIN san_bay sbdi ON sbdi.ma_san_bay = cb.san_bay_di
JOIN san_bay sbden ON sbden.ma_san_bay = cb.san_bay_den
WHERE sbdi.ma_iata = :from
  AND sbden.ma_iata = :to
  AND cb.ngay_bay = :date
ORDER BY gia_tu ASC
";
$stmt = db()->prepare($sql); //db() hàm được định nghĩa trong db.php dùng để kết nối và trả về đối tượng PDO(PHP Data Objects), prepare($sql) gửi câu lệnh đến sql để dàn dựng trước
$stmt->execute([":from"=>$from, ":to"=>$to, ":date"=>$date]); //mảng truyền vào khớp các giá trị thực từ người dùng vào các vị trí tương ứng trong câu SQL
$flights = $stmt->fetchAll(); //Mỗi phần tử trong mảng $flights sẽ là một mảng con chứa thông tin của một chuyến bay (như mã chuyến bay, tên hãng, giá vé...)

function phút_thành_giờ($m){
  $h = intdiv((int)$m, 60);
  $mi = (int)$m % 60;
  return $h . "h " . $mi . "m";
}

?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Danh sách vé</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f5f7fb;}
    .card{border-radius:16px;}
    .flight-card{border-radius:14px;}
    .price{font-weight:800;}
    .badge-soft{background:#eef2ff;color:#334155;}
  </style>
</head>
<body class="py-4">
<div class="container">

  <?php if ($flash): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <div class="h5 mb-1"><?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?></div>
          <div class="text-muted">Ngày <?= htmlspecialchars($date) ?> | <?= (int)$pax ?> hành khách</div>
        </div>
        <a class="btn btn-outline-primary" href="index.php">Đổi tìm kiếm</a>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 mb-2">
    <div class="badge badge-soft p-2">Ưu tiên: Bay thẳng (demo)</div>
    <div class="badge badge-soft p-2">Sắp xếp: Giá thấp → cao</div>
  </div>

  <?php if (!count($flights)): ?>
    <div class="alert alert-warning">Không tìm thấy chuyến bay phù hợp.</div>
  <?php endif; ?>

  <?php foreach ($flights as $f): ?>
    <div class="card flight-card shadow-sm mb-3">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-3">
          <div>
            <div class="fw-semibold mb-1"><?= htmlspecialchars($f["ten_hang"]) ?> • <span class="text-muted"><?= htmlspecialchars($f["so_hieu_chuyen"]) ?></span></div>
            <div class="d-flex gap-3 align-items-center flex-wrap">
              <div>
                <div class="fw-semibold"><?= htmlspecialchars(substr($f["gio_cat_canh"],0,5)) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($f["diem_di"]) ?></div>
              </div>
              <div class="text-center">
                <div class="small text-muted"><?= htmlspecialchars(phút_thành_giờ($f["thoi_gian_bay_phut"])) ?></div>
                <div class="small"><?= ((int)$f["so_diem_dung"] === 0) ? "Trực tiếp" : ((int)$f["so_diem_dung"]." quá cảnh") ?></div>
              </div>
              <div>
                <div class="fw-semibold"><?= htmlspecialchars(substr($f["gio_ha_canh"],0,5)) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($f["diem_den"]) ?></div>
              </div>
            </div>
          </div>

          <div class="text-end">
            <div class="text-muted small">Từ</div>
            <div class="price text-danger"><?= vnd($f["gia_tu"]) ?><span class="text-muted fw-normal">/người</span></div>
            <div class="text-muted small"><?= (int)$f["so_loai_ve"] ?> loại vé</div>

            <button
              class="btn btn-primary mt-2"
              data-bs-toggle="modal"
              data-bs-target="#fareModal"
              data-flight-id="<?= (int)$f["ma_chuyen_bay"] ?>"
              data-flight='<?= htmlspecialchars(json_encode([
                "ma_chuyen_bay" => (int)$f["ma_chuyen_bay"],
                "ten_hang" => $f["ten_hang"],
                "so_hieu_chuyen" => $f["so_hieu_chuyen"],
                "diem_di" => $f["diem_di"],
                "diem_den" => $f["diem_den"],
                "gio_cat_canh" => substr($f["gio_cat_canh"],0,5),
                "gio_ha_canh" => substr($f["gio_ha_canh"],0,5),
                "thoi_gian_bay_phut" => (int)$f["thoi_gian_bay_phut"],
                "ngay_bay" => $date,
                "pax" => $pax
              ], JSON_UNESCAPED_UNICODE)) ?>'
            >Chọn</button>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

</div>

<!-- Modal chọn loại vé -->
<div class="modal fade" id="fareModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header">
        <h5 class="modal-title">Chọn loại vé</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="border rounded-3 p-3 mb-3" id="flightSummary"></div>
        <div class="row g-3" id="fareCards"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const fareModal = document.getElementById('fareModal');

function formatVND(n){
  return new Intl.NumberFormat('vi-VN').format(n) + ' VND';
}
function minutesToHM(m){
  const h = Math.floor(m/60);
  const mi = m%60;
  return `${h}h ${mi}m`;
}

fareModal.addEventListener('show.bs.modal', async (event) => {
  const btn = event.relatedTarget;
  const flight = JSON.parse(btn.getAttribute('data-flight'));
  const flightId = btn.getAttribute('data-flight-id');

  document.getElementById('flightSummary').innerHTML = `
    <div class="d-flex flex-wrap justify-content-between gap-2">
      <div>
        <div class="fw-semibold">${flight.ten_hang} • ${flight.so_hieu_chuyen}</div>
        <div class="text-muted small">${flight.diem_di} → ${flight.diem_den} | ${flight.ngay_bay} | ${flight.pax} hành khách</div>
      </div>
      <div class="text-end">
        <div class="fw-semibold">${flight.gio_cat_canh} → ${flight.gio_ha_canh}</div>
        <div class="text-muted small">${minutesToHM(flight.thoi_gian_bay_phut)} • Trực tiếp</div>
      </div>
    </div>
  `;

  const wrap = document.getElementById('fareCards');
  wrap.innerHTML = `<div class="col-12"><div class="alert alert-info">Đang tải loại vé...</div></div>`;

  const res = await fetch(`get_loai_ve.php?ma_chuyen_bay=${encodeURIComponent(flightId)}`);
  const fares = await res.json();

  wrap.innerHTML = '';
  if (!Array.isArray(fares) || fares.length === 0) {
    wrap.innerHTML = `<div class="col-12"><div class="alert alert-warning">Chưa có loại vé cho chuyến này.</div></div>`;
    return;
  }

  fares.forEach(f => {
    const col = document.createElement('div');
    col.className = 'col-12 col-md-6 col-lg-4';
    col.innerHTML = `
      <div class="card h-100 shadow-sm" style="border-radius:16px;">
        <div class="card-body d-flex flex-column">
          <div class="fw-semibold mb-1">${f.ten_loai_ve}</div>
          <div class="fs-5 fw-bold text-primary mb-2">${formatVND(f.gia_ve)}<span class="text-muted fw-normal">/người</span></div>

          <ul class="list-unstyled small mb-3">
            <li>🧳 Xách tay: <b>${f.hanh_ly_xach_tay_kg} kg</b></li>
            <li>🧳 Ký gửi: <b>${f.hanh_ly_ky_gui_kg} kg</b></li>
            <li>🔁 Đổi vé: <b>${f.chinh_sach_doi}</b></li>
            <li>💸 Hoàn vé: <b>${f.chinh_sach_hoan}</b></li>
            <li>🎟️ Còn chỗ: <b>${f.so_cho_con}</b></li>
          </ul>

          <form method="post" action="select_fare.php" class="mt-auto">
            <input type="hidden" name="ma_chuyen_bay" value="${flightId}">
            <input type="hidden" name="ma_loai_ve" value="${f.ma_loai_ve}">
            <input type="hidden" name="date" value="${flight.ngay_bay}">
            <input type="hidden" name="pax" value="${flight.pax}">
            <button class="btn btn-primary w-100" ${Number(f.so_cho_con) <= 0 ? 'disabled' : ''}>Chọn</button>
          </form>
        </div>
      </div>
    `;
    wrap.appendChild(col);
  });
});
</script>
</body>
</html>