			<?php
			$farmRecID="";
			$PageUrlArr=[];
			$PageUrl=explode("?",$PageUrl)[0];
			foreach (explode("/",$PageUrl) as $path) {
				if($path!=="" && $path!=="/"){array_push($PageUrlArr,$path);}
			}
			$UserActivated=0;
			if (count($PageUrlArr)==2){
				$UserID="";
				$AuthCode=$PageUrlArr[1];
				$query="SELECT UserID, Name, Lastname, Email, Password, EmailAuth, Lastupdate, LastupdateIP, EmailVerified, EmailVerifiedIP FROM users 
				WHERE EmailAuth='". mysql_real_escape_string($AuthCode) ."' AND isnull(IsBlocked) AND isnull(EmailVerified) AND isnull(EmailVerifiedIP)";
				//echo $query;
				$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
					$UserID=$row["UserID"];
					$Email=$row["Email"];
				}
				
				$UserPass=strtoupper(RandomString(6));
				$UserPassHash=password_hash($UserPass, PASSWORD_BCRYPT, ['cost' => 10]);
				
				if($UserID!=="" && $UserID!==null){
					$query="UPDATE users SET Password='". mysql_real_escape_string($UserPassHash) ."', EmailVerified=now(), EmailVerifiedIP='". $UIP ."' WHERE UserID='". mysql_real_escape_string($UserID) ."'";
					mysql_query($query) or die ("errore connessione db ".mysql_error());
					$UserActivated=1;
					
					$query="SELECT FarmID, Auth FROM users_farms WHERE isnull(IsDeleted) AND UID='". $UserID ."' ORDER BY IsDefault DESC LIMIT 1";
					$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
						$farmRecID=$row["FarmID"];
					}
				}
			}
			if($UserActivated==1){
				//Invio email
				$NotificationSubject="Sensilize - ". lang(48);
				$bodymessage=lang(48) ."<br>". lang(7) .": ". $Email ."<br>". lang(8) .": ". $UserPass;

				//Compongo l'email dal template e invio
				$NotificationTemplate=str_replace("%bodymsg%", $bodymessage,$NotificationTemplate);
				$NotificationTemplate=str_replace("%MailLogo%", $MailLogo,$NotificationTemplate);
				$NotificationTemplate=str_replace("%weburl%", $DomainSSL,$NotificationTemplate);
				SendMail($farmRecID, $NotificationFromName, $NotificationFromEmail, $Email, $NotificationSubject, $NotificationTemplate, "");
				
				//Messaggio di conferma a video
				echo '
				<div id="formConfirmMsg">
					<div class="row">
						<div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 col-xl-3" style="text-align: center;"><h3><i class="fa fa-check-circle-o fa-2x" style="color: #008000;"></i></h3></div>
						<div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 col-xl-9"><h3>'. lang(50) .'</h3>
							<p>'. lang(51) .'</p>
						</div>
					</div>
				</div>';
			}else{
				echo lang(52);
			}
			?>
