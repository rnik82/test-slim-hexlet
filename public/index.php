<?php

// Все обработчики ниже можно протестировать через локальный сервер http://localhost:8080/

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

// Старт PHP сессии для пакета slim/flash
session_start();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

// Подключение к проекту пакет slim/flash
$container->set('flash', function () {
  return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

// Получаем роутер — объект, отвечающий за хранение и обработку маршрутов
$router = $app->getRouteCollector()->getRouteParser();

// return $response->getBody()->write('Welcome to Slim!');
// Благодаря пакету slim/http этот же код можно записать короче
// return $response->write('Welcome to Slim!');

// $app = AppFactory::create();

// Обработчик для страницы с формой, которую заполняет пользователь.
// Эта форма отправляет POST-запрос на адрес /users, указанный в атрибуте action
$app->get('/users/new', function ($request, $response) {
  $params = [
      'user' => ['nickname' => '', 'email' => ''],
      'errors' => []
  ];
  return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('users.create');

// $repo = new App\UserRepository(); - используем файл

// Обработка данных формы post
$app->post('/users', function ($request, $response) use ($router) {
    // $validator = new Validator();

    // Добавление флеш-сообщения. Оно станет доступным на следующий HTTP-запрос.
    // 'success' — тип флеш-сообщения. Используется при выводе для форматирования.
    // Например, можно ввести тип success и отражать его зеленым цветом
    $this->get('flash')->addMessage('success', 'User was added successfully');

    $user = $request->getParsedBodyParam('user'); // асс массив, типа ["nickname" => "Igor","email" => "Igor@mail.ru"]
    // Получаем JSON-представление данных (user) в виде строки
    //$userJson = json_encode($user); // {"nickname":"Igor","email":"igor@mail.ru"}
    // Читаем данные из файла data.txt в корне проекта
    $data = file_get_contents('data.txt'); // просто строка
    //print_r($data);
    // Берем JSON строку и преобразовываем её в PHP-значение, в данном случае в ассоц массив (так как true)
    $users = json_decode($data, true) ?? []; // асс массив
    //print_r($users);
    $id = count($users) + 1;
    $user['id'] = $id;
    $users[$id] = $user;
    // Получаем JSON-представление данных (users) в виде строки
    $usersJson = json_encode($users);
    // Записываем данные о user в файл (JSON-представление)
    file_put_contents('data.txt', $usersJson . "\n"); //  . "\n", FILE_APPEND
    // После добавления данных в файл происходит редирект на адрес /users
    return $response->withRedirect($router->urlFor('users.index'), 302);

    // $errors = $validator->validate($user);
    // if (count($errors) === 0) {
        // Если ошибок нет, то данные формы сохраняются, например, в базу данных
        // $repo->save($user);
        // После добавления данных в файл происходит редирект на адрес /users
    //     return $response->withRedirect('/users', 302);
    // }
    // $params = [
    //     'user' => $user,
    //     'errors' => $errors
    // ];
    // return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('users.store');

// Обработчик с добавленной формой поиска для массива users (see template in templates/users/index.phtml)
$app->get('/users', function ($request, $response) {

  // Извлечение flash-сообщений, установленных на предыдущем запросе
  $messages = $this->get('flash')->getMessages();
  //print_r($messages);
  // таким способом извлекаем данные формы на сервере внутри фреймворка Slim
  $nickname = $request->getQueryParam('nickname');

  // Читаем данные из файла data.txt в корне проекта
  $data = file_get_contents('data.txt');
  
  // Берем JSON строку и преобразовываем её в PHP-значение, в данном случае в ассоциативный массив (так как true)
  $usersAssArr = json_decode($data, true, 3);
  // получаем массив значений (каждое значение примерно таеон ["nickname" => "Igor","email" => "Igor@mail.ru"])
  $users = array_values($usersAssArr);
  // фильтруем наш массив для вывода результатов поиска
  $filteredUsers = array_filter($users, fn($user) => str_contains($user['nickname'], $nickname));

  // Данные из обработчика нужно сохранить и затем передать в шаблон в виде
  // ассоциативного массива. Передается третьим параметром в метод render
  $params = ['users' => $filteredUsers, 'nickname' => $nickname, 'flash' => $messages];

  // $this в Slim это контейнер зависимостей.
  // Метод render() выполняет рендеринг указанного шаблона и добавляет результат в ответ
  return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users.index');

// $users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];
// // Обработчик с добавленной формой поиска для массива users (see template in templates/users/index.phtml)
// $app->get('/users', function ($request, $response) use ($users) {

//   // таким способом извлекаем данные формы на сервере внутри фреймворка Slim
//   $name = $request->getQueryParam('name');

//   // фильтруем наш массив для вывода результатов поиска
//   $filteredNames = array_filter($users, fn($user) => str_contains($user, $name));

//   // Данные из обработчика нужно сохранить и затем передать в шаблон в виде
//   // ассоциативного массива. Передается третьим параметром в метод render
//   $params = ['users' => $filteredNames, 'name' => $name];

//   // $this в Slim это контейнер зависимостей.
//   // Метод render() выполняет рендеринг указанного шаблона и добавляет результат в ответ
//   return $this->get('renderer')->render($response, 'users/index.phtml', $params);
// });


// Обработчик с шаблонизатором (see template in templates/users/show.phtml)
$app->get('/users/{id}', function ($request, $response, $args) {
  // Получаем искомый id
  $id = $args['id'];
  // Читаем данные из файла data.txt
  $data = file_get_contents('data.txt'); // просто JSON в виде строки
  // Берем JSON строку и преобразовываем её в PHP-значение, в данном случае в ассоц массив (так как true)
  $users = json_decode($data, true); // асс массив всех users
  // Находим нужного user по id
  $user = $users[$id] ?? null;
  // Если не нашелся, то реализуем код ошибки 404
  if (!$user) {
    return $response->withStatus(404)->write("There is no user with id = {$id}");
  }
  // Данные из обработчика нужно сохранить и затем передать в шаблон в виде
  // ассоциативного массива. Передается третьим параметром в метод render
  $params = ['id' => $id, 'nickname' => $user['nickname'], 'email' => $user['email']];
  // Указанный путь ('/users/{id}') считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
  // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
  // $this в Slim это контейнер зависимостей
  return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users.show');

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

//$app->post('/users', function ($request, $response) {
  // Устанавливаем статус ответа (запрашиваемый ресурс был временно перемещён в новое местоположение)
//  return $response->withStatus(302);
//});

$app->run();