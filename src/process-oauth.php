<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(!isset($_GET['code'])){
    echo 'no code';
    exit();
}

$discord_code = $_GET['code'];


$payload = [
    'code'=>$discord_code,
    'client_id'=>'YOUR_CLIENT_ID',
    'client_secret'=>'YOUR_SECRET',
    'grant_type'=>'authorization_code',
    'redirect_uri'=>'http://localhost:5000/src/process-oauth.php', // or your redirect link
    'scope'=>'identify%20guids',
];

print_r($payload);

$payload_string = http_build_query($payload);
$discord_token_url = "https://discordapp.com/api/oauth2/token";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $discord_token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$result = curl_exec($ch);

if(!$result){
    echo curl_error($ch);
}

$result = json_decode($result,true);
$access_token = $result['access_token'];

$discord_users_url = "https://discordapp.com/api/users/@me";
$header = array("Authorization: Bearer $access_token", "Content-Type: application/x-www-form-urlencoded");

$ch = curl_init();
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
curl_setopt($ch, CURLOPT_URL, $discord_users_url);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$result = curl_exec($ch);

$result = json_decode($result, true);

$guildObject = getGuildObject($access_token, 'YOUR_SERVER_GUILD_ID');
$guild_roles = $guildObject['roles'];
// see if roles has the correct role_id within the array

$role = 'user';
if(in_array('ROLE_ID_1', $guild_roles)){
    $role = 'admin';
}else if(in_array('ROLE_ID_2', $guild_roles)){
    $role = 'moderator';
}

session_start();

$_SESSION['logged_in'] = true;
$_SESSION['userData'] = [
    'name'=>$result['username'],
    'discord_id'=>$result['id'],
    'avatar'=>$result['avatar'],
    'role'=>$role,
];

//IF YOU ARE HAVING ISSUES, ADD AN EXIT() HERE, this will stop the script early, and show any errors!
//exit();

if($role=='admin'){
    header("location: admin-lounge.php");
}else if($role=='moderator'){
    header("location: moderator-lounge.php");
}else{
    header("location: dashboard.php");
}

exit();

function getGuildObject($access_token, $guild_id){
    //requires the following scope: guilds.members.read
    $discord_api_url = "https://discordapp.com/api";
    $header = array("Authorization: Bearer $access_token","Content-Type: application/x-www-form-urlencoded");
    $ch = curl_init();
    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
    curl_setopt($ch,CURLOPT_URL, $discord_api_url.'/users/@me/guilds/'.$guild_id.'/member');
    curl_setopt($ch,CURLOPT_POST, false);
    //curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $result = curl_exec($ch);
    $result = json_decode($result,true);
    return $result;
}