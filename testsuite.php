<?php

include 'myapp.php';
include './bdd/ArrBDD.php';

$specs = array();
// include all specs to run the test
include_once './test_demo/spec1.php';
include_once './test_demo/spec2.php';

// Output the raw results
//outputPre(runTest($specs));
//outputJSON(runTest($specs, true));
outputJSON(runTest($specs));
