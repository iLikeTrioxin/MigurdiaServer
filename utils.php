<?php

//----------------------------------\
//                                  |
//     Essential utilities          |
//                                  |
//----------------------------------/

function requireField(string $fieldName){
    return isset( $_REQUEST[$fieldName] ) ? $_REQUEST[$fieldName] : criticalError("Field '$fieldName' not specified.", 1);
}

function optionalField(string $fieldName, $defaultValue=''){
    return isset( $_REQUEST[$fieldName] ) ? $_REQUEST[$fieldName] : $defaultValue;
}

function success($result=''){
    $tmp = array();

    $tmp['success'] = 1;
    $tmp['result' ] = $result;

    die( json_encode( $tmp ) );
}

function criticalError(string $error, int $errorCode=0){
    $tmp = array();
    
    $tmp['errorMsg' ] = $error;
    $tmp['errorCode'] = $errorCode;
    $tmp['success'  ] = 0;
    
    die( json_encode( $tmp ) );
}

function post(string $url, array $data, bool $json_decode=false) {
    $ch = curl_init($url);

    curl_setopt( $ch, CURLOPT_POST          , true  );
    curl_setopt( $ch, CURLOPT_POSTFIELDS    , $data );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true  );

    $resp = curl_exec($ch);

    curl_close($ch);

    return $json_decode ? $resp : json_decode($resp);
}

?>