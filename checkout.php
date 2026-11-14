<?php
$id = $_GET['id'] ?? '';
$queryString = !empty($id) ? '?id=' . urlencode($id) : '';
header('Location: endereco.php' . $queryString);
exit;

