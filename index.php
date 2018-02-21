<?php
require_once __DIR__ . '/vendor/autoload.php';

$log = new Log('world.admin@example.com', __DIR__ . '/error.log');
$cache = new Cache($log, __DIR__ . '/cache.html');

try {
    $response = main();
    $cache->save($response);
    echo $response;
} catch (Exception $e) {
    $log->error((string) $e);
    $fromCache = $cache->load();
    if (!$fromCache) {
        echo 'Sorry, an error has happened! We are notified. Please, try again later.';
        exit();
    }

    echo $fromCache;
    exit();
}

function main() {
    $btcComHtml = fetchBtcCom();
    $stats = parseStats($btcComHtml);
    $optimalFee = calculateOptimalFee($stats);

    return Html::render($stats, $optimalFee);
}

function fetchBtcCom() {
    $btcComClient = new \BtcCom\Client(
        new GuzzleHttp\Client(),
        'https://btc.com/stats/unconfirmed-tx'
    );
    $btcComResponse = $btcComClient->fetch();

    $bodyStream = $btcComResponse->getBody();
    $html = $bodyStream->getContents();
    $bodyStream->close();

    return $html;
}

function parseStats($html) {
    $parser = new \BtcCom\Parser();
    return $parser->parseStats($html);
}

function calculateOptimalFee($stats) {
    $threshold = 2;
    $optimalFee = OptimalFee::fromStats($stats, $threshold);
    if (!$optimalFee) {
        throw new Exception("Couldn't calculate the optimal fee. No matching candidate found for the threshold '$threshold'.");
    }

    return $optimalFee;
}
