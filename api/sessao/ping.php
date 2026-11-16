<?php
require_once __DIR__ . "/../../config/auth.php";

exigirLoginApi();

header("Content-Type: application/json; charset=utf-8");
echo json_encode(["sucesso" => true]);
?>
