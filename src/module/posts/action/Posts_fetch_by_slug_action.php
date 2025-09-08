<?php
namespace App\module\posts\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class Posts_fetch_by_slug_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response, array $args): Response {
    $slug = $args['slug'];
    $slug_decoded = urldecode($slug);

    $sql =
      'SELECT id, datetime, headline, slug, body, image, alt_headline, alt_body, comments, exif_info
      FROM pixelpost_pixelpost WHERE slug = ? LIMIT 1';

    $sth = $this->pdo->prepare($sql);
    $sth->execute([$slug_decoded]);
    $result = $sth->fetch();

    [$id, $datetime, $headline, $slug, $body, $image, $alt_headline, $alt_body, $comments, $exif_info] = $result;

    $post_processed =
    [
      'id' => $id, 
      'datetime' => $datetime,
      'headline' => $headline,
      'slug' => $slug,
      'body' => $body,
      'image' => $image,
      'alt_headline' => $alt_headline,
      'alt_body' => $alt_body,
      'comments' => $comments
    ];

    $response_array = [
      'status' => 'success',
      'data' => $post_processed
    ];

    $output_json = json_encode($response_array);

    $response->getBody()->write($output_json);
    return $response->withHeader('Content-Type', 'application/json');
  }
}
?>
