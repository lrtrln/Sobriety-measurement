<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\Utils;

class ResourceSizeChecker
{
    private $client;
    private $resourceLoadTimes = [];
    private $cache             = [];

    private $totalCssWeight   = 0;
    private $totalJsWeight    = 0;
    private $totalImgWeight   = 0;
    private $totalFrameWeight = 0;
    private $totalVideoWeight = 0;

    private $cssExtensions   = ['.css'];
    private $jsExtensions    = ['.js'];
    private $imgExtensions   = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.tif', '.tiff'];
    private $videoExtensions = ['.mp4', '.webm', '.ogg', '.avi', '.wmv', '.mov', '.mpeg'];
    private $filesExtensions = ['.csv', '.doc', '.docx', '.json', '.pdf', '.xsl', '.xlsx'];
    private $jsonExtensions  = ['.json'];

    /**
     * Constructor for ResourceSizeChecker
     * 
     * Initializes the Guzzle HTTP client with specific settings
     */
    public function __construct()
    {
        $userAgent    = ($this->isCli()) ? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:129.0) Gecko/20100101 Firefox/129.0' : $_SERVER['HTTP_USER_AGENT'];
        $this->client = new Client([
            'timeout'          => 300,
            'connect_timeout'  => 120,
            'referer'          => true,
            'headers'          => [
                'Accept'          => ' */*',
                'Content-Type'    => 'application/json',
                'User-Agent'      => $userAgent,
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Cache-Control'   => 'max-age=0',
            ],
            'timeout'          => 30,
            'debug'            => false,
            'force_ip_resolve' => 'v4',
        ]);
    }

    /**
     * Get the size of a resource
     * 
     * @param string $url The URL of the resource
     * @return int The size of the resource in bytes, or 0 if there's an error
     */
    private function getResourceSize(string $url): int
    {
        if (isset($this->cache[$url])) {
            return $this->cache[$url];
        }

        try {
            $response = $this->client->request('GET', $url);

            if ($response->getStatusCode() === 200) {
                $size              = intval($response->getBody()->getSize());
                $this->cache[$url] = $size;

                return $size;
            } else {
                return 0; 
            }
        } catch (RequestException $e) {
            // Gérer les erreurs Guzzle
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();

                return 0; 
            } else {
                return 0; 
            }
        } catch (Exception $e) {
                      
            return 0; 
        }
    }

    /**
     * Get PageSpeed insights for a URL
     * 
     * @param string $url The URL to analyze
     * @param string $strategy The strategy to use (desktop or mobile)
     * @return array An array of PageSpeed metrics
     */
    private function getPageSpeed(string $url, string $strategy = 'desktop'): array
    {
        $api      = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
        $response = $this->client->request('GET', $api, [
            'query' => [
                'url'      => $url,
                'strategy' => $strategy,
                'category' => 'performance',
                //'key'      => 'FREE',
            ],
        ]);

        $loadTime = 0;

        if ($response->getStatusCode() == 200) {
            $body   = $response->getBody()->getContents();
            $result = json_decode($body, true);

            $metrics = [
                'performanceScore'       => $result['lighthouseResult']['categories']['performance']['score'] * 100,
                'firstContentfulPaint'   => $result['lighthouseResult']['audits']['first-contentful-paint']['displayValue'],
                'speedIndex'             => $result['lighthouseResult']['audits']['speed-index']['displayValue'],
                'largestContentfulPaint' => $result['lighthouseResult']['audits']['largest-contentful-paint']['displayValue'],
                'interactive'            => $result['lighthouseResult']['audits']['interactive']['displayValue'],
                'totalBlockingTime'      => $result['lighthouseResult']['audits']['total-blocking-time']['displayValue'],
                'cumulativeLayoutShift'  => $result['lighthouseResult']['audits']['cumulative-layout-shift']['displayValue'],
                'totalRequests'          => $result['lighthouseResult']['audits']['network-requests']['details']['items'], 
                'domElements' => $result['lighthouseResult']['audits']['dom-size']['displayValue'],                        
                'totalByteWeight' => $result['lighthouseResult']['audits']['total-byte-weight']['displayValue'],           
            ];
        }

        return $metrics;
    }

    /**
     * Asynchronously load resources
     * 
     * @param array $urls An array of URLs to load
     * @return array The results of the asynchronous requests
     */
    public function asyncLoadResources(array $urls): array
    {
        $promises = [];
        foreach ($urls as $url) {
            $promises[$url] = $this->client->getAsync($url)->then(
                function ($response) use ($url) {
                    $this->resourceLoadTimes[$url] = microtime(true) - $this->resourceLoadTimes[$url];

                    return $response->getBody()->getContents();
                },
                function ($exception) {
                    return "Error: " . $exception->getMessage();
                }
            );
            $this->resourceLoadTimes[$url] = microtime(true);
        }

        $results = Utils::settle($promises)->wait();

        return $results;
    }

    /**
     * Extract resources from page content
     * 
     * @param string $pageContent The HTML content of the page
     * @param string $baseUrl The base URL of the page
     * @return array An array of resource URLs
     */
    private function extractResources(string $pageContent, string $baseUrl): array
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($pageContent);

        $cssLinks   = $this->extractLinks($doc, 'link', 'href', $baseUrl);
        $jsLinks    = $this->extractLinks($doc, 'script', 'src', $baseUrl);
        $imgLinks   = $this->extractLinks($doc, 'img', 'src', $baseUrl);
        $frameLinks = $this->extractLinks($doc, 'iframe', 'src', $baseUrl);
        $videoLinks = $this->extractLinks($doc, 'video', 'src', $baseUrl);

        return array_merge($cssLinks, $jsLinks, $imgLinks, $frameLinks, $videoLinks);
    }

    /**
     * Extract links from a DOMDocument
     * 
     * @param DOMDocument $doc The DOMDocument to extract links from
     * @param string $tag The tag name to look for
     * @param string $attribute The attribute name containing the URL
     * @param string $baseUrl The base URL to resolve relative URLs
     * @param string|null $pattern Optional regex pattern to filter URLs
     * @return array An array of extracted URLs
     */
    private function extractLinks(DOMDocument $doc, string $tag, string $attribute, string $baseUrl, string|null $pattern = null): array
    {
        $links = [];
        foreach ($doc->getElementsByTagName($tag) as $element) {
            $url = $element->getAttribute($attribute);
            if ($url) {
                $absoluteUrl = $this->makeAbsoluteUrl($url, $baseUrl);
                if (!$pattern || preg_match($pattern, $absoluteUrl)) {
                    $links[] = $absoluteUrl;
                }
            }
        }

        return $links;
    }

    /**
     * Check if a resource URL is of a specific type
     * 
     * @param string $resourceUrl The URL of the resource
     * @param array $extensions An array of file extensions to check against
     * @return bool True if the resource is of the specified type, false otherwise
     */
    private function isOfType(string $resourceUrl, array $extensions): bool
    {
        $urlPath = parse_url($resourceUrl, PHP_URL_PATH);
        if (!$urlPath) {
            return false;
        }
        foreach ($extensions as $extension) {
            if (substr($urlPath, -strlen($extension)) === $extension) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert a relative URL to an absolute URL
     * 
     * @param string $url The URL to convert
     * @param string $baseUrl The base URL to use for conversion
     * @return string The absolute URL
     */
    private function makeAbsoluteUrl(string $url, string $baseUrl): string
    {
        if (strpos($url, 'blob:') === 0) {
            return $url;
        }
        // If the URL starts with '//', add the appropriate protocol
        if (substr($url, 0, 2) === '//') {
            $url = 'https:' . $url;
        }

        // If the URL is already absolute, return it as is
        if (parse_url($url, PHP_URL_SCHEME) !== null) {
            return $url;
        }

        // If the URL starts with '/', it is relative to the root of the site
        if ($url[0] === '/') {
            return rtrim($baseUrl, '/') . $url;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Generate a list of resources sorted by size
     * 
     * @param array $resources An array of resources with their sizes
     * @return string A formatted string containing the list of resources
     */
    private function generateResourcesList(array $resources): string
    {
        $out = '';
        $out .= "Liste des ressources triées par poids (+ lourd --> + léger):\n";
        $out .= "----------------------------------------\n";

        foreach ($resources as $i => $resource) {
            $resourceUrl      = $resource[0];
            $resourceSizeInKb = $resource[1];
            $resourceSizeInMb = $resource[2];

            if ($this->isCli()) {
                $out .= sprintf(
                    "%s - %sKo (%s Mo) \n",
                    $resourceUrl,
                    $this->nformat($resourceSizeInKb, 2),
                    $this->nformat($resourceSizeInMb, 2)
                );
            } else {
                $out .= sprintf(
                    "<a href='%s' class='underline'>%s - %sKo (%s Mo)</a> \n",
                    $resourceUrl,
                    sprintf('R%d - %s', $i, pathinfo($resourceUrl, PATHINFO_EXTENSION)),
                    $this->nformat($resourceSizeInKb, 2),
                    $this->nformat($resourceSizeInMb, 2)
                );
            }
        }

        return $out;
    }

    /**
     * Format a number with specified decimal places
     * 
     * @param float $num The number to format
     * @param int $decimals The number of decimal places
     * @return string The formatted number as a string
     */
    private function nformat(float $num, int $decimals): string
    {
        if (is_null($num) || !is_numeric($num)) {
            return '0';
        }

        return number_format($num, $decimals);
    }

    /**
     * Convert bytes to kilobytes
     * 
     * @param int $bytes The number of bytes
     * @return float The equivalent in kilobytes
     */
    private function bytesToKo(int $bytes): float
    {
        return $bytes / 1024;
    }

    /**
     * Convert bytes to megabytes
     * 
     * @param int $bytes The number of bytes
     * @return float The equivalent in megabytes
     */
    private function bytesToMo(int $bytes): float
    {
        return $bytes / 1048576;
    }

    /**
     * Check a page and analyze its resources
     * 
     * @param string $url The URL of the page to check
     * @param string $arg Optional argument
     * @return string A formatted string containing the analysis results
     */
    public function checkPage(string $url, string $arg = ''): string
    {
        if (!$this->validateUrl($url)) {
            return "Error: Invalid or non-public URL provided.";
        }

        try {
            $response = $this->client->request('GET', $url);
            $body     = $response->getBody()->getContents();
            $baseUrl  = $this->getBaseUrl($url);

            $resources    = $this->extractResources($body, $baseUrl);
            $resourceData = [];
            $totalBytes   = 0;

            $pageSpeed = $pageTime = $markup1 = $markup2 = '';

            $promises = [];
            foreach ($resources as $resourceUrl) {
                $promises[$resourceUrl] = $this->client->getAsync($resourceUrl);
            }

            $results = Utils::settle($promises)->wait();

            foreach ($results as $resourceUrl => $result) {
                $size = 0;
                if ($result['state'] === 'fulfilled') {
                    /** @var \GuzzleHttp\Psr7\Response $response */
                    $response = $result['value'];
                    if ($response->getStatusCode() === 200) {
                        $size = $response->getBody()->getSize();
                    }
                }

                $totalBytes += $size;
                $resourceData[] = [$resourceUrl, $this->bytesToKo($size), $this->bytesToMo($size)];

                // Calculate total weight by resource type
                if ($this->isOfType($resourceUrl, $this->cssExtensions)) {
                    $this->totalCssWeight += $size;
                } elseif ($this->isOfType($resourceUrl, $this->jsExtensions)) {
                    $this->totalJsWeight += $size;
                } elseif ($this->isOfType($resourceUrl, $this->imgExtensions)) {
                    $this->totalImgWeight += $size;
                } elseif (strpos($resourceUrl, 'iframe') !== false) {
                    $this->totalFrameWeight += $size;
                } elseif ($this->isOfType($resourceUrl, $this->videoExtensions)) {
                    $this->totalVideoWeight += $size;
                }
            }

            // Sort resources by decreasing size
            usort($resourceData, function ($a, $b) {
                return $b[1] - $a[1];
            });

            // Generate the list of resources
            $resourceList = $this->generateResourcesList($resourceData);

            // Calculate metrics
            $domElements   = $this->countDomElements($body);
            $maxDepth      = $this->calculateMaxDepth($this->getDomElements($body));
            $totalRequests = count($resources);
            $totalWeightMo = $this->bytesToMo($totalBytes);

            // Generate the summary
            $recap = $this->generateRecap($domElements, $maxDepth, $totalWeightMo, $totalRequests);
            $note  = $this->calculateNote($domElements, $totalWeightMo, $totalRequests);

            // Add the summary of weights by resource type
            $weightByResourceType = $this->generateWeightByResourceTypeRecap();

            $serverResponseTime = number_format($this->calculateServerResponseTime($url), 3);

            if ($serverResponseTime > 0) {
                $serverTime = sprintf('- Temps de réponse serveur : %ss', $serverResponseTime);
            } else {
                $serverTime = '';
            }

            if ($this->addPageSpeed() || $arg == 1) {
                $pageSpeed = sprintf("- Score Pagespeed desktop : %s\n",
                    $this->getPageSpeed($url)['performanceScore']
                );
                $pageSpeed .= sprintf("- Score Pagespeed mobile : %s\n",
                    $this->getPageSpeed($url, 'mobile')['performanceScore']
                );

                $pageTime = sprintf('- Temps chargement page : %s',
                    $this->getPageSpeed($url)['interactive']
                );
            }

            if ($note == 'A' || $note == 'B') {
                $class = 'text-green-400';
            } elseif ($note == 'C' || $note == 'D') {
                $class = 'text-orange-400';
            } else {
                $class = 'text-red-400';
            }

            $out = sprintf("%s\n%s\n", $recap, $serverTime);

            if ($this->addPageSpeed() || $arg == 1) {
                $out .= sprintf("%s\n%s\n", $pageTime, $pageSpeed);
            }

            if (!$this->isCli()) {
                $markup1 = sprintf('<span class="pt-2 %s">', $class);
                $markup2 = '</span>';
            }

            $out .= <<<TEXT
            {$markup1}-------------------------------------------
            Note sobriété : {$note}
            -------------------------------------------{$markup2}
            {$weightByResourceType}
            {$resourceList}
            TEXT;

            return $out;
        } catch (Exception $e) {
            return "Error retrieving resource size for $url: " . $e->getMessage();
        }
    }

    /**
     * Generate a recap of the total weight by resource type
     * 
     * @return string A formatted string containing the weight recap
     */
    private function generateWeightByResourceTypeRecap(): string
    {
        $recap = "Poids total par type de ressource:\n";

        if ($this->totalCssWeight > 0) {
            $recap .= sprintf(
                "- CSS: %sKo (%sMo)\n",
                $this->nformat($this->bytesToKo($this->totalCssWeight), 2),
                $this->nformat($this->bytesToMo($this->totalCssWeight), 2)
            );
        }
        if ($this->totalJsWeight > 0) {
            $recap .= sprintf(
                "- JS: %sKo (%sMo)\n",
                $this->nformat($this->bytesToKo($this->totalJsWeight), 2),
                $this->nformat($this->bytesToMo($this->totalJsWeight), 2)
            );
        }
        if ($this->totalImgWeight > 0) {
            $recap .= sprintf(
                "- Images: %sKo (%sMo)\n",
                $this->nformat($this->bytesToKo($this->totalImgWeight), 2),
                $this->nformat($this->bytesToMo($this->totalImgWeight), 2)
            );
        }
        if ($this->totalFrameWeight > 0) {
            $recap .= sprintf(
                "- Iframes: %sKo (%sMo)\n",
                $this->nformat($this->bytesToKo($this->totalFrameWeight), 2),
                $this->nformat($this->bytesToMo($this->totalFrameWeight), 2)
            );
        }
        if ($this->totalVideoWeight > 0) {
            $recap .= sprintf(
                "- Vidéos: %sKo (%sMo)\n",
                $this->nformat($this->bytesToKo($this->totalVideoWeight), 2),
                $this->nformat($this->bytesToMo($this->totalVideoWeight), 2)
            );
        }

        return $recap;
    }

    /**
     * Count the number of DOM elements in an HTML string
     * 
     * @param string $html The HTML content
     * @return int The number of DOM elements
     */
    private function countDomElements(string $html): int
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        return $doc->getElementsByTagName('*')->length;
    }

    /**
     * Get the DOM elements from an HTML string
     * 
     * @param string $html The HTML content
     * @return DOMElement The root element of the DOM
     */
    private function getDomElements(string $html): DOMElement
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        return $doc->documentElement;
    }

    /**
     * Calculate the maximum depth of the DOM tree
     * 
     * @param DOMNode $node The node to start from
     * @param int $depth The current depth
     * @return int The maximum depth of the DOM tree
     */
    public function calculateMaxDepth(DOMNode $node, int $depth = 0): int
    {
        $maxDepth = $depth;
        foreach ($node->childNodes as $child) {
            $maxDepth = max($maxDepth, $this->calculateMaxDepth($child, $depth + 1));
        }

        return $maxDepth;
    }

    /**
     * Generate a recap of the page analysis
     * 
     * @param int $domElements The number of DOM elements
     * @param int $maxDepths The maximum depth of the DOM tree
     * @param float $totalWeightMo The total weight of the page in megabytes
     * @param int $totalRequests The total number of requests
     * @return string A formatted string containing the recap
     */
    private function generateRecap(int $domElements, int $maxDepths, float $totalWeightMo, int $totalRequests): string
    {
        $pageSize = $this->nformat($totalWeightMo, 2);

        return <<<TXT
        -------------------------------------------
        Mesures
        -------------------------------------------
        - Nombre d'éléments DOM : {$domElements}
        - Profondeur max de nœuds : {$maxDepths}
        - Poids total de la page : {$pageSize}Mo
        - Nombre de requêtes HTML : {$totalRequests}
        TXT;
    }

    /**
     * Calculate the total page load time
     * 
     * @param string $url The URL of the page to analyze
     * @return string A formatted string containing the load time and resource weights
     */
    public function totalPageTime(string $url): string
    {
        $startTime = microtime(true);

        // Load the main page content
        $pageContent = $this->getResourceSize($url);

        if (isset($pageContent['error']) && $pageContent['error']) {
            return $pageContent['message'];
        }

        // Extract and analyze resources
        $resources = $this->extractResources($pageContent, $url);
        foreach ($resources as $resourceUrl) {
            $sizeInfo = $this->getResourceSize($resourceUrl);
            if (isset($sizeInfo['error']) && $sizeInfo['error']) {
                continue;
            }
            $this->categorizeResource($resourceUrl, $sizeInfo);
        }

        $endTime  = microtime(true);
        $loadTime = $endTime - $startTime;

        // Generate recap
        $recap = $this->generateWeightByResourceTypeRecap();
        $recap .= sprintf("- Temps de chargement page : %.2f secondes", $loadTime);

        return $recap;
    }

    /**
     * Calculate the overall note based on DOM elements, total weight, and total requests
     * 
     * @param int $domElements The number of DOM elements
     * @param float $totalWeightMo The total weight of the page in megabytes
     * @param int $totalRequests The total number of requests
     * @return string A letter grade from A to G
     */
    private function calculateNote(int $domElements, float $totalWeightMo, int $totalRequests): string
    {
        $domScore     = $this->calculateDomScore($domElements);
        $weightScore  = $this->calculateWeightScore($totalWeightMo);
        $requestScore = $this->calculateRequestScore($totalRequests);

        $totalScore = $domScore + $weightScore + $requestScore;
        $note = 'G';
        
        if ($totalScore >= 90) {
            $note = 'A';
        } elseif ($totalScore >= 80) {
            $note = 'B';
        } elseif ($totalScore >= 70) {
            $note = 'C';
        } elseif ($totalScore >= 60) {
            $note = 'D';
        } elseif ($totalScore >= 50) {
            $note = 'E';
        } elseif ($totalScore >= 40) {
            $note = 'F';
        }
        
        return $note;
    }

    /**
     * Calculate the score based on the number of DOM elements
     * 
     * @param int $domElements The number of DOM elements
     * @return float The calculated score
     */
    private function calculateDomScore(int $domElements): float
    {
        $maxElements = 1500;
        $score       = 33.33 * (1 - min($domElements, $maxElements) / $maxElements);

        return $score;
    }

    /**
     * Calculate the score based on the total weight of the page
     * 
     * @param float $totalWeightMo The total weight of the page in megabytes
     * @return float The calculated score
     */
    private function calculateWeightScore(float $totalWeightMo): float
    {
        $maxWeight = 10;
        $score     = 33.33 * (1 - min($totalWeightMo, $maxWeight) / $maxWeight);

        return $score;
    }

    /**
     * Calculate the score based on the total number of requests
     * 
     * @param int $totalRequests The total number of requests
     * @return float The calculated score
     */
    private function calculateRequestScore(int $totalRequests): float
    {
        $maxRequests = 200;
        $score       = 33.33 * (1 - min($totalRequests, $maxRequests) / $maxRequests);

        return $score;
    }

    /**
     * Get the base URL from a full URL
     * 
     * @param string $url The full URL
     * @return string The base URL
     */
    private function getBaseUrl(string $url): string
    {
        $parsedUrl = parse_url($url);

        return $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
    }

    /**
     * Calculate the server response time for a given URL
     * 
     * @param string $url The URL to check
     * @return float The server response time in seconds
     */
    public function calculateServerResponseTime(string $url): float
    {
        try {
            $start  = microtime(true);
            $client = new Client();
            $client->request('GET', $url);
            $end          = microtime(true);
            $responseTime = $end - $start;

            return $responseTime;
        } catch (GuzzleHttp\Exception\RequestException $e) {

            return 0;
        }
    }

    /**
     * Check if PageSpeed analysis should be added
     * 
     * @return bool True if PageSpeed analysis should be added, false otherwise
     */
    public function addPageSpeed(): bool
    {
        if (isset($_GET['pagespeed']) && $_GET['pagespeed'] == 1) {
            return true;
        }

        return false;
    }

    /**
     * Check if the script is running in CLI mode
     * 
     * @return bool True if running in CLI mode, false otherwise
     */
    public function isCli(): bool
    {
        if (PHP_SAPI === 'cli') {
            return true;
        }

        return false;
    }

    /**
     * Validate a URL to ensure it is public and not a local resource.
     *
     * @param string $url The URL to validate.
     * @return bool True if the URL is valid and public, false otherwise.
     */
    private function validateUrl(string $url): bool
    {
        // 1. Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsedUrl = parse_url($url);

        // 2. Scheme check
        if (!isset($parsedUrl['scheme']) || !in_array($parsedUrl['scheme'], ['http', 'https'])) {
            return false;
        }

        // 3. Host check - prevent requests to local/private networks
        if (!isset($parsedUrl['host'])) {
            return false;
        }
        
        $host = $parsedUrl['host'];
        // For IPv6 localhost
        if ($host === '[::1]') {
            return false;
        }
        $ip = gethostbyname($host);

        if ($ip === $host && !filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        return true;
    }

}
