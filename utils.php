<?php

//----------------------------------\
//                                  |
//     Essential utilities          |
//                                  |
//----------------------------------/

function requireField(string $fieldName){
    return $_REQUEST[$fieldName] ?? returnResult("Field '$fieldName' not specified.", 8);
}

function optionalField(string $fieldName, $defaultValue=''){
    return $_REQUEST[$fieldName] ?? $defaultValue;
}

// exits script giving exitCode and result
function returnResult($result, int $exitCode=0){
    $tmp = array();

    $tmp['exitCode'] = $exitCode;
    $tmp[ 'result' ] = $result;

    die( json_encode( $tmp ) );
}