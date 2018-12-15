<?php

use c00\common\CovleDate;
use c00\log\channel\sql\Database;
use c00\log\LogQuery;
use c00\logViewer\Settings;
use c00\logViewer\ViewDatabase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

$app->get('/config/{id}', function($id) use ($app, $settings) {
	$dbSettings = $settings->databases[$id] ?? null;
	if (!$dbSettings) throw new Exception("ID $id unknown.");

	$db = ViewDatabase::new($dbSettings);

	//Additional data
	$data = [
		"id" => $id,
		"name" => $dbSettings->name,
		"dbName" => $dbSettings->database,
		"firstLogDate" => $db->getFirstLogDate()->toMiliseconds(),
		"lastLogDate" => $db->getLastLogDate()->toMiliseconds()
	];

	return $app->json($data);
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

$app->get('/log/{dbId}/{since}/{until}', function(Request $r, $dbId, $since, $until) use ($app, $settings) {
	$dbSettings = $settings->databases[$dbId] ?? null;
	if (!$dbSettings) throw new Exception("ID $dbId unknown.");

	$db = ViewDatabase::new($dbSettings);

    $since = CovleDate::fromMilliseconds($since);
    $until = CovleDate::fromMilliseconds($until);

    $page = (int) $r->query->get('page', 0);
    $perPage = 30;
    $offset = $page * $perPage;

    $bags = $db->getBagsSince($since, $until, $perPage, $offset);

    $log = [];
    foreach ($bags as $bag) {
        $log[] = $bag->toShowable();
    }

    //Calc paging
	$totalRows = $db->getCount($since, $until);

    $result = [
        'log' => $log,
		'page' => $page,
		'pageCount' => ceil($totalRows / $perPage)
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

//region Preflight
$app->after(function (Request $request, Response $response) {
	$response->headers->set('Access-Control-Allow-Origin', '*');
	$response->headers->set('Access-Control-Allow-Headers', 'X-Auth-Token,Content-Type');
	$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

	return $response;
});

$app->options('/{whatever}', function(Request $r) use ($app){

	$response = new \Symfony\Component\HttpFoundation\Response(json_encode(['status'=>'ok']));

	$response->headers->set('Access-Control-Allow-Origin', '*');
	$response->headers->set('Access-Control-Allow-Headers', 'X-Auth-Token,Content-Type');
	$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

	return $response;
})->assert("whatever", ".*");
//endregion

$app->run();