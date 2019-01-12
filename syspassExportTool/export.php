<?php
/**
* <h1>SYSPASS export tool</h1>
* The SYSPASS export tool implements an application that simply 
* export all the passwords (decrypted) and some of the passwords 
* linked infos hold in the syspass database in to a CSV or JSON 
* file.
* 
*
* @author  Fabio Lucattini
* @version 0.0.1
* @since   2019-01-12
*/

require __DIR__ . '/vendor/autoload.php';
use Defuse\Crypto\Crypto;
use Defuse\Crypto\KeyProtectedByPassword;
use Defuse\Crypto\Key;

$outputFolder   = "output";
$outputFilename = "output_syspass_decrypt";
$CSV_SEPARATOR  = ",";

/**
 * database connection parameter 
 * this setup is based on my docker-compose file!
 */
$hostname   ="127.0.0.1";
$port       ="3002";
$dbname     ="syspass";
$user       ="root";
$password   ="syspass";

/******************** [Decrypt functionality] ********************/
function unlockKey($key, $password){
    $unlockedKey = KeyProtectedByPassword::loadFromAsciiSafeString($key)->unlockKey($password);
    return $unlockedKey;
}

function decrypt($accountKey,$accountPassword,$masterKey){
    $unlockedKey        = unlockKey($accountKey, $masterKey);
    $decryptedPassword  = Crypto::decrypt($accountPassword, $unlockedKey);
    $encoding           = mb_detect_encoding($decryptedPassword);
    if (strtolower($encoding) === 'utf-8') {
        $decryptedPassword = utf8_decode($decryptedPassword);
    }

    return $decryptedPassword;
}

/******************** [UTILITY] ********************/

function printMSG($msg){
    echo "\n\n".$msg."\n\n";
}

function createFolder($dirName){
    $dirName = $dirName;
    if (!file_exists($dirName)) {
        mkdir($dirName, 0755, true);
    }
}

function createFile($filename,$string){
    $myfile = fopen($filename, "w") or die("Unable to open file!");
    fwrite($myfile, $string);
    fclose($myfile);
}

function setAsCSVText($arg){
    return "\"$arg\"";
}

/******************** [DATABASE Functionality] ********************/

function db_connect($hostname,$port,$dbname,$user,$password){
    try {
        $db = new PDO ("mysql:host=$hostname;port=$port;dbname=$dbname", $user, $pass);
        return $db;   
    } catch (PDOException $e) {
        printMSG("Errore: " . $e->getMessage());
        return null;
    }    
}

/**
 * Allows to exec a query on a specific db
 * @param 
 */
function db_query($db,$sql){
    if($db==null) return null;
    try {
        $res = $db->query($sql);
        $res->setFetchMode(PDO::FETCH_ASSOC);  
        return $res;
    }catch (PDOException $e) {
        printMSG("Errore: " . $e->getMessage());
        return null;
    }   
}

function getTag($db,$idAccount){
    $sql="SELECT t.id, t.name FROM Tag t INNER JOIN AccountToTag att ON att.accountId = $idAccount AND att.tagId = t.id ";
    $res  = db_query($db,$sql);
    $ret = array();
    while($row = $res->fetch()){  
        $ret[]= $row["name"];
    }
    if(count($ret)>0){
        return implode("|",$ret);
    }else{
        return null;
    }
}

/******************** [START] ********************/

/** 
 * check the argument
 * php export.php <MASTER_KEY>
 */
if(!isset($argv)||count($argv)<2){
    printMSG("NO MASTERKEY PASSED");
    die();
}else{
    $masterKey = $argv[1];
}

$conn = db_connect($hostname,$port,$dbname,$user,$password);

$sql="SELECT a.id, ug.name as 'User Group', u.name as 'User Name', c.name as 'Client Name', a.name as 'Account Name', a.login, a.url, a.pass, a.key, a.notes, a.dateAdd, a.isPrivate, a.isPrivateGroup, a.passDate FROM Account a INNER JOIN UserGroup ug ON a.userGroupId = ug.id INNER JOIN User u ON a.userId = u.id INNER JOIN Client c ON a.clientId = c.id";
$res  = db_query($conn,$sql);

if($res!=null){
    $arr = array();

    //csv header
    $csv = "id,User Group,User Name,Client Name,Account Name,login,url,pass,key,notes,dateAdd,isPrivate,isPrivateGroup,passDate,passDecrypt,tags\n";
    
    while($row = $res->fetch()){  
        $id   = $row['id'];
        if(isset($id)&&$id!=null){
            $pass = $row['pass'];
            $key  = $row['key'];

            $tags = getTag($conn,$id);

            $decryptPass = decrypt($key,$pass,$masterKey);

            $arr[$id]                   = $row;
            $arr[$id]['notes']          = str_replace(array("\r", "\n"), ' ', $arr[$id]['notes']);
            
            $arr[$id]['User Group']     = setAsCSVText($arr[$id]['User Group']);
            $arr[$id]['User Name']      = setAsCSVText($arr[$id]['User Name']);
            $arr[$id]['Client Name']    = setAsCSVText($arr[$id]['Client Name']);
            $arr[$id]['Account Name']   = setAsCSVText($arr[$id]['Account Name']);
            $arr[$id]['url']            = setAsCSVText($arr[$id]['url']);
            $arr[$id]['notes']          = setAsCSVText($arr[$id]['notes']);
            
            $arr[$id]['passDecrypt']    = setAsCSVText($decryptPass);
            $arr[$id]['tags']           = setAsCSVText($tags);
            $csv.= implode($CSV_SEPARATOR,$arr[$id]);
            $csv.= "\n"; 
        }
    }  
    
    createFolder($outputFolder);
    // createFile($outputFolder."/".$outputFilename.".json",json_encode($arr)); //to enable the JSON output
    createFile($outputFolder."/".$outputFilename.".csv",$csv);
    printMSG("FILE $outputFilename CREATED! in $outputFolder");

}else{
    printMSG("NULL DB VALUE");
}
