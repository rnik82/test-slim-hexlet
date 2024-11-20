<?php

// Все обработчики ниже можно протестировать через локальный сервер http://localhost:8080/

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
// Включаем поддержку переопределения метода в Slim
use Slim\Middleware\MethodOverrideMiddleware;

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
// Включаем поддержку переопределения метода в Slim
$app->add(MethodOverrideMiddleware::class);

// Получаем роутер — объект, отвечающий за хранение и обработку маршрутов
$router = $app->getRouteCollector()->getRouteParser();

// return $response->getBody()->write('Welcome to Slim!');
// Благодаря пакету slim/http этот же код можно записать короче
// return $response->write('Welcome to Slim!');

$app->get('/', function ($request, $response) {
  return $this->get('renderer')->render($response, 'index.phtml');
});

// Обработчик для страницы с формой, которую заполняет пользователь.
// Эта форма отправляет POST-запрос на адрес /users, указанный в атрибуте action
$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('users.create');

// $repo = new App\UserRepository(); это не нужно, так как используем файл

// Обработка данных формы post
$app->post('/users', function ($request, $response) use ($router) {

    // С помощью $request->getParsedBodyParam мы получаем данные из формы (то что вводит пользователь, шаблон new.phtml)
    $user = $request->getParsedBodyParam('user'); // асс массив, типа ["nickname" => "Igor","email" => "Igor@mail.ru"]
    //dd($user);
    $validator = new SlimHexlet\Validator();
    // Проверяем корректность данных
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
      // Если данные корректны, то сохраняем, добавляем флеш и выполняем редирект

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

      // Добавление флеш-сообщения. Оно станет доступным на следующий HTTP-запрос.
      // 'success' — тип флеш-сообщения. Используется при выводе для форматирования.
      // Например, можно ввести тип success и отражать его зеленым цветом
      $this->get('flash')->addMessage('success', 'User was added successfully');

      // После добавления данных в файл происходит редирект на адрес /users
      return $response->withRedirect($router->urlFor('users.index'), 302);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    // Если возникли ошибки, то устанавливаем код ответа в 422 и рендерим форму с указанием ошибок
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, "users/new.phtml", $params);

})->setName('users.store');

// Обработчик с добавленной формой поиска для массива users (see template in templates/users/index.phtml)
$app->get('/users', function ($request, $response) {

  // Извлечение flash-сообщений, установленных на предыдущем запросе
  $messages = $this->get('flash')->getMessages();
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
    return $response->write("There is no user with id = {$id}")
              ->withStatus(404);
  }
  // Данные из обработчика нужно сохранить и затем передать в шаблон в виде
  // ассоциативного массива. Передается третьим параметром в метод render
  $params = ['id' => $id, 'nickname' => $user['nickname'], 'email' => $user['email']];
  // Указанный путь ('/users/{id}') считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
  // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
  // $this в Slim это контейнер зависимостей
  return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users.show');

// Редактирование юзера
$app->get('/users/{id}/edit', function ($request, $response, array $args) {
    //$post = $repo->find($args['id']); // не используем репозиторий (БД), вместо этого используем файл
    $id = $args['id'];
    //dd($id);
    $data = file_get_contents('data.txt'); // читаем файл (просто строка)
    //dd($data);
    $users = json_decode($data, true); // преобразуем в асс массив
    //dd(gettype($users));
    $user = $users[$id];
    //dd($user);

    $params = [
        'user' => $user,
        'errors' => [],
        'userData' => $user
    ];
  return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

$app->patch('/users/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $data = file_get_contents('data.txt'); // читаем файл (просто строка)
    $users = json_decode($data, true); // преобразуем в асс массив
    $user = $users[$id];
    // С помощью $request->getParsedBodyParam мы получаем данные из формы,
    // то что вводит пользователь (шаблон new.phtml)
    $userData = $request->getParsedBodyParam('user');

    $validator = new SlimHexlet\Validator();
    $errors = $validator->validate($userData);

    if (count($errors) === 0) {
        $user['nickname'] = $userData['nickname'];
        $user['email'] = $userData['email'];
        //dd($userData['name']);
        //$repo->save($post); // не используем репозиторий (БД), вместо этого используем файл

        // Обновляем юзера в списке
        $users[$id] = $user;
        // Получаем JSON-представление данных (users) в виде строки
        $usersJson = json_encode($users);
        // Записываем данные о user в файл (JSON-представление)
        file_put_contents('data.txt', $usersJson . "\n");
        $this->get('flash')->addMessage('success', 'User has been updated');
        return $response->withRedirect($router->urlFor('users.index'));
    }

    $params = [
        'user' => $user,
        'userData' => $postData,
        'errors' => $errors
    ];

    return $this->get('renderer')
              ->render($response->withStatus(422), 'users/edit.phtml', $params);
});

$app->delete('/users/{id}', function ($request, $response, array $args) use ($router) {
  $id = $args['id'];
  //$repo->destroy($id);
  $data = file_get_contents('data.txt'); // читаем файл (просто строка)
  $users = json_decode($data, true); // преобразуем в асс массив
  $users[$id] = null; // удаляем пользователя - перезаписываем значение на null
  // Получаем JSON-представление данных (users) в виде строки
  $usersJson = json_encode($users);
  // Записываем новые данные в файл (JSON-представление)
  file_put_contents('data.txt', $usersJson . "\n");
  $this->get('flash')->addMessage('success', 'User has been removed');
  return $response->withRedirect($router->urlFor('users.index'));
});


$app->run();