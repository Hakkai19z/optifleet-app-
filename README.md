# 🚗 OptiFleet — Plateforme de Gestion de Flotte

![CI OptiFleet](https://github.com/Hakkai19z/optifleet-app-/actions/workflows/ci.yml/badge.svg)
![Symfony](https://img.shields.io/badge/Symfony-7-000000?logo=symfony)
![React](https://img.shields.io/badge/React-18-61DAFB?logo=react&logoColor=000)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=fff)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=fff)

OptiFleet est une application web complète de gestion de flotte de véhicules d'entreprise : affectation des véhicules aux conducteurs, suivi des entretiens et du carburant, alertes automatiques sur les échéances (contrôle technique, assurance, révisions), réservations avec détection de conflits, et statistiques de coûts.

Projet fil rouge réalisé dans le cadre du titre **CDA (Concepteur Développeur d'Applications)** — API REST découplée d'une SPA React, avec authentification JWT, contrôle d'accès par rôles et une CI complète (style, analyse statique, tests, build).

## Aperçu

<table>
<tr>
<td><img src="docs/screenshots/login.jpg" alt="Connexion"></td>
<td><img src="docs/screenshots/dashboard.jpg" alt="Tableau de bord"></td>
</tr>
<tr>
<td><img src="docs/screenshots/vue-flotte.jpg" alt="Vue flotte"></td>
<td><img src="docs/screenshots/statistiques.jpg" alt="Statistiques"></td>
</tr>
</table>

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Démarrage rapide](#démarrage-rapide)
- [Comptes de test](#comptes-de-test)
- [Variables d'environnement](#variables-denvironnement)
- [Documentation API](#documentation-api)
- [Tests](#tests)
- [Intégration continue](#intégration-continue)
- [Sécurité](#sécurité)
- [Structure du projet](#structure-du-projet)
- [Déploiement production](#déploiement-production)

## Fonctionnalités

### Espace Administrateur / Gestionnaire
- **Tableau de bord** — indicateurs clés, coûts de maintenance sur 6 mois, répartition de la flotte par statut
- **Flotte** — CRUD complet des véhicules (immatriculation, quota kilométrique, catégorie), vue liste et vue carte avec changement de statut rapide
- **Affectations** — attribution / libération de véhicules aux conducteurs, avec détection automatique des conflits
- **Entretiens** — historique et planification (vidange, révision, contrôle technique, pneus, freins…) avec coûts
- **Alertes** — génération automatique sur les entretiens échus et les documents proches de l'expiration, + signalements remontés par les conducteurs
- **Carburant** — suivi des pleins par véhicule, calcul de consommation moyenne
- **Réservations** — planification en vue tableau ou calendrier mensuel, avec blocage des créneaux qui se chevauchent
- **Documents** — assurance, carte grise, contrôle technique, avec badges d'échéance (vert / orange / rouge)
- **Statistiques** — coûts mensuels (entretien + carburant), consommation moyenne par véhicule, export CSV
- **Administration** — gestion des utilisateurs et des catégories de véhicules

### Espace Conducteur
- **Mon véhicule** — véhicule actuellement affecté, quota kilométrique, historique d'entretiens, localisation
- Déclaration de pleins et consultation de ses propres réservations
- **Signalement de panne** — génère automatiquement une alerte pour le gestionnaire
- Inscription en libre-service (`/register`), avec connexion automatique après création du compte

### Sécurité
- Authentification **JWT (RS256)** avec expiration courte du jeton d'accès (15 min)
- Hiérarchie de rôles `ADMIN > GESTIONNAIRE > CONDUCTEUR` (Symfony Security Voters)
- Rate limiting sur les endpoints de connexion et d'inscription (anti brute-force)
- Un conducteur ne peut déclarer un plein / consulter ses réservations que pour son **propre** véhicule affecté

## Stack technique

| Composant | Technologie |
|-----------|-------------|
| Backend | Symfony 7 + API Platform 3 |
| Base de données | PostgreSQL 16 |
| Auth | JWT RS256 (LexikJWTAuthenticationBundle) |
| Frontend | React 18 + Vite + Tailwind CSS 3 |
| Graphiques | Recharts |
| ORM | Doctrine ORM |
| Tests backend | PHPUnit |
| Tests frontend | Vitest + Testing Library |
| Style / Analyse statique | php-cs-fixer, PHPStan, ESLint |
| Conteneurisation | Docker + Docker Compose |
| CI/CD | GitHub Actions |

## Démarrage rapide

**Prérequis** : Docker ≥ 24.0, Docker Compose ≥ 2.20

```bash
git clone https://github.com/Hakkai19z/optifleet-app-.git
cd optifleet-app-
cp backend/.env.example backend/.env
docker compose up -d
```

Au premier démarrage, initialisez la base de données :

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
docker compose exec app php bin/console lexik:jwt:generate-keypair --overwrite
```

L'application est accessible sur :
- **Frontend** : http://localhost:3000
- **API Backend** : http://localhost:8000/api
- **Documentation API (Swagger)** : http://localhost:8000/api/docs

## Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@optifleet.fr | Admin@1234 |
| Gestionnaire | gestionnaire@optifleet.fr | Gest@1234 |
| Conducteur | conducteur@optifleet.fr | Cond@1234 |

Un nouveau conducteur peut aussi être créé librement via **Créer un compte** sur la page de connexion.

## Variables d'environnement

Copiez `backend/.env.example` vers `backend/.env` et configurez :

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | URL de connexion PostgreSQL |
| `APP_SECRET` | Clé secrète Symfony (32 caractères minimum) |
| `DEFAULT_URI` | URL de base utilisée pour la génération d'URL en CLI |
| `JWT_PASSPHRASE` | Passphrase des clés JWT RS256 |
| `GOOGLE_MAPS_API_KEY` | Clé API Google Geocoding (géolocalisation des véhicules) — voir ci-dessous |
| `MAILER_DSN` | DSN du serveur email (ex : `smtp://user:pass@smtp.gmail.com:587`) |
| `CORS_ALLOW_ORIGIN` | Origines autorisées pour les requêtes cross-origin |

<details>
<summary>Obtenir une clé Google Maps Geocoding (gratuite)</summary>

1. [console.cloud.google.com](https://console.cloud.google.com) → créer un projet
2. **APIs & Services → Library** → activer **Geocoding API**
3. **APIs & Services → Credentials → Create Credentials → API Key**
4. Restreindre la clé à l'API Geocoding uniquement
5. Coller la clé dans `GOOGLE_MAPS_API_KEY`

La carte affichée dans l'application (embed Google Maps) ne nécessite en revanche **aucune clé**.
</details>

## Documentation API

L'API est entièrement documentée et testable via Swagger UI généré par API Platform :

```
http://localhost:8000/api/docs
```

## Tests

```bash
# Tests backend (PHPUnit)
docker compose exec app vendor/bin/phpunit --coverage-text

# Analyse statique (PHPStan)
docker compose exec app vendor/bin/phpstan analyse

# Style de code (php-cs-fixer)
docker compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff

# Tests frontend (Vitest)
docker compose exec front npm run test

# Lint frontend (ESLint)
docker compose exec front npm run lint
```

## Intégration continue

Chaque push sur `main` déclenche deux workflows GitHub Actions :

- **Backend Tests** : installation des dépendances, vérification du style, analyse statique PHPStan, migrations, fixtures, PHPUnit
- **Frontend Tests** : installation, ESLint, Vitest, build de production

## Sécurité

- Mots de passe hachés avec **bcrypt** (coût 12) ; politique de complexité (min. 8 caractères, majuscule + minuscule + chiffre). Le mot de passe n'est **jamais** exposé ni écrit en clair, y compris via l'API (propriété transitoire `plainMotDePasse` + processor de hachage)
- JWT signé **RS256** (clé privée/publique dédiée), TTL de 15 minutes
- Contrôle d'accès par **Security Voters** Symfony (ex. `PleinVoter`) **et sécurité au niveau des champs** : le rôle n'est modifiable que par un administrateur (aucune auto-élévation de privilèges possible)
- **Cloisonnement des données par conducteur** : un conducteur ne voit que ses réservations et les pleins des véhicules qu'il conduit (extensions Doctrine + voters, en collection comme en lecture unitaire)
- **En-têtes de sécurité HTTP** sur toutes les réponses : `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Content-Security-Policy`, et `HSTS` en production
- Rate limiting sur `/api/auth/login` et `/api/auth/register`
- **CORS** restreint : seul `/api` est ouvert, à l'origine définie par `CORS_ALLOW_ORIGIN`
- Conformité **RGPD** : suppression de compte (`DELETE /api/auth/delete-account`) et politique de confidentialité accessible dans l'application
- `.env` exclu du dépôt à tous les niveaux (jamais de secret versionné)

## Structure du projet

```
optifleet-app-/
├── backend/              # API Symfony 7 + API Platform
│   ├── src/
│   │   ├── Controller/    # Endpoints métier (auth, dashboard, gestionnaire, conducteur…)
│   │   ├── Entity/        # Entités Doctrine exposées via API Platform
│   │   ├── Repository/    # Requêtes DQL personnalisées
│   │   ├── Security/      # Voters (contrôle d'accès fin)
│   │   ├── Service/       # Logique métier (alertes, entretiens, géocodage…)
│   │   └── EventListener/ # Écouteurs Doctrine (géocodage auto à la sauvegarde)
│   └── tests/             # PHPUnit (unitaires + fonctionnels)
├── frontend/              # SPA React 18 + Vite
│   └── src/
│       ├── pages/          # Une page par fonctionnalité
│       ├── components/     # Composants UI réutilisables
│       ├── services/       # Appels API (axios)
│       ├── store/          # État global (Zustand)
│       └── hooks/          # Hooks personnalisés
├── docs/screenshots/      # Captures d'écran (README)
├── .github/workflows/     # CI GitHub Actions
├── docker-compose.yml
└── docker-compose.prod.yml
```

## Déploiement production

```bash
cp backend/.env.example backend/.env
# Éditer backend/.env avec les valeurs de production (secrets forts, DSN mail réel…)
docker compose -f docker-compose.prod.yml up -d
```
