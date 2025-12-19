<?php
namespace App\module\categories\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class Categories_fetch_list_by_post_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response, array $args): Response {
    $post_id = $args['id'];

    $sql =
      'SELECT pixelpost_categories.id AS cat_id, name, pixelpost_categories.slug AS cat_slug, alt_name
      FROM pixelpost_categories INNER JOIN pixelpost_catassoc
      ON pixelpost_catassoc.cat_id = pixelpost_categories.id WHERE pixelpost_catassoc.image_id = ?';

    $sth = $this->pdo->prepare($sql);
    $sth->execute([$post_id]);
    $categories = $sth->fetchAll();

    $categories_processed = [];
    foreach ($categories as $category) {
      [$cat_id, $name, $cat_slug, $alt_name] = $category;
      array_push(
        $categories_processed,
        [
          'id' => $cat_id, 
          'name' => $name,
          'slug' => $cat_slug,
          'alt_name' => $alt_name
        ]
        );
    }
    
    $response_array = [
      'status' => 'success',
      'data' => $categories_processed
    ];
    $output_json = json_encode($response_array);

    $response->getBody()->write($output_json);
    return $response->withHeader('Content-Type', 'application/json');
  }
}
?>
