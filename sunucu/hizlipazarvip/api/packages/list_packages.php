<?php
require_once '../../config/db.php';
require_once '../../config/headers.php';

$query = $db->query("SELECT id, name, price, duration_days FROM packages ORDER BY price ASC");
$packages = $query->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "packages" => $packages
]);
