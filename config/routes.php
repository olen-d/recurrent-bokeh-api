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
    $group->get('/attributes/values', \App\module\attributes\action\Attributes_fetch_values_list_action::class);
    $group->get('/attributes/post/id/{id}', \App\module\attributes\action\Attributes_fetch_list_by_post_action::class);
    $group->get('/categories', \App\module\categories\action\Categories_fetch_list_action::class);
    $group->get('/categories/post/id/{id}', \App\module\categories\action\Categories_fetch_list_by_post_action::class);
    $group->get('/categories/posts', \App\module\categories\action\Categories_fetch_list_and_posts_action::class);
    $group->get('/post/{slug}', \App\module\posts\action\Posts_fetch_by_slug_action::class);
    $group->get('/posts', \App\module\posts\action\Posts_fetch_list_action::class);
    $group->get('/posts/after/{datetime}', \App\module\posts\action\Posts_fetch_list_after_action::class);
    $group->get('/posts/before/{datetime}', \App\module\posts\action\Posts_fetch_list_before_action::class);
    $group->get('/posts/previous/{datetime}', \App\module\posts\action\Posts_fetch_previous_action::class);
    $group->get('/posts/next/{datetime}', \App\module\posts\action\Posts_fetch_next_action::class);
    $group->get('/posts/attribute/{slug}/after/{datetime}', \App\module\posts\action\Posts_fetch_list_by_attribute_after_action::class);
    $group->get('/posts/attribute/{slug}/before/{datetime}', \App\module\posts\action\Posts_fetch_list_by_attribute_before_action::class);
    $group->get('/posts/category/{slug}', \App\module\posts\action\Posts_fetch_list_by_category_action::class);
    $group->get('/posts/category/{slug}/after/{datetime}', \App\module\posts\action\Posts_fetch_list_by_category_after_action::class);
    $group->get('/posts/category/{slug}/before/{datetime}', \App\module\posts\action\Posts_fetch_list_by_category_before_action::class);
    $group->get('/posts/discussed/after[/{key}]', \App\module\posts\action\Posts_fetch_list_discussed_after_action::class);
    $group->get('/posts/discussed/before[/{key}]', \App\module\posts\action\Posts_fetch_list_discussed_before_action::class);
    $group->get('/posts/favorite', \App\module\posts\action\Posts_fetch_list_favorite_action::class);
    $group->get('/posts/tag/{slug}/after/{datetime}', \App\module\posts\action\Posts_fetch_list_by_tag_after_action::class);
    $group->get('/posts/tag/{slug}/before/{datetime}',\App\module\posts\action\Posts_fetch_list_by_tag_before_action::class);
    $group->get('/tags', \App\module\tags\action\Tags_fetch_values_list_action::class);
    $group->get('/tags/post/id/{id}', \App\module\tags\action\Tags_fetch_list_by_post_action::class);
    $group->get('/pixel', \App\resources\migrations\convert\Pixelpost_create_slugs::class);
    $group->get('/pixelcat', \App\resources\migrations\Pixelpost_create_category_slugs::class);
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
