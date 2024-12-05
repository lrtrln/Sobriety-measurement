<?php

require 'vendor/autoload.php';
include_once 'mesure.php';

function run()
{
    $url = $_POST['url'] ?? null;

    if (!$url) {
        return false;
    }

    $checker = new ResourceSizeChecker();
	
    return $checker->checkPage($url);
}

