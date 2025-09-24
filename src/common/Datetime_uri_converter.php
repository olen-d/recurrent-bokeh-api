<?php
namespace App\common;

class Datetime_uri_converter {
  private string $datetime;

  public function datetime_uri_to_mysql(string $datetime) {
    $this->datetime = $datetime;

    $year = substr($this->datetime, 0, 4);
    $month = substr($this->datetime, 4, 2);
    $day = substr($this->datetime, 6, 2);
    $hours = substr($this->datetime, 8, 2);
    $minutes = substr($this->datetime, 10, 2);
    $seconds = substr($this->datetime, 12, 2);
    $datetime_mysql = $year . '-' . $month . '-' . $day . ' ' . $hours . ':' . $minutes . ':' . $seconds;
    return $datetime_mysql;
  }

  public function datetime_mysql_to_uri(string $datetime) {
    $this->datetime = $datetime;

    $dt_space_removed = str_replace(' ', '', $this->datetime);
    $dt_colons_removed = str_replace(':', '', $dt_space_removed);
    $dt_hyphens_removed = str_replace('-', '', $dt_colons_removed);

    return $dt_hyphens_removed;
  }
}
?>
