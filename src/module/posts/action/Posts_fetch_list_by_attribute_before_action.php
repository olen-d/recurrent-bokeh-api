<?php
namespace App\module\posts\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\common\Datetime_uri_converter;

final class Posts_fetch_list_by_attribute_before_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response, array $args): Response {
    $page_datetime = $args['datetime'];
    $page_datetime_decoded = urldecode($page_datetime);

    $slug = $args['slug'];
    $slug_decoded = urldecode($slug);

    $queryParams = $request->getQueryParams();
    $page_size = array_key_exists('limit', $queryParams) ? $queryParams['limit'] : 12;

    $dtc = new Datetime_uri_converter();
    $page_datetime_mysql = $dtc->datetime_uri_to_mysql($page_datetime_decoded);
  
    $sql =
      'SELECT pixelpost_pixelpost.id AS post_id, datetime, headline, pixelpost_pixelpost.slug AS post_slug, body, 
      image, alt_headline, alt_body, comments, exif_info 
      FROM pixelpost_pixelpost INNER JOIN (pixelpost_customfieldsvalues, pixelpost_customfields) 
      ON (pixelpost_customfieldsvalues.parent_id = pixelpost_pixelpost.id AND pixelpost_customfields.id = pixelpost_customfieldsvalues.customfield_id) 
      WHERE datetime < :datetime AND pixelpost_customfieldsvalues.value = :slug ORDER BY datetime DESC, pixelpost_pixelpost.id DESC LIMIT :limit';

    $sth = $this->pdo->prepare($sql);
    $sth->bindParam(':datetime', $page_datetime_mysql, PDO::PARAM_STR);
    $sth->bindParam(':slug', $slug_decoded, PDO::PARAM_STR);
    $sth->bindValue(':limit', $page_size + 1, PDO::PARAM_INT);
    $sth->execute();
    $previous_posts = $sth->fetchAll();
    $prev_page_post = sizeof($previous_posts) <= $page_size ? [] : array_pop($previous_posts);

    $posts_processed = [];
    foreach ($previous_posts as $post) {
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
    
    $pagination = [
      'limit' => $page_size,
      'hasPreviousPage' => false,
      'hasNextPage' => false
    ];

    $links =['self' => "/posts/attribute/{$slug}/before/{$page_datetime_decoded}?limit=$page_size"];

    if (sizeof($prev_page_post)> 0) {
      $prev_datetime = end($posts_processed)['datetime'];
      $prev_datetime_uri = $dtc->datetime_mysql_to_uri($prev_datetime);
      reset($posts_processed);

      $pagination['hasPreviousPage'] = true;
      $pagination['previousDatetime'] = $prev_datetime_uri;

      $links['previous'] = "/posts/attribute/{$slug}/before/{$prev_datetime_uri}?limit=$page_size";
    }

    reset($posts_processed);
    $after_datetime_mysql = sizeof($posts_processed) > 0 ? current($posts_processed)['datetime'] : $page_datetime_mysql;

    $sql_next =
      'SELECT pixelpost_pixelpost.id AS post_id, datetime, headline, pixelpost_pixelpost.slug AS post_slug, body,
      image, alt_headline, alt_body, comments, exif_info
      FROM pixelpost_pixelpost INNER JOIN (pixelpost_customfieldsvalues, pixelpost_customfields)
      ON (pixelpost_customfieldsvalues.parent_id = pixelpost_pixelpost.id AND pixelpost_customfields.id = pixelpost_customfieldsvalues.customfield_id)
      WHERE datetime > :datetime AND pixelpost_customfieldsvalues.value = :slug ORDER BY datetime ASC, pixelpost_pixelpost.id ASC LIMIT 1';

    $sth_next = $this->pdo->prepare($sql_next);
    $sth_next->bindParam(':datetime', $after_datetime_mysql, PDO::PARAM_STR);
    $sth_next->bindParam(':slug', $slug_decoded, PDO::PARAM_STR);
    $sth_next->execute();
    $next_posts = $sth_next->fetchAll();
  
    if (sizeof($next_posts) > 0) {
      $next_datetime_uri = $dtc->datetime_mysql_to_uri($after_datetime_mysql);

      $pagination['hasNextPage'] = true;
      $pagination['nextDatetime'] = $next_datetime_uri;

      $links['next'] = "/posts/attribute/{$slug}/after/{$next_datetime_uri}?limit=$page_size";
    } 

    $response_array = [
      'status' => 'success',
      'data' => $posts_processed,
      'pagination' => $pagination,
      'links' => $links
    ];

    $output_json = json_encode($response_array);

    $response->getBody()->write($output_json);
    return $response->withHeader('Content-Type', 'application/json');
  }
}
?>
