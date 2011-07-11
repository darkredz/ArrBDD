<?php

// Example function used in application business logic

function myFuncToGenerateHash($accessNumber){
    return substr($accessNumber, -4) . md5($accessNumber);
    //Make it fail
    //return sha1($accessNumber);
}
