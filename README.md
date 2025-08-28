# Système de Consultations Médicales

Un système complet de gestion des consultations médicales composé d'une API backend Laravel et d'une interface frontend Next.js.

## 📋 Table des matières

- [Architecture](#architecture)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [API Documentation](#api-documentation)
- [Déploiement](#déploiement)
- [Contribution](#contribution)

## 🏗️ Architecture

Le projet est divisé en deux parties principales :

- **Backend** : API REST développée avec Laravel
- **Frontend** : Interface utilisateur développée avec Next.js

```
├── backend/          # API Laravel
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── ...
└── frontend/         # Application Next.js
    ├── pages/
    ├── components/
    ├── styles/
    └── ...
```

## 🔧 Prérequis

### Backend (Laravel)
- PHP >= 8.1
- Composer
- MySQL/PostgreSQL
- Node.js (pour Laravel Mix si utilisé)

### Frontend (Next.js)
- Node.js >= 16.0
- npm ou yarn

## 🚀 Installation

### Backend Laravel

1. **Cloner le repository et naviguer vers le dossier backend**
   ```bash
   cd backend
   ```

2. **Installer les dépendances PHP**
   ```bash
   composer install
   ```

3. **Configurer l'environnement**
   ```bash
   cp .env.example .env
   ```
   Modifier le fichier `.env` avec vos configurations de base de données.

4. **Générer la clé d'application**
   ```bash
   php artisan key:generate
   ```

5. **Exécuter les migrations et seeders**
   ```bash
   php artisan migrate --seed
   ```

6. **Démarrer le serveur de développement**
   ```bash
   php artisan serve --port=8001
   ```

L'API sera accessible sur `http://localhost:8001`

### Frontend Next.js

1. **Naviguer vers le dossier frontend**
   ```bash
   cd frontend
   ```

2. **Installer les dépendances**
   ```bash
   npm install
   ```

3. **Configurer l'environnement**
   ```bash
   cp .env.local.example .env.local
   ```
   Modifier le fichier `.env.local` avec l'URL de votre API backend.

4. **Démarrer en mode développement**
   ```bash
   npm run dev
   ```

L'application sera accessible sur `http://localhost:3000`

## ⚙️ Configuration

### Variables d'environnement Backend (.env)
```env
APP_NAME="Consultations Médicales"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8001

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medical_consultations
DB_USERNAME=root
DB_PASSWORD=

# Autres configurations...
```

### Variables d'environnement Frontend (.env.local)
```env
NEXT_PUBLIC_API_URL=http://localhost:8001/api
NEXT_PUBLIC_APP_NAME=Consultations Médicales
```

## 🎯 Utilisation

### Commandes Backend

#### Développement
```bash
# Démarrer le serveur
php artisan serve --port=8001

# Migrations
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

#### Production
```bash
# Optimisations
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### Commandes Frontend

#### Développement
```bash
# Démarrer en mode développement
npm run dev

# Linter et formatage
npm run lint
npm run lint:fix
```

#### Production
```bash
# Build pour production
npm run build

# Démarrer en production
npm start

# Analyser le bundle
npm run analyze
```

## 📚 API Documentation

### Endpoints principaux

#### Authentification
- `POST /api/login` - Connexion
- `POST /api/register` - Inscription
- `POST /api/logout` - Déconnexion

#### Consultations
- `GET /api/consultations` - Liste des consultations
- `POST /api/consultations` - Créer une consultation
- `GET /api/consultations/{id}` - Détails d'une consultation
- `PUT /api/consultations/{id}` - Modifier une consultation
- `DELETE /api/consultations/{id}` - Supprimer une consultation

#### Patients
- `GET /api/patients` - Liste des patients
- `POST /api/patients` - Créer un patient
- `GET /api/patients/{id}` - Détails d'un patient

#### Médecins
- `GET /api/doctors` - Liste des médecins
- `POST /api/doctors` - Créer un médecin

## 📁 Structure du projet

### Backend
```
backend/
├── app/
│   ├── Http/Controllers/    # Contrôleurs
│   ├── Models/             # Modèles Eloquent
│   ├── Middleware/         # Middlewares
│   └── Requests/           # Form Requests
├── database/
│   ├── migrations/         # Migrations
│   └── seeders/           # Seeders
└── routes/
    └── api.php            # Routes API
```

### Frontend
```
frontend/
├── pages/                 # Pages Next.js
├── components/           # Composants réutilisables
├── hooks/               # Hooks personnalisés
├── services/            # Services API
├── styles/              # Styles CSS
└── utils/               # Utilitaires
```

## 🚀 Déploiement

### Backend (Laravel)
1. Configurez votre serveur web (Apache/Nginx)
2. Pointez le document root vers `public/`
3. Configurez les variables d'environnement
4. Exécutez les migrations en production
5. Optimisez l'application avec les commandes cache

### Frontend (Next.js)
1. Buildez l'application : `npm run build`
2. Déployez sur Vercel, Netlify, ou votre serveur
3. Configurez les variables d'environnement

## 🤝 Contribution

1. Fork le projet
2. Créez une branche pour votre fonctionnalité
3. Committez vos changements
4. Pushez vers la branche
5. Ouvrez une Pull Request

## 📝 Scripts utiles

### Package.json (Frontend)
```json
{
  "scripts": {
    "dev": "next dev",
    "build": "next build",
    "start": "next start",
    "lint": "next lint"
  }
}
```

## 🐛 Dépannage

### Problèmes courants

#### Backend
- **Erreur de base de données** : Vérifiez les credentials dans `.env`
- **Permission denied** : `chmod -R 775 storage bootstrap/cache`
- **Key not set** : `php artisan key:generate`

#### Frontend
- **Cannot connect to API** : Vérifiez `NEXT_PUBLIC_API_URL` dans `.env.local`
- **Build errors** : `rm -rf .next && npm run build`

## 📞 Support

Pour toute question ou problème, n'hésitez pas à ouvrir une issue sur le repository.

---
