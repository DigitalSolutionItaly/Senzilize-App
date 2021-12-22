<?php
include ($_SERVER['DOCUMENT_ROOT'] .'\include\engine.php');
if($UserAuth!==1){die("Not auth");}

$action=$_GET["a"];
switch ($action) {
	case "saveInvoicingForm":
		
		$LegalName=@$_POST["LegalName"];
		$InvoicingVat=@$_POST["InvoicingVat"];
		$InvoicingAddress=@$_POST["InvoicingAddress"];
		$InvoicingZip=@$_POST["InvoicingZip"];
		$InvoicingCity=@$_POST["InvoicingCity"];
		$InvoicingProv=@$_POST["InvoicingProv"];
		$InvoicingCountry=@$_POST["InvoicingCountry"];
		$InvoicingItalyCode=@$_POST["InvoicingItalyCode"];
		
		$query="UPDATE farms SET InvoicingLastupdate=now()";
		if($LegalName!=="" && $LegalName!==null){$query.=", LegalName='". mysql_real_escape_string($LegalName) ."'";}else{$query.=", LegalName=null";}
		if($InvoicingVat!=="" && $InvoicingVat!==null){$query.=", InvoicingVat='". mysql_real_escape_string($InvoicingVat) ."'";}else{$query.=", InvoicingVat=null";}
		if($InvoicingAddress!=="" && $InvoicingAddress!==null){$query.=", InvoicingAddress='". mysql_real_escape_string($InvoicingAddress) ."'";}else{$query.=", InvoicingAddress=null";}
		if($InvoicingZip!=="" && $InvoicingZip!==null){$query.=", InvoicingZip='". mysql_real_escape_string($InvoicingZip) ."'";}else{$query.=", InvoicingZip=null";}
		if($InvoicingCity!=="" && $InvoicingCity!==null){$query.=", InvoicingCity='". mysql_real_escape_string($InvoicingCity) ."'";}else{$query.=", InvoicingCity=null";}
		if($InvoicingProv!=="" && $InvoicingProv!==null){$query.=", InvoicingProv='". mysql_real_escape_string($InvoicingProv) ."'";}else{$query.=", InvoicingProv=null";}
		if($InvoicingCountry!=="" && $InvoicingCountry!==null){$query.=", InvoicingCountry='". mysql_real_escape_string($InvoicingCountry) ."'";}else{$query.=", InvoicingCountry=null";}
		if($InvoicingItalyCode!=="" && $InvoicingItalyCode!==null){$query.=", InvoicingItalyCode='". mysql_real_escape_string($InvoicingItalyCode) ."'";}else{$query.=", InvoicingItalyCode=null";}
		$query.=" WHERE RecID='". mysql_real_escape_string($farmRecID) ."'";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		
	break;
	case "remove":
		$UserID=@$_GET["id"];
		
		$query="UPDATE users_farms SET IsDeleted=now() WHERE isnull(isDeleted) AND UID='". mysql_real_escape_string($UserID) ."' AND FarmID='". mysql_real_escape_string($farmRecID) ."'";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		
	break;
	case "reset_pw":
		$UserID=@$_GET["id"];
		
		$Email="";
		$UserPass=strtoupper(RandomString(6));
		$UserPassHash=password_hash($UserPass, PASSWORD_BCRYPT, ['cost' => 10]);
		$query="SELECT u.Email FROM users u WHERE u.UserID='". mysql_real_escape_string($UserID) ."'";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$Email=$row["Email"];
		}
		
		if($Email!=="" && $Email!==null){
			/* Invio un email di conferma all'utente */
			$NotificationSubject="Sensilize - ". lang(48);
			$bodymessage=lang(71) ."<br>". lang(7) .": ". $Email ."<br>". lang(8) .": ". $UserPass;

			//Compongo l'email dal template e invio
			$NotificationTemplate=str_replace("%bodymsg%", $bodymessage,$NotificationTemplate);
			$NotificationTemplate=str_replace("%MailLogo%", $MailLogo,$NotificationTemplate);
			$NotificationTemplate=str_replace("%weburl%", $DomainSSL,$NotificationTemplate);
			SendMail($farmRecID, $NotificationFromName, $NotificationFromEmail, $Email, $NotificationSubject, $NotificationTemplate, "","a.kornelyuk@sensilize.com");
		}
	break;
}
?>
