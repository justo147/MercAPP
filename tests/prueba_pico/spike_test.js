// tests/prueba_pico/spike_test.js

// Se utiliza k6: una herramienta de código abierto que permite simular
// múltiples usuarios accediendo a tu aplicación web al mismo tiempo.
// Su objetivo es medir cómo responde el servidor bajo carga, detectando
// cuellos de botella antes de que lleguen usuarios reales.

/**
 * @fileoverview SPIKE TESTING - Prueba de Pico | MercApp
 * =========================================================
 * La prueba de pico simula un aumento repentino y brutal de usuarios
 * en un intervalo de tiempo muy corto, como el que podría producirse
 * ante una campaña viral, un anuncio o un evento inesperado.
 * El objetivo es evaluar cómo reacciona el sistema ante picos de
 * tráfico imprevistos y si es capaz de recuperarse tras el pico.
 *
 * ¿QUÉ SE EVALÚA?
 * ─────────────────────────────────────────────────────────
 * 1. REACCIÓN ANTE PICOS REPENTINOS
 *    Cómo responde el servidor cuando de golpe recibe
 *    una cantidad masiva de usuarios en pocos segundos.
 *
 * 2. TIEMPO DE RESPUESTA EN EL PICO
 *    Si los tiempos se disparan o se mantienen aceptables
 *    durante el momento de máxima concurrencia.
 *
 * 3. TASA DE ERRORES EN EL PICO
 *    Cuántas peticiones fallan en el momento del pico
 *    y si el sistema empieza a rechazar conexiones.
 *
 * 4. RECUPERACIÓN POST-PICO
 *    Si el servidor vuelve a responder con normalidad
 *    una vez que el pico de tráfico desaparece.
 *
 * UMBRALES DE ÉXITO (thresholds)
 * ─────────────────────────────────────────────────────────
 * - El 95% de las peticiones deben responder en < 8000ms
 * - La tasa de errores debe mantenerse por debajo del 15%
 * - Login:  p95 < 9000ms
 * - Home:   p95 < 7000ms
 * - Perfil: p95 < 8000ms
 *
 * FASES DEL TEST
 * ─────────────────────────────────────────────────────────
 * 1. Carga base:       0   → 10  usuarios en 10 segundos
 * 2. Pico repentino:   10  → 100 usuarios en 5 segundos
 * 3. Pico sostenido:   100 usuarios durante 10 segundos
 * 4. Bajada brusca:    100 → 10  usuarios en 5 segundos
 * 5. Recuperación:     10  usuarios durante 10 segundos
 * 6. Fin:              10  → 0   usuarios en 5 segundos
 *
 * REPORTES GENERADOS
 * ─────────────────────────────────────────────────────────
 * - HTML visual:   docs/tests/spike_test_report.html
 * - JSON en bruto: tests/prueba_pico/spike_test_result.json
 *
 * @module spike_test
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
 * Opciones globales del test de pico.
 * Los umbrales son más permisivos que en los tests anteriores
 * ya que el pico repentino puede causar degradación temporal.
 */
export const options = {
    stages: [
        { duration: '10s', target: 10 }, // Carga base:      0   → 10  usuarios
        { duration: '5s', target: 100 }, // Pico repentino:  10  → 100 usuarios
        { duration: '10s', target: 100 }, // Pico sostenido:  100 usuarios
        { duration: '5s', target: 10 }, // Bajada brusca:   100 → 10  usuarios
        { duration: '10s', target: 10 }, // Recuperación:    10  usuarios
        { duration: '5s', target: 0 }, // Fin:             10  → 0   usuarios
    ],
    thresholds: {
        http_req_duration: ['p(95)<8000'], // 95% de peticiones < 8s
        error_rate: ['rate<0.15'],  // Menos del 15% de errores
        login_duration: ['p(95)<9000'], // Login < 9s en el p95
        home_duration: ['p(95)<7000'], // Home  < 7s en el p95
        perfil_duration: ['p(95)<8000'], // Perfil < 8s en el p95
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
 * El patrón de carga es en pico: sube de golpe y baja de golpe.
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
            'Login: responde en <9s': (r) => r.timings.duration < 9000,
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
            'Home: responde en <7s': (r) => r.timings.duration < 7000,
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
            'Perfil: responde en <8s': (r) => r.timings.duration < 8000,
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
    console.log('   RESULTADO SPIKE TEST - MercApp');
    console.log('========================================');
    console.log(`Estado general:   ${passed ? '✅ PASSED' : '❌ FAILED'}`);
    console.log(`Total requests:   ${data.metrics.http_reqs?.values?.count ?? '-'}`);
    console.log(`Tasa de errores:  ${((data.metrics.error_rate?.values?.rate ?? 0) * 100).toFixed(2)}%`);
    console.log(`Duración media:   ${(data.metrics.http_req_duration?.values?.avg ?? 0).toFixed(2)}ms`);
    console.log(`p95 duración:     ${(data.metrics.http_req_duration?.values['p(95)'] ?? 0).toFixed(2)}ms`);
    console.log('========================================\n');

    return {
        'docs/tests/spike_test_report.html': htmlReport(data, { title: 'Spike Test — MercApp' }),
        'tests/prueba_pico/spike_test_result.json': JSON.stringify(data, null, 2),
    };
}