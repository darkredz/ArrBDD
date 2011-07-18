<?php

require_once './bdd/ArrMock.php';

// --------- Mocking Methods ----------
echo "<h2>Mocking Methods</h2>";
$mongoMock = ArrMock::create('Mongo');
$mongoMock->method('getHost')
          ->returns('127.0.0.1');
    
$mongoMock->method('getHost')
          ->args('slaves')
          ->returns(array('192.168.1.2', '192.168.1.5'));
    
$mongoMock->staticMethod('getPath')
          ->returns('/var/db/');
    
$mongoMock->method('findOne')
          ->args(array('username'=>'leng'), array('username', 'email'))
          ->returns(array('_id'=>md5('leng'), 'username'=>'leng', 'email'=>'leng@asd.com'));

$mongoMock->method('findOne')
          ->args(array('username'=>'kyle'), array('username', 'email'))
          ->returns(array('_id'=>md5('kyle'), 'username'=>'kyle', 'email'=>'kyle@asd.com'));
    
//Test mock method
var_dump( $mongoMock->getHost() );

//Test mock method with arguments
var_dump( $mongoMock->getHost('slaves') );

//Test mock static method
var_dump( $mongoMock::getPath() );

//Test mock method with arguments
$rs = $mongoMock->findOne(array('username'=>'leng'), array('username', 'email') );
var_dump($rs);

//Test mock method with arguments
$rs = $mongoMock->findOne( array('username'=>'kyle'), array('username', 'email') );
var_dump($rs);

//Test __toString() mock
$mongoMock->toString('Mongo object __toString() test');
echo "<br/>$mongoMock<br/>";

// Another Mock
$anotherNewMock = ArrMock::create('Another');

$anotherNewMock->staticMethod('foo')
               ->returns('bar');
    

$anotherNewMock->staticMethod('foo2')
               ->returns('bar2');

$anotherNewMock->staticMethod('getPath')
               ->returns('/var/logs');  
               
//make non existing calls
#var_dump( $mongoMock::getPaths() );
#var_dump( $anotherNewMock::getPaths() );
#var_dump( $anotherNewMock->getPathxx() );






// ------------ Mocking non-static and static attributes --------------
echo "<h2>Mocking non-static and static attributes </h2>";

$mock = ArrMock::create('Example', array('staticVar1', 'staticVar2'));
$mock->abc = '999';
$mock::$staticVar1 = 100;
$mock::$staticVar2 = 200;

var_dump( $mock->abc );
var_dump( $mock::$staticVar1 );
var_dump( $mock::$staticVar2 );






// ------------ Using handler function for mocking methods --------------
echo "<h2>Using handler function for mocking methods</h2>";

$calcHandler = function($args){
    list($a, $b) = $args;
    return ($a + $b);
};
        
$mock->method('amount')->args(1, 5)->returns(6);
$mock->method('amount')->args(6, 7)->returns(13);
$mock->method('amount')->handle($calcHandler);

var_dump( $mock->amount(1, 5) );         // 6 
var_dump( $mock->amount(6, 7) );         // 13
var_dump( $mock->amount(100, 200) );     // 300 handler
var_dump( $mock->amount(100, 500) );     // 600 handler
var_dump( $mock->amount(6, 7) );         // 13

//static
$mock->staticMethod('amount2')->args(1, 3)->returns(4);
$mock->staticMethod('amount2')->args(2, 4)->returns(6);
$mock->staticMethod('amount2')->handle($calcHandler);

var_dump( $mock::amount2(1, 3) );         // 4
var_dump( $mock::amount2(2, 4) );         // 6






// ------------ Counting method calls --------------
echo "<h2>Counting method calls</h2>";

// Total calls for $mock->amount() method
var_dump( $mock->totalCalls('amount') );
var_dump( $mock->totalCalls('amount', 6, 7) );      // total calls with this arguments 6 and 7
var_dump( $mock->totalCalls('amount', 1, 5) );      // total calls with this arguments 1 and 5
var_dump( $mock->totalCalls('amount', 1, 2, 3) );   // This has not been called

// Total calls for $mock::amount2()
var_dump( $mock->totalCalls('amount2') );
var_dump( $mock->totalCalls('amount2', 1, 3) );
var_dump( $mock->totalCalls('amount2', 2, 4) );





// ------------ Return values http://code.google.com/p/arr-bdd/issues/detail?id=2--------------
echo "<h2>Return values sequence</h2>";

$mock->method('amount')->returns(1)
                       ->returns(10, 3)         // return value 10 on 3rd call onwards
                       ->returns(100, 5)        // return value 10 on 5th call onwards
                       ->returns(999);
                       
$mock->method('amount')->args('foo')
                       ->returns('bar')
                       ->returns('bar 2');

var_dump( $mock->amount() );        // 1
var_dump( $mock->amount() );        // 1
var_dump( $mock->amount() );        // 10
var_dump( $mock->amount() );        // 10
var_dump( $mock->amount() );        // 100
var_dump( $mock->amount() );        // 999
var_dump( $mock->amount('foo') );   // bar
var_dump( $mock->amount() );        // 999
var_dump( $mock->amount() );        // 999
var_dump( $mock->amount('foo') );   // bar 2
var_dump( $mock->amount('foo') );   // bar 2


// Static
$mock->staticMethod('test')->returns(10)
                           ->returns(100, 3)
                           ->returns('Done!');
                           
$mock->staticMethod('test')->args(9)->returns(999);

echo "\n<strong>Statics</strong>\n";
var_dump( $mock::test() );    // 10
var_dump( $mock::test() );    // 10
var_dump( $mock::test() );    // 100
var_dump( $mock::test() );    // 100
var_dump( $mock::test() );    // Done!
var_dump( $mock::test() );    // Done!
var_dump( $mock::test(9) );   // 999

