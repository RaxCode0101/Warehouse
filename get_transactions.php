<?php
header('Content-Type: application/json');
include "koneksi.php";

$q = $conn->query("SELECT id, invoice, item_name, qty, type, date FROM transactions ORDER BY id DESC");

$data = [];
while ($row = $q->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>