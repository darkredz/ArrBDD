<?php

// Example using assert, each assert should be on multiple lines. Make it fail to see the effect

$specs["(using assert) GIVEN an account Number string"] = array(
    'subject' => function(){
        return '101055008899';
    },
    
    "SHOULD contain 12 characters" => function($accountNumber){
        return assert(strlen($accountNumber)===12);
    },
    "SHOULD start with '1010'" => function($accountNumber){
        return assert(strpos($accountNumber, '1010')===0);
    },
    
    "WHEN it is hashed" => array(
        'subject' => function($accountNumber){
            return myFuncToGenerateHash($accountNumber);
        },
        
        "THEN it should have 36 in length" => function($hashAccNum){
            $length = strlen($hashAccNum);
            // Single assert without message
            return  assert( $length===36 );
            
            // Single assert with message
            // return  array( assert($length===36), "Length 36? Value: $length");
        },
        
        "THEN it should have last 4 access number digits in front of the hash string And 36 in length" => function($hashAccNum, $accountNumber){
            $length = strlen($hashAccNum);
            $last4Chars = substr($accountNumber, -4);
            
            // Multiple assert without message example
            return array(
                array( assert( strpos($hashAccNum, $last4Chars)===0 ) ),
                array( assert( $length===36 ) )
            );
            
            // Multiple assert with message example
            /*return array(
                array( assert( strpos($hashAccNum, $last4Chars)===0 ), "First 4 char should be: $last4Chars . Hash: $hashAccNum"),
                array( assert( $length===36 ), "Length 36? Value: $length" )
            );
            */
        }        
    )
);

//include_once 'bdd.php';
//outputPre(runTest($specs));
//outputJSON(runTest($specs));
