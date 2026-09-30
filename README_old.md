# UniFolio

[![wakatime](https://wakatime.com/badge/user/593f8558-cce2-4776-b789-4f687a124d15/project/adb9500d-e615-45e9-977c-ac49faab9167.svg)](https://wakatime.com/badge/user/593f8558-cce2-4776-b789-4f687a124d15/project/adb9500d-e615-45e9-977c-ac49faab9167)

Un outil de création de portfolio à destination de l'IUT de Troyes.

## Installation

### Installer les dépendences PHP

```bash
    sudo apt install composer
OR
    sudo pacman -S composer
```

### Clonage du projet

```bash
    git clone https://github.com/CyndelHerolt/UniFolio.git
    cd UniFolio
    composer install ou composer update
```

### Mise à jours des infos

```bash
    cp .env .env.local
```

Mettre à jour le fichier .env.local avec vos informations

### Créer la database et importer les données

```bash
    # Créer un nouveau user

    mariadb -u root -p
    
    # Dans l'invite MariaDB :
    CREATE USER 'unifolio_user'@'localhost' IDENTIFIED BY 'uniflio_password';   

    bin/console doctrine:database:create

# Mettre à jour la database

    bin/console doctrine:schema:update --force

```

### Installation des dépendances front

```bash
    npm install --force

ou

    yarn install --force
```

### Compilation des ressources front

```bash
    npm run encore dev --watch

ou

    yarn encore dev --watch
```

## Licence

[MPL-2.0](https://choosealicense.com/licenses/mpl-2.0/)
