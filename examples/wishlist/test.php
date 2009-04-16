<?php
/**
 * PHPSpec
 * This file will run all specs within this directory and all child-directories.
 * TODO safeguard sjekke at mode = test
 */

chdir('/specs');

require_once 'PHPSpec.php';

$options = new stdClass;
$options->recursive = true;
$options->specdocs = true;
$options->reporter = 'html';

PHPSpec_Runner::run($options);
?>