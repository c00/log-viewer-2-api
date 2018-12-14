<?php

use c00\common\CovleDate;
use c00\log\channel\sql\Database;
use c00\log\LogQuery;
use c00\logViewer\Settings;
use c00\logViewer\ViewDatabase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

//Setup log
$settings = new Settings('settings', __DIR__ . "/../");
$settings->load();

//setup Routing
$app = new Silex\Application(['debug' => true]);

$app->before(function(Request $r) use ($app, $settings){

});

//Routes
$app->get('/', function() use ($app) {
    return $app->json("ok");
});

$app->get('/configs', function() use ($app, $settings) {
    $result = [];
    foreach ($settings->databases as $key => $db) {
        $result[] = ['id' => $key, 'name' => "{$db->name} ({$db->database})"];
    }

    return $app->json($result);
});

$app->get('/log/{dbId}/{since}', function($dbId, $since) use ($app, $settings) {

    $dbSettings = $settings->databases[$dbId] ?? null;
    if (!$dbSettings) throw new Exception("ID $dbId unknown.");

    $db = ViewDatabase::new($dbSettings);

	$since = CovleDate::fromMilliseconds($since);
    $bags = $db->getBagsSince($since);
    $last = null;

    $log = [];
    foreach ($bags as $bag) {
        if (!$last) $last = $bag->date;

        $log[] = $bag->toShowable();
    }

    if (!$last) $last = $since;

    $result = [
        'log' => $log,
        'until' => $last->toMiliseconds()
    ];

    return $app->json($result);
});

$app->get('/log/{since}/{until}', function($since, $until) use ($app) {
    /** @var Database $db */
    $db = $app['db'];

    $since = CovleDate::fromMilliseconds($since);
    $until = CovleDate::fromMilliseconds($until);

    $bags = $db->getBagsSince($since, $until);

    $log = [];
    foreach ($bags as $bag) {
        $log[] = $bag->toShowable();
    }

    $result = [
        'log' => $log
    ];

    return $app->json($result);
});

$app->post('/log-query', function() use ($app) {
    $body = json_decode(file_get_contents("php://input"), true);

    $q = new LogQuery();

    if (isset($body['since'])) $q->since = CovleDate::fromMilliseconds($body['since']);
    if (isset($body['until'])) $q->until = CovleDate::fromMilliseconds($body['until']);
    $q->includeLevels = $body['levels'] ?? null;
    $q->page = $body['page'] ?? 0;

    /** @var Database $db */
    $db = $app['db'];

    $bags = $db->queryBags($q);

    $last = null;
    $log = [];
    foreach ($bags as $bag) {
        if (!$last) $last = $bag->date;

        $log[] = $bag->toShowable();
    }

    if (!$last) $last = $q->since;

    $result = [
        'log' => $log,
        'until' => $last->toMiliseconds()
    ];

    return $app->json($result);
});

//Error handling
$app->error(function(Exception $e, $code) use ($app){

    return new JsonResponse(
        [
            "status" => 'failed',
            'code' => $e->getCode(),
            'message' => $e->getMessage()
        ], 500);
});

$app->run();
