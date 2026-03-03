<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function getPdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = (string) env('DB_HOST', '127.0.0.1');
    $port = (string) env('DB_PORT', '3306');
    $database = (string) env('DB_NAME', 'wandelbos');
    $username = (string) env('DB_USER', 'root');
    $password = (string) env('DB_PASS', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    initializeApplicationSchema($pdo);

    return $pdo;
}

function initializeApplicationSchema(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS content_blocks (
            id VARCHAR(100) NOT NULL,
            title VARCHAR(255) NULL,
            body TEXT NULL,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS locations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(160) NOT NULL,
            short_text TEXT NULL,
            long_text MEDIUMTEXT NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            panorama_url VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_locations_slug (slug),
            KEY idx_locations_active_sort (is_active, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    try {
        $pdo->exec('ALTER TABLE locations ADD COLUMN hero_image_url VARCHAR(255) NULL AFTER panorama_url');
    } catch (Throwable $exception) {
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS location_images (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            location_id INT UNSIGNED NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            caption VARCHAR(255) NULL,
            description TEXT NULL,
            year_label VARCHAR(50) NULL,
            source VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_location_images_location (location_id),
            CONSTRAINT fk_location_images_location
                FOREIGN KEY (location_id)
                REFERENCES locations (id)
                ON UPDATE CASCADE
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    try {
        $pdo->exec('ALTER TABLE location_images ADD COLUMN description TEXT NULL AFTER caption');
    } catch (Throwable $exception) {
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS location_quizzes (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            location_id INT UNSIGNED NOT NULL,
            question TEXT NOT NULL,
            option_a VARCHAR(255) NOT NULL,
            option_b VARCHAR(255) NOT NULL,
            option_c VARCHAR(255) NOT NULL,
            correct_option ENUM("a", "b", "c") NOT NULL DEFAULT "a",
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_location_quizzes_location (location_id),
            CONSTRAINT fk_location_quizzes_location
                FOREIGN KEY (location_id)
                REFERENCES locations (id)
                ON UPDATE CASCADE
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    seedDefaultRouteData($pdo);
    $initialized = true;
}

function seedDefaultRouteData(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM locations')->fetchColumn();

    if ($count > 0) {
        return;
    }

    $defaults = [
        [
            'name' => 'Startpunt Philippine',
            'slug' => 'startpunt-philippine',
            'short_text' => 'Welkom bij de historische wandelroute van Philippine.',
            'long_text' => 'Op deze locatie begon vroeger de toegang naar het oude buitengebied. Hier start de route en vind je context over het ontstaan van de omgeving.',
            'latitude' => 51.2939,
            'longitude' => 3.7562,
            'sort_order' => 1,
        ],
        [
            'name' => 'Oude Dijkzone',
            'slug' => 'oude-dijkzone',
            'short_text' => 'Historische dijkstructuren beschermden de polders.',
            'long_text' => 'Deze zone laat zien hoe dijken de leefbaarheid van het gebied bepaalden. Je ziet nog sporen in de verkaveling en hoogteverschillen in het landschap.',
            'latitude' => 51.2951,
            'longitude' => 3.7543,
            'sort_order' => 2,
        ],
        [
            'name' => 'Bosrand Herinneringspunt',
            'slug' => 'bosrand-herinneringspunt',
            'short_text' => 'Een plek met verhalen van bewoners door de generaties heen.',
            'long_text' => 'Op dit punt worden verhalen over het dagelijks leven in het gebied gedeeld, ondersteund met oude foto’s en mondelinge geschiedenis.',
            'latitude' => 51.2927,
            'longitude' => 3.7598,
            'sort_order' => 3,
        ],
    ];

    $insertLocation = $pdo->prepare(
           'INSERT INTO locations (name, slug, short_text, long_text, latitude, longitude, sort_order, is_active, hero_image_url)
            VALUES (:name, :slug, :short_text, :long_text, :latitude, :longitude, :sort_order, 1, :hero_image_url)'
    );

    $insertImage = $pdo->prepare(
        'INSERT INTO location_images (location_id, image_url, caption, description, year_label, source)
         VALUES (:location_id, :image_url, :caption, :description, :year_label, :source)'
    );

    $insertQuiz = $pdo->prepare(
        'INSERT INTO location_quizzes (location_id, question, option_a, option_b, option_c, correct_option, sort_order)
         VALUES (:location_id, :question, :option_a, :option_b, :option_c, :correct_option, :sort_order)'
    );

    foreach ($defaults as $item) {
        $insertLocation->execute([
            ':name' => $item['name'],
            ':slug' => $item['slug'],
            ':short_text' => $item['short_text'],
            ':long_text' => $item['long_text'],
            ':latitude' => $item['latitude'],
            ':longitude' => $item['longitude'],
            ':sort_order' => $item['sort_order'],
            ':hero_image_url' => 'https://placehold.co/1600x900/5a4a2f/f1e7d0?text=Historische+foto',
        ]);

        $locationId = (int) $pdo->lastInsertId();

        $insertImage->execute([
            ':location_id' => $locationId,
            ':image_url' => 'https://placehold.co/1200x700/5a4a2f/f1e7d0?text=Historische+foto',
            ':caption' => 'Voorbeeld historische foto',
            ':description' => '',
            ':year_label' => 'ca. 1900',
            ':source' => 'Archief (demo)',
        ]);

        $insertQuiz->execute([
            ':location_id' => $locationId,
            ':question' => 'Welke functie had deze plek historisch vooral?',
            ':option_a' => 'Handel en doorgang',
            ':option_b' => 'Luchthaven',
            ':option_c' => 'Industriehaven',
            ':correct_option' => 'a',
            ':sort_order' => 1,
        ]);
    }
}

function getDatabaseConnection(): ?PDO
{
    try {
        return getPdo();
    } catch (Throwable $exception) {
        return null;
    }
}

function getContentBlocks(): array
{
    $pdo = getPdo();
    $stmt = $pdo->query('SELECT id, title, body FROM content_blocks');
    $rows = $stmt->fetchAll();

    $content = [];

    foreach ($rows as $row) {
        $content[(string) $row['id']] = [
            'title' => $row['title'],
            'body' => $row['body'],
        ];
    }

    return $content;
}

function contentTitle(array $content, string $id, string $fallback): string
{
    if (isset($content[$id]['title']) && $content[$id]['title'] !== null && $content[$id]['title'] !== '') {
        return (string) $content[$id]['title'];
    }

    return $fallback;
}

function contentBody(array $content, string $id, string $fallback): string
{
    if (isset($content[$id]['body']) && $content[$id]['body'] !== null && $content[$id]['body'] !== '') {
        return (string) $content[$id]['body'];
    }

    return $fallback;
}

function fetchLocations(bool $activeOnly = true): array
{
    $pdo = getPdo();
    $sql = 'SELECT * FROM locations';

    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }

    $sql .= ' ORDER BY sort_order ASC, name ASC';

    return $pdo->query($sql)->fetchAll();
}

function fetchLocationBySlug(string $slug): ?array
{
    $pdo = getPdo();
    $stmt = $pdo->prepare('SELECT * FROM locations WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $location = $stmt->fetch();

    return is_array($location) ? $location : null;
}

function fetchLocationById(int $id): ?array
{
    $pdo = getPdo();
    $stmt = $pdo->prepare('SELECT * FROM locations WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $location = $stmt->fetch();

    return is_array($location) ? $location : null;
}

function saveLocation(array $payload, int $id = 0): int
{
    $pdo = getPdo();

    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE locations
             SET name = :name,
                 slug = :slug,
                 short_text = :short_text,
                 long_text = :long_text,
                 latitude = :latitude,
                 longitude = :longitude,
                 sort_order = :sort_order,
                 is_active = :is_active,
                 panorama_url = :panorama_url,
                 hero_image_url = :hero_image_url
             WHERE id = :id'
        );

        $stmt->execute([
            ':name' => (string) $payload['name'],
            ':slug' => (string) $payload['slug'],
            ':short_text' => (string) ($payload['short_text'] ?? ''),
            ':long_text' => (string) ($payload['long_text'] ?? ''),
            ':latitude' => $payload['latitude'],
            ':longitude' => $payload['longitude'],
            ':sort_order' => (int) ($payload['sort_order'] ?? 0),
            ':is_active' => !empty($payload['is_active']) ? 1 : 0,
            ':panorama_url' => (string) ($payload['panorama_url'] ?? ''),
            ':hero_image_url' => (string) ($payload['hero_image_url'] ?? ''),
            ':id' => $id,
        ]);

        return $id;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO locations (name, slug, short_text, long_text, latitude, longitude, sort_order, is_active, panorama_url, hero_image_url)
         VALUES (:name, :slug, :short_text, :long_text, :latitude, :longitude, :sort_order, :is_active, :panorama_url, :hero_image_url)'
    );

    $stmt->execute([
        ':name' => (string) $payload['name'],
        ':slug' => (string) $payload['slug'],
        ':short_text' => (string) ($payload['short_text'] ?? ''),
        ':long_text' => (string) ($payload['long_text'] ?? ''),
        ':latitude' => $payload['latitude'],
        ':longitude' => $payload['longitude'],
        ':sort_order' => (int) ($payload['sort_order'] ?? 0),
        ':is_active' => !empty($payload['is_active']) ? 1 : 0,
        ':panorama_url' => (string) ($payload['panorama_url'] ?? ''),
        ':hero_image_url' => (string) ($payload['hero_image_url'] ?? ''),
    ]);

    return (int) $pdo->lastInsertId();
}

function insertLocationImage(int $locationId, string $imageUrl, string $caption = '', string $description = '', string $yearLabel = '', string $source = ''): void
{
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'INSERT INTO location_images (location_id, image_url, caption, description, year_label, source)
         VALUES (:location_id, :image_url, :caption, :description, :year_label, :source)'
    );
    $stmt->execute([
        ':location_id' => $locationId,
        ':image_url' => $imageUrl,
        ':caption' => $caption,
        ':description' => $description,
        ':year_label' => $yearLabel,
        ':source' => $source,
    ]);
}

function upsertPrimaryLocationImage(int $locationId, string $imageUrl, string $caption = '', string $yearLabel = '', string $source = ''): void
{
    $pdo = getPdo();
    $stmt = $pdo->prepare('SELECT id FROM location_images WHERE location_id = :location_id ORDER BY id DESC LIMIT 1');
    $stmt->execute([':location_id' => $locationId]);
    $imageId = (int) ($stmt->fetchColumn() ?: 0);

    if ($imageId > 0) {
        $update = $pdo->prepare(
            'UPDATE location_images
             SET image_url = :image_url,
                 caption = :caption,
                 description = :description,
                 year_label = :year_label,
                 source = :source
             WHERE id = :id'
        );
        $update->execute([
            ':image_url' => $imageUrl,
            ':caption' => $caption,
            ':description' => '',
            ':year_label' => $yearLabel,
            ':source' => $source,
            ':id' => $imageId,
        ]);

        return;
    }

    insertLocationImage($locationId, $imageUrl, $caption, '', $yearLabel, $source);
}

function fetchLocationImages(int $locationId): array
{
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'SELECT id, location_id, image_url, caption, description, year_label, source
         FROM location_images
         WHERE location_id = :location_id
         ORDER BY id DESC'
    );
    $stmt->execute([':location_id' => $locationId]);

    return $stmt->fetchAll();
}

function updateLocationImage(int $imageId, int $locationId, string $imageUrl, string $caption = '', string $description = '', string $yearLabel = '', string $source = ''): bool
{
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'UPDATE location_images
         SET image_url = :image_url,
             caption = :caption,
             description = :description,
             year_label = :year_label,
             source = :source
         WHERE id = :id AND location_id = :location_id'
    );
    $stmt->execute([
        ':image_url' => $imageUrl,
        ':caption' => $caption,
        ':description' => $description,
        ':year_label' => $yearLabel,
        ':source' => $source,
        ':id' => $imageId,
        ':location_id' => $locationId,
    ]);

    return $stmt->rowCount() > 0;
}

function fetchLocationQuizzes(int $locationId): array
{
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'SELECT id, question, option_a, option_b, option_c, correct_option
         FROM location_quizzes
         WHERE location_id = :location_id
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([':location_id' => $locationId]);

    return $stmt->fetchAll();
}
