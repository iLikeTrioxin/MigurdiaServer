<?php

header('Access-Control-Allow-Origin: *');

session_start();

// This API needs so much more work that
// for now shouldn't be even consired beta
// PS. It's my secound php script in my life
// If you see any bugs or things I could do better
// please commit your change or tell me about it
// on discord or any other media (can be found on https://www.yukiteru.xyz)

//  Error codes:
//      1 - Something went wrong (server-side) ex. can't connect to database
//      2 - Can't find user with such name/email
//      3 - Wrong password
//      4 - Wrong email
//      5 - User with this name already exists
//      6 - User with thus email already exits
//      7 - Session expired

include('credentials.php');
include('utils.php');

$MySQLDatabase = new mysqli('localhost', $MySQLuser, $mySQLpassword, $MySQLdbname);

if( $MySQLDatabase->connect_errno ) criticalError('Something went wrong, try again later.', 1);

//----------------------------------\
//                                  |
//     Database related stuff       |
//                                  |
//----------------------------------/

//----------------------\
//  Email Verification  |
//----------------------/

// checks if user with specified email is verified and returns true or false
function isEmailVerified($email){
    global $MySQLDatabase;

    $sql      = "SELECT `verificationCode` FROM `Users` WHERE `email` = '$email';";
    $response = $MySQLDatabase->query($sql);
    
    if($code = $response->fetch_row())
        return $code == 'verified' ? true : false;
    
    return false;
}

// 0 - ok 1- already done
function sendVerificationEmail($email){
    global $MySQLDatabase;

    $code = bin2hex(random_bytes(4));

    $sql = "UPDATE `Users` SET `verificationCode` = '$code' WHERE `email` = '$email';";
    
    if( !( $MySQLDatabase->query($sql) ) )
        return 1;

    $msg = "Your code: $code";
    
    mail($email, 'Kieruzele - verification email.', $msg);

    return 0;
}

// 0 - correct 1 - not correct
function confirmUserEmail(string $email, $code){
    global $MySQLDatabase;

    $sql = "SELECT `VerificationCode` FROM `Users` WHERE `email` = '$email';";

    $response = $MySQLDatabase->query($sql);

    if($response->fetch_row()[0] != $code)
        return 1;
    
    $sql = "UPDATE `Users` SET `VerificationCode` = 'verified' WHERE `Email` = '$email';";
    
    $response = $MySQLDatabase->query($sql);

    return 0;    
}

//-------------------\
// account managment |
//-------------------/

function signin($username, $password){
    global $MySQLDatabase;

    $username = $MySQLDatabase->real_escape_string($username);
    $password = $MySQLDatabase->real_escape_string($password);

    $sql      = "SELECT `ID`, (`Password` = '$password') AS `Authenticated` FROM `Users` WHERE `Username` = '$username' OR `Email` = '$username';";
    
    $response = $MySQLDatabase->query($sql);
    $row      = $response->fetch_assoc();

    if ( ! $response->num_rows   ) return 1;
    if ( ! $row['Authenticated'] ) return 2;

    $_SESSION['migurdia']['userID'] = (int) $row['ID'];

    return 0;
}

function signup($username, $email, $password){
    global $MySQLDatabase;

    $email    = strtolower($email);

    $username = $MySQLDatabase->real_escape_string($username);
    $email    = $MySQLDatabase->real_escape_string($email   );
    $password = $MySQLDatabase->real_escape_string($password);

    $sql = "SELECT `Username`, `Email` FROM `Users` WHERE `Users`.`Username` = '$username' OR `Users`.`Email` = '$email'";

    $response = $MySQLDatabase->query($sql);

    if($row = $response->fetch_assoc()) return ( strtolower($row['Username']) == strtolower($username) ? 1 : 2 );
    
    $sql = "INSERT INTO `Users` (`Username`, `Email`, `Password`, `VerificationCode`) VALUES ('$username', '$email', '$password', '00000000');";

    if( ! $MySQLDatabase->query($sql) )
        return 3;

    return 0;
}

function isSignedIn(){
    if( isset($_SESSION['migurdia']['userID']) )
        if(   $_SESSION['migurdia']['userID']  ) return true;

    return false;
}

//-------------------\
// Tag related stuff |
//-------------------/

// returns array(array(tagID, tag), array(tagID, tag)...)
function getTagProposals($hint, int $limit=20){
    global $MySQLDatabase;
    
    $hint = $MySQLDatabase->real_escape_string($hint);

    $sql = "SELECT * FROM `Tags` WHERE `tag` LIKE \"%$hint%\" LIMIT $limit;";
    
    $response  = $MySQLDatabase->query($sql);
    $tags      = array();

    while($row = $response->fetch_row())
        array_push($tags, $row);

    $response->free_result();

    return $tags;
}

function addTags(int $fileID, array $tags){
    global $MySQLDatabase;

    $sql = 'INSERT INTO `FilesTags` (`FileID`, `TagID`) VALUES ';
    foreach($tags as $tag){
        $sql .= "($fileID, $tag)";
    }

    $result = $MySQLDatabase->query($sql);
    
    if( !$result ) return false;

    return true;
}

function checkTag(string $tag){
    global $MySQLDatabase;

    $sql = "SELECT * from `Tags` WHERE `Tag` = '$tag';";
    $result = $MySQLDatabase->query($sql);

    if($result->num_rows) return $result->fetch_assoc()['ID'];

    $sql = "INSERT INTO `Tags` (`Tag`) VALUES ('$tag');";
    $result = $MySQLDatabase->query($sql);

    return $MySQLDatabase->insert_id;
}

//------------------//
// Author managment //
//------------------//

function doesAuthorExist(int $authorID){
    global $MySQLDatabase;

    $sql = "SELECT 1 from `People` WHERE `ID` = $authorID;";
    $result = $MySQLDatabase->query($sql);

    if( $result->fetch_assoc() ) return true;

    return false;
}

function addAuthor(string $firstName, string $secondName, string $surname){
    global $MySQLDatabase;

    $sql    = "INSERT INTO `People` (`FirstName`, `SecondName`, `Surname`) VALUES ('$firstName', '$secondName', '$surname')";
    $result = $MySQLDatabase->query($sql);

    if( $result->fetch_assoc() ) return true;

    return false;
}

//----------------\
// File managment |
//----------------/

function optimizeURL(string $URL){
    global $MySQLDatabase;
    
    $URL = strtolower($URL);
    // remove protocol prefix
    $URL = str_replace( array('https:', 'http:'), '', $URL);
    
    $sql    = "SELECT * FROM `ServersPaths` WHERE REGEXP_LIKE('$URL', `Path`);";
    $result = $MySQLDatabase->query($sql);
    
    $rows = array();
    while($row = $result->fetch_assoc()){
        array_push($rows, $row);
    }

    usort($rows, function($a, $b) { return strlen($b['Path']) - strlen($a['Path']); });

    $remaining = str_replace( $rows[0]['Path'], '', $URL );

    return array('storageServer' => $rows[0], 'remaining' => $remaining);
}

function verifyFileURL(string $URL){
    // dont use https as it will ad overhead and server-client connection will be secure anyway
    $URL = str_replace('https', 'http', $URL);

    $stream = fopen($URL, 'rb');

    if( $stream == false ) return 1;

    $data = fread($stream, 512);

    fclose($stream);
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->buffer($data);
}

function addFile(string $name, string $description, string $URL, int $authorID, array $tags){
    global $MySQLDatabase;

    if( !isSignedIn() ) return 1;
    
    $MIME = verifyFileURL($URL);

    if( !$MIME ) return 2;

    $optimizedURL  = optimizeURL($URL);
    $storageServer = $optimizedURL['storageServer']['ID'];
    $remaining     = $optimizedURL['remaining'    ];
    $postedBy      = $_SESSION['migurdia']['userID'];
    $fileFormat    = checkFileFormat($MIME);

    $sql = "INSERT INTO `Files` (`Name`, `Description`, `StorageServer`, `ServerPath`, `Author`, `PostedBy`, `FileFormat`) VALUES " .
           "('$name', '$description', $storageServer, '$remaining', $authorID, $postedBy, $fileFormat);";
    $result = $MySQLDatabase->query($sql);
    
    if( !$result ) return 3;

    // setting tags
    $file    = $MySQLDatabase->insert_id;
    $tagsIDs = array();

    foreach($tags as $tag) array_push($tagsIDs, checkTag($tag));

    setFileTags($file, $tagsIDs);

    return 0;
}

function setFileTags(int $fileID, array $tagsIDs){
    global $MySQLDatabase;

    // Delete all tags from current file
    $sql = "DELETE FROM `FilesTags` WHERE `FileID` = $fileID";
    $result = $MySQLDatabase->query($sql);

    // insert new tags
    $sql = 'INSERT INTO `FilesTags` (`FileID`, `TagID`) VALUES';
    foreach($tagsIDs as $i => $tagID) $sql .= (" ($fileID, $tagID)" . ((count($tagsIDs) - 1) == $i ? ';' : ','));
    $result = $MySQLDatabase->query($sql);
}

// getfiles v0.0.1
function getFiles(array $tags=[], array $formats=[], int $limit=20){
    global $MySQLDatabase;

    if( !isSignedIn() ) return 1;

    $userID = $_SESSION['migurdia']['userID'];

    $sql = " SELECT                                                                                    "
         . "   `Files`.`ID`,                                                                           "
         . "   `Files`.`Name`,                                                                         "
         . "   CONCAT(`ServersPaths`.`Path`, `Files`.`ServerPath`) AS `URL`                            "
         . " FROM                                                                                      "
         . "   (SELECT `TagID` FROM `UsersPermissions` WHERE `userID` = $userID) AS `UserPermissions`  "
         . "   RIGHT JOIN `FilesTags`    ON `FilesTags`   .`TagID` = `UserPermissions`. `TagID`        "
         . "   RIGHT JOIN `Files`        ON `Files`       .   `ID` = `FilesTags`      .`FileID`        "
         . "   INNER JOIN `Tags`         ON `Tags`        .   `ID` = `FilesTags`      . `TagID`        "
         . "   INNER JOIN `ServersPaths` ON `ServersPaths`.   `ID` = `Files`          .`StorageServer` "
         . " GROUP BY                                                                                  "
         . "   `Files`.`ID`                                                                            "
         . " HAVING                                                                                    "
         . "   (SUM(`Tags`.`RequiresPermission`) - COUNT(`UserPermissions`.`TagID`) = 0)               ";

    if( !empty($tags) ){
        if( isset($tags['unwanted']) ){
            $unwantedTags = '(';
            $lastKey   = array_key_last($tags['unwanted']);

            foreach($tags['unwanted'] as $key => $tag){
                if ( !is_numeric($tag) ) continue;

                $unwantedTags .= $tag;
                $unwantedTags .= ($lastKey = $key) ?  ')' : ',';
            }
            
            $sql .= "AND (SUM(IF(`FilesTags`.`TagID` IN $unwantedTags, 1, 0)) = 0)";
        }

        if( isset($tags['wanted']) ){
            $wantedTags = '(';
            $lastKey   = array_key_last($tags['wanted']);

            foreach($tags['wanted'] as $key => $tag){
                if( !is_numeric($tag) ) continue;

                $wantedTags .=  $tag;
                $wantedTags .= ($lastKey = $key) ?  ')' : ',';
            }
            
            $sql .= "ORDER BY SUM(IF(`FilesTags`.`TagID` IN $wantedTags, 1, 0)) DESC;";
        }
    }

    $sql .= " LIMIT $limit; ";

    $response = $MySQLDatabase->query($sql);
    $result   = array();

    while( $row = $response->fetch_assoc() ) array_push($result, $row);

    $response->free_result();

    return $result;
}

//-------------------\
// File format stuff |
//-------------------/

function getFileFormatProposals($hint, int $limit=20){
    global $MySQLDatabase;
    
    $hint = $MySQLDatabase->real_escape_string($hint);

    $sql = "SELECT * FROM `FileFormats` WHERE `format` LIKE \"%$hint%\" LIMIT $limit;";
    
    $response = $MySQLDatabase->query($sql);
    $formats  = array();

    while($row = $response->fetch_row())
        array_push($formats, $row);

    $response->free_result();

    return $fileFormats;
}

function checkFileFormat(string $MIME){
    global $MySQLDatabase;

    $sql = "SELECT `ID` from `FileFormats` WHERE `MIME` = '$MIME';";
    $result = $MySQLDatabase->query($sql);

    if($result->num_rows) return $result->fetch_assoc()['ID'];

    $sql = "INSERT INTO `FileFormats` (`MIME`) VALUES ('$MIME');";
    $result = $MySQLDatabase->query($sql);

    return $MySQLDatabase->insert_id;
}

//-------------------------------\
//                               |
//         MAIN SECTION          |
//                               |
//-------------------------------/

# connetion purpose
$cp = strtolower(requireField('method'));

switch($cp){
	case 'getfiles':{
        $tags = optionalField('tags', '[]');
        $tags = json_decode  ($tags );

        $result = getFiles($tags);
 
        switch( $result ){
            case  1: criticalError('Session expired.', 7); break;
        }

		success($result);

		break;
	}
    case 'signin':{
        $username = requireField('username');
        $password = requireField('password');

        switch ( signin($username, $password) ) {
            case 0:  success('')                                               ; break;
            case 1:  criticalError('Cannot find user with such name/email.', 2); break;
            case 2:  criticalError('Wrong password.'                       , 3); break;
            default: criticalError('Unknown.'                              , 1); break;
        }

        break;
    }
    case 'signup':{
        $username = requireField('username'); // This will be checked, obviously
        $email    = requireField('email'   ); // Checked only by build-in PHP function cuz I'm lazy
        $password = requireField('password'); // I won't check if null, if someone don't want to have password, I don't care
        
        if( !filter_var($email, FILTER_VALIDATE_EMAIL) )
            criticalError('Invalid email adress.', 4);

        switch ( signup($username, $email, $password) ) {
            case 0:  success()                                  ; break;
            case 1:  criticalError('Username already taken.', 5); break;
            case 2:  criticalError('Email already taken.'   , 6); break;
            default: criticalError('Unknown.'               , 1); break;
        }

        break;
    }
	case 'gettagproposals':{
		$hint         = optionalField('hint');
		$proposedTags = getTagProposals($hint);
        
        success($proposedTags);

		break;
	}
	case 'getfileformatproposals':{
		$hint                = optionalField('hint');
		$proposedFileFormats = getFileFormatProposals($hint);

        success($proposedFileFormats);

		break;
	}
    case 'addfile':{
        $files  = requireField('files');
        $files  = json_decode ($files, true);
        $result = array();

        foreach($files as $file){
            if( !isset($file['URL'        ]) ) { array_push($result, array('error')); continue; }

            if( !isset($file['tags'       ]) ) $file['tags'       ] = array(); 
            if( !isset($file['name'       ]) ) $file['name'       ] = 'Untitled';
            if( !isset($file['description']) ) $file['description'] = '';
            if( !isset($file['author'     ]) ) $file['author'     ] = 1;

            $success = addFile(
                $file['name'],
                $file['description'],
                $file['URL'],
                $file['author'],
                $file['tags']
            );

            switch($success){
                case 1:  break;
            }

            $tmp = array(
                'suppliedURL' => $file['URL'],
                'success' => $success,
                'fileID'  => 0
            );

            array_push($result, $tmp);
        }

        success($result);
        
        break;
    }
    case 'signout': { session_destroy(); break; }
	default: { criticalError("unknown connection purpose('$cp').", 1); break; }
}

exit;

?>