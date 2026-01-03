<?php
namespace App\module\attributes\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class Attributes_fetch_list_by_post_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response, array $args): Response {
    $post_id = $args['id'];

    $sql =
      "SELECT pixelpost_customfields.id, pixelpost_customfields.name, pixelpost_customfields.alt_name, 
      pixelpost_customfieldsvalues.value, pixelpost_customfieldsvalues.alt_value  
      FROM pixelpost_customfields INNER JOIN pixelpost_customfieldsvalues 
      ON pixelpost_customfields.id = pixelpost_customfieldsvalues.customfield_id 
      WHERE pixelpost_customfieldsvalues.parent_id = ? 
      AND pixelpost_customfields.visible = 'on' 
      AND pixelpost_customfields.enable = 'on' ORDER BY pixelpost_customfields.fieldorder ASC";
    $sth = $this->pdo->prepare($sql);
    $sth->execute([$post_id]);
    $attributes = $sth->fetchAll();

    $attributes_processed = [];
    foreach ($attributes as $attribute) {
      [$id, $name, $alt_name, $value, $alt_value] = $attribute;
      array_push(
        $attributes_processed,
        [
          'id' => $id, 
          'name' => $name,
          'altName' => $alt_name,
          'value' => $value,
          'altValue' => $alt_value
        ]
        );
    }
    
    $response_array = [
      'status' => 'success',
      'data' => $attributes_processed
    ];
    $output_json = json_encode($response_array);

    $response->getBody()->write($output_json);
    return $response->withHeader('Content-Type', 'application/json');
  }
}
?>
