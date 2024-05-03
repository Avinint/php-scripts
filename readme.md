Qu'est ce que Rector ?


Rector est un outil d'analyse de Code PHP mais contrairement aux autres solutions (Code Sniffer/PHP compat , PHAN) et comme Psalm,  son système de règles permet non seulement de détecter les problèmes mais également de proposer une solution de refactorisation (Et même d'effectuer ces changements automatiquement... )

Cet outil ne permet pas seulement d'assurer les problèmes de compatibilté entre versions, il permet également de suggérer d'utiliser les nouvelles fonctionnalités des langages ainsi que d'assurer des refactorisations pures (remplacement du return final d'une fonction, par plusieurs return). Il va sans dire que toutes les règles ne vont pas faire l'unanimité et ont peut choisir lesquelles on va appliquer. Notamment par un système de blacklists et des sets (règles regroupées).

Installation :

Contrairement aux autres solutions, Rector ne permet pas une installation globale par composer. Il faudra donc l'installer dans chaque projet en lançant la commande suivante depuis votre projet :

1
composer require rector/rector --dev
Configuration :

Ajouter un fichier rector.php dans la racine de votre projet avec la commande :

1
vendor/bin/rector init

Dans les versions récentes de Rector (note du 27/07/2023) :

1
vendor/bin/rector

Remplacer  le contenu de Rector.php par :

1
<?php
2
​
3
declare(strict_types=1);
4
​
5
use Rector\Config\RectorConfig;
6
use Rector\Core\ValueObject\PhpVersion;
7
use Rector\Set\ValueObject\LevelSetList;
8
​
9
return static function (RectorConfig $rectorConfig): void {
10
    // J'avais prévu de lancer l'analyse sur le répertoire de projet et d'utiliser paths
11
    :// pour filtrer sur modules, ressources.. etc. , ce n'est pas possible
12
    /*$rectorConfig->paths([
13
                             __DIR__ . '/controllers',
14
                         ]);*/
15
​
16
    $rectorConfig->phpVersion(PhpVersion::PHP_81);
17
    $rectorConfig->disableParallel();
18
    $rectorConfig->sets([
19
                            LevelSetList::UP_TO_PHP_81
20
                        ]);
21
};
22
​

Essentiellement, on remplace le contenu de la fonction anonyme pour :

définir les chemins dans lesquels on effectue l'analyse. (Les répertoires de l'exemple correspondent à easy2do, les modifier si vous travaillez sur un autre type de projet.
faire croire à Rector qu'on est déjà en version 8.1.
J'ai  enlève les traitements parallèles parce qu'ils créaient un bug
Je définis quelles règles vont être appliquées en utilisant un set  (une liste de règles (Rules) qui est là défini comme tous les sets jusqu'à PHP 8.1
Il y a d'autres façons de faire (Notamment en ajoutant les règles une par une). Par exemple :

rectorConfig->rule(TypedPropertyRector::class);

Se rendre sur la doc officielle de Rector pour en savoir plus.


Si vous êtes pressés, vous pouvez vous rendre sur la dernière partie de ce tuto "solutions" pour récupérer la version de rector.php que j'utilise pour mes audits php8, qui permet de contourner une des causes des plantage signalés dans la partie problème et éviter les retours superflus de l'outil d'analyse.

Utilisation :

Lancer la commande suivante pour analyser et afficher les modifications suggérées - sous forme de diff - sans les effectuer :

vendor/bin/rector process modules --dry-run 2>&1 > rector-modules.diff


Il faut faire une analyse par sous répertoire. L'option paths sur le répertoire du projet dans rector.php ne suffit pas pour filtrer les répertoires analysés. Rector passera sur tous les chemins inclus dans le répertoire, ce qui prendra des plombes, et le script plantera sur les chemins "point" et "double point". (. et ..)

De toutes façons, Rector ne produit pas des commentaires mais des diffs et afficher la totalité d'un projet serait bien trop long dans un seul fichier.

Enlever l'option dry-run si vous voulez effectuer ces changements et exécuter votre code. (Si vous êtes suffisamment confiant ou inconscient pour faire cela, il est fortement recommander d'isoler votre code dans un commit voir dans une branche.

Êtes-vous près à gérer autant de changements dans votre code ? Je ne crois pas. Mais si cette démarche vous intéresse, vous pouvez définir vos propres Sets de règles afin d'enlever celles qui effectuent des changements non voulus, ou celles qui font des changements bizarres ou risqués. Vous pouvez également blacklister certaines règles, certains répertoires, ou même certaines règles dans certains répertoires, grace à la méthode skip :

1
$rectorConfig->skip([
2
        // single file
3
        __DIR__ . '/src/ComplicatedFile.php',
4
        // or directory
5
        __DIR__ . '/src',
6
        // or fnmatch
7
        __DIR__ . '/src/*/Tests/*',
8
​
9
 // is there single rule you don't like from a set you use?
10
        LongArrayToShortArrayRector::class,
11
​
12
        // or just skip rule in specific directory
13
        LongArrayToShortArrayRector::class => [
14
            // single file
15
            __DIR__ . '/src/ComplicatedFile.php',
16
            // or directory
17
            __DIR__ . '/src',
18
            // or fnmatch
19
            __DIR__ . '/src/*/Tests/*',
20
        ],
21
    ]);

Merci la doc officielle


Liste des règles :

Tout l'outil est en PHP il est donc facile de comprendre ce que fait chaque règle, et également de créer les notres.

Pour la liste complète il suffit de suivre les répertoires des classes Rule. Celles-ci sont réparties en catégories.

https://github.com/rectorphp/rector/tree/main/rules


Le mieux est de se baser sur les noms des classes affichés quand on lance l'analyse.

De nombreuses sont liées à des modifications apportées à PHP,  la classe PhpVersionFeature, qui fait correspondre chaque fonctionnalité à une version de PHP a une vrai valeur de documentation. Il y a très peu de documents en ligne qui documentent l'historique des changements du langage PHP de manière aussi synthétique. Les règles qui nous intéressent dans le cadre de la migration d'une version à une autre correspondent toutes à une constante définie dans ce fichier. Il est donx très utile pour retrouver une règle précise. Normalement les règles correspondent à la version de PHP dans laquelle une fonctionnalité est supprimée et non quand elle est juste dépréciée.

Problèmes rencontrés :




Plantages :

Dans les exemples donnés sur la documentation officielle, l'application est lancée sur le répertoire src, commun à de nombreuses applications, pour avoir un  lancement unique j'ai tenté de lancer le script sur le répertoire de l'application, comme on peut le faire sur d'autres outils comme PHPCompat etc. et ce n'est pas une bonne idée.  la méthode paths dans rector.php permet de définir les chemins sur lesquels les analyses vont avoir lieu mais la totalité des fichiers et répertoires contenus dans le projet vont être scannés, et le problème c'est qu'apparemment, leur outil pour lister, contrairement à glob ou scandir essaie d'analyser .. et . les entrées correspondant au répertoire en cours et au répertoire parent, ce qui pose problème car ces deux entrées renvoient false au lieu d'une chaine, et ce cas de figure n'a pas été pris en compte.....
A vrai dire, ce bug est facile à résoudre, si on est prêt à retoucher les fichiers de vendor.  L'autre problème est que l’exécution sur le répertoire entier est très très longue. Et ce n'est pas le nombre de ligne qui semble être en cause.

Lenteurs, freezes :

Il est de toutes façons préférable de scanner séparément,  modules, ressources etc. car si l'analyse de module se passe sans problème, l'analyse de ressources plante également. Certains sous dossiers provoquent des lenteurs extrêmes inexpliquées (authentification par exemple) alors d'autres se passe bien. Il y a probablement d'autres sous répertoires qui provoquent le crash mais je n'ai pas eu le temps d'approfondir. Dans  Lib, mPDF semble également freezer le processus d'analyse, mais il y a un moyen plus efficace de mettre à jour le code des libraries.

Il pourrait être intéressant d'approfondir pour mieux comprendre les limites de l'outil et voir si également on n'a pas des choses à améliorer mais cela prend du temps.
Mise à jour 23 /11/2022 : Il semblerait que certains plantages / freezes soient liés à la présence de composer et de son système d'autoload dans plusieurs répertoires différents, notamment certaines de nos ressources.







Compte rendu d'analyse  :

Un dernier point, contrairement aux premiers outils cités,  Rector ne gère pas l'écriture d'un compte-rendu dans un fichier et écrit tout dans la console. On doit copier coller le contenu de celle-ci dans un fichier, ce qui ne pose pas vraiment de problème en soi.

Par contre on réalise que l'output du fichier est bien plus verbeux que la concurrence. En effet, en plus du fichier,  et de la ligne, au lieu d'une explication succincte, on a un diff du problème et de la solution proposée. Et un résumé des règles correspondantes à ces changements. Pour le coup, les règles ne sont pas des descriptions mais une liste des noms des classes correspondant aux règles concernés. En général le nom est clair mais il n'est pas exclu qu'on ait besoin d'aller vérifier la liste des règles pour comprendre tel ou tel changement. Et surtout les diffs génèrent un texte très long, peu digeste. Le rapport sur modules du projet uniscite fait à peu près 4000 lignes, ce qui est peu digeste... L'outil propose une option  pour ne pas afficher les diffs, mais cette option cache toutes les informations : C'est à dire aussi les infos telles que la classe analysée, la ligne de problème et le type de problème. Ce serait bien que l'outil propose également un compte-rendu plus succinct.

En plus, il faut vérifier que la proposition soit pertinente. Par exemple, dans une classe du projet, il détecte une déclaration de variable dans un if et il propose d'initialiser la variable a null avant la condition. Ce qui serait pertinent si la variable était utilisée après le if mais cela n'est pas le cas. Dans le cadre d'une démarche consacrée à la migration vers 8.x, cette préconisation est encore moins pertinente. Cela rajoute du travail car il faut filtrer les règles non pertinentes qui alourdissent le processus.


Solutions :

Voici la configuration de rector.php que j'utilise :

1
<?php
2
​
3
declare(strict_types=1);
4
​
5
use Rector\Config\RectorConfig;
6
use Rector\Core\ValueObject\PhpVersion;
7
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
8
use Rector\Php56\Rector\FunctionLike\AddDefaultValueForUndefinedVariableRector;
9
use Rector\Php71\Rector\List_\ListToArrayDestructRector;
10
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
11
use Rector\Set\ValueObject\LevelSetList;
12
​
13
​
14
return static function (RectorConfig $rectorConfig): void {
15
    $rectorConfig->phpVersion(PhpVersion::PHP_81);
16
    $rectorConfig->disableParallel();
17
    $rectorConfig->skip([
18
        __DIR__ . '/ressources/export/lib/vendor/**/*',
19
        LongArrayToShortArrayRector::class,
20
        ListToArrayDestructRector::class,
21
        NullToStrictStringFuncCallArgRector::class,
22
        AddDefaultValueForUndefinedVariableRector::class]);
23
    $rectorConfig->sets([
24
        LevelSetList::UP_TO_PHP_81
25
    ]);
26
};

Je ignore avec la méthode skip, les règles de codage que j'ai jugé non pertinentes pour les projets easy2do, et à fortiori pour le passage à php 8. Notamment l'utilisation de la syntaxe courte pour les tableaux et la desttructuraction de tableau utilisant cette même syntaxe plutôt que list(), qui ne sont pas pertinents concernant les problèmes de compatibilité php8. Et surtout qui allonge inujtilement les rapports vu que très peu de devs utilisent ces syntaxes "modernes".

Le deuxième point important est que je fais un skip pour ignorer un certain chemin dans la ressource export qui correspond à la présence d'un composer qui à priori entre en conflit avec le composer du fichier racine, et dont rector dépend pour l'autoload etc. et cela évite le plantage silencieux lors de l'analyse de la ressource. Mais cela ne résoud pas tous les problèmes et je recommande toujours d'analyser les ressources une par une.





