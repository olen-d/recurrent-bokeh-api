<?php
namespace App\module\tags\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class Tags_fetch_list_by_post_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response, array $args): Response {
    $post_id = $args['id'];

    $sql =
      'SELECT tag, alt_tag
      FROM pixelpost_tags 
      WHERE img_id = ?';

    $sth = $this->pdo->prepare($sql);
    $sth->execute([$post_id]);
    $results = $sth->fetchAll();

    $results_processed = [];
    foreach ($results as $result) {
      [$tag, $alt_tag] = $result;
      array_push(
        $results_processed,
        [
          'tag' => $tag,
          'name' => str_replace('_', ' ', $tag),
          'alt_tag' => $alt_tag
        ]
        );
    }
    
    $response_array = [
      'status' => 'success',
      'data' => $results_processed
    ];
    $output_json = json_encode($response_array);

    $response->getBody()->write($output_json);
    return $response->withHeader('Content-Type', 'application/json');
  }
}
?>
