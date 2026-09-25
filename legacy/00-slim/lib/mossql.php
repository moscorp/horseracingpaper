<?
	include_once "ez_sql_core.php";
	include_once "ez_sql_mysqli.php";

// 	define("db_user", "innomtzv_melvin");			// <-- mysql db user
// 	define("db_password", "mopass.24626388");		// <-- mysql db password
// 	define("db_name", "innomtzv_moosay");		// <-- mysql db pname
// 	define("db_host", "localhost");	// <-- mysql server host
	

//     $dbuser = "innomtzv_melvin";
//     $dbpassword = "mopass.24626388";

require_once("lib/constants.php");

if ( !ini_get("register_globals") ) { 
	extract($_POST); 
	extract($_GET); 
	extract($_SERVER); 
	extract($_FILES); 
	extract($_ENV); 
	extract($_COOKIE); 

	if ( isset($_SESSION) ) { 
		extract($_SESSION); 
	} 
} 
// 	$db = new ezSQL_mysql(db_user,db_password,db_name,db_host);

	$db = new ezSQL_mysqli(DB_USER,DB_PASS,DB_NAME,DB_HOST);
?>