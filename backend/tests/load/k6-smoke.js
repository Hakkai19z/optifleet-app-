// Test de charge léger (k6) — mesure ENF01 : temps de réponse API p95 < 800 ms.
//
// Prérequis : l'application doit tourner (docker compose up -d).
// Exécution :  k6 run backend/tests/load/k6-smoke.js
//   variables : BASE_URL (défaut http://localhost:8000)
//
// Le seuil ci-dessous fait échouer le test si le p95 dépasse 800 ms, ce qui
// permet d'instrumenter l'exigence ENF01 au lieu de l'affirmer.

import http from 'k6/http'
import { check, sleep } from 'k6'

export const options = {
  vus: 10,
  duration: '30s',
  thresholds: {
    http_req_duration: ['p(95)<800'], // ENF01
    http_req_failed: ['rate<0.01'],
  },
}

const BASE = __ENV.BASE_URL || 'http://localhost:8000'

export default function () {
  const login = http.post(
    `${BASE}/api/auth/login`,
    JSON.stringify({ email: 'gestionnaire@optifleet.fr', motDePasse: 'Gest@1234' }),
    { headers: { 'Content-Type': 'application/json' } },
  )
  check(login, { 'login 200': (r) => r.status === 200 })

  const token = login.json('token')
  const auth = { headers: { Authorization: `Bearer ${token}` } }

  const flotte = http.get(`${BASE}/api/gestionnaire/vue-flotte`, auth)
  check(flotte, { 'vue-flotte 200': (r) => r.status === 200 })

  const stats = http.get(`${BASE}/api/statistiques`, auth)
  check(stats, { 'statistiques 200': (r) => r.status === 200 })

  sleep(1)
}
