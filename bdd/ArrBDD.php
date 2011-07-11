<?php
/**
 * ArrBDD class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.doophp.com/arr-bdd
 * @copyright Copyright &copy; 2011 Leng Sheng Hong
 * @license http://www.doophp.com/license
 * @since 1.0
 */

/**
 * ArrBDD - a simple BDD library utilizing PHP associative arrays and closures
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @since 1.0
 */
 
/**
 * This library should be called Arr! BDD, need to convert this to a class later on.
 */

//echo '<pre>';
//print_r($specs);
global $assertFailMsg;
$assertFailMsg = array();

function onAssertFail($file, $line, $expr) {
    global $assertErrorKey;
    global $assertError;
    global $assertFailMsg;
    
    if( empty($expr) )
        $assertError = "Assertion failed in $file on line $line";
    else
        $assertError = "Assertion failed in $file on line $line: $expr";
    //print "<br/>[$assertErrorKey] \n\t$assertError\n<br/>";
    //print "<br/>$assertError<br/>";
    
    if( is_array($assertErrorKey) ){
        if( empty($assertFailMsg[$assertErrorKey[0]][$assertErrorKey[1]]) ){
            $assertFailMsg[$assertErrorKey[0]][$assertErrorKey[1]] = array();
        }
        $assertFailMsg[$assertErrorKey[0]][$assertErrorKey[1]][] = $assertError;        
    }
    else{
        if( empty($assertFailMsg[$assertErrorKey]) ){
            $assertFailMsg[$assertErrorKey] = array();
        }    
        $assertFailMsg[$assertErrorKey][] = $assertError;
    }
    $assertError = array( $assertError );    
}

assert_options (ASSERT_CALLBACK, 'onAssertFail');
assert_options (ASSERT_WARNING, 0); 
//    echo '<pre>';

function arrBDD($specs, $includeSubject=false){
    global $assertErrorKey;
    global $assertError;
    global $assertFailMsg;
    
    $testResults = array();
        
    foreach($specs as $specName => $spec){
        $results = array();
        
        $subject = null;
        
        // Subject can be a closure or the actual value
        if( isset( $spec['subject'] ) ){
            $sbj = $spec['subject'];
            
            if(is_callable($sbj))
                $subject = $sbj();
            else
                $subject = $sbj;        
        }
        
        // include subject to the result
        if( $includeSubject ){
            $results['subject'] = $subject;
        }
        
        foreach( $spec as $stepName => $step ){
            if($stepName=='subject') continue;
            
            $assertErrorKey = $stepName;
            
            // not a WHEN
            if( is_callable($step) ){
                $rs = $step($subject);
                // $results[$stepName] = ($rs) ? $rs : $assertError;
                evalAsserts($results, $rs, $assertError, $stepName);                
            }
            // a WHEN 
            else if( is_array($step) ){
                $_subject = null;
                
                // get inner step's subject, pass main step subject and inner subject to THEN closure
                if( isset( $step['subject'] ) ){
                    $sbj = $step['subject'];                
                    
                    // Need passing back the main subject to the inner step subject closure
                    if(is_callable($sbj))
                        $_subject = $sbj($subject);
                    else
                        $_subject = $sbj;        
                }        
                
                // include subject to the result
                if( $includeSubject ){
                    $results[$stepName]['subject'] = $_subject;
                }                
                
                foreach( $step as $_stepName => $_step ){                
                    if($_stepName=='subject') continue;
                    
                    $assertErrorKey = array($stepName, $_stepName);                
                    $rs = $_step($_subject, $subject);
                    
                    evalAsserts($results, $rs, $assertError, $stepName, $_stepName);                    
                }
            }
        }

        $testResults[$specName] = $results;
    }
    
    //print_r($assertFailMsg);
    return $testResults;
}


function evalAsserts(&$results, &$rs, &$assertError, $stepName, $_stepName = NULL){
    global $assertFailMsg;
    // if an array is returned, check if the first value(assertion value) pass the test
    if( is_array($rs) ){
        //====== single assertion =====
        if( $rs[0]===true ){
            $rs = $rs[0];
        }
        // if fail, then use the assertion failed msg provided, if available
        else if( !empty($rs[1]) && is_string($rs[1]) ){
            $err = $rs[1];
            $assertError = array($err);
            $rs = false;        // failed
        }
        // Single assertion, if fail, and no debug msg specify, use default assertion error msg
        else if( empty($rs[1]) ){
            $rs = false;        
        }
        
        //====== multiple assertion =====
        else if( is_array($rs[0]) ){
            $asserts = $rs;
            
            // multiple assertion, get the error debug msg if available, eg. array( expr, "msg on the test" )
            // $assert[0] = expr,  $assert[1] = msg 
            $rs = array();
            foreach( $asserts as $assert ){
                # var_dump($assert);
                if( $assert[0]!==true ){
                    if( !empty($assert[1]) )
                        $rs[] = $assert[1];
                    else
                        $rs[] = false;//$assertError[0];
                }
                else{
                    $rs[] = true;
                }
            }
        }
        
    }
    
    // assign result/fail msg for the steps (inner step, if inner is set)
    // if test passed, use boolean true, else if failed use the assertion error message
    if( isset($_stepName) ){
        $rst = &$results[$stepName][$_stepName];
        if(!empty($assertFailMsg[$stepName][$_stepName]))
            $afMsg = $assertFailMsg[$stepName][$_stepName];
    }
    else{
        $rst = &$results[$stepName];
        if(!empty($assertFailMsg[$stepName]))
            $afMsg = $assertFailMsg[$stepName];
    }    
    
    if( empty($afMsg) ){
        $rst = ($rs) ? $rs : $assertError;                
    }
    else if(!empty($rs)){
        $assertError = $afMsg;
        $i = 0;
        foreach( $rs as &$rsvalue){
            if($rsvalue===true) continue;
            if($rsvalue===false)
                $rsvalue = $assertError[$i++];
        }
        $rst = $rs;                                        
    }
    else{
        if(is_array($assertError) && sizeof($assertError)===1)    
            $rst = $assertError[0];                
        else
            $rst = $assertError;
    }    
}


//echo "\n============= Test Results ================\n";
//print_r( arrBDD($specs) );
//echo "\n\n\n\n\n\n";

function runTest($specs, $includeSubject=false){    
    return arrBDD($specs, $includeSubject);
}

function outputPre( $result ){
    echo '<pre>';
    print_r( $result );
}

function outputVardump( $result ){
    echo '<pre>';
    var_dump( $result );
}

function outputJSON( $result ){
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1970 05:00:00 GMT');
    header('Content-type: application/json');
    echo json_encode( $result );
}
