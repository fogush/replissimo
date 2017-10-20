<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Rpodwika\Silex\YamlConfigServiceProvider;
use Replissimo\DatabaseHelper;
use Replissimo\DumpRunner;

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());
$app->register(new YamlConfigServiceProvider(__DIR__ . '/../config/parameters.yml'));
$app->register(new DoctrineServiceProvider(), [
    'db.options' => $app['config']['database'],
]);

$app['database_helper'] = function ($app) {
    return new DatabaseHelper($app['db'], $app['config']['replissimo']);
};
$app['dump_runner'] = function ($app) {
    return new DumpRunner($app['database_helper']);
};

return $app;
