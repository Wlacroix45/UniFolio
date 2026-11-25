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
# Nettoyage idempotent de l'ancienne FK page.templates_id avant suppression de la table V1 templates (#39)
if [ -n "$DATABASE_URL" ]; then
    php -r '
        $url = getenv("DATABASE_URL");
        if (!$url) exit(0);
        $params = parse_url($url);
        $host = $params["host"] ?? "localhost";
        $port = $params["port"] ?? 3306;
        $user = $params["user"] ?? "root";
        $pass = $params["pass"] ?? "";
        $db = ltrim($params["path"] ?? "", "/");
        if ($db === "") exit(0);
        try {
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $stmt = $pdo->prepare(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = '\''page'\'' AND COLUMN_NAME = '\''templates_id'\'' AND REFERENCED_TABLE_NAME IS NOT NULL"
            );
            $stmt->execute(["db" => $db]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $fk) {
                $pdo->exec("ALTER TABLE `page` DROP FOREIGN KEY `" . str_replace("`", "", $fk) . "`");
            }
        } catch (Throwable $e) {
            // Table/FK absente ou base neuve : on laisse doctrine:schema:update poursuivre
        }
    ' || true
fi
php bin/console doctrine:schema:update --force --no-interaction || true
php bin/console doctrine:fixtures:load -n || true

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
mkdir -p var/cache var/log public/uploads/departement/logo public/upload/files
chown -R www-data:www-data var public/uploads public/upload

echo "==> Application UniFolio prête !"

exec "$@"
