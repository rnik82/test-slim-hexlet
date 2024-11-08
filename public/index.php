<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

//$app->addErrorMiddleware(true, true, true);

// return $response->getBody()->write('Welcome to Slim!');
// Благодаря пакету slim/http этот же код можно записать короче
// return $response->write('Welcome to Slim!');

$app = AppFactory::create();

$app->get('/', function ($request, $response) {
    return $response->write('GET /');
});

$app->get('/users', function ($request, $response) {
  return $response->write('GET /users');
});

$app->get('/companies', function ($request, $response) {
    return $response->write('GET /companies');
});

$app->post('/companies', function ($request, $response) {
    return $response->write('POST /companies');
});

$app->post('/users', function ($request, $response) {
  return $response->withStatus(302);
});

$app->run();