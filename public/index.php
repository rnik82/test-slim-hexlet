<?php

// Все обработчики ниже можно протестировать через локальный сервер http://localhost:8080/

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

// return $response->getBody()->write('Welcome to Slim!');
// Благодаря пакету slim/http этот же код можно записать короче
// return $response->write('Welcome to Slim!');

// $app = AppFactory::create();

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

// Обработчик с добавленной формой поиска для массива users (see template in templates/users/index.phtml)
$app->get('/users', function ($request, $response) use ($users) {

  // таким способом извлекаем данные формы на сервере внутри фреймворка Slim
  $name = $request->getQueryParam('name');

  // фильтруем наш массив для вывода результатов поиска
  $filteredNames = array_filter($users, fn($user) => str_contains($user, $name));

  // Данные из обработчика нужно сохранить и затем передать в шаблон в виде
  // ассоциативного массива. Передается третьим параметром в метод render
  $params = ['users' => $filteredNames, 'name' => $name];

  // $this в Slim это контейнер зависимостей.
  // Метод render() выполняет рендеринг указанного шаблона и добавляет результат в ответ
  return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});


// Обработчик с шаблонизатором (see template in templates/users/show.phtml)
$app->get('/users/{id}', function ($request, $response, $args) {
  // Данные из обработчика нужно сохранить и затем передать в шаблон в виде
  // ассоциативного массива. Передается третьим параметром в метод render
  $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
  // Указанный путь ('/users/{id}') считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
  // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
  // $this в Slim это контейнер зависимостей
  return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
  // Любая изменяемая часть маршрута, то что внутри {}, называется плейсхолдером — заполнителем
  // Доступ к значению конкретного плейсхолдера осуществляется по имени через массив $args,
  // который передается третьим параметром в функцию-обработчик
  $id = $args['id'];
  return $response->write("Course id: {$id}");
});

$app->get('/', function ($request, $response) {
    return $response->write('GET /');
});

$app->get('/companies', function ($request, $response) {
    return $response->write('GET /companies');
});

// $app->post('/users', function ($request, $response) {
  // Так параметры извлекаются из объекта $request
  // getQueryParams() — извлекает все параметры
  // getQueryParam($name, $defaultValue) — извлекает значение конкретного параметра,
  // вторым параметром принимает значение по умолчанию
//  $page = $request->getQueryParam('page', 1); 
//  $per = $request->getQueryParam('per', 10);
  // Тут обработка
//  return $response;
//});

$app->post('/companies', function ($request, $response) {
    return $response->write('POST /companies');
});

$app->post('/users', function ($request, $response) {
  // Устанавливаем статус ответа (запрашиваемый ресурс был временно перемещён в новое местоположение)
  return $response->withStatus(302);
});

$app->run();