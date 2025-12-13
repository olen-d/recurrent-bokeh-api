<?php
namespace App\module\posts\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class Posts_fetch_list_by_category_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response, array $args): Response {
    $slug = $args['slug'];
    $slug_decoded = urldecode($slug);

    $sql =
      'SELECT pixelpost_pixelpost.id AS post_id, datetime, headline, pixelpost_pixelpost.slug AS post_slug, body, 
      image, alt_headline, alt_body, comments, exif_info, pixelpost_categories.id as category_id, name, 
      pixelpost_categories.slug as category_slug 
      FROM pixelpost_pixelpost INNER JOIN (pixelpost_catassoc, pixelpost_categories) 
      ON (pixelpost_catassoc.image_id = pixelpost_pixelpost.id AND pixelpost_categories.id = pixelpost_catassoc.cat_id) 
      WHERE pixelpost_categories.slug = ? ORDER BY datetime DESC';

    $sth = $this->pdo->prepare($sql);
    $sth->execute([$slug_decoded]);
    $posts = $sth->fetchAll();

    $posts_processed = [];
    foreach ($posts as $post) {
      [$post_id, $datetime, $headline, $post_slug, $body, $image, $alt_headline, $alt_body, $comments, $exif_info] = $post;
      array_push(
        $posts_processed,
        [
          'id' => $post_id, 
          'datetime' => $datetime,
          'headline' => $headline,
          'slug' => $post_slug,
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
