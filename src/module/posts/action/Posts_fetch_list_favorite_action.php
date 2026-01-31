<?php
namespace App\module\posts\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class Posts_fetch_list_favorite_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response): Response {

    $queryParams = $request->getQueryParams();
    $page_size = array_key_exists('limit', $queryParams) ? $queryParams['limit'] : 12;

    $sql = 
      "SELECT id, datetime, headline, slug, body, image, alt_headline, alt_body, comments, exif_info
      FROM pixelpost_pixelpost WHERE sd_photog_fav = '1' ORDER BY datetime DESC LIMIT :limit";

    $sth = $this->pdo->prepare($sql);
    $sth->bindValue(':limit', $page_size, PDO::PARAM_INT);
    $sth->execute();
    $posts = $sth->fetchAll();

    $posts_processed = [];
    foreach ($posts as $post) {
      [$id, $datetime, $headline, $slug, $body, $image, $alt_headline, $alt_body, $comments, $exif_info] = $post;
      array_push(
        $posts_processed,
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
        ]
        );
    }
    
    $response_array = [
      'status' => 'success',
      'data' => $posts_processed
    ];
    $output_json = json_encode($response_array);

    $response->getBody()->write($output_json);
    return $response->withHeader('Content-Type', 'application/json');
  }
}
?>
