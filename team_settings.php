<?php
include ($_SERVER['DOCUMENT_ROOT'] .'\include\engine.php');
$page=@$_GET["p"];
if($page===null){$page="";}
if($UserAuth!==1){die("Not auth");}
switch ($page) {
	case "settings":
		echo "<div class='PageTitle'>". lang(261) ."</div>";
		echo "<div class='tabs fieldTabs'>";
			echo "<ul>
				<li><a href='#tabs-people'>". lang(262) ."</a></li>
				<li><a href='#tabs-invoicing'>". lang(160) ."</a></li>
			</ul>
			<div id='tabs-people'>";
				$query="SELECT u.UserID, u.Name, u.Lastname, u.Email FROM users_farms uf
				LEFT OUTER JOIN users u ON u.UserID=uf.UID WHERE isnull(uf.isDeleted) AND uf.FarmID='". $farmRecID ."' AND uf.UID<>". $UID ." AND not isnull(u.UserID) GROUP BY u.UserID";
				$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
					echo "<div class='row tabs-people-row'>";
						echo "<div class='col-xs-12 col-sm-12 col-md-3 col-lg-3 col-xl-3'><a onclick='showTeamUser(". $row["UserID"] .")' class='link'>". $row["Name"] ." ". $row["Lastname"] ."</a></div>";
						echo "<div class='col-xs-12 col-sm-12 col-md-3 col-lg-3 col-xl-3'>". $row["Email"] ."</div>";
						echo "<div class='col-xs-12 col-sm-12 col-md-3 col-lg-6 col-xl-6'>
							<a class='link' onclick=\"resetTeamUserPassword(". $row["UserID"] .",'". $row["Name"] ." ". $row["Lastname"] ."')\">". lang(12) ."</a>&nbsp;&nbsp;
							<a class='link' title='". lang(264) ."' onclick=\"removeTeamUser(". $row["UserID"] .",'". $row["Name"] ." ". $row["Lastname"] ."')\" style='color: #ab2632;'><i class='fa fa-times'></i></a>
						</div>";
					echo "</div>";
				}
				echo "<div class='row tabs-people-row'>";
					echo "<div class='col-xs-12 col-sm-12 col-md-4 col-lg-4 col-xl-4'><a onclick=\"showTeamUser('new')\" class='link'>". lang(263) ."</a></div>";
				echo "</div>";
			echo "</div>
			<div id='tabs-invoicing'>";
				$query="SELECT LegalName, InvoicingVat, InvoicingAddress, InvoicingZip, 
				InvoicingCity, InvoicingProv, InvoicingCountry, InvoicingItalyCode 
				FROM farms WHERE RecID='". mysql_real_escape_string($farmRecID) ."'";
				$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
					$LegalName=$row["LegalName"];
					$InvoicingVat=$row["InvoicingVat"];
					$InvoicingAddress=$row["InvoicingAddress"];
					$InvoicingZip=$row["InvoicingZip"];
					$InvoicingCity=$row["InvoicingCity"];
					$InvoicingProv=$row["InvoicingProv"];
					$InvoicingCountry=$row["InvoicingCountry"];
					$InvoicingItalyCode=$row["InvoicingItalyCode"];
				}
				echo "<div style='margin: 0 auto;margin-top: 30px;width: fit-content;max-width: 100%;margin-bottom:100px;'>
					<div class='form_container' style='width: 500px;max-width: 100%;'>
						<form id='InvoicingForm'>
							<div class='form_row'>
								<div class='form_label'>". lang(164) ."</div>
								<div class='form_field'>
									<input type='text' name='LegalName' class='form-control' placeholder='". lang(165) ."' value='". $LegalName ."'>
								</div>
							</div>
							<div class='form_row'>
								<div class='form_label'>". lang(166) ."</div>
								<div class='form_field'>
									<input type='text' name='InvoicingVat' class='form-control' placeholder='". lang(167) ."' value='". $InvoicingVat ."'>
								</div>
							</div>
							<div class='form_row'>
								<div class='form_label'>". lang(168) ."</div>
								<div class='form_field'>
									<input type='text' name='InvoicingAddress' class='form-control' placeholder=\"". lang(169) ."\" value='". $InvoicingAddress ."'>
								</div>
							</div>
							<div class='form_row'>
								<div class='form_label'>". lang(170) ."</div>
								<div class='form_field'>
									<input type='text' name='InvoicingZip' class='form-control' placeholder=\"". lang(171) ."\" value='". $InvoicingZip ."'>
								</div>
							</div>
							<div class='form_row'>
								<div class='form_label'>". lang(172) ."</div>
								<div class='form_field'>
									<input type='text' name='InvoicingCity' class='form-control' placeholder=\"". lang(173) ."\" value='". $InvoicingCity ."'>
								</div>
							</div>
							<div class='form_row'>
								<div class='form_label'>". lang(174) ."</div>
								<div class='form_field'>
									<input type='text' name='InvoicingProv' class='form-control' placeholder=\"". lang(175) ."\" value='". $InvoicingProv ."'>
								</div>
							</div>
							<div class='form_row'>
								<div class='form_label'>". lang(176) ."</div>
								<div class='form_field'>
									<input type='text' name='InvoicingCountry' class='form-control' placeholder=\"". lang(177) ."\" value='". $InvoicingCountry ."'>
								</div>
							</div>
							<div class='form_row'>
								<div class='form_label'>". lang(178) ."</div>
								<div class='form_field'>
									<input type='text' name='InvoicingItalyCode' class='form-control' placeholder=\"". lang(179) ."\" value='". $InvoicingItalyCode ."'>
								</div>
							</div>
						</form>
						<div class='form_row'>
							<div class='form_field'>
								<button class='btn form-control' onclick='saveInvoicingForm();'>". lang(87) ."</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>";
	break;
	case "user":
		$UserID=$_GET["id"];
		
		$UName="";
		$ULastname="";
		$UEmail="";
		$UPhone="";
		$URecTimeStamp="";
		$query="SELECT * FROM users WHERE UserID='". $UserID ."'";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$UName=$row["Name"];
			$ULastname=$row["Lastname"];
			$UEmail=$row["Email"];
			$UPhone=$row["Phone"];
			$URecTimeStamp=$row["RecTimeStamp"];
		}
		
		$ULangID="";
		$ULangCode="";
		$query="SELECT u.LangID, l.Code FROM users u 
		LEFT OUTER JOIN languages l ON l.LangID=u.LangID 
		WHERE u.UserID='". $UserID ."' AND isnull(u.isBlocked)";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$ULangID=$row["LangID"];
			$ULangCode=$row["Code"];
		}
		if($UserID=="new"){
			echo "<div class='PageTitle'>". lang(263) ."</div>";
		}else{
			echo "<div class='PageTitle'>". $UName ." ". $ULastname ."</div>";
		}
		echo "<div style='margin: 0 auto;margin-top: 30px;width: fit-content;max-width: 100%;margin-bottom:100px;'>
			<div class='form_container' style='width: 500px;max-width: 100%;'>
				<form id='UserDataForm'>
					<div class='form_row'>
						<div class='form_label'>". lang(37) ."</div>
						<div class='form_field'>
							<input tyle='text' name='Name' value='$UName' class='form-control'>	
						</div>
					</div>
					<div class='form_row'>
						<div class='form_label'>". lang(39) ."</div>
						<div class='form_field'>
							<input tyle='text' name='Lastname' value='$ULastname' class='form-control'>	
						</div>
					</div>
					<div class='form_row'>
						<div class='form_label'>". lang(7) ."</div>
						<div class='form_field'>
							<input tyle='text' name='Email' value='$UEmail' class='form-control'>	
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
										if(intval($ULangID)==intval($row["LangID"])){echo " selected";}
									echo ">". $row["Name"] ."</option>";
								}

							echo "</select>
						</div>
					</div>
				</form>
				<div class='form_row'>
					<div class='form_field'>
						<button class='btn form-control' onclick=\"saveUserData('". $UserID ."');\">". lang(87) ."</button>
					</div>
				</div>
				<div class='form_row'>
					<div class='form_field'>
						". lang(163) .": ". (new DateTime($URecTimeStamp))->format('d/m/Y') ."
					</div>
				</div>
			</div>
		</div>";
	break;
}
?>