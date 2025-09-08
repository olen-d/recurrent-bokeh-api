<?php
use Psr\Container\containerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../src/classes/Database_connection.php';

use Database_connection\Database_connection;

use function DI\create;

return [
  'settings' => function () {
    return require __DIR__ . '/settings.php';
  },

  'Database_connection' => create(Database_connection::class),

  PDO::class => function (ContainerInterface $container) {
    $db_settings = $container->get('settings')['db'];
    $pdo = new PDO(
      "mysql:host={$db_settings['host']};dbname={$db_settings['dbname']}",
      $db_settings['user'],
      $db_settings['pass']
    );
    return $pdo;
  },

  App::class => function (ContainerInterface $container) {
    $app = AppFactory::createFromContainer($container);

    (require 'routes.php')($app);

    return $app;
  },
];
?>
