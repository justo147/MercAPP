<?php
/**
 * @fileoverview DATABASE PERFORMANCE TEST - Prueba de Base de Datos | MercApp
 * =============================================================================
 * Analiza el archivo slow_queries.log generado por MySQL para identificar
 * las consultas más lentas, su frecuencia de ejecución y posibles mejoras.
 *
 * HERRAMIENTA: MySQL Slow Query Log (MariaDB 10.4.32)
 * UMBRAL:      Queries con tiempo > 0.5ms registradas
 * FUENTE:      C:/xampp/mysql/logs/slow_queries.log
 */

$logFile = 'C:/xampp/mysql/logs/slow_queries.log';
$content = file_get_contents($logFile);
$blocks  = preg_split('/^# Time:/m', $content);

$queries = [];

foreach ($blocks as $block) {
    if (!preg_match('/Query_time:\s+([\d.]+)/', $block, $qt)) continue;

    $queryTime = (float)$qt[1];
    $lockTime  = 0;
    $rowsExam  = 0;
    $rowsSent  = 0;

    if (preg_match('/Lock_time:\s+([\d.]+)/', $block, $lt))   $lockTime = (float)$lt[1];
    if (preg_match('/Rows_examined:\s+(\d+)/', $block, $re))  $rowsExam = (int)$re[1];
    if (preg_match('/Rows_sent:\s+(\d+)/', $block, $rs))      $rowsSent = (int)$rs[1];

    // Extrae la query SQL
    if (preg_match('/SET timestamp=\d+;\s*(.+?)(?=\n#|\z)/s', $block, $sql)) {
        $query = trim($sql[1]);
    } else {
        continue;
    }

    // Agrupa queries iguales
    $key = preg_replace('/\s+/', ' ', strtolower(substr($query, 0, 120)));
    if (!isset($queries[$key])) {
        $queries[$key] = [
            'query'      => $query,
            'count'      => 0,
            'max_time'   => 0,
            'total_time' => 0,
            'lock_time'  => $lockTime,
            'rows_exam'  => $rowsExam,
            'rows_sent'  => $rowsSent,
        ];
    }
    $queries[$key]['count']++;
    $queries[$key]['total_time'] += $queryTime;
    if ($queryTime > $queries[$key]['max_time']) {
        $queries[$key]['max_time'] = $queryTime;
    }
}

// Ordena por tiempo máximo descendente
usort($queries, fn($a, $b) => $b['max_time'] <=> $a['max_time']);

$totalQueries = array_sum(array_column($queries, 'count'));
$totalTime    = array_sum(array_column($queries, 'total_time'));
$slowest      = $queries[0]['max_time'] ?? 0;

// Nivel de criticidad
// Nivel de criticidad
function severity(float $t): array {
    if ($t >= 0.02)  return ['label' => 'CRÍTICO',  'color' => '#ef4444'];
    if ($t >= 0.005) return ['label' => 'LENTO',    'color' => '#f59e0b'];
    return                   ['label' => 'NORMAL',   'color' => '#00c896'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>DB Performance Report — MercApp</title>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg:      #0d0f14;
      --surface: #13161d;
      --border:  #1e2230;
      --accent:  #00e5ff;
      --success: #00c896;
      --warning: #f59e0b;
      --danger:  #ef4444;
      --text:    #e2e8f0;
      --muted:   #718096;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Syne', sans-serif; background: var(--bg); color: var(--text); padding: 2rem; }
    h1 { font-size: 1.8rem; font-weight: 800; color: var(--accent); margin-bottom: 0.25rem; }
    .subtitle { font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; color: var(--muted); margin-bottom: 2rem; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem; text-align: center; }
    .card .val { font-size: 2rem; font-weight: 800; color: var(--accent); font-family: 'JetBrains Mono', monospace; }
    .card .lbl { font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.3rem; }
    table { width: 100%; border-collapse: collapse; background: var(--surface); border-radius: 10px; overflow: hidden; margin-top: 1rem; }
    thead { background: #1a1d28; }
    th { padding: 0.85rem 1rem; text-align: left; font-size: 0.75rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; font-family: 'JetBrains Mono', monospace; }
    td { padding: 0.85rem 1rem; border-top: 1px solid var(--border); font-size: 0.8rem; vertical-align: top; }
    td.mono { font-family: 'JetBrains Mono', monospace; font-size: 0.72rem; color: #a78bfa; max-width: 400px; word-break: break-all; }
    .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.65rem; font-weight: 700; font-family: 'JetBrains Mono', monospace; }
    .section-title { font-size: 1rem; font-weight: 700; color: var(--text); margin: 2rem 0 0.5rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; }
    .footer { text-align: center; color: var(--muted); font-size: 0.7rem; font-family: 'JetBrains Mono', monospace; margin-top: 2rem; }
  </style>
</head>
<body>

  <h1>🗄️ Database Performance Report — MercApp</h1>
  <p class="subtitle">Fuente: slow_queries.log · MariaDB 10.4.32 · <?= date('Y-m-d H:i') ?></p>

  <div class="grid">
    <div class="card">
      <div class="val"><?= $totalQueries ?></div>
      <div class="lbl">Total queries</div>
    </div>
    <div class="card">
      <div class="val"><?= count($queries) ?></div>
      <div class="lbl">Queries únicas</div>
    </div>
    <div class="card">
      <div class="val"><?= number_format($slowest * 1000, 1) ?>ms</div>
      <div class="lbl">Más lenta</div>
    </div>
    <div class="card">
      <div class="val"><?= number_format($totalTime * 1000, 1) ?>ms</div>
      <div class="lbl">Tiempo total</div>
    </div>
  </div>

  <p class="section-title">📋 Queries analizadas</p>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Query</th>
        <th>Veces</th>
        <th>Tiempo máx.</th>
        <th>Tiempo total</th>
        <th>Filas exam.</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($queries as $i => $q):
        $sev = severity($q['max_time']); ?>
      <tr>
        <td style="color:var(--muted); font-family:'JetBrains Mono',monospace"><?= $i + 1 ?></td>
        <td class="mono"><?= htmlspecialchars(substr($q['query'], 0, 200)) ?>...</td>
        <td style="font-family:'JetBrains Mono',monospace; color:var(--accent)"><?= $q['count'] ?>x</td>
        <td style="font-family:'JetBrains Mono',monospace; color:<?= $sev['color'] ?>"><?= number_format($q['max_time'] * 1000, 2) ?>ms</td>
        <td style="font-family:'JetBrains Mono',monospace; color:var(--muted)"><?= number_format($q['total_time'] * 1000, 2) ?>ms</td>
        <td style="font-family:'JetBrains Mono',monospace"><?= $q['rows_exam'] ?></td>
        <td><span class="badge" style="background:<?= $sev['color'] ?>22; color:<?= $sev['color'] ?>; border:1px solid <?= $sev['color'] ?>44"><?= $sev['label'] ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p class="footer">Generado automáticamente · MercApp DB Performance Test · <?= date('d/m/Y H:i') ?></p>

</body>
</html>
```

<!-- Ábrelo en el navegador:
http://localhost/MercApp/tests/prueba_bd/generate_db_report.php -->