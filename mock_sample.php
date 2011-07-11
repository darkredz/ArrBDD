<?php

require_once './bdd/ArrMock.php';

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

//Test __toString() magic call mock
$mongoMock->toString('Mongo object');
echo "<br/>$mongoMock<br/>";

// --------- Another Mock -------------------
$anotherNewMock = ArrMock::create('Another');

$anotherNewMock->staticMethod('lolmethod')
      ->returns('asdasdasda');
    

$anotherNewMock->staticMethod('gg2')
               ->returns(123);

$anotherNewMock->staticMethod('getPath')
               ->returns('asdasdasda');  
               
//make non existing calls
var_dump( $mongoMock::getPaths() );
var_dump( $anotherNewMock::getPaths() );
var_dump( $anotherNewMock->getPathxx() );

