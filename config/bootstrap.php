<?php

use DI\ContainerBuilder;
use Slim\App;

use Database_connection\Database_connection;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$container = (new ContainerBuilder())
  ->addDefinitions(__DIR__ . '/container.php')
  ->build();

return $container->get(App::class);
?>
