<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
  $app->group('/api/v1', function (RouteCollectorProxy $group) {
    $group->get('', function (ServerRequestInterface $request, ResponseInterface $response) {
      $response->getBody()->write('I Love Doritos');
  
      return $response;
    });
    $group->get('/post', \App\module\posts\action\Posts_fetch_current_action::class);
    // Stud for getting an arbitrary post by id
    // $group->get('/post/{id:[0-9]+}', \App\module\posts\action\Posts_fetch_by_id_action::class);
    $group->get('/post/{slug}', \App\module\posts\action\Posts_fetch_by_slug_action::class);
    $group->get('/posts', \App\module\posts\action\Posts_fetch_list_action::class);
    $group->get('/posts/previous/{datetime}', \App\module\posts\action\Posts_fetch_previous_action::class);
    $group->get('/posts/next/{datetime}', \App\module\posts\action\Posts_fetch_next_action::class);
    $group->get('/pixel', \App\resources\migrations\convert\Pixelpost_create_slugs::class);
    $group->get('/doritos', App\application\middlware\Cors_middleware::class);
  })->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
  });
};
?>
