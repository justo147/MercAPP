// tests/prueba_estres/stress_test.js

// Se utiliza k6: una herramienta de código abierto que permite simular
// múltiples usuarios accediendo a tu aplicación web al mismo tiempo.
// Su objetivo es medir cómo responde el servidor bajo carga, detectando
// cuellos de botella antes de que lleguen usuarios reales.

/**
 * @fileoverview STRESS TESTING - Prueba de Estrés | MercApp
 * =========================================================
 * La prueba de estrés lleva el sistema más allá de su capacidad
 * esperada de forma progresiva, con el objetivo de encontrar el
 * punto de ruptura y evaluar cómo se comporta y recupera el servidor
 * ante una demanda superior a la normal.
 *
 * ¿QUÉ SE EVALÚA?
 * ─────────────────────────────────────────────────────────
 * 1. PUNTO DE RUPTURA
 *    A partir de qué número de usuarios el sistema empieza
 *    a degradarse o fallar de forma significativa.
 *
 * 2. DEGRADACIÓN PROGRESIVA
 *    Cómo aumentan los tiempos de respuesta a medida que
 *    crece la carga más allá del límite esperado.
 *
 * 3. TASA DE ERRORES BAJO SOBRECARGA
 *    Porcentaje de peticiones fallidas cuando el sistema
 *    opera por encima de su capacidad normal.
 *
 * 4. CAPACIDAD DE RECUPERACIÓN
 *    Si el servidor es capaz de volver a responder
 *    correctamente tras reducir la carga extrema.
 *
 * UMBRALES DE ÉXITO (thresholds)
 * ─────────────────────────────────────────────────────────
 * - El 95% de las peticiones deben responder en < 5000ms
 * - La tasa de errores debe mantenerse por debajo del 10%
 * - Login:  p95 < 6000ms
 * - Home:   p95 < 4000ms
 * - Perfil: p95 < 5000ms
 *
* FASES DEL TEST
 * ─────────────────────────────────────────────────────────
 * 1. Carga normal:     0   → 100 usuarios en 1 minuto
 * 2. Sobrecarga:       100 → 200 usuarios en 1 minuto
 * 3. Estrés máximo:    200 → 300 usuarios en 1 minuto
 * 4. Bajada gradual:   300 → 0   usuarios en 30 segundos
 *
 * REPORTES GENERADOS
 * ─────────────────────────────────────────────────────────
 * - HTML visual:  docs/tests/stress_test_report.html
 * - JSON en bruto: tests/prueba_estres/stress_test_result.json
 *
 * @module stress_test
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

/**
 * Opciones globales del test de estrés.
 * Máximo reducido a 300 VUs para evitar saturar localhost.
 * Los umbrales son más permisivos que en el test de carga,
 * ya que se espera degradación al superar el límite normal.
 */

export const options = {
    stages: [
        { duration: '1m',  target: 100 }, // Carga normal:   0   → 100 usuarios
        { duration: '1m',  target: 200 }, // Sobrecarga:     100 → 200 usuarios
        { duration: '1m',  target: 300 }, // Estrés máximo:  200 → 300 usuarios
        { duration: '30s', target: 0   }, // Bajada gradual: 300 → 0   usuarios
    ],
    thresholds: {
        http_req_duration: ['p(95)<5000'], // 95% de peticiones < 5s
        error_rate:        ['rate<0.10'],  // Menos del 10% de errores
        login_duration:    ['p(95)<6000'], // Login < 6s en el p95
        home_duration:     ['p(95)<4000'], // Home  < 4s en el p95
        perfil_duration:   ['p(95)<5000'], // Perfil < 5s en el p95
    },
};

// ─────────────────────────────────────────────
// Datos de prueba
// ─────────────────────────────────────────────

/** @type {string} URL base de la aplicación */
const BASE_URL = 'http://localhost/MercApp/public/views';

/** Credenciales de usuario de prueba (deben existir en la BD) */
const USUARIO = {
    email: 'noelia@gmail.com',
    password: '123456789',
};

/** IDs de perfiles a visitar aleatoriamente */
const PERFILES = [2, 3, 4, 5];

// ─────────────────────────────────────────────
// Escenario principal (se ejecuta por cada VU)
// ─────────────────────────────────────────────

/**
 * Función principal ejecutada por cada usuario virtual (VU) en bucle.
 * Simula el flujo: Login → Home → Perfil de usuario.
 * Igual que el test de carga pero bajo condiciones de sobrecarga extrema.
 *
 * @returns {void}
 */
export default function () {

    // ── 1. Login ──────────────────────────────
    group('Login', () => {
        const res = http.post(`${BASE_URL}/auth/login.php`, {
            email: USUARIO.email,
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
            'Login: responde en <6s': (r) => r.timings.duration < 6000,
        });

        sleep(1);
    });

    // ── 2. Home ───────────────────────────────
    group('Home', () => {
        const res = http.get(`${BASE_URL}/home.php`);

        homeTrend.add(res.timings.duration);
        errorRate.add(res.status !== 200);

        check(res, {
            'Home: status 200': (r) => r.status === 200,
            'Home: responde en <4s': (r) => r.timings.duration < 4000,
            'Home: contiene productos': (r) => r.body.includes('Productos') || r.body.includes('producto'),
        });

        sleep(1);
    });

    // ── 3. Perfil de usuario ──────────────────
    group('Perfil', () => {
        const userId = PERFILES[Math.floor(Math.random() * PERFILES.length)];
        const res = http.get(`${BASE_URL}/profile.php?id=${userId}`);

        perfilTrend.add(res.timings.duration);
        errorRate.add(res.status !== 200);

        check(res, {
            'Perfil: status 200': (r) => r.status === 200,
            'Perfil: responde en <5s': (r) => r.timings.duration < 5000,
        });

        sleep(1);
    });
}

// ─────────────────────────────────────────────
// Resumen final — consola + HTML + JSON
// ─────────────────────────────────────────────

/**
 * Genera los reportes al finalizar el test.
 * Produce tres salidas: consola, HTML visual y JSON en bruto.
 *
 * @param {Object} data - Objeto de métricas proporcionado por k6.
 * @returns {{ [filepath: string]: string }} Mapa de archivos a generar.
 */
export function handleSummary(data) {
    const passed = Object.values(data.metrics)
        .filter(m => m.thresholds)
        .every(m => Object.values(m.thresholds).every(t => t.ok));

    console.log('\n========================================');
    console.log('   RESULTADO STRESS TEST - MercApp');
    console.log('========================================');
    console.log(`Estado general:   ${passed ? '✅ PASSED' : '❌ FAILED'}`);
    console.log(`Total requests:   ${data.metrics.http_reqs?.values?.count ?? '-'}`);
    console.log(`Tasa de errores:  ${((data.metrics.error_rate?.values?.rate ?? 0) * 100).toFixed(2)}%`);
    console.log(`Duración media:   ${(data.metrics.http_req_duration?.values?.avg ?? 0).toFixed(2)}ms`);
    console.log(`p95 duración:     ${(data.metrics.http_req_duration?.values['p(95)'] ?? 0).toFixed(2)}ms`);
    console.log('========================================\n');

    return {
        'docs/tests/stress_test_report.html': htmlReport(data, { title: 'Stress Test — MercApp' }),
        'tests/prueba_estres/stress_test_result.json': JSON.stringify(data, null, 2),
    };
}