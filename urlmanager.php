<?php
include ($_SERVER['DOCUMENT_ROOT'] .'\include\engine.php');

$PageTitle="";
$PageSection="";
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$PageUrl=$_SERVER["REQUEST_URI"];
$PageUrl=substr($PageUrl, 1);
$PageUrlArr=explode("?",$PageUrl);
$PageUrl=$PageUrlArr[0];
if($PageUrl==""){
	$PageTitle=lang(1);
	$PageSection="home";
	include ($_SERVER['DOCUMENT_ROOT'] .'\include\header.php');
	include ($_SERVER['DOCUMENT_ROOT'] .'\index.php');
	include ($_SERVER['DOCUMENT_ROOT'] .'\include\footer.php');
	die();
}elseif($PageUrl=="login"){
	$PageTitle=lang(2);
	$PageSection="login";
	include ($_SERVER['DOCUMENT_ROOT'] .'\include\header_light.php');
	include ($_SERVER['DOCUMENT_ROOT'] .'\login.php');
	include ($_SERVER['DOCUMENT_ROOT'] .'\include\footer_light.php');
	die();
}elseif($PageUrl=="logout" || $PageUrl=="logout/"){
	setcookie("SK", "", time() + (86400 * 30), "/"); // 86400 = 1 day
	header("location: /login");
	die();
}elseif($PageUrl=="signup"){
	$PageTitle=lang(3);
	$PageSection="signup";
	include ($_SERVER['DOCUMENT_ROOT'] .'\include\header_light.php');
	include ($_SERVER['DOCUMENT_ROOT'] .'\signup.php');
	include ($_SERVER['DOCUMENT_ROOT'] .'\include\footer_light.php');
	die();
}elseif(substr($PageUrl,0,9)=="verifica/"){
	$PageTitle=lang(4);
	$PageSection="verify";
	include ($_SERVER['DOCUMENT_ROOT'] .'\include\header_light.php');
	include ($_SERVER['DOCUMENT_ROOT'] .'\verify.php');
	include ($_SERVER['DOCUMENT_ROOT'] .'\include\footer_light.php');
	die();
}elseif(substr($PageUrl,0,10)=="passreset/"){
	$PageTitle=lang(5);
	$PageSection="passreset";
	include ($_SERVER['DOCUMENT_ROOT'] .'\include\header_light.php');
	include ($_SERVER['DOCUMENT_ROOT'] .'\passreset.php');
	include ($_SERVER['DOCUMENT_ROOT'] .'\include\footer_light.php');
	die();
}
?>
