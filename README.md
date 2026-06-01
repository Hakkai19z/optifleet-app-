# OptiFleet — Plateforme de Gestion de Flotte

OptiFleet est une application web de gestion de flotte de véhicules d'entreprise. Elle permet de gérer les véhicules, les affectations, les entretiens et les alertes automatiques.

## Prérequis

- Docker >= 24.0
- Docker Compose >= 2.20
- Node.js >= 20 (développement local frontend)
- PHP >= 8.3 (développement local backend)

## Installation rapide

```bash
git clone https://github.com/kahil-mokhtari/optifleet.git
cp backend/.env.example backend/.env
docker-compose up -d
```

L'application sera accessible à :
- **Frontend** : http://localhost:3000
- **API Backend** : http://localhost:8000/api
- **API Documentation** : http://localhost:8000/api/docs

## Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@optifleet.fr | Admin@1234 |
| Gestionnaire | gestionnaire@optifleet.fr | Gest@1234 |
| Conducteur | conducteur@optifleet.fr | Cond@1234 |

## Variables d'environnement obligatoires

Copiez `backend/.env.example` vers `backend/.env` et configurez :

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | URL de connexion PostgreSQL |
| `APP_SECRET` | Clé secrète Symfony (32 chars minimum) |
| `JWT_PASSPHRASE` | Passphrase pour les clés JWT RS256 |
| `GOOGLE_MAPS_API_KEY` | Clé API Google Maps Geocoding |
| `MAILER_DSN` | DSN du serveur email (ex: smtp://user:pass@smtp.gmail.com:587) |

## Lancer les tests

### Tests backend (PHPUnit)
```bash
docker-compose exec app vendor/bin/phpunit --coverage-text
```

### Tests frontend (Vitest)
```bash
docker-compose exec front npm run test
```

## Architecture technique

| Composant | Technologie |
|-----------|-------------|
| Backend | Symfony 7 + API Platform 3 |
| Base de données | PostgreSQL 16 |
| Auth | JWT RS256 (LexikJWTAuthenticationBundle) |
| Frontend | React 18 + Vite + Tailwind CSS 3 |
| ORM | Doctrine ORM |
| Tests backend | PHPUnit |
| Tests frontend | Vitest |
| Conteneurisation | Docker + Docker Compose |
| CI/CD | GitHub Actions |

## Structure du projet

```
optifleet/
├── backend/          # API Symfony 7 + API Platform
├── frontend/         # SPA React 18 + Vite
├── .github/          # Workflows CI/CD
├── docker-compose.yml
└── docker-compose.prod.yml
```

## Déploiement production

```bash
cp backend/.env.example backend/.env
# Éditer backend/.env avec les valeurs de production
docker-compose -f docker-compose.prod.yml up -d
```
