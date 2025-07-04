<?php

require 'vendor/autoload.php';
include_once 'mesure.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function run()
{
    $url = $_POST['url'] ?? null;
    $pagespeed = isset($_POST['pagespeed']) ? 1 : 0;

    if (!$url) {
        return false;
    }

    $apiKey = $_ENV['GOOGLE_PAGESPEED_API_KEY'] ?? null;

    $checker = new ResourceSizeChecker($apiKey);
	
    return $checker->checkPage($url, $pagespeed);
}

