#!/usr/bin/php
<?php
require_once('OpenCartTranslator.class.php');

$initWin = array(
                'fromLanguage' => "english",
                'toLanguage'   => "hungarian",
                'pathFrom'     => "c:\Documents and Settings\DI\scripts\en",
                'pathTo'       => "c:\Documents and Settings\DI\scripts\hu",
                'newVersion'   => "1.4.7"
);

$initLinux = array(
                'fromLanguage' => "english",
                'toLanguage'   => "hungarian",
                'pathFrom'     => "/home/distvan/scripts/en",
                'pathTo'       => "/home/distvan/scripts/hu",
                'newVersion'   => "1.4.7"
);
 
$sample = new OpenCartTranslator($initLinux);
$sample->translate();

?>
