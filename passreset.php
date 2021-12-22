			<div id="resetPassword" class="formsBlock" style="display:block;">
				<?php
				$PassResetToken="";
				$PageUrlArr=[];
				$PageUrl=explode("?",$PageUrl)[0];
				foreach (explode("/",$PageUrl) as $path) {
					if($path!=="" && $path!=="/"){array_push($PageUrlArr,$path);}
				}
				if (count($PageUrlArr)==2){
					$PassResetToken=$PageUrlArr[1];
				}
				if($PassResetToken!==""){
					$UserID="";
					$query="SELECT UserID FROM users WHERE isnull(IsBlocked) AND PassResetToken='". mysql_real_escape_string($PassResetToken) ."' AND TIMESTAMPDIFF(MINUTE, PassResetTimestamp, now())<15";
					$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
						$UserID=$row["UserID"];
					}
					if($UserID==="" || $UserID===null){$PassResetToken="";}
				}
				if($PassResetToken!==""){
					?>
					<div class="signupFormGroupTitle" style="text-align:center;"><?=lang(31)?></div>
					<div class="signupRow">
						<div class="signupField">
							<input type="password" id="Password1" autocomplete="false" class="form-control form-required" placeholder="<?=lang(31)?>" style="margin-top: 0px;" onkeyup="validatePassword(this.value);">
							<div id="passMsg"></div>
						</div>
					</div>
					<div class="signupRow">
						<div class="signupField">
							<input type="password" id="Password2" autocomplete="false" class="form-control form-required" placeholder="<?=lang(32)?>" style="margin-top: 0px;">
						</div>
					</div>
					<div id="resetPasswordErr" class="form-alert-message" style="padding-bottom: 15px;text-align: center;"><?=lang(33)?></div>
					<input type="button" value="<?=lang(34)?>" class="btn" onclick="resetPassword();" id="resetPasswordButton" style="display:none;width:100%;margin-top: 0px;">	
					<?php
				}else{
					echo "<br>". lang(35);
				}
				?>
			</div>

<script>					

	function resetPassword(){
		var Password1=$("#Password1").val();
		var Password2=$("#Password2").val();
		if(Password1==Password2){
			jQuery.ajax({
				url: "/include/signup_mgr.php?a=resetPassword&token=<?=$PassResetToken?>&p1="+ encodeURIComponent(Password1) +"&p2="+ encodeURIComponent(Password2),
				success: function(data, stato) {
					$("#resetPassword").html(data);
				},
				error: function(richiesta, stato, errori) {
					alert("errore");
				}
			});
		}else{
			$("#resetPasswordErr").slideDown();
		}
	}
</script>