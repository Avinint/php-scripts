<?php
/**
 * Renomme tous les fichiers du répertoire avec comme argument search et replace
 */
$dir = scandir (getcwd());
[$script] = $argv;
$bFichierRetourModule = false;
//$listeBlanche = [
//'controler', 'bin', 'scripts', 'view', 'library'
//];
$listeBlanche = ['lib'];
//$listeBlanche = ['modules', 'ressources'];
//$listeBlanche = ['modules', 'lib', 'ressources', 'scripts', 'swagger', 'tests', 'webhooks'];
//$listeBlanche         = ['admin', 'commun', 'cron', 'pages', 'sso'];
$fichiersEnCours   = [];
$repertoiresComplexes = ['ressources'];

function getCommande($chemin, $nomFichier, $operateur = '>')
{
    $time = date('H:i');
    echo "\n$nomFichier $chemin $time\n";
    return "vendor/bin/rector process $chemin  --dry-run 2>&1 $operateur rector-$nomFichier.diff";
}

function bRepertoireValideOuFichierPHP($element, $listeBlanche)
{
    return bRepertoireAccepte($element, $listeBlanche) || bFichierPHPValide($element);
}

function bFichierPHPValide($fichier)
{
    return strpos($fichier, '.php') && $fichier !==  'rector.php';
}

function bRepertoireAccepte($element, $listeBlanche)
{
    var_dump($element);
    return is_dir($element) && bRepertoireValide($element) && in_array($element, $listeBlanche);
}
function bRepertoireValide($dir)
{
    return $dir[0] !== '.';
}

/**
 * @param string $element
 * @param array $fichiersEnCours
 * @return void
 */
function aExecuterParSousRepertoires(string $element, array &$fichiersEnCours): void
{
    $subDir = array_filter(scandir($element), function ($subDirElement) {
        return bRepertoireValide($subDirElement);
    });
    foreach ($subDir as $subDirElement) {
        $fichiersEnCours = vEcritureFichierAnalyse($element, $fichiersEnCours, $subDirElement);
    }
}

/**
 * @param string $element
 * @param array $fichiersEnCours
 * @param $subDirElement
 * @return array
 */
function vEcritureFichierAnalyse(string $element, array $fichiersEnCours, $subDirElement = ''): array
{
    $aliasFichier = $subDirElement ? $element : 'autre';
    $chemin = $subDirElement ? "$element/$subDirElement" : $element;
    if (in_array($element, $fichiersEnCours)) {
        echo shell_exec(getCommande($chemin, $aliasFichier, '>>'));
    } else {
        echo shell_exec(getCommande($chemin, $aliasFichier));
        $fichiersEnCours[] = $aliasFichier;
    }
    
    return $fichiersEnCours;
}

foreach ($dir as $element) {

    if (bRepertoireValideOuFichierPHP($element, $listeBlanche)) {
        if (in_array($element, $repertoiresComplexes)) {
            aExecuterParSousRepertoires($element, $fichiersEnCours);
        } elseif ($element === 'lib') {
            var_dump("lib");
            $subDir =  'lib/interne/PHP';
            var_dump(getcwd(). '/' . $subDir);
            var_dump(is_dir(getcwd(). '/' . $subDir));
            if (is_dir(getcwd(). '/' . $subDir)) {
                echo shell_exec(getCommande($subDir, $element));
            }
        } elseif ($element === 'core') {

            $subDir =  'core/modules';
            if (is_dir(getcwd(). '/' . $subDir) && !is_dir(getcwd(). '/ressources')) {
                $element = 'ressources';
                echo shell_exec(getCommande($subDir, $element));
            }

        } else {
            $fichiersEnCours = vEcritureFichierAnalyse($element, $fichiersEnCours);

        }
    }
}
