<?php

use DI\ContainerBuilder;
use Slim\App;

use Database_connection\Database_connection;

require_once __DIR__ . '/../vendor/autoload.php';


$container = (new ContainerBuilder())
  ->addDefinitions(__DIR__ . '/container.php')
  ->build();

return $container->get(App::class);
?>
