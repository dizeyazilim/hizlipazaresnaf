<?php
require_once '../../config/db.php';

$user_id = intval($_GET['user_id']);

$sql = "
SELECT 
  u.id,
  u.name,
  u.email,
  u.created_at,
  u.payment_status,
  u.package_id,
  p.name AS package_name,
  p.duration_days,
  -- Bitiş tarihi: ilk ödeme tarihi + paket süresi
  DATE_ADD(u.created_at, INTERVAL p.duration_days DAY) as expire_at,
  -- Kalan gün
  DATEDIFF(DATE_ADD(u.created_at, INTERVAL p.duration_days DAY), NOW()) as days_left
FROM users u
LEFT JOIN packages p ON u.package_id = p.id
WHERE u.id = ?
LIMIT 1
";

$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success'=>false, 'message'=>'Kullanıcı bulunamadı']);
    exit;
}

echo json_encode([
    'success' => true,
    'user' => $user
]);
