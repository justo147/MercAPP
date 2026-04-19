<?php
/**
 * Proxy para la API de Nominatim (OpenStreetMap).
 * Normaliza direcciones españolas sin necesitar API key.
 *
 * GET /api/normalize_address.php?q=Calle+Mayor+1+Sevilla
 * Devuelve: JSON array con hasta 5 sugerencias
 */

header('Content-Type: application/json; charset=utf-8');

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

// Construir URL de Nominatim — solo España, formato JSON
$params = http_build_query([
    'q'            => $query,
    'format'       => 'json',
    'countrycodes' => 'es',
    'limit'        => 5,
    'addressdetails' => 1,
]);

$url = 'https://nominatim.openstreetmap.org/search?' . $params;

// Nominatim requiere User-Agent identificativo (ToS)
$opts = [
    'http' => [
        'method'          => 'GET',
        'header'          => "User-Agent: MercApp/1.0 (proyecto-escolar)\r\n",
        'timeout'         => 5,
        'ignore_errors'   => true,
    ],
];

$context  = stream_context_create($opts);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo conectar con el servicio de direcciones']);
    exit;
}

$data = json_decode($response, true);

if (!is_array($data)) {
    echo json_encode([]);
    exit;
}

// Devolver solo los campos que necesita el frontend
$resultado = array_map(function ($item) {
    $addr = $item['address'] ?? [];

    // Construir etiqueta legible: calle, municipio, provincia, CP
    $partes = array_filter([
        $addr['road']         ?? null,
        $addr['house_number'] ?? null,
        $addr['postcode']     ?? null,
        $addr['city']         ?? $addr['town'] ?? $addr['village'] ?? null,
        $addr['county']       ?? $addr['state'] ?? null,
    ]);

    return [
        'display_name' => $item['display_name'],
        'label'        => implode(', ', $partes) ?: $item['display_name'],
        'lat'          => $item['lat'],
        'lon'          => $item['lon'],
    ];
}, $data);

echo json_encode(array_values($resultado));
