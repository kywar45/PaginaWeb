<?php

require __DIR__ . '/../src/Repositories/BranchRepository.php';
require __DIR__ . '/../src/Services/BranchService.php';

function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE admin_tiendas (id INTEGER PRIMARY KEY, code TEXT, identificador TEXT, nombre TEXT,
    direccion TEXT, estatus INTEGER, contact_name TEXT, phone TEXT, rfc TEXT, horario_atencion TEXT,
    google_maps_url TEXT, store INTEGER)');
$insert = $pdo->prepare('INSERT INTO admin_tiendas VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$insert->execute(array(1, 'CENTRO', 'centro', 'Centro', 'Calle Uno 123', 1, 'Privado', '+52 55 1234 5678', 'RFC-PRIVADO', 'Lunes a sábado', 'https://maps.app.goo.gl/example', 1));
$insert->execute(array(2, null, 'oficina', 'Oficina', 'No pública', 1, null, null, null, null, null, 0));
$insert->execute(array(3, null, 'inactiva', 'Inactiva', 'No pública', 0, null, null, null, null, null, 1));
$insert->execute(array(4, null, 'otra', 'Otra tienda', 'Calle Dos 456', 1, null, null, null, null, null, 1));
$repo = new \App\Repositories\BranchRepository($pdo);
$rows = $repo->listActive();
check(array_map('intval', array_column($rows, 'id')) === array(1, 4), 'Only active store=1 rows are exposed, ordered by ID');
check($rows[0]['telefono'] === '+52 55 1234 5678', 'phone is mapped to the public phone field');
check(!array_key_exists('rfc', $rows[0]) && !array_key_exists('contact_name', $rows[0]), 'Internal store data stays private');
$result = (new \App\Services\BranchService($repo))->listBranches()['sucursales'];
check($result[0]['id'] === 1 && $result[0]['telefono_url'] === 'tel:+525512345678', 'Public contract retains actual store ID and clickable phone');
check($result[0]['google_maps_url'] === 'https://maps.app.goo.gl/example', 'Stored map is retained');
check($result[1]['google_maps_url'] === 'https://www.google.com/maps/search/?api=1&query=Calle%20Dos%20456', 'Missing map uses the address');
check($result[1]['codigo_postal'] === '' && $result[1]['latitud'] === null && $result[1]['longitud'] === null, 'Missing fields are not invented');
check($result[1]['telefono'] === null && $result[1]['horario_atencion'] === null, 'Optional fields remain empty');
echo "Store repository checks passed: admin_tiendas filtering, public fields, contact mapping, real IDs and map fallback.\n";
