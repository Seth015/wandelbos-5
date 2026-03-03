<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$content = getContentBlocks();
$title = (string) ($content['home_origin_title']['title'] ?? '');
$body = (string) ($content['home_origin_body']['body'] ?? '');

echo $title . PHP_EOL;
echo substr(strip_tags($body), 0, 220) . PHP_EOL;
