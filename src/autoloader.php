<?php
// This file is part of the Hatsize Lab Activity Module for Moodle
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package     mod_hatsize
 * @copyright   2016 Hatsize Learning {@link http://hatsize.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class HatsizeAutoloader {
  public static function loadClass($class) {
    $filename = __DIR__ . "/" . str_replace('\\', DIRECTORY_SEPARATOR, $class) . ".php";

    if(file_exists($filename)) {
      includeFile($filename);
      return true;
    }
  }
}

/**
 * Scope isolated include.
 *
 * Prevents access to $this/self from included files.
 */
function includeFile($file) {
  include $file;
}

spl_autoload_register('HatsizeAutoloader::loadClass', true, false);
