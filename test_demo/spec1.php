<?php

/**
Test this: anaccount number 101055008899 hashed into md5() string plus the last 4 digits
*/

$specs["GIVEN an account number string"] = array(
    'subject' => function(){
        return '101055008899';
    },
    
    "SHOULD contain 12 characters" => function($accountNumber){
        return (strlen($accountNumber)===12);
    },
    "SHOULD start with '1010'" => function($accountNumber){
        return (strpos($accountNumber, '1010')===0);
    },    
    
    "WHEN it is hashed" => array(
        'subject' => function($accountNumber){
            return myFuncToGenerateHash($accountNumber);
        },
        
        // Single assert with message example
        "THEN it should have 36 in length" => function($hashAccNum){
            $length = strlen($hashAccNum);
            return array( ($length===36), "Length expected 36. value: $length" );
        },
        
        // Multiple assert with message example
        "THEN it should have last 4 access number digits in front of the hash string And 36 in length" => function($hashAccNum, $accountNumber){
            $length = strlen($hashAccNum);
            $last4Chars = substr($accountNumber, -4);
            
            return array(            
                array( ( strpos($hashAccNum, $last4Chars)===0 ), "First 4 char should be: $last4Chars. Hash: $hashAccNum"),
                array( ( $length===36 ), "Length is 36? value: $length"),                
            );
        }        
    )
);


//include_once 'bdd.php';
//outputPre(runTest($specs));
//outputJSON(runTest($specs));
