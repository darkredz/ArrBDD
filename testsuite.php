<?php

include 'myapp.php';
include './bdd/ArrBDD.php';

$specs = array();
// include all specs to run the test
include_once './test_demo/spec1.php';
include_once './test_demo/spec2.php';
include_once './test_demo/hello.php';


$bdd = new ArrBDD();
//$results = $bdd->run($specs, true);     //show subject
$results = $bdd->run($specs);

// Output the raw results
//$bdd->outputPre($results);
//$bdd->outputVarDump($results);
$bdd->outputJSON($results);
