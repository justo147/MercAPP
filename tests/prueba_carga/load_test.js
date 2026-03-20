// tests/prueba_carga/load_test.js

// Se utiliza k6: una herramienta de código abierto que permite simular
// múltiples usuarios accediendo a tu aplicación web al mismo tiempo.
// Su objetivo es medir cómo responde el servidor bajo carga, detectando
// cuellos de botella antes de que lleguen usuarios reales.

/**
 * @fileoverview LOAD TESTING - Prueba de Carga | MercApp
 * =========================================================
 * La prueba de carga simula el comportamiento de múltiples usuarios
 * accediendo a la aplicación de forma simultánea en condiciones normales
 * de uso, con el objetivo de evaluar la estabilidad y el rendimiento
 * del sistema bajo una demanda esperada y sostenida.
 *
 * ¿QUÉ SE EVALÚA?
 * ─────────────────────────────────────────────────────────
 * 1. TIEMPO DE RESPUESTA
 *    Cuánto tarda el servidor en responder cada petición.
 *    Se analiza la media, el percentil 95 (p95) y el máximo.
 *
 * 2. TASA DE ERRORES
 *    Porcentaje de peticiones que devuelven un código HTTP
 *    de error (4xx, 5xx) bajo carga sostenida.
 *
 * 3. THROUGHPUT
 *    Número de peticiones por segundo que el servidor
 *    es capaz de procesar sin degradarse.
 *
 * 4. ESTABILIDAD DEL FLUJO DE USUARIO
 *    Se simula el recorrido más habitual de un usuario:
 *      → Login → Home → Perfil de usuario
 *    Comprobando que cada paso responde correctamente
 *    incluso con 200 usuarios concurrentes activos.
 *
 * UMBRALES DE ÉXITO (thresholds)
 * ─────────────────────────────────────────────────────────
 * - El 95% de las peticiones deben responder en < 2000ms
 * - La tasa de errores debe mantenerse por debajo del 5%
 * - Login:  p95 < 3000ms
 * - Home:   p95 < 1500ms
 * - Perfil: p95 < 2000ms
 *
 * FASES DEL TEST
 * ─────────────────────────────────────────────────────────
 * 1. Rampa inicial:    0  → 50  usuarios en 30 segundos
 * 2. Rampa de carga:   50 → 200 usuarios en 1 minuto
 * 3. Carga sostenida:  200 usuarios durante 2 minutos
 * 4. Bajada gradual:   200 → 0  usuarios en 30 segundos
 *
 * @module load_test
 * @tool k6
 * @version 1.0.0
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Librería local de generación de reportes HTML para k6.
import { htmlReport } from '../_lib/k6-reporter.js';

// ─────────────────────────────────────────────
// Métricas personalizadas
// ─────────────────────────────────────────────

/** @type {Rate} Tasa de errores HTTP globales */
const errorRate = new Rate('error_rate');

/** @type {Trend} Tiempo de respuesta del login */
const loginTrend = new Trend('login_duration');

/** @type {Trend} Tiempo de respuesta del home */
const homeTrend = new Trend('home_duration');

/** @type {Trend} Tiempo de respuesta del perfil */
const perfilTrend = new Trend('perfil_duration');

// ─────────────────────────────────────────────
// Configuración del test
// ─────────────────────────────────────────────
export const options = {
    stages: [
        { duration: '30s', target: 50  }, // Rampa: sube a 50 usuarios
        { duration: '1m',  target: 200 }, // Rampa: sube a 200 usuarios
        { duration: '2m',  target: 200 }, // Carga sostenida: 200 usuarios
        { duration: '30s', target: 0   }, // Bajada gradual
    ],
    thresholds: {
        http_req_duration: ['p(95)<2000'],
        error_rate:        ['rate<0.05'],
        login_duration:    ['p(95)<3000'],
        home_duration:     ['p(95)<1500'],
        perfil_duration:   ['p(95)<2000'],
    },
};

// ─────────────────────────────────────────────
// Datos de prueba
// ─────────────────────────────────────────────
const BASE_URL = 'http://localhost/MercApp/public/views';

/** Credenciales de usuario de prueba (deben existir en la BD) */
const USUARIO = {
    email:    'noelia@gmail.com',
    password: '123456789',
};

/** IDs de perfiles a visitar aleatoriamente */
const PERFILES = [2, 3, 4, 5];

// ─────────────────────────────────────────────
// Escenario principal (se ejecuta por cada VU)
// ─────────────────────────────────────────────
export default function () {

    // ── 1. Login ──────────────────────────────
    group('Login', () => {
        const res = http.post(`${BASE_URL}/auth/login.php`, {
            email:    USUARIO.email,
            password: USUARIO.password,
        });

        if (res.status !== 200 && res.status !== 302) {
            console.log(`❌ Login error - Status: ${res.status} - URL: ${res.url}`);
            console.log(`   Body: ${res.body?.substring(0, 200)}`);
        }

        loginTrend.add(res.timings.duration);
        errorRate.add(res.status !== 200 && res.status !== 302);

        check(res, {
            'Login: status 200 o 302': (r) => r.status === 200 || r.status === 302,
            'Login: responde en <3s':  (r) => r.timings.duration < 3000,
        });

        sleep(1);
    });

    // ── 2. Home ───────────────────────────────
    group('Home', () => {
        const res = http.get(`${BASE_URL}/home.php`);

        homeTrend.add(res.timings.duration);
        errorRate.add(res.status !== 200);

        check(res, {
            'Home: status 200':        (r) => r.status === 200,
            'Home: responde en <1.5s': (r) => r.timings.duration < 1500,
            'Home: contiene productos':(r) => r.body.includes('Productos') || r.body.includes('producto'),
        });

        sleep(1);
    });

    // ── 3. Perfil de usuario ──────────────────
    group('Perfil', () => {
        const userId = PERFILES[Math.floor(Math.random() * PERFILES.length)];
        const res    = http.get(`${BASE_URL}/profile.php?id=${userId}`);

        perfilTrend.add(res.timings.duration);
        errorRate.add(res.status !== 200);

        check(res, {
            'Perfil: status 200':      (r) => r.status === 200,
            'Perfil: responde en <2s': (r) => r.timings.duration < 2000,
        });

        sleep(1);
    });
}

// ─────────────────────────────────────────────
// Resumen final — consola + HTML + JSON
// ─────────────────────────────────────────────
export function handleSummary(data) {
    const passed = Object.values(data.metrics)
        .filter(m => m.thresholds)
        .every(m => Object.values(m.thresholds).every(t => t.ok));

    console.log('\n========================================');
    console.log('   RESULTADO LOAD TEST - MercApp');
    console.log('========================================');
    console.log(`Estado general:   ${passed ? '✅ PASSED' : '❌ FAILED'}`);
    console.log(`Total requests:   ${data.metrics.http_reqs?.values?.count ?? '-'}`);
    console.log(`Tasa de errores:  ${((data.metrics.error_rate?.values?.rate ?? 0) * 100).toFixed(2)}%`);
    console.log(`Duración media:   ${(data.metrics.http_req_duration?.values?.avg ?? 0).toFixed(2)}ms`);
    console.log(`p95 duración:     ${(data.metrics.http_req_duration?.values['p(95)'] ?? 0).toFixed(2)}ms`);
    console.log('========================================\n');

    return {
        // Reporte HTML visual con gráficas — para documentación
        'docs/tests/load_test_report.html': htmlReport(data, { title: 'Load Test — MercApp' }),

        // JSON con todas las métricas en bruto
        'tests/prueba_carga/load_test_result.json': JSON.stringify(data, null, 2),
    };
}