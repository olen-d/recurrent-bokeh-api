<?php
namespace App\module\tags\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class Tags_fetch_values_list_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response): Response {
    $queryParams = $request->getQueryParams();
    $classification = array_key_exists('classification', $queryParams) ? $queryParams['classification'] : 'logrithmic';
    $total_classes = array_key_exists('classes', $queryParams) ? $queryParams['classes'] : 5;
    $total_values = array_key_exists('limit', $queryParams) ? $queryParams['limit'] : 50;

    $sql = 'SELECT tag, COUNT(pixelpost_tags.tag) AS tag_count 
    FROM pixelpost_tags, pixelpost_pixelpost 
    WHERE pixelpost_tags.img_id = pixelpost_pixelpost.id 
    GROUP BY pixelpost_tags.tag ORDER BY tag_count DESC LIMIT :limit';

    $sth = $this->pdo->prepare($sql);
    $sth->bindValue(':limit', $total_values, PDO::PARAM_INT);
    $sth->execute();
    $results = $sth->fetchAll();

    $results_processed = [];
    foreach ($results as $result) {
      [$tag, $tag_count] = $result;

      array_push(
        $results_processed,
        [
          'tag' => $tag, 
          'name' => str_replace('_', ' ', $tag),
          'count' => $tag_count
        ]
        );
    }

    $counts = array_map(function($n) { if ($n['count']) return $n['count']; }, $results_processed);
    $count_min = min($counts);
    $count_max = max($counts);
    $class = $total_classes;

    $meta = [];
    $meta += ['count_minimum' => $count_min];
    $meta += ['count_maximum' => $count_max];


    if ($classification === 'logrithmic') {
      $count_max_log = log($count_max);
      $range_log = $count_max_log - log($count_min);
      $class_size_log = $range_log / $total_classes;
      $class_threshold_log = $count_max_log - $class_size_log;

      foreach ($results_processed as $key => $result_processed) {
        if (log($result_processed['count']) < $class_threshold_log) {
          $class--;
          $class_threshold_log = $class_threshold_log - $class_size_log;
        }
        $results_processed[$key] += ['class' => $class];
      }
    }

    $response_array = [
      'status' => 'success',
      'data' => $results_processed,
      'meta' => $meta
    ];
    $output_json = json_encode($response_array);

    $response->getBody()->write($output_json);
    return $response->withHeader('Content-Type', 'application/json');
  }
}
?>
