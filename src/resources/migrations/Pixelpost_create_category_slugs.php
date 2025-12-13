<?php

namespace App\resources\migrations;
// Access the database
// Add the slug field after headline
// INDEX THE SLUG FIELD

// Select id, headline
// Loop through the headlines
// Replace spaces with -
// Remove anything except a-zA-Z0-9 and space
// Check to see if the slug already exists
// If it does, add -current timestamp
// Echo the result
// Insert the slug
// Have a nice day!.
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

final class Pixelpost_create_category_slugs {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function __invoke(Request $request, Response $response): Response {
    // Fix outdated default value for datetime
    // $alter_default_datetime = $this->pdo->query(
    //   'ALTER TABLE pixelpost_pixelpost
    //   CHANGE datetime datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'
    // );

    $add_col_slug = $this->pdo->query(
      'ALTER TABLE pixelpost_categories
      ADD slug CHAR(192) AFTER name
      '
    );
  
    $add_idx_slug = $this->pdo->query(
      'CREATE INDEX idx_cat_slug
      ON pixelpost_categories (slug(16))'
    );

    $posts = $this->pdo->query(
      'SELECT id, name
      FROM pixelpost_categories'
    );

    $slugs_processed = [];
    foreach ($posts as $post) {
      [$id, $name] = $post;

      // Replace spaces with -
      $slug_trimmed = trim($name);
      $slug_spaces_replaced = preg_replace('/\s+/', '-', $slug_trimmed);
      // Remove anything except a-zA-Z0-9 and space
      $slug_special_chars_removed = preg_replace('/[^A-Za-z0-9-]/', '', $slug_spaces_replaced);
      // Check to make sure the slug isn't empty and assign a value if it is
      $slug_checked = ($slug_special_chars_removed == '') ? ('special-characters-only') : ($slug_special_chars_removed);
      // Make everything lowercase
      $slug_lowercase = strtolower($slug_checked);
      // Check to see if the slug already exists
      // This loop isn't exactly efficient, but the conversion only happens once so no one cares
      foreach ($slugs_processed as $slug) {
        $key = array_search($slug_lowercase, $slug);
        // If it does, add -current timestamp
        if ($key !== false) {
          $unique = mt_rand(10000,90000);
          $slug_lowercase .= '-' . $unique;
        }
      }

      array_push(
        $slugs_processed,
        [
          'id' => $id, 
          'name' => $name,
          'slug' => $slug_lowercase
        ]
        );
    }
    
    foreach ($slugs_processed as $item) {
      ['id' => $id, 'slug' => $slug] = $item;
      $squirrel = 'UPDATE pixelpost_categories SET slug=? WHERE id =?';
      $update_slug = $this->pdo->prepare($squirrel)->execute([$slug, $id]);
    }

    $response_array = [
      'status' => 'success',
      'data' => $slugs_processed
    ];

    $output_json = json_encode($response_array);

    $response->getBody()->write($output_json);
    return $response->withHeader('Content-Type', 'application/json');
  }
}
?>
