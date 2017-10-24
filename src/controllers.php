<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//Request::setTrustedProxies(['127.0.0.1']);

$app->get('/', function () use ($app) {    
    $databases = $app['database_helper']->getAllowedDatabases();

    return $app['twig']->render('index.html.twig', ['databases' => $databases]);
});

$app->post('/run', function (Request $request) use ($app) {
    try {
        $databaseToCopy = $request->get('database');
        $userName = $request->get('user_name');
        $newDatabase = "_{$databaseToCopy}_{$userName}";

        $databaseHelper = $app['database_helper'];
        if ($databaseHelper->isDatabaseNameValid($newDatabase)) {
            return new Response("Database name '$newDatabase' is invalid", 400);
        }

        $databases = $databaseHelper->getAllowedDatabases();

        if (!in_array($databaseToCopy, $databases)) {
            return new Response("Database '$databaseToCopy' doesn't exist on the server.", 400);
        }
        if (in_array($newDatabase, $databases)) {
            return new Response(
                "Database '$newDatabase' already exists. You have to use another name or delete it.",
                400
            );
        }

        $databaseHelper->createDatabase($newDatabase);

        $connectionSettings = $app['config']['database'];
        $app['dump_runner']->copyDatabase($databaseToCopy, $newDatabase, $connectionSettings);

        return new Response('', 200);
    } catch (Exception $exception) {
        return new Response($exception->getMessage(), 400);
    }
})->bind('run');

$app->get('/check', function (Request $request) use ($app) {
    try {
        $databaseToCopy = $request->get('database');
        $userName = $request->get('user_name');
        $newDatabase = "_{$databaseToCopy}_{$userName}";

        if ($app['database_helper']->isDatabaseNameValid($newDatabase)) {
            return new Response("Database name '$newDatabase' is invalid", 400);
        }

        //Returns: running - nothing,
        //not running and there are logs - an error occurred,
        //not running and no logs - finished successfully
        $stillRunning = $app['dump_runner']->checkRunning($newDatabase);
        if ($stillRunning) {
            return $app->json(['finished' => false, 'resultMessage' => '']);
        }

        $logs = $app['dump_runner']->getLogs($newDatabase);
        if ($logs) {
            $resultMessage = 'Something went wrong: <br>' . nl2br($logs);
            $status = 400;
        } else {
            $resultMessage = "Database '$databaseToCopy' has been copied into '$newDatabase'. Perfetto!";
            $status = 200;
        }
        return $app->json(['finished' => true, 'resultMessage' => $resultMessage], $status);
    } catch (Exception $exception) {
        $resultMessage = 'Something went wrong: <br>' . nl2br($exception->getMessage());
        return $app->json(['finished' => false, 'resultMessage' => $resultMessage], 400);
    }
})->bind('check');

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = [
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    ];

    return new Response($app['twig']->resolveTemplate($templates)->render(['code' => $code]), $code);
});
