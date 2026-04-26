<?php

require_once '../../../includes/db_connect.php';

$sucursal = (int)$_POST["sucursal"];

$map = [
    1 => 'central',
    2 => 'peten',
    3 => 'xela'
];

$conn =
    connectToDatabase(
        $map[$sucursal]
    );

$clientesDb = [
    1 => 'db_rmym',
    2 => 'db_rmympt',
    3 => 'db_rmymxela'
];

$db =
    $clientesDb[$sucursal];


$sql = "
SELECT
iddepartamento,
nombre
FROM {$db}.adm_departamentopais
ORDER BY nombre
";

$r =
    $conn->query($sql);

$arr = [];

while (
    $row = $r->fetch_assoc()
) {
    $arr[] = $row;
}

echo json_encode($arr);
