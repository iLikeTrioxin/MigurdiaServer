<?php

header('Access-Control-Allow-Origin: *');

// additional setting to make communication with API easier
if( isset($_REQUEST['sid']) ) session_id($_REQUEST['sid']);

session_start();

// This API needs so much more work that
// for now shouldn't be even considered beta
// PS. It's my second php script in my life
// If you see any bugs or things I could do better
// please commit your change or tell me about it
// on discord or any other media (can be found on https://www.yukiteru.xyz)

//  Exit codes:
//      0 - Success
//      1 - Something went wrong (server-side) ex. can't connect to database
//      2 - Can't find user with such name/email
//      3 - Wrong password
//      4 - Wrong email
//      5 - User with this name already exists
//      6 - User with thus email already exits
//      7 - Session expired
//      8 - Bad request

include('credentials.php');
include('utils.php');

$MySQLDatabase = new mysqli('localhost', $MySQLuser, $MySQLpassword, $MySQLdbname);

if( $MySQLDatabase->connect_errno ) returnResult('Something went wrong, try again later.', 1);

//----------------------------------\
//                                  |
//     Database related stuff       |
//                                  |
//----------------------------------/

//----------------------\
//  Email Verification  |
//----------------------/

// checks if user with specified email is verified and returns true or false
function isEmailVerified($email): int {
    global $MySQLDatabase;

    $sql      = "SELECT `verificationCode` FROM `Users` WHERE `email` = '$email';";
    $response = $MySQLDatabase->query($sql);

    if($code = $response->fetch_row())
        return $code == 'verified';

    return false;
}

// 0 - ok 1- already done
function sendVerificationEmail($email): int {
    global $MySQLDatabase;

    try {
        $code = bin2hex(random_bytes(4));
    } catch (Exception $e) { return 1; }

    $sql = "UPDATE `Users` SET `verificationCode` = '$code' WHERE `email` = '$email';";

    if( !( $MySQLDatabase->query($sql) ) )
        return 1;

    $msg = "Your code: $code";

    mail($email, 'Migurdia - verification email.', $msg);

    return 0;
}

// 0 - correct 1 - not correct
function confirmUserEmail(string $email, $code): int {
    global $MySQLDatabase;

    $sql = "SELECT `VerificationCode` FROM `Users` WHERE `email` = '$email';";

    $response = $MySQLDatabase->query($sql);

    if($response->fetch_row()[0] != $code)
        return 1;

    $sql = "UPDATE `Users` SET `VerificationCode` = 'verified' WHERE `Email` = '$email';";

    $response = $MySQLDatabase->query($sql);

    if(!$response) return 1;

    return 0;
}

//--------------------\
// account management |
//--------------------/

function signIn($username, $password): int {
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

function signup($username, $email, $password): int {
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

function isSignedIn(): int{
    if( isset($_SESSION['migurdia']['userID']) )
        if(   $_SESSION['migurdia']['userID']  ) return true;

    return false;
}

//-------------------\
// Tag related stuff |
//-------------------/

// returns array(array(tagID, tag), array(tagID, tag)...)
function getTagProposals(array &$result, string $hint, int $limit=20): int {
    global $MySQLDatabase;

    $hint = $MySQLDatabase->real_escape_string($hint);

    $sql = "SELECT * FROM `Tags` WHERE `tag` LIKE \"%$hint%\" LIMIT $limit;";

    $response  = $MySQLDatabase->query($sql);

    while($row = $response->fetch_row())
        array_push($result, $row);

    $response->free_result();

    return 0;
}

// returns internal error code
function addTags(int $postID, array $tags): int {
    global $MySQLDatabase;

    $sql = 'INSERT INTO `PostsTags` (`PostID`, `TagID`) VALUES ';
    foreach($tags as $tag) $sql .= "($postID, $tag)";

    $result = $MySQLDatabase->query($sql);

    if( !result ) return 1;

    return 0;
}

// returns tag ID
function checkTag(string $tag): int {
    global $MySQLDatabase;

    $sql = "SELECT * from `Tags` WHERE `Tag` = '$tag';";
    $result = $MySQLDatabase->query($sql);

    if($result->num_rows) return $result->fetch_assoc()['ID'];

    $sql = "INSERT INTO `Tags` (`Tag`) VALUES ('$tag');";

    $MySQLDatabase->query($sql);

    return $MySQLDatabase->insert_id;
}

//-----------------\
// File management |
//-----------------/

function optimizeURL(array &$result, string $url): int {
    global $MySQLDatabase;

    $lcUrl = strtolower($url);
    
    $sql = $MySQLDatabase->prepare("SELECT `ID`, `URL` FROM `Hostings` WHERE REGEXP_LIKE(?, `URL`);");
    $sql->bind_param('s', $lcUrl);
    $sql->execute();
    $sql->store_result();
    $sql->bind_result($hosting['ID'], $hosting['URL']);

    if($sql->num_rows == 0){
        $result['hosting'  ] = array('ID' => NULL, 'URL' => NULL);
        $result['remaining'] = $url;
        return 0;
    }

    $hostings = [];
    while($sql->fetch())
        array_push($hostings, $hosting);

    usort($hostings, function($a, $b) { return strlen($b['URL']) - strlen($a['URL']); });

    $result['hosting'  ] = $hostings[0];
    $result['remaining'] = str_replace( $hostings[0]['URL'], '', $url );

    return 0;
}

function verifyFileURL(string $url): int {
    $stream = fopen($url, 'rb');

    if( $stream == false ) return 1;

    $data = fread($stream, 512);

    fclose($stream);

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);

    if( !$fileInfo->buffer($data) ) return 1;

    return 0;
}

function addPost(string $name, string $description, string $fileUrl, string $thumbnailUrl, array $tags): int {
    global $MySQLDatabase;

    if( !isSignedIn   (             ) ) return 1;
    if(  verifyFileURL(     $fileUrl) ) return 2;
    if(  verifyFileURL($thumbnailUrl) ) return 3;

    $optimizedFileUrl      = array();
    $optimizedThumbnailUrl = array();

    if( optimizeURL($optimizedFileUrl     ,      $fileUrl) ) return 4;
    if( optimizeURL($optimizedThumbnailUrl, $thumbnailUrl) ) return 5;

    $fileUrlHosting = $optimizedFileUrl['hosting'  ]['ID'];
    $fileUrlPath    = $optimizedFileUrl['remaining'];

    $thumbnailUrlHosting = $optimizedThumbnailUrl['hosting'  ]['ID'];
    $thumbnailUrlPath    = $optimizedThumbnailUrl['remaining'];

    $postedBy   = $_SESSION['migurdia']['userID'];

    $sql = 'INSERT INTO `Posts` '
         . '(`Name`, `Description`, `FileHosting`, `FilePath`, `ThumbnailHosting`, `ThumbnailPath`, `PostedBy`) '
         . 'VALUES (?,?,?,?,?,?,?)';

    $sql = $MySQLDatabase->prepare($sql);
    $sql->bind_param('ssisisi',
        $name,
        $description,
        $fileUrlHosting,
        $fileUrlPath,
        $thumbnailUrlHosting,
        $thumbnailUrlPath,
        $postedBy
    );
    
    if( !$sql->execute() ) return 6;

    // setting tags
    $postID  = $sql->insert_id;
    $tagsIDs = array();

    foreach($tags as $tag) array_push($tagsIDs, checkTag($tag));

    setPostTags($postID, $tagsIDs);

    return 0;
}

function setPostTags(int $postID, array $tagsIDs): int {
    global $MySQLDatabase;

    // Delete all tags from current file
    $sql = "DELETE FROM `PostsTags` WHERE `PostID` = $postID";
    $MySQLDatabase->query($sql);

    // insert new tags
    $sql = 'INSERT INTO `PostsTags` (`PostID`, `TagID`) VALUES';
    foreach($tagsIDs as $i => $tagID) $sql .= (" ($postID, $tagID)" . ((count($tagsIDs) - 1) == $i ? ';' : ','));

    $MySQLDatabase->query($sql);

    return 0;
}

function getPosts(array &$result, array $tags=[], int $limit=20, int $offset=0): int {
    global $MySQLDatabase;

    if( !isSignedIn() ) return 1;

    $userID = $_SESSION['migurdia']['userID'];

    $sql = "SELECT                                                                                                      "
         . "  `Posts`.`ID`             AS `id`,                                                                         "
         . "  `Posts`.`Name`           AS `name`,                                                                       "
         . "  `FileHosting`.`URL`      AS `fileHosting`,                                                                "
         . "  `Posts`.`FilePath`       AS `filePath`,                                                                   "
         . "  `ThumbnailHosting`.`URL` AS `thumbnailHosting`,                                                           "
         . "  `Posts`.`ThumbnailPath`  AS `thumbnailPath`                                                               "
         . "FROM                                                                                                        "
         . "  (SELECT `TagID` FROM `UsersPermissions` WHERE `userID` = $userID) AS `UserPermissions`                    "
         . "  RIGHT JOIN `PostsTags` ON `PostsTags`.`TagID` = `UserPermissions` . `TagID`                               "
         . "  RIGHT JOIN `Posts`     ON `Posts`    .   `ID` = `PostsTags`       .`PostID`                               "
         . "  LEFT  JOIN `Tags`      ON `Tags`     .   `ID` = `PostsTags`       . `TagID`                               "
         . "  LEFT  JOIN `Hostings`  AS      `FileHosting` ON      `FileHosting`.    `ID` = `Posts`.     `FileHosting`  "
         . "  LEFT  JOIN `Hostings`  AS `ThumbnailHosting` ON `ThumbnailHosting`.    `ID` = `Posts`.`ThumbnailHosting`  "
         . "GROUP BY                                                                                                    "
         . "  `Posts`.`ID`                                                                                              "
         . "HAVING                                                                                                      "
         . "  (COALESCE(SUM(`Tags`.`RequiresPermission`), 0) - COUNT(`UserPermissions`.`TagID`) = 0)                    ";

    if( !empty($tags) ){
        if( isset($tags['unwanted']) ){
            $unwantedTags = '(';
            $lastKey   = array_key_last($tags['unwanted']);

            foreach($tags['unwanted'] as $key => $tag){
                if ( !is_numeric($tag) ) continue;

                $unwantedTags .= $tag;
                $unwantedTags .= ($lastKey == $key) ?  ')' : ',';
            }

            $sql .= "AND (SUM(IF(`PostsTags`.`TagID` IN $unwantedTags, 1, 0)) = 0)";
        }

        if( isset($tags['wanted']) ){
            $wantedTags = '(';
            $lastKey   = array_key_last($tags['wanted']);

            foreach($tags['wanted'] as $key => $tag){
                if( !is_numeric($tag) ) continue;

                $wantedTags .=  $tag;
                $wantedTags .= ($lastKey == $key) ?  ')' : ',';
            }

            $sql .= " ORDER BY SUM(IF(`PostsTags`.`TagID` IN $wantedTags, 1, 0)) DESC `Posts`.`ID` ASC ";
        }else{
            $sql .= " ORDER BY `Posts`.`ID` ASC ";
        }
    }else{
        $sql .= " ORDER BY `Posts`.`ID` ASC ";
    }

    $sql .= " LIMIT $limit OFFSET $offset; ";

    $response = $MySQLDatabase->query($sql);

    if($response == false) return 1;

    while( $row = $response->fetch_assoc() ) array_push($result, $row);
    
    $response->free_result();

    return 0;
}

//-------------------------------\
//                               |
//         MAIN SECTION          |
//                               |
//-------------------------------/

# connection purpose
$cp = strtolower(requiredField('method'));

switch($cp){
    case 'getposts':{
        $tags   = optionalField('tags', '[]');
        $amount = (int) optionalField('amount', 20);
        $offset = (int) optionalField('offset', 0);

        $tags   = json_decode($tags);
        $amount = ($amount > 100) ? 100 : $amount;

        if( !is_array($tags) ) $tags = array();

        $result   = array();
        $exitCode = getPosts($result, $tags, $amount, $offset);

        switch( $exitCode ){
            case  0: returnResult($result               , 0); break;
            case  1: returnResult('Session expired.'    , 7); break;
            default: returnResult('Something went wrong', 1); break;
        }

        break;
    }
    case 'signin':{
        $username = requiredField('username');
        $password = requiredField('password');

        switch ( signIn($username, $password) ) {
            case 0:  returnResult(array("SID" => session_id()))               ; break;
            case 1:  returnResult('Cannot find user with such name/email.', 2); break;
            case 2:  returnResult('Wrong password.'                       , 3); break;
            default: returnResult('Unknown.'                              , 1); break;
        }

        break;
    }
    case 'signup':{
        $username = requiredField('username'); // This will be checked, obviously
        $email    = requiredField('email'   ); // Checked only by build-in PHP function cuz I'm lazy
        $password = requiredField('password'); // I won't check if null, if someone doesn't want to have password, I don't care

        if( !filter_var($email, FILTER_VALIDATE_EMAIL) )
            returnResult('Invalid email address.', 4);

        switch ( signup($username, $email, $password) ) {
            case 0:  returnResult(array("SID" => session_id())); break;
            case 1:  returnResult('Username already taken.', 5); break;
            case 2:  returnResult('Email already taken.'   , 6); break;
            default: returnResult('Unknown.'               , 1); break;
        }

        break;
    }
    case 'gettagproposals':{
        $hint   = requiredField('hint');
        $amount = optionalField('amount', 10);
        
        $result   = array();
        $exitCode = getTagProposals($result, $hint, $amount);

        returnResult($result);

        break;
    }
    case 'addposts':{
        $posts  = requiredField('posts');
        $posts  = json_decode ($posts, true);
        $result = array();

        foreach($posts as $post){
            if( !isset($post['fileUrl'     ]) ) { array_push($result, array('error')); continue; }
            if( !isset($post['thumbnailUrl']) ) { array_push($result, array('error')); continue; }
            if( !isset($post['tags'        ]) ) $post['tags'       ] = array();
            if( !isset($post['name'        ]) ) $post['name'       ] = 'Untitled';
            if( !isset($post['description' ]) ) $post['description'] = '';

            $success = addPost(
                $post['name'],
                $post['description'],
                $post['fileUrl'],
                $post['thumbnailUrl'],
                $post['tags']
            );

            $tmp = array(
                'suppliedURL' => $post['fileUrl'],
                'exitCode'    => $success
            );

            array_push($result, $tmp);
        }

        returnResult($result);

        break;
    }
    case 'signout': { session_destroy(); returnResult([], 0); break; }
    default: { returnResult("unknown connection purpose('$cp').", 1); break; }
}

exit;