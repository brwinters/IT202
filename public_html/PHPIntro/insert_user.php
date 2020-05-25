<?php
require(__DIR__ . "/config.php");

$connection_string = "mysql:host=$dbhost;dbname=$dbdatabase;charset=utf8mb4";
try{
	$db = new PDO($connection_string, $dbuser, $dbpass);
	$stmt = $db->prepare("INSERT INTO User (email) VALUES(:email)");
	$r = $stmt->execute(array(
		":email"=>"test@test.com"
	));
	echo var_export($r, true);
	echo var_export($stmt->errorInfo(), true);
}
catch (Exception $e){
	echo $e->getMessage();
}
?>