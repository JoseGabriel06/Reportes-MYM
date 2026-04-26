<?php

require_once '../../../includes/db_connect.php';

$sucursal = (int)$_POST["sucursal"];

$dep = (int)$_POST["departamento"];


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
id_municipio,
nombre
FROM {$db}.adm_municipio
WHERE id_departamento=?
ORDER BY nombre
";

$stmt =
    $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $dep
);

$stmt->execute();

$res =
    $stmt->get_result();

$data = [];

while (
    $r = $res->fetch_assoc()
) {
    $data[] = $r;
}

echo json_encode($data);
