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
    $api_base_url = $_ENV['API_BASE_URL'];

    $slug = $args['slug'];
    $slug_decoded = urldecode($slug);

    $sql =
      'SELECT id, datetime, headline, slug, body, image, alt_headline, alt_body, comments, exif_info
      FROM pixelpost_pixelpost WHERE slug = ? LIMIT 1';

    $sth = $this->pdo->prepare($sql);
    $sth->execute([$slug_decoded]);
    $result = $sth->fetch();

    [$id, $datetime, $headline, $slug, $body, $image, $alt_headline, $alt_body, $comments, $exif_info] = $result;

    // Get any attributes associated with the post
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$api_base_url}/attributes/post/id/{$id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response_attributes = curl_exec($ch);
    curl_reset($ch);

    // Get any categories associated with the post
    curl_setopt($ch, CURLOPT_URL, "{$api_base_url}/categories/post/id/{$id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response_categories = curl_exec($ch);
    curl_reset($ch);

    // Get any tags associated with the post
    curl_setopt($ch, CURLOPT_URL, "{$api_base_url}/tags/post/id/{$id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response_tags = curl_exec($ch);

    curl_close($ch);

    $attributes = [];
    if ($response_attributes === false) {
      // Log an error
    } else {
      $attributes_json = json_decode($response_attributes);
      $attributes = $attributes_json->data;
    }
  
    $categories = [];
    if($response_categories === false) {
      // Log an error
    } else {
      $categories_json = json_decode($response_categories);
      $categories = $categories_json->data;
    }
  
    $tags = [];
    if($response_tags === false) {
      // Log an error
    } else {
      $tags_json = json_decode($response_tags);
      $tags = $tags_json->data;
    }
  
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
      'comments' => $comments,
      'attributes' => $attributes,
      'categories' => $categories,
      'tags' => $tags
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
