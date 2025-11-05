<?php
namespace App\module\posts\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\common\Datetime_uri_converter;

final class Posts_fetch_list_discussed_before_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response, array $args): Response {
    $queryParams = $request->getQueryParams();
    $page_order = $queryParams['count'];
    $page_size = $queryParams['limit'];

    $page_order_decoded = urldecode($page_order);

    $current_datetime = date("Y-m-d H:i:s");
  
    $dtc = new Datetime_uri_converter();

    $sql_order = $page_order_decoded === 'most' ? 'DESC' : 'ASC';

    $page_key = array_key_exists('key', $args) ? $args['key'] : false; // key is in the format of comment count, datetime, id
    $page_key_decoded = $page_key ? urldecode($page_key) : 'none';

    $page_key_pattern = '/[0-9]+d[0-9]{14}d[0-9]+/';

    $discussed_posts = '';

    if(preg_match($page_key_pattern, $page_key_decoded)) {
      $page_keys = explode('d', $page_key_decoded);

      [$page_comments, $page_datetime, $page_id] = $page_keys;

      $page_datetime_mysql = $dtc->datetime_uri_to_mysql($page_datetime);

      $sql =
        "SELECT * FROM (
          SELECT pixelpost_pixelpost.id, pixelpost_pixelpost.datetime, headline, slug, body, image, alt_headline, alt_body, comments, exif_info, 
          COUNT(pixelpost_comments.parent_id) AS comment_count
          FROM pixelpost_comments INNER JOIN pixelpost_pixelpost ON pixelpost_comments.parent_id = pixelpost_pixelpost.id
          WHERE (pixelpost_pixelpost.datetime <= :current_datetime)
          GROUP BY pixelpost_comments.parent_id
          ORDER BY comment_count {$sql_order}, pixelpost_pixelpost.datetime {$sql_order}, pixelpost_pixelpost.id {$sql_order}
        ) AS page_discussed
        WHERE comment_count <= :page_comments
          AND ( comment_count < :page_comments OR ( datetime <= :page_datetime
            AND ( datetime < :page_datetime OR id < :page_id )
          )
        ) LIMIT :limit";

      $sth = $this->pdo->prepare($sql);
      $sth->bindParam(':current_datetime', $current_datetime, PDO::PARAM_STR);
      $sth->bindParam(':page_comments', $page_comments, PDO::PARAM_INT);
      $sth->bindParam(':page_datetime', $page_datetime_mysql, PDO::PARAM_STR);
      $sth->bindParam(':page_id', $page_id, PDO::PARAM_INT);
      $sth->bindValue(':limit', $page_size + 1, PDO::PARAM_INT);
      $sth->execute();
      $discussed_posts = $sth->fetchAll();
    } else {
      $sql =
        "SELECT pixelpost_pixelpost.id, pixelpost_pixelpost.datetime, headline, slug, body, image, alt_headline, alt_body, comments, exif_info, 
        COUNT(pixelpost_comments.parent_id) AS comment_count
        FROM pixelpost_comments INNER JOIN pixelpost_pixelpost ON pixelpost_comments.parent_id = pixelpost_pixelpost.id
        WHERE (pixelpost_pixelpost.datetime <= :current_datetime)
        GROUP BY pixelpost_comments.parent_id
        ORDER BY comment_count {$sql_order}, pixelpost_pixelpost.datetime {$sql_order}, pixelpost_pixelpost.id {$sql_order} LIMIT :limit";

      $sth = $this->pdo->prepare($sql);
      $sth->bindParam(':current_datetime', $current_datetime, PDO::PARAM_STR);
      $sth->bindValue(':limit', $page_size + 1, PDO::PARAM_INT);
      $sth->execute();
      $discussed_posts = $sth->fetchAll(); 
    }

    $prev_page_post = sizeof($discussed_posts) < $page_size ? [] : array_pop($discussed_posts);

    $posts_processed = [];
    foreach ($discussed_posts as $post) {
      [$id, $datetime, $headline, $slug, $body, $image, $alt_headline, $alt_body, $comments, $exif_info, $comment_count] = $post;
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
          'comments' => $comments,
          'comment_count' => $comment_count
        ]
      );
    }
    
    $pagination = [
      'limit' => $page_size,
      'hasPreviousPage' => false,
      'hasNextPage' => false
    ];

    $self_key = $page_key;
    $links =['self' => "/posts/discussed/before/{$self_key}?count={$page_order}?limit=$page_size"];

    if (sizeof($prev_page_post)> 0) {
      $prev_post = end($posts_processed);
      [
        'comment_count' => $prev_comment_count,
        'datetime' => $prev_datetime,
        'id' => $prev_id,
      ] = $prev_post;
      $prev_datetime_uri = $dtc->datetime_mysql_to_uri($prev_datetime);
      reset($posts_processed);

      $pagination['hasPreviousPage'] = true;
      $prev_key = "{$prev_comment_count}d{$prev_datetime_uri}d{$prev_id}";
      $pagination['previousKey'] = $prev_key;

      $links['previous'] = "/posts/discussed/before/{$prev_comment_count}d{$prev_datetime_uri}d{$prev_id}?count={$page_order_decoded}&limit={$page_size}";
    }

    reset($posts_processed);
    $latest_post = current($posts_processed);
    [
      'comment_count' => $next_comment_count,
      'datetime' => $next_datetime_mysql,
      'id' => $next_id,
    ] = $latest_post;

    $sql_order_reverse = $sql_order === 'ASC' ? 'DESC' : 'ASC';

    $sql_next =
      "SELECT * FROM (
        SELECT pixelpost_pixelpost.id, pixelpost_pixelpost.datetime, headline, slug, body, image, alt_headline, alt_body, comments, exif_info, 
        COUNT(pixelpost_comments.parent_id) AS comment_count
        FROM pixelpost_comments INNER JOIN pixelpost_pixelpost ON pixelpost_comments.parent_id = pixelpost_pixelpost.id
        WHERE (pixelpost_pixelpost.datetime <= :current_datetime)
        GROUP BY pixelpost_comments.parent_id
        ORDER BY comment_count {$sql_order_reverse}, pixelpost_pixelpost.datetime {$sql_order_reverse}, pixelpost_pixelpost.id {$sql_order_reverse}
      ) AS next_discussed
      WHERE comment_count >= :next_comment_count
        AND ( comment_count > :next_comment_count OR ( datetime >= :next_datetime
          AND ( datetime > :next_datetime OR id > :next_id )
        )
      ) LIMIT 1";
    $sth_next = $this->pdo->prepare($sql_next);
    $sth_next->bindParam(':current_datetime', $current_datetime, PDO::PARAM_STR);
    $sth_next->bindParam(':next_comment_count', $next_comment_count, PDO::PARAM_INT);
    $sth_next->bindParam(':next_datetime', $next_datetime_mysql, PDO::PARAM_STR);
    $sth_next->bindParam(':next_id', $next_id, PDO::PARAM_INT);
    $sth_next->execute();
    $next_posts = $sth_next->fetchAll();
  
    if (sizeof($next_posts) > 0) {
      $next_datetime_uri = $dtc->datetime_mysql_to_uri($next_datetime_mysql);

      $pagination['hasNextPage'] = true;
      $next_key = "{$next_comment_count}d{$next_datetime_uri}d{$next_id}";
      $pagination['nextKey'] = $next_key;

      $links['next'] = "/posts/discussed/after/{$next_comment_count}d{$next_datetime_uri}d{$next_id}?count={$page_order_decoded}&limit={$page_size}";
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
