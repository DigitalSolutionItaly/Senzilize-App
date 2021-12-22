<?php
include ($_SERVER['DOCUMENT_ROOT'] .'\include\engine.php');

echo "<div class='PageTitle'>". lang(260) ."</div>";
echo "<div class='tabs fieldTabs'>";
	echo "<ul>
		<li><a href='#tabs-userdata'>". lang(159) ."</a></li>
		<li><a href='#tabs-changepass'>". lang(161) ."</a></li>
	</ul>";
	echo "<div id='tabs-userdata'>
		<div style='margin: 0 auto;margin-top: 30px;width: fit-content;max-width: 100%;margin-bottom:100px;'>
			<div class='form_container' style='width: 500px;max-width: 100%;'>
				<form id='UserDataForm'>
					<div class='form_row'>
						<div class='form_label'>". lang(37) ."</div>
						<div class='form_field'>
							<input tyle='text' value='$UName' class='form-control' disabled>	
						</div>
					</div>
					<div class='form_row'>
						<div class='form_label'>". lang(39) ."</div>
						<div class='form_field'>
							<input tyle='text' value='$ULastname' class='form-control' disabled>	
						</div>
					</div>
					<div class='form_row'>
						<div class='form_label'>". lang(7) ."</div>
						<div class='form_field'>
							<input tyle='text' value='$UEmail' class='form-control' disabled>	
						</div>
					</div>
					<div class='form_row'>
						<div class='form_label'>". lang(187) ."</div>
						<div class='form_field'>
							<input tyle='text' name='Phone' value='$UPhone' class='form-control'>	
						</div>
					</div>
					<div class='form_row'>
						<div class='form_label'>". lang(162) ."</div>
						<div class='form_field'>
							<select name=LangID class='form-control'>";
								$query="SELECT LangID, Code, Name from languages WHERE Active=1 ORDER BY Name";
								$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
								while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
									echo "<option value='". $row["LangID"] ."'";
										if(intval($LangID)==intval($row["LangID"])){echo " selected";}
									echo ">". $row["Name"] ."</option>";
								}

							echo "</select>
						</div>
					</div>
				</form>
				<div class='form_row'>
					<div class='form_field'>
						<button class='btn form-control' onclick='saveUserData();'>". lang(87) ."</button>
					</div>
				</div>
				<div class='form_row'>
					<div class='form_field'>
						". lang(163) .": ". (new DateTime($URecTimeStamp))->format('d/m/Y') ."
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id='tabs-changepass'>
		<div id='changepassForm' style='margin: 0 auto;margin-top: 30px;width: fit-content;max-width: 100%;margin-bottom:100px;'>
			<div class='form_container' style='width: 500px;max-width: 100%;'>
				<div class='form_row'>
					<!--<div class='form_label'>". lang(180) ."</div>-->
					<div class='form_field'>
						<input type='password' id='oldPassword' class='form-control' placeholder='". lang(180) ."'>
					</div>
				</div>
				<div class='form_row'>
					<div class='form_field'>
						<button class='btn form-control' onclick='checkOldPassword();'>". lang(181) ."</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>";
?>