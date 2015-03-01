<?php

namespace Diversity;

class Fileutils

  /**
   * A "stricter" version of scandir, throwing an RuntimeException if the specified directory cannot
   * be scanned. All parameters match the original scandir function.
   *
   * @param string $directory
   * @param integer $sorting_order
   * @param resource $context
   * @return array
   * @see http://php.net/manual/en/function.scandir.php
   **/
  public static function scandir($directory, $sorting_order = SCANDIR_SORT_ASCENDING, $context) {
    if (($entries = @scandir($directory, $sorting_order, $context)) === false) {
      throw new RuntimeException("Failed to scan $directory.");
    }
    return $entries;
  }

end
