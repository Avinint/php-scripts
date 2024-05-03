<?php
/**
 * Renomme tous les fichiers du répertoire avec comme argument search et replace
 */
$dir = scandir (getcwd());
[$script, $search, $replace] = $argv;

foreach ($dir as $fileName) {
  $newFile = str_replace($search, $replace, $fileName);
  rename($fileName,$newFile);
}
