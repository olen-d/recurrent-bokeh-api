<?php
namespace App\module\categories\action;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class Categories_fetch_list_and_posts_action {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response): Response {
    $queryParams = $request->getQueryParams();
    $count_min = array_key_exists('limit', $queryParams) ? $queryParams['limit'] : 3;

    $sql = "WITH filtered_posts AS 
    (SELECT cat_id, name AS cat_name, pixelpost_categories.slug AS cat_slug, alt_name AS cat_alt_name, 
    pixelpost_pixelpost.id AS id, pixelpost_pixelpost.datetime AS datetime, headline, pixelpost_pixelpost.slug AS slug,
    body, image, alt_headline, alt_body, comments, ROW_NUMBER() 
    OVER (PARTITION BY cat_id ORDER BY pixelpost_pixelpost.dateTime DESC) AS post_rank 
    FROM pixelpost_categories LEFT JOIN (pixelpost_catassoc, pixelpost_pixelpost) ON (pixelpost_catassoc.cat_id = pixelpost_categories.id AND pixelpost_pixelpost.id = pixelpost_catassoc.image_id)) 
    SELECT cat_id, cat_name, cat_slug, cat_alt_name, id, datetime, headline, slug, body, image, alt_headline, alt_body, comments 
    FROM filtered_posts WHERE post_rank <= :num_images ORDER BY cat_name, datetime DESC";

    $sth = $this->pdo->prepare($sql);
    $sth->bindValue(':num_images', $count_min, PDO::PARAM_INT);
    $sth->execute();
    $categories = $sth->fetchAll();
  
    $categories_processed = [];
    $current_cat_name = '';

    foreach ($categories as $category) {
      [
        $cat_id,
        $cat_name,
        $cat_slug,
        $cat_alt_name,
        $id,
        $datetime,
        $headline,
        $slug,
        $body,
        $image,
        $alt_headline,
        $alt_body,
        $comments
      ] = $category;

      if ($cat_name === $current_cat_name) {
        $index = array_key_last($categories_processed);

        $new_post = [
          'id' => $id,
          'datetime' => $datetime,
          'headline' => $headline,
          'slug' => $slug,
          'body' => $body,
          'image' => $image,
          'altHeadline' => $alt_headline,
          'altBody' => $alt_body,
          'comments' => $comments
        ];

        array_push(
          $categories_processed[$index]['posts'],
          $new_post
        );
      } else {
        $new_data = [
          'catId' => $cat_id,
          'catName' => $cat_name,
          'catSlug' => $cat_slug,
          'catAltName' => $cat_alt_name,
          'posts' => []
        ];
        
        $new_post = [
          'id' => $id,
          'datetime' => $datetime,
          'headline' => $headline,
          'slug' => $slug,
          'body' => $body,
          'image' => $image,
          'altHeadline' => $alt_headline,
          'altBody' => $alt_body,
          'comments' => $comments
        ];

        array_push(
          $new_data['posts'],
          $new_post
        );

        array_push(
          $categories_processed,
          $new_data
        );

        $current_cat_name = $cat_name;
      }
    }
    
    $response_array = [
      'status' => 'success',
      'data' => $categories_processed
    ];
    $output_json = json_encode($response_array);

    $response->getBody()->write($output_json);
    return $response->withHeader('Content-Type', 'application/json');
  }
}
?>
