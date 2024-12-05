<?php

require 'vendor/autoload.php';
include_once('mesure.php');

$options = getopt("u:a::");

if (!isset($options['u'])) {
    echo "Usage: php mesure.php -u <URL_de_la_page> [-a pagespeed]\n";
    exit(1);
}

if (isset($options['a']) && $options['a'] == 'pagespeed') {
    $pagespeed = 1;
} else {
    $pagespeed = 0;
}

$resourceChecker = new ResourceSizeChecker();
$result = $resourceChecker->checkPage($options['u'], $pagespeed);

print $result;