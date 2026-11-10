#!/bin/bash
set -e

# Script d'entrée pour le conteneur Docker de l'application UniFolio.
# Développer a l'aide de l'IA

# wait bd
if [ -n "$DATABASE_URL" ]; then
    echo "==> Vérification de la connexion à la base de données..."
    php -r '
        $retries = 30;
        $url = getenv("DATABASE_URL");
        if (!$url) exit(0);
        $params = parse_url($url);
        $host = $params["host"] ?? "localhost";
        $port = $params["port"] ?? 3306;
        $user = $params["user"] ?? "root";
        $pass = $params["pass"] ?? "";
        while ($retries > 0) {
            try {
                new PDO("mysql:host=$host;port=$port", $user, $pass);
                echo "Connexion BDD réussie !\n";
                exit(0);
            } catch (Exception $e) {
                $retries--;
                sleep(1);
            }
        }
        echo "Impossible de se connecter à la base de données\n";
        exit(1);
    '
fi

#init bd
echo "==> Initialisation de la base de données Symfony..."
php bin/console doctrine:database:create --if-not-exists --no-interaction || true
php bin/console doctrine:schema:update --force --no-interaction || true

#installation des assets Symfony
echo "==> Installation des assets Symfony..."
php bin/console assets:install public --no-interaction || true

#build style front
if [ ! -f "public/build/entrypoints.json" ]; then
    echo "==> Compilation des assets CSS/JS frontend via npm..."
    if command -v npm > /dev/null 2>&1; then
        npm run build || true
    fi
fi

# clear du cache Symfony
echo "==> Vidage du cache Symfony..."
php bin/console cache:clear --no-interaction || true

# set permissions
mkdir -p var/cache var/log
chown -R www-data:www-data var

echo "==> Application UniFolio prête !"

exec "$@"
