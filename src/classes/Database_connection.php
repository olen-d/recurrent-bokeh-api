<?php
namespace Database_connection;

class Database_connection {
  private $connections = [];
  private $db_settings;

  public function __construct(array $db_settings) {
    $this->db_settings = $db_settings;
  }

  public function get_connection(string $name='default'):PDO {
    if (!isset($this->connections[$name])) {
      $db_settings - $this->settings[$name] ?? $this->config['default'];
      $dsn = "{$db_settings['driver']}:host={$db_settings['host']};dbname={$db_settings['dbname']}";
      $this->connections[$name] = new PDO($dsn, $db_settings['user'], $db_settings['pass'], [
        PDO::ATTR_PERSISTENT => $db_settings['persistent'] ?? false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
      ]);
    }
    return $this->connections[$name];
  }
}

?>
