# UniFolio

Un outil de création de portfolio à destination de l'IUT de Troyes.

Notre équipe reprenons le projet UniFolio pour le mettre à jour et le rendre plus fonctionnel. Nous allons ajouté de nouvelles fonctionnalités, amélioré l'interface utilisateur et optimisé les performances.

---

## Démarrage rapide d'une image avec des données de test avec Docker (Recommandé)

### Prérequis

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/)

### Lancement des conteneurs

Pour construire l'image et démarrer les conteneurs :

```bash
docker compose up -d --build
```

### Accès à l'application

Une fois les conteneurs lancés, ouvrez votre navigateur à l'adresse :
**[http://localhost:8000](http://localhost:8000)**

### Commandes utiles avec Docker

- **Exécuter une commande Symfony Console :**

  ```bash
  docker compose exec app php bin/console <commande>
  # OU via le CLI Symfony :
  docker compose exec app symfony console <commande>
  ```

- **Consulter les logs des conteneurs :**

  ```bash
  docker compose logs -f app
  ```

- **Arrêter les conteneurs :**

  ```bash
  docker compose down
  ```

- **Arrêter les conteneurs et réinitialiser les volumes de données :**
  ```bash
  docker compose down -v
  ```

---

## Installation locale (sans Docker)

### Prérequis

- PHP 8.4 ou supérieur
- Composer
- Node.js et npm (ou yarn)
- Serveur de base de données (MariaDB / MySQL)
- Symfony CLI (recommandé)

### 1. Cloner le projet et installer les dépendances PHP

```bash
git clone https://github.com/Wlacroix45/UniFolio.git
cd UniFolio
composer install
```

### 2. Configuration de l'environnement

```bash
cp .env .env.local
```

Mettre à jour le fichier `.env.local` avec vos informations de connexion à la base de données (variable `DATABASE_URL`).

### 3. Créer la base de données et le schéma

```bash
# Créer la base de données
bin/console doctrine:database:create

# Mettre à jour le schéma de la base de données
bin/console doctrine:schema:update --force

# Optionnel : charger les données de test (fixtures)
bin/console doctrine:fixtures:load -n
```

### 4. Installation et compilation des ressources front

```bash
npm install --force
npm run build
# ou pour le mode développement continu :
npm run encore dev --watch
```

### 5. Lancement du projet

Avec Symfony CLI :

```bash
symfony server:start
```

Ou avec le serveur interne de PHP :

```bash
php -S localhost:8000 -t public
```

L'application sera accessible à l'adresse : `http://localhost:8000`

---

## Fonctionnalités

- Création et gestion de portfolios étudiants
- Personnalisation des compétences et projets
- Interface d'administration

## Auteurs

- [Cyndel Herolt](https://github.com/CyndelHerolt)
- [Wyatt Lacroix](https://github.com/Wlacroix45)
- [Ilona Cosme Vinou](https://github.com/fushizaki)
- [Célestin Maubert](https://github.com/korasrar)
- [Ilane Riotte](https://github.com/ilaneriotte)

## Licence

[MPL-2.0](https://choosealicense.com/licenses/mpl-2.0/)
