# Outillage de mesure — performance, accessibilité, sécurité

Ces outils instrumentent les exigences non fonctionnelles qui restaient « non mesurées »
au cahier des charges (ENF01, ENF02, ENF05, ENF06). Ils s'exécutent contre une application
en cours d'exécution ; ils ne sont pas encore branchés à la chaîne d'intégration bloquante,
mais chaque commande est reproductible telle quelle.

## Performance — temps de réponse API (ENF01)

Test de charge léger avec [k6](https://k6.io). Le seuil `p(95)<800` fait échouer le test
si le 95ᵉ centile dépasse 800 ms.

```bash
docker compose up -d
k6 run backend/tests/load/k6-smoke.js
# ou avec une autre cible :
BASE_URL=https://mon-hote k6 run backend/tests/load/k6-smoke.js
```

## Performance web et accessibilité (ENF02, ENF06)

Audit [Lighthouse CI](https://github.com/GoogleChrome/lighthouse-ci). La configuration
`frontend/lighthouserc.json` exige un score d'accessibilité ≥ 0,90 (WCAG) et surveille la
performance.

```bash
cd frontend
npm run build && npm run preview &   # sert le build de production
npx @lhci/cli autorun
```

## Scan de vulnérabilités dynamique (ENF05)

Scan passif/actif avec [OWASP ZAP](https://www.zaproxy.org) en mode baseline, sans
installation (image officielle) :

```bash
docker compose up -d
docker run --rm --network host -t ghcr.io/zaproxy/zaproxy:stable \
  zap-baseline.py -t http://localhost:8000/api/docs -m 5
```

Le rapport liste les alertes par niveau ; le tri par exposition réelle suit la même méthode
que la section 13.3 du dossier (une alerte sur une dépendance de développement n'a pas le
même poids qu'une alerte sur une ressource exposée en production).
