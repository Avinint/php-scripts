<?php

$dir = scandir (getcwd());
[$script, $file] = $argv;

if (!in_array($file, $dir)) {
    echo "Fichier inexistant";
} else {
    $zip = new ZipArchive;
    var_dump(getcwd(). '/'.$file);
    if ($zip->open(getcwd() . '/'.$file) === TRUE) {
        $zip->extractTo(getcwd() . '/zip');
        $zip->close();
        echo 'ok';
    } else {
        echo 'échec';
    }
}


?>
