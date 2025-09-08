<?php
require_once '../../config/db.php';

$sql = "
SELECT 
  u.id,
  u.name,
  u.email,
  u.phone,
  u.role,
  u.payment_status,
  u.subscription_start,
  u.subscription_end,
  p.name AS package_name,
  DATEDIFF(u.subscription_end, NOW()) AS days_left
FROM users u
LEFT JOIN packages p ON u.package_id = p.id
WHERE u.payment_status = 0 OR u.subscription_end <= NOW()
ORDER BY u.name ASC
";

$stmt = $db->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'users' => $users
]);
?>