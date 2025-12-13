<?php
namespace App\module\categories\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class Categories_fetch_list_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response): Response {
    $categories = $this->pdo->query(
      'SELECT id, name, slug, alt_name
      FROM pixelpost_categories ORDER BY name ASC'
    );

    $categories_processed = [];
    foreach ($categories as $category) {
      [$id, $name, $slug, $alt_name] = $category;
      array_push(
        $categories_processed,
        [
          'id' => $id, 
          'name' => $name,
          'slug' => $slug,
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
