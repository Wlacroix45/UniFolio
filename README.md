# UniFolio

Un outil de création de portfolio à destination de l'IUT de Troyes.

Notre équipe reprenons le projet UniFolio pour le mettre à jour et le rendre plus fonctionnel. Nous avons ajouté de nouvelles fonctionnalités, amélioré l'interface utilisateur et optimisé les performances.

## Prérequis

- PHP 8.1 ou supérieur
- Composer
- Node.js et npm (ou yarn)
- Serveur de base de données (MariaDB / MySQL)
- Symfony CLI (recommandé)

## Installation

### Installer les dépendances PHP

```bash
sudo apt install composer
# OU
sudo pacman -S composer
```

### Clonage du projet

```bash
git clone https://github.com/Wlacroix45/UniFolio.git
cd UniFolio
composer install
```

### Mise à jour des infos

```bash
cp .env .env.local
```

Mettre à jour le fichier `.env.local` avec vos informations de connexion à la base de données (variable `DATABASE_URL`).

### Créer la database et importer les données

```bash
# Se connecter pour créer un nouveau user
mariadb -u root -p

# Dans l'invite MariaDB :
CREATE USER 'unifolio_user'@'localhost' IDENTIFIED BY 'uniflio_password';
GRANT ALL PRIVILEGES ON unifolio.* TO 'unifolio_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Créer la base de données
bin/console doctrine:database:create

# Mettre à jour le schéma de la base de données
bin/console doctrine:schema:update --force

# Optionnel : charger les données de test (fixtures)
# bin/console doctrine:fixtures:load -n
```

### Installation des dépendances front

```bash
npm install --force
# OU
yarn install --force
```

### Compilation des ressources front

```bash
npm run encore dev --watch
# OU
yarn encore dev --watch
```

## Lancement du projet

Avec Symfony CLI :

```bash
symfony server:start
```

Ou avec le serveur interne de PHP :

```bash
php -S localhost:8000 -t public
```

L'application sera accessible à l'adresse : `http://localhost:8000`

## Fonctionnalités

- Création et gestion de portfolios étudiants
- Personnalisation des compétences et projets
- Interface d'administration

## Auteurs

- [Cyndel Herolt](https://github.com/CyndelHerolt)

## Licence

[MPL-2.0](https://choosealicense.com/licenses/mpl-2.0/)
