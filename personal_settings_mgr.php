<?php
include ($_SERVER['DOCUMENT_ROOT'] .'\include\engine.php');
$action=$_GET["a"];
switch ($action) {
	case "saveUserData":
		$UserID=@$_GET["id"];
		$LangID=@$_POST["LangID"];
		$Phone=@$_POST["Phone"];
		if($Phone==null){$Phone="";}
		if($UserID!=="" && $UserID!==null && $UserAuth==1){
			$Name=@$_POST["Name"];
			$Lastname=@$_POST["Lastname"];
			$Email=@$_POST["Email"];
			if($UserID!=="new"){
				$query="UPDATE users";
			}else{
				$query="INSERT INTO users";
				$UserPass=strtoupper(RandomString(6));
				$UserPassHash=password_hash($UserPass, PASSWORD_BCRYPT, ['cost' => 10]);
			}
			$query.=" SET Name='". mysql_real_escape_string($Name) ."', Lastname='". mysql_real_escape_string($Lastname) ."', 
			Email='". mysql_real_escape_string($Email) ."', Username='". mysql_real_escape_string($Email) ."',
			LangID='". mysql_real_escape_string($LangID) ."', Phone='". mysql_real_escape_string($Phone) ."'";
			if($UserID!=="new"){
				$query.=" WHERE UserID='". $UserID ."'";
			}else{
				$query.=", Password='". mysql_real_escape_string($UserPassHash) ."', 
				EmailVerified=now(), EmailVerifiedIP='". $UIP ."', /*RecUID='". $UID ."', */RecUIP='". $UIP ."'";
			}
			
			//Controllo se l'utente che si sta cercando di creare non è già presente nel sistema
			$ExistUserID="";
			$query_check="SELECT UserID FROM users WHERE Email='". mysql_real_escape_string($Email) ."'";
			$result_check = mysql_query($query_check) or die ("errore connessione db ".mysql_error());
			while ($row_check = mysql_fetch_array($result_check, MYSQL_ASSOC)){
				$ExistUserID=$row_check["UserID"];
			}
			if($ExistUserID==="" || $ExistUserID===null){
				mysql_query($query) or die ("errore connessione db ".mysql_error());
			}
			if($UserID=="new"){
				if($ExistUserID==="" || $ExistUserID===null){
					$UserID=mysql_insert_id();
				}else{
					$UserID=$ExistUserID;
				}
				
				/* Creo la relazione tra utente e farm */
				$query="INSERT INTO users_farms SET UID='". $UserID ."', FarmID='". $farmRecID ."', Auth=2";
				mysql_query($query) or die ("errore connessione db ".mysql_error());
				
				if($Email!=="" && $Email!==null){
					
					/* Invio un email di conferma all'utente */
					$NotificationSubject="Sensilize - ". lang(48);
					$bodymessage=lang(48) ."<br>". lang(7) .": ". $Email ."<br>". lang(8) .": ". $UserPass;

					//Compongo l'email dal template e invio
					$NotificationTemplate=str_replace("%bodymsg%", $bodymessage,$NotificationTemplate);
					$NotificationTemplate=str_replace("%MailLogo%", $MailLogo,$NotificationTemplate);
					$NotificationTemplate=str_replace("%weburl%", $DomainSSL,$NotificationTemplate);
					SendMail($farmRecID, $NotificationFromName, $NotificationFromEmail, $Email, $NotificationSubject, $NotificationTemplate, "","a.kornelyuk@sensilize.com");
				}
				echo $UserID;
			}
		}else{
			$query="UPDATE users SET LangID='". mysql_real_escape_string($LangID) ."', Phone='". mysql_real_escape_string($Phone) ."' WHERE UserID='". $UID ."'";
			mysql_query($query) or die ("errore connessione db ".mysql_error());
		}
	break;
	case "checkOldPassword":
		$OldPassword=$_GET["p"];
		
		$query="Select * FROM users WHERE Email='". mysql_real_escape_string($UEmail) ."'";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$checkpass=$row["Password"];
			if (!password_verify($OldPassword, $checkpass)) {
				echo "ERR". lang(182);
			}else{
				echo "<div class='form_container' style='width: 500px;max-width: 100%;'>
					<div class='form_row'>
						<div class='form_label'>". lang(31) ."</div>
						<div class='form_field'>
							<input type='password' id='Password1' class='form-control' placeholder='". lang(31) ."' onkeyup='validatePassword(this.value);'>
							<div id='passMsg'></div>
						</div>
					</div>
					<div class='form_row'>
						<div class='form_label'>". lang(32) ."</div>
						<div class='form_field'>
							<input type='password' id='Password2' class='form-control' placeholder='". lang(32) ."'>
						</div>
					</div>
					<div class='form_row'>
						<div class='form_field'>
							<button class='btn form-control' onclick='changePassword();' id='resetPasswordButton'>". lang(87) ."</button>
						</div>
					</div>
				</div>";
			}
		}
	break;
}
?>
