<?php
namespace App\module\attributes\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class Attributes_fetch_values_list_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response): Response {
    $queryParams = $request->getQueryParams();
    $classification = array_key_exists('classification', $queryParams) ? $queryParams['classification'] : 'logrithmic';
    $count_min = array_key_exists('minimum', $queryParams) ? $queryParams['minimum'] : 240;
    $total_classes = array_key_exists('classes', $queryParams) ? $queryParams['classes'] : 5;

    $sql = "SELECT * FROM (SELECT pixelpost_customfields.name, value, COUNT(pixelpost_customfieldsvalues.value) AS value_count 
      FROM pixelpost_customfieldsvalues, pixelpost_customfields
      WHERE pixelpost_customfieldsvalues.customfield_id = pixelpost_customfields.id
      AND pixelpost_customfields.visible = 'on' 
      GROUP BY pixelpost_customfields.name, pixelpost_customfieldsvalues.value
      ORDER BY value_count DESC) AS vc WHERE value_count >= :minimum";

    $sth = $this->pdo->prepare($sql);
    $sth->bindValue(':minimum', $count_min, PDO::PARAM_INT);
    $sth->execute();
    $attributes = $sth->fetchAll();

    $attributes_processed = [];
    foreach ($attributes as $attribute) {
      [$name, $value, $value_count] = $attribute;
      array_push(
        $attributes_processed,
        [
          'name' => $name,
          'value' => $value, 
          'count' => $value_count
        ]
        );
    }

    $counts = array_map(function($n) { if ($n['count']) return $n['count']; }, $attributes_processed);
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

      foreach ($attributes_processed as $key => $attribute_processed) {
        if (log($attribute_processed['count']) < $class_threshold_log) {
          $class--;
          $class_threshold_log = $class_threshold_log - $class_size_log;
        }
        $attributes_processed[$key] += ['class' => $class];
      }
    }

    $attribute_names = array_column($attributes_processed, 'name');
    $attribute_values = array_column($attributes_processed, 'value');
    $attribute_counts = array_column($attributes_processed, 'count');
    $attribute_classes = array_column($attributes_processed, 'class');

    array_multisort($attribute_names, SORT_ASC, $attribute_values, SORT_ASC, $attributes_processed);

    $attributes_condensed = [];
    $attribute_names_unique = array_unique($attribute_names);
    foreach ($attribute_names_unique as $name) {
      $attributes_condensed[$name] = [];
    }

    foreach ($attributes_processed as $attribute_processed) {
      array_push($attributes_condensed[$attribute_processed['name']], [
        'value' => $attribute_processed['value'],
        'count' => $attribute_processed['count'],
        'class' => $attribute_processed['class']
      ]);
    }

    $response_array = [
      'status' => 'success',
      'data' => $attributes_condensed,
      'meta' => $meta
    ];
    $output_json = json_encode($response_array);

    $response->getBody()->write($output_json);
    return $response->withHeader('Content-Type', 'application/json');
  }
}
?>
