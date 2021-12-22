<?php
include ($_SERVER['DOCUMENT_ROOT'] .'\include\engine.php');

$action=$_GET["a"];
switch ($action) {
	case "farm_notes":
		echo getSidebarNotes();
	break;
	case "createNotice":
		$FarmRecID="";
		$fieldRecID=@$_POST["fieldRecID"];
		$PolygonPath=@$_POST["PolygonPath"];
		$CenterLat=$_POST["CenterLat"];
		$CenterLng=$_POST["CenterLng"];
		$ReportTypeID=@$_POST["ReportTypeID"];	
		$ReportSubject=@$_POST["ReportSubject"];
		$ReportText=@$_POST["ReportText"];
		if($fieldRecID===null){$fieldRecID="";}
		if($ReportTypeID===null){$ReportTypeID="";}
		if($ReportSubject===null){$ReportSubject="";}
		if($ReportText===null){$ReportText="";}	
		if($PolygonPath!=="" && $PolygonPath!==null){
			$PolygonPath=str_replace("),(","],[",$PolygonPath);
			$PolygonPath=str_replace("(","",$PolygonPath);
			$PolygonPath=str_replace(")","",$PolygonPath);
			$PolygonPathArr=explode("],[",$PolygonPath);
			array_push($PolygonPathArr, $PolygonPathArr[0]);
			$GeoJson="[";
			for ($p = 0; $p <= count($PolygonPathArr)-1 ; ++$p) {
				if($p>0){$GeoJson.=",";}
				$CoordArr=explode(",",$PolygonPathArr[$p]);
				$GeoJson.="[". $CoordArr[1].",".$CoordArr[0] ."]";
			}
			$GeoJson.="]";
		}else{
			$GeoJson="";
		}

		$query="INSERT INTO reporting SET FarmID='". $farmRecID ."'";
		if($fieldRecID!==""){$query.=", FieldID='". mysql_real_escape_string($fieldRecID) ."'";}
		if($ReportTypeID!==""){$query.=", ReportTypeID='". mysql_real_escape_string($ReportTypeID) ."'";}
		if($ReportSubject!==""){$query.=", ReportSubject='". mysql_real_escape_string($ReportSubject) ."'";}
		if($ReportText!==""){$query.=", ReportText='". mysql_real_escape_string($ReportText) ."'";}
		$query.=", CenterLat='". mysql_real_escape_string($CenterLat) ."'";
		$query.=", CenterLng='". mysql_real_escape_string($CenterLng) ."'";
		if($GeoJson!==""){$query.=", GeoJson='". mysql_real_escape_string($GeoJson) ."'";}
		$query.=", RecAppUID='". mysql_real_escape_string($UID) ."'";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		$ReportID=mysql_insert_id();
		echo $ReportID;
		
		$fieldName="";
		$query="SELECT name FROM field WHERE RecID='". $fieldRecID ."'";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$fieldName=$row["name"];
		}
		
		$UsersArr=[];
		$query="SELECT uf.UID, uf.Auth, u.Name, u.Lastname, u.Email, uf.FarmID, u.LangID, l.Code LangCode FROM users_farms uf
		LEFT OUTER JOIN users u ON u.UserID=uf.UID
		LEFT OUTER JOIN languages l ON l.LangID=u.LangID
		WHERE isnull(uf.isDeleted) AND uf.UID NOT IN (". $UID .") AND uf.FarmID IN (SELECT f.RecID FROM field fd
		LEFT OUTER JOIN farms f ON f.uuid=fd.farmUuid WHERE ";
		if($fieldRecID!==""){
			$query.="fd.RecID='". $fieldRecID ."'";
		}else{
			$query.="f.RecID='". $farmRecID ."'";
		}
		$query.=")";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			array_push($UsersArr,(object)array("UID"=>$row["UID"],"Name"=>$row["Name"],"Lastname"=>$row["Lastname"],"Email"=>$row["Email"],"LangCode"=>$row["LangCode"]));
			$FarmRecID=$row["FarmID"];
		}
		
		foreach ($UsersArr as $u=>$UserObj) {

			//Invio una email di notifica
			$NotificationSubject=getSensilizeLangElement($UserObj->LangCode,316);
			if($fieldRecID!==""){
				$bodymessage=getSensilizeLangElement($UserObj->LangCode,317);
			}else{
				$bodymessage=getSensilizeLangElement($UserObj->LangCode,318);
			}
			$NotificationSubject=str_replace("{{FIELD_NAME}}",$fieldName,$NotificationSubject);
			$NotificationSubject=str_replace("{{FARM_NAME}}",$farmName,$NotificationSubject);
			$bodymessage=str_replace("{{FIELD_NAME}}",$fieldName,$bodymessage);
			$bodymessage=str_replace("{{FARM_NAME}}",$farmName,$bodymessage);

			if($ReportSubject!=="" || $ReportText!==""){
				$bodymessage.="<div style='margin-top:20px;padding:10px;background-color:whitesmoke;border:1px solid silver;'>";
					if($ReportSubject!==""){
						$bodymessage.="<div style='font-size:18px;'>". $ReportSubject ."</div>";
					}
					if($ReportText!==""){
						$bodymessage.="<div style='font-size:12px;";
						if($ReportSubject!==""){
							$bodymessage.="margin-top:10px;";
						}
						$bodymessage.="'>". $ReportText ."</div>";
					}
				$bodymessage.="</div>";
			}
			
			$NotificationTemplate=str_replace("%bodymsg%", $bodymessage,$NotificationTemplate);
			$NotificationTemplate=str_replace("%MailLogo%", $MailLogo,$NotificationTemplate);
			$NotificationTemplate=str_replace("%weburl%", $DomainSSL,$NotificationTemplate);
			SendMail($farmRecID, $NotificationFromName, $NotificationFromEmail, $UserObj->Email, $NotificationSubject, $NotificationTemplate, "");
				
			//Creo la notifica in-app
			$query="INSERT INTO notifications SET toUID='". mysql_real_escape_string($UserObj->UID) ."', NotTypeID=1, NotificationElementID='". $ReportID ."'";
			if($fieldRecID!==""){
				$query.=", FieldID='". mysql_real_escape_string($fieldRecID) ."'";
			}else{
				$query.=", FarmID='". mysql_real_escape_string($farmRecID) ."'";
			}
			mysql_query($query) or die ("errore connessione db ".mysql_error());

		}
	break;
	case "getFieldNotices":
		$fieldRecID=$_GET["fieldRecID"];
		
		$n_counter=0;
		$query="SELECT r.RecID, r.ReportTypeID, r.ReportSubject, r.ReportText, r.CenterLat, r.CenterLng, 
		r.GeoJson, r.RecUID, concat(u.Name,' ', u.Lastname) RecUser, r.RecTimestamp FROM reporting r
		LEFT OUTER JOIN users u ON u.UserID=r.RecUID 
		WHERE r.FieldID='". mysql_real_escape_string($fieldRecID) ."' ORDER BY r.RecTimestamp";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$n_counter++;
			echo '<div class="notice_row" onclick="showFieldNotice('. $fieldRecID .','. $row["RecID"] .');">
				<div class="notice_title">'. $row["ReportSubject"] .'</div>
				<div class="notice_desc">'. $row["ReportText"] .'</div>
				<div class="notice_datetime">'. (new DateTime($row["RecTimestamp"]))->format('d/m H:i') .'</div>
			</div>';
		}
		if($n_counter==0){
			echo lang(209);
		}
	break;
	case "show_notification":
		$NotificationID=$_GET["id"];
						  
		$query="SELECT n.RecID, n.FieldID, f.name FieldName, n.NotTypeID, nt.NotType_LangElementID, n.IsRead, n.RecTimestamp 
		FROM notifications n
		LEFT OUTER JOIN field f ON f.RecID=n.FieldID
		LEFT OUTER JOIN notifications_type nt ON nt.NotTypeID=n.NotTypeID
		WHERE toUID='". $UID ."' AND n.RecID='". mysql_real_escape_string($NotificationID) ."'";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$notification_title=$row["FieldName"];
			$notification_desc=lang($row["NotType_LangElementID"]);
			$notification_datetime=(new DateTime($row["RecTimestamp"]))->format('d/m H:i');
			echo '{"title":"'. $notification_title .'", "desc":"'. $notification_desc .'", "datetime":"'. $notification_datetime .'"}';
		}
		$query="UPDATE notifications SET IsRead=now() WHERE RecID='". mysql_real_escape_string($NotificationID) ."'";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		
	break;
	case "saveField":
		$fieldUuid=$_POST["fieldUuid"];
		$farmUuid=$_POST["farmUuid"];
		$FieldName=$_POST["FieldName"];
		$PolygonPath=$_POST["PolygonPath"];
		$CenterLat=$_POST["CenterLat"];
		$CenterLng=$_POST["CenterLng"];
		$FieldSizeMq=$_POST["FieldSizeMq"];
		
		$PolygonPath=str_replace("),(","],[",$PolygonPath);
		$PolygonPath=str_replace("(","",$PolygonPath);
		$PolygonPath=str_replace(")","",$PolygonPath);
		$PolygonPathArr=explode("],[",$PolygonPath);
		array_push($PolygonPathArr, $PolygonPathArr[0]);

		$GeoJson="[";
		for ($p = 0; $p <= count($PolygonPathArr)-1 ; ++$p) {
			if($p>0){$GeoJson.=",";}
			$CoordArr=explode(",",$PolygonPathArr[$p]);
			$GeoJson.="[". $CoordArr[1].",".$CoordArr[0] ."]";
		}
		$GeoJson.="]";
		if($fieldUuid!=="" && $fieldUuid!=="undefined" && $fieldUuid!==null){
			
			$FieldRecID="";
			$query="SELECT RecID FROM field WHERE uuid='". mysql_real_escape_string($fieldUuid) ."' AND farmUuid='". mysql_real_escape_string($farmUuid) ."'";
			$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				$FieldRecID=$row["RecID"];
			}
			if($FieldRecID==""){echo "ERR";die();}
			$query="UPDATE field SET name='". mysql_real_escape_string($FieldName) ."', GeoJson='". mysql_real_escape_string($GeoJson) ."', 
			FieldSizeMq='". mysql_real_escape_string(number_format($FieldSizeMq, 2, '.', '')) ."' WHERE RecID='". mysql_real_escape_string($FieldRecID) ."'";
			mysql_query($query) or die ("errore connessione db ".mysql_error());
			
		}else{
			
			$query="INSERT INTO field SET farmUuid='". mysql_real_escape_string($farmUuid) ."', name='". mysql_real_escape_string($FieldName) ."', 
			GeoJson='". mysql_real_escape_string($GeoJson) ."', FieldSizeMq='". mysql_real_escape_string(number_format($FieldSizeMq, 2, '.', '')) ."', RecUID='". $UID ."'";
			mysql_query($query) or die ("errore connessione db ".mysql_error());
			$FieldRecID=mysql_insert_id();
			
		}
		$FieldUuid="Temp_". $FieldRecID;
		$ImageUuid="TempImageForField_". $FieldRecID;

		$acquisitionDate=date("Y-m-d") ."T00:00:00.000Z";

		$query="UPDATE field SET uuid='". mysql_real_escape_string($FieldUuid) ."', CenterLat='". mysql_real_escape_string($CenterLat) ."', CenterLng='". mysql_real_escape_string($CenterLng) ."' WHERE RecID='". $FieldRecID ."'";
		mysql_query($query) or die ("errore connessione db ".mysql_error());

		$query="INSERT INTO satelliteimage SET fieldUuid='". mysql_real_escape_string($FieldUuid) ."', uuid='". mysql_real_escape_string($ImageUuid) ."', acquisitionDate='". mysql_real_escape_string($acquisitionDate) ."'";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		
		echo $FieldRecID;
		
	break;
	case "fieldWeather":
		$FieldRecID=@$_GET["RecID"];
		$SeasonID=@$_GET["SeasonID"];
		echo "<div class='PageContentBody weather-details'>";
			$query="SELECT RecID, uuid, name, FieldSizeMq, FieldSizeHt, PlanID, SoilTypeID, SoilType, TimezoneOffset, 
			CenterLat, CenterLng, Price, RecTimestamp, WeatherLocationID, ifnull(WeatherLat,CenterLat) WeatherLat, ifnull(WeatherLng,CenterLng) WeatherLng, 
			IsActive, SubscriptionStart, SubscriptionEnd FROM (
				SELECT RecID, uuid, f.name, FieldSizeMq, FORMAT((FieldSizeMq/10000),2) FieldSizeHt, f.PlanID, st.TypeID SoilTypeID, st.Name SoilType, ifnull(wl.TimezoneOffset,0) TimezoneOffset,
				CenterLat, CenterLng, ifnull(FORMAT((FieldSizeMq/10000)*". $costPerHt .",2),0) Price, f.RecTimestamp, f.WeatherLocationID, wl.Lat WeatherLat, wl.Lng WeatherLng,
				if(s.SubscriptionStart<now() AND s.SubscriptionEnd>now(),1,0) IsActive, s.SubscriptionStart, s.SubscriptionEnd FROM field f
				LEFT OUTER JOIN weather_locations wl ON wl.LocationID=f.WeatherLocationID
				LEFT OUTER JOIN plans p ON p.PlanID=f.PlanID AND isnull(p.IsDeleted)
				LEFT OUTER JOIN soiltypes st ON st.TypeID=f.SoilTypeID
				LEFT OUTER JOIN subscriptions s ON s.PlanID=p.PlanID AND not isnull(s.StripeSubscriptionID)
				WHERE farmUuid='". mysql_real_escape_string($farmUuid) ."' AND isnull(f.isDeleted) AND f.RecID='". mysql_real_escape_string($FieldRecID) ."'
			) d1";
			//echo $query;
			$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				
				$CurrentTemperature=0;
				$CurrentWindSpeed=0;
				$CurrentWindDirection=0;
				$CurrentHumidity=0;
				$CurrentCloudiness=0;
				$CurrentPressure=0;
				
				$weather_str="";
				//$weather_str=file_get_contents("http://api.openweathermap.org/data/2.5/weather?lat=". $row["WeatherLat"] ."&lon=". $row["WeatherLng"] ."&units=metric&lang=". $LangCode ."&APPID=". $weather_api); // ok <---
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "http://api.openweathermap.org/data/2.5/weather?lat=". $row["WeatherLat"] ."&lon=". $row["WeatherLng"] ."&units=metric&lang=". $LangCode ."&APPID=". $weather_api);
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$weather_str = curl_exec($ch);
				curl_close($ch);
				
				$weather_json=json_decode($weather_str, true);
				
				echo "<div class='PageTitle'>". $row["name"] ."</div>";
				
				if(@$weather_json["dt"]!==null){
					$CurrentLocalTime = new DateTime(gmdate("Y-m-d H:i:s", $weather_json["dt"]));
					$CurrentLocalTime->add(new DateInterval('PT' . $row["TimezoneOffset"] . 'H'));
					$CurrentTemperature=number_format($weather_json["main"]["temp"], 1, '.', '');
					$CurrentWindSpeed=$weather_json["wind"]["speed"];
					$CurrentWindDirection=$weather_json["wind"]["deg"];
					$CurrentHumidity=$weather_json["main"]["humidity"];
					$CurrentCloudiness=$weather_json["clouds"]["all"];
					$CurrentPressure=$weather_json["main"]["pressure"];
					echo '<div class="weather-panel">
						<div class="weather-row">
							<div class="weather-degrees">
								<!--<img src="/assets/img/sun.svg" alt="sun" class="weather-degrees-icon">-->
								<img src="http://openweathermap.org/img/wn/'. @$weather_json["weather"][0]["icon"] .'@2x.png" alt="sun" class="content-icon" style="width: 40px;height: 40px;margin: 0px;display: inline-block;">
								<p class="weather-degrees-num">+'. $CurrentTemperature .'&deg;</p>
							</div>
							<p class="weather-item-text">
								<span class="weather-item-span">'. $CurrentLocalTime->format('H:i') .'</span>
								'. lang(217) .'
							</p>
						</div>
						<div class="weather-flex">
							<div class="weather-item">
								<img src="/assets/img/wind.svg">
								<p class="weather-item-text">
									<span class="weather-item-span">'. $CurrentWindSpeed .' m/s <img src="/assets/img/weather-share.svg" title="'. $CurrentWindDirection .'&deg;" class="weather-share" style="transform: rotate('. $CurrentWindDirection .'deg);"></span>
									'. lang(220) .'
								</p>
							</div>
							<div class="weather-item">
								<img src="/assets/img/humidity.svg">
								<p class="weather-item-text">
									<span class="weather-item-span">'. $CurrentHumidity .' %</span>
									'. lang(221) .'
								</p>
							</div>
							<div class="weather-item">
								<img src="/assets/img/cloud.svg">
								<p class="weather-item-text">
									<span class="weather-item-span">'. $CurrentCloudiness .' %</span>
									'. lang(222) .'
								</p>
							</div>
							<div class="weather-item">
								<img src="/assets/img/pressure.svg">
								<p class="weather-item-text">
									<span class="weather-item-span"> '. $CurrentPressure .' mb</span>
									'. lang(223) .'
								</p>
							</div>
						</div>
					</div>';
				}
				echo '<div class="content-table" id="DailyWeatherTable">';
					echo '<div class="content-table-head">';
						echo '<ul class="weather-choose-day">
							<li class="active" data-day="today">'. lang(217) .'</li>
							<li data-day="tomorrow">'. lang(218) .'</li>
							<li data-day="next7">'. lang(219) .'</li>
						</ul>';
						//echo '<input type=text class="datepicker" value="'. date("d/m/Y") .'">';
						echo '<ul class="weather-choose-time">
							<li data-time="00:00-05:00" class="disabled_today">00:00 - 05:00</li>
							<li data-time="06:00-12:00" class="disabled_today">06:00 - 12:00</li>
							<li class="active" data-time="13:00-18:00">13:00 - 18:00</li>
							<li data-time="19:00-24:00">19:00 - 24:00</li>
						</ul>';
					echo '</div>';
					echo '<div class="DailyWeather" id="DailyWeather_'. mysql_real_escape_string($FieldRecID) .'"></div>
				</div>';
				
				$WeatherChart0From="";
				$WeatherChart0To="";
				if($SeasonID!=="" && $SeasonID!==""){
					$seasons_arr=json_decode($seasons);
					foreach($seasons_arr as $season){
						if(intval($season->id)==intval($SeasonID)){
							$WeatherChart0From=(new DateTime($season->from))->format('d/m/Y');
							$WeatherChart0To=(new DateTime($season->to))->format('d/m/Y');
						}
					}
				}
				if($WeatherChart0From=="" || $WeatherChart0To==""){
					//Data di inizio coltura dell'ultima stagione (attiva attualmente)
					$query2="SELECT DateFrom FROM fields_cultures 
					WHERE isnull(isDeleted) AND FieldID='". mysql_real_escape_string($FieldRecID) ."' 
					ORDER BY DateTo DESC LIMIT 1";
					$result2 = mysql_query($query2) or die ("errore connessione db ".mysql_error());
					while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)){
						$WeatherChart0From=$row2["DateFrom"];
					}
					if($WeatherChart0From===null){
						$WeatherChart0From="";
					}else{
						$WeatherChart0From=(new DateTime($WeatherChart0From))->format('d/m/Y');
					}
					$WeatherChart0To=date("d/m/Y");
				}
				echo "<div style='display:none;text-align:center;' id='datepickers_weather-chart-0_". $FieldRecID ."'>";
					echo "<input type=text name='DateFrom' class='Date_weather-chart-0 datepicker_from form-control' placeholder='dd/mm/yyyy' value='". $WeatherChart0From ."' onchange=\"loadWeatherCharts('". $FieldRecID ."', 'weather-chart-0')\" style='width:150px;display: inline-block;margin-right:10px;'>
					<input type=text name='DateTo' class='Date_weather-chart-0 datepicker_to form-control' placeholder='dd/mm/yyyy' value='". $WeatherChart0To ."' onchange=\"loadWeatherCharts('". $FieldRecID ."', 'weather-chart-0')\" style='width:150px;display: inline-block;'>";
				echo "</div>";
				echo '<div class="weather-chart" id="loading_weather-chart-0_'. $FieldRecID .'" style="color: #bbcfe8;display:none;">'. lang(224) .'</div>';
				echo '<div class="weather-chart" id="weather-chart-0_'. $FieldRecID .'"></div>';
			}
		echo "</div>";
	break;
	case "fieldForm":
		$FieldRecID=@$_GET["RecID"];
		
		echo "<div class='PageContentBody'>";
			$query="SELECT RecID, uuid, f.name, FieldSizeMq, FORMAT((FieldSizeMq/10000),2) FieldSizeHt, f.PlanID, st.TypeID SoilTypeID, st.Name SoilType,
			CenterLat, CenterLng, ifnull(FORMAT((FieldSizeMq/10000)*". $costPerHt .",2),0) Price, f.RecTimestamp,
			if(s.SubscriptionStart<now() AND s.SubscriptionEnd>now(),1,0) IsActive, s.SubscriptionStart, s.SubscriptionEnd FROM field f
			LEFT OUTER JOIN plans p ON p.PlanID=f.PlanID AND isnull(p.IsDeleted)
			LEFT OUTER JOIN soiltypes st ON st.TypeID=f.SoilTypeID
			LEFT OUTER JOIN subscriptions s ON s.PlanID=p.PlanID AND not isnull(s.StripeSubscriptionID)
			WHERE farmUuid='". mysql_real_escape_string($farmUuid) ."' AND isnull(f.isDeleted) AND f.RecID='". mysql_real_escape_string($FieldRecID) ."'";
			$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				echo "<div class='FieldDataText' id='FieldDataText_". $FieldRecID ."'>";
					echo "<div class='PageTitle'>". $row["name"] ." <a class='editFieldDataButton' id='editFieldData_". $FieldRecID ."' onclick='editFieldData(". $FieldRecID .")'><i class='fa fa-pencil'></i></a></div>";
				echo "</div>
				
				<div id='". $FieldRecID ."_uploader_container' class='uploader_container field_uploader_container'>
					<div id='". $FieldRecID ."_dropzone' class='dropzone field_dropzone'><i class='fa fa-cloud-upload' aria-hidden='true'></i>". lang(138) ."</div>          
					<span style='display:none;'>
						<input id='". $FieldRecID ."_fileupload' class='fileupload' type='file' name='files[]' multiple=''>
					</span>
					<div id='". $FieldRecID ."_progress' class='progress'>
						<div class='progress-bar progress-bar-success'></div>
					</div>
					<div id='". $FieldRecID ."_uploadbar' class='uploadbar' style='display: block;'>
						<div id='". $FieldRecID ."_uploadinfo' class='uploadinfo'><a onclick=\"$('#". $FieldRecID ."_fileupload').click();\">". lang(122) ."</a></div>
						<div id='". $FieldRecID ."_docsinfo' class='docsinfo'></div>
					</div>
					<div id='". $FieldRecID ."_stopbar' class='stopbar' style='display: none;'>
						<a>". lang(139) ."</a>
					</div>
					<div id='". $FieldRecID ."_uploader' class='uploader'></div>
				</div>";
				
				echo "<div class='FieldDataForm' id='FieldDataForm_". $FieldRecID ."'>";
					echo "<div class='PageTitle'>". lang(133) ."</div>";
					echo "<div class='s_table' id='saveFieldDataForm_". $FieldRecID ."'>";
						echo "<div class='s_table_row s_table_head'>";
							echo "<div class='s_table_cell'>". lang(124) ."</div>";
							echo "<div class='s_table_cell'>". lang(134) ."</div>";
							echo "<div class='s_table_cell'></div>";
						echo "</div>";
						echo "<div class='s_table_row'>";
							echo "<div class='s_table_cell'><input type=text name='FieldName' class='form-control' value='". $row["name"] ."'></div>";
							echo "<div class='s_table_cell'>
								<select name='SoilType' class='form-control'>";
									echo "<option value=''>". lang(126) ."</option>";
									$query2="SELECT TypeID, Name FROM soiltypes ORDER BY Name";
									$result2 = mysql_query($query2) or die ("errore connessione db ".mysql_error());
									while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)){
										echo "<option value='". $row2["TypeID"] ."'";
											if($row["SoilTypeID"]==$row2["TypeID"]){echo " selected";}
										echo ">". $row2["Name"] ."</option>";
									}
								echo "</select>
							</div>";
							echo "<div class='s_table_cell'><button class='btn' onclick='saveFieldData(". $FieldRecID .");'>". lang(87) ."</button></div>";
						echo "</div>";
					echo "</div>";
				echo "</div>";
			}
			echo "<div class='tabs fieldTabs'>
				<ul>
					<li><a href='#tabs-data'>". lang(145) ."</a></li>
					<li><a href='#tabs-cultures'>". lang(131) ."</a></li>
					<li><a href='#tabs-reporting'>". lang(199) ."</a></li>
					<li><a href='#tabs-docs' class='tabs-docs-li'>". lang(102) ."</a></li>
					<li><a href='#tabs-images' class='tabs-images-li'>". lang(103) ."</a></li>
				</ul>
				<div id='tabs-data'>";
		
					$query="SELECT RecID, uuid, f.name, FieldSizeMq, FORMAT((FieldSizeMq/10000),2) FieldSizeHt, f.PlanID, st.TypeID SoilTypeID, st.Name SoilType,
					CenterLat, CenterLng, ifnull(FORMAT((FieldSizeMq/10000)*". $costPerHt .",2),0) Price, f.RecTimestamp, if(isnull(sp.PaymentID),0,1) Payed,
					if(s.SubscriptionStart<now() AND s.SubscriptionEnd>now(),1,0) IsActive, s.SubscriptionStart, s.SubscriptionEnd FROM field f
					LEFT OUTER JOIN plans p ON p.PlanID=f.PlanID AND isnull(p.IsDeleted)
					LEFT OUTER JOIN soiltypes st ON st.TypeID=f.SoilTypeID
					LEFT OUTER JOIN subscriptions s ON s.PlanID=p.PlanID AND (NOT ISNULL(s.StripeSubscriptionID) OR p.Interval='BANK')
					LEFT OUTER JOIN subscriptions_payments sp ON sp.SubscriptionID=s.SubscriptionID
					WHERE farmUuid='". mysql_real_escape_string($farmUuid) ."' AND isnull(f.isDeleted) AND f.RecID='". mysql_real_escape_string($FieldRecID) ."'";
					//echo $query;
					$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){

							$ndviArr=[];
							$query2="SELECT YEAR(acquisitionDate) acquisitionYear, MONTH(acquisitionDate) acquisitionMonth, FORMAT(AVG(ndviAverage), 2) ndviAverage FROM (
								SELECT STR_TO_DATE(i.acquisitionDate,'%Y-%m-%d') acquisitionDate, ifnull(if(fi.ndviAverage='',null,fi.ndviAverage),0) ndviAverage FROM satelliteimage i
								LEFT OUTER JOIN fieldsatelliteimage fi ON fi.uuid=i.uuid
								LEFT OUTER JOIN (SELECT satelliteimageUuid, url FROM geomap GROUP BY satelliteimageuuid) g ON g.satelliteimageUuid=i.uuid
								WHERE i.fieldUuid='". $row["uuid"] ."' 
								GROUP BY i.acquisitionDate 
								ORDER BY i.acquisitionDate DESC
							)d1 GROUP BY YEAR(acquisitionDate), MONTH(acquisitionDate) ORDER BY YEAR(acquisitionDate) DESC, MONTH(acquisitionDate) DESC LIMIT 6";
							$result2 = mysql_query($query2) or die ("errore connessione db ".mysql_error());
							while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)){
								$ndviObj=(object) array(
									'ndvi_date' => mb_convert_encoding(ucfirst(strftime("%B",strtotime($row2["acquisitionYear"] ."-". $row2["acquisitionMonth"] ."-01"))), "utf-8", "Windows-1251"),
									'ndvi_val' => $row2["ndviAverage"]
								);
								array_push($ndviArr,$ndviObj);
							}
							$ndviJson='{"ndvi_dates": [';
							$ndviArr=array_reverse($ndviArr);
							foreach ($ndviArr as $n=>$ndviObj) {
								if($n>0){$ndviJson.=',';}
								$ndviJson.='{"ndvi_date": "'. $ndviObj->ndvi_date .'", "ndvi_val": "'. $ndviObj->ndvi_val .'"}';
							}
							$ndviJson.=']}';
							echo "<div class='row'>";
								echo "<div class='col-xs-12 col-sm-12 col-md-5 col-lg-5 col-xl-5'>";
									echo "<span><a class='link' onclick=\"openInGoogleMaps('". $row["CenterLat"] ."','". $row["CenterLng"] ."')\"><i class='fa fa-map-o' aria-hidden='true' style='margin-right: 5px;'></i>". lang(324) ."</a></span><br>";
									echo lang(135) .": ". number_format(floatval($row["FieldSizeHt"]),2,",","") ." ". lang(113);
									echo "<br>". lang(114) .": ". DMS_Position($row["CenterLat"],$row["CenterLng"]);
									if($row["SoilType"]!=="" && $row["SoilType"]!==null){
										echo "<br>". lang(125) .": ". $row["SoilType"];
									}
									//[ABBONAMENTO]
									if(intval($row["IsActive"])==1){
										echo "<br>". lang(115) ." ". (new DateTime($row["SubscriptionEnd"]))->format('d/m/Y') ." - &euro; ". number_format($row["Price"],2,",",".") ." ". lang(116);
										if (intval($row["Payed"])==0){
											echo " - <span style='color:orange;'>". lang(239) ."</span>";
										}
									}else{
										echo "<br><button class='btn form-control' style='width: auto;margin-top: 10px;' onclick='showSubscriptionForm(\"". $FieldRecID ."\")'>". lang(129) ." &euro; ". number_format(floatval($row["Price"]),2,",",".") ." ". lang(116) ."</button>";
									}
									if($DevArea==1){
										echo "<br>Only for test purpose: <a href='javascript:updateGeopardData(". $row["RecID"] .",\"". $row["uuid"] ."\")'>load Geopard data</a>";
									}
									echo "<br><br>";
									echo "<span style='font-size: smaller; color: #616161;'>". lang(118) ." ". (new DateTime($row["RecTimestamp"]))->format('d/m/Y') ." ". lang(119) ." ". (new DateTime($row["RecTimestamp"]))->format('H:i') ."</span>";
									echo "<a style='text-decoration:underline;cursor:pointer;color: #ab2632; font-size: smaller;display: block;margin-top: 5px;' id='deleteFieldButton_". $FieldRecID ."' onclick='deleteField(". $FieldRecID .",". intval($row["IsActive"]) .",\"". number_format(floatval($row["Price"]),2,",",".") ."\")'>". lang(120) ."</a>";

								echo "</div>";
								echo "<div class='col-xs-12 col-sm-12 col-md-7 col-lg-7 col-xl-7'>";
									echo "<div id='ndviChart_". $FieldRecID ."' class='ndviChart'>". $ndviJson ."</div>";
									$query="SELECT c.RecID, c.DateFrom, c.DateTo, c.CultureTypeID, ct.Name CultureTypeName, ct.LangID CultureTypeLangID,
									ifnull(c.Variety,'-') Variety, c.TillingTypeID, t.Name TillingName, c.yield_estimated FROM fields_cultures c
									LEFT OUTER JOIN culturestypes ct ON ct.TypeID=c.CultureTypeID
									LEFT OUTER JOIN tillingtypes t ON t.TypeID=c.TillingTypeID
									WHERE isnull(isDeleted) AND FieldID='". mysql_real_escape_string($FieldRecID) ."' AND ((c.DateTo>now() AND c.DateFrom<now()) OR c.DateFrom>now()) ORDER BY DateFrom DESC LIMIT 1";
									//echo $query;
									$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
									$num_rows = mysql_num_rows($result);
									if($num_rows>0){
										echo "<div class='row'>";
											echo "<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12'>";
											echo "<div class='currentCultureArea'>";
												while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
													echo "<div class='currentCultureAreaTitle'>". lang(228) ."<a class='cultureModalButton' onclick=\"cultureModal('". mysql_real_escape_string($FieldRecID) ."',1,'". $row["RecID"] ."')\"><i class='fa fa-pencil'></i></a></div>";
													echo "<div class='s_table'>";
														echo "<div class='s_table_row'>";
															echo "<div class='s_table_cell'><nobr>". lang(104) ."<sup><i class='fa fa-question-circle' style='margin-left: 5px;' title='". lang(188) ."'></i></sup></nobr></div>";
															echo "<div class='s_table_cell'><nobr>". lang(105) ."<sup><i class='fa fa-question-circle' style='margin-left: 5px;' title='". lang(189) ."'></i></sup></nobr></div>";
															echo "<div class='s_table_cell'>". lang(106) ."</div>";
															echo "<div class='s_table_cell'>". lang(107) ."</div>";
															echo "<div class='s_table_cell'>". lang(229) ."</div>";
														echo "</div>";
														echo "<div class='s_table_row'>";
															echo "<div class='s_table_cell'>". (new DateTime($row["DateFrom"]))->format('d/m/Y') ."</div>";
															echo "<div class='s_table_cell'>". (new DateTime($row["DateTo"]))->format('d/m/Y') ."</div>";
															echo "<div class='s_table_cell'>". lang($row["CultureTypeLangID"]) ."</div>";
															echo "<div class='s_table_cell'>". $row["Variety"] ."</div>";
															echo "<div class='s_table_cell'>". $row["yield_estimated"] ." ". lang(240) ."</div>";
														echo "</div>";
													echo "</div>";
													echo "<div class='closeColture'><a class='link' onclick=\"closeCurrentCulture('". mysql_real_escape_string($FieldRecID) ."', '". $row["RecID"] ."');\">". lang(231) ."</a></div>";
												}
												echo "</div>";
											echo "</div>";
										echo "</div>";
									echo "</div>";
								}else{
									echo "</div>";
									echo "<div style='text-align:center;' class='col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12'><button style='width:fit-content;' class='btn form-control' onclick='cultureModal(". mysql_real_escape_string($FieldRecID) .",1,\"\");'>". lang(259) ."</button></div>";
								}
							echo "</div>";
					}
				echo "</div>
				<div id='tabs-cultures'></div>
				<div id='tabs-reporting'>
					<a class='new_note_link' title='". str_replace("'","&apos;",lang(313)) ."' style='float: right;margin-bottom: 20px;' onclick='initiateNoticePolygon(". $FieldRecID .");'>
					<i class='fa fa-pencil-square-o' aria-hidden='true' style='margin-right: 5px;'></i>". lang(313) ."</a>
					<a class='new_note_link' title='". str_replace("'","&apos;",lang(312)) ."' style='float: right;margin-bottom: 20px;' onclick='initiateNoticeMarker(". $FieldRecID .");'>
					<i class='fa fa-map-marker' aria-hidden='true' style='margin-right: 5px;'></i>". lang(312) ."</a>
					<div id='reporting_". $FieldRecID ."' class='reporting'></div>
				</div>
				<div id='tabs-docs'>";
					/*echo "<div id='docs_". $FieldRecID ."_uploader_container' class='uploader_container'>";
						echo "<div id='docs_". $FieldRecID ."_dropzone' class='dropzone'><i class='fa fa-cloud-upload' aria-hidden='true'></i>". lang(138) ."</div>";*/
						echo "<div id='docs_". $FieldRecID ."' class='docs'></div>";
						/*
						echo "<span style='display:none;'>
							<input id='docs_". $FieldRecID ."_fileupload' class='fileupload' type='file' name='files[]' multiple=''>
						</span>
						<div id='docs_". $FieldRecID ."_progress' class='progress'>
							<div class='progress-bar progress-bar-success'></div>
						</div>
						<div id='docs_". $FieldRecID ."_uploadbar' class='uploadbar' style='display: block;'>
							<div id='docs_". $FieldRecID ."_uploadinfo' class='uploadinfo'><a>". lang(122) ."</a></div>
							<div id='docs_". $FieldRecID ."_docsinfo' class='docsinfo'></div>
						</div>
						<div id='docs_". $FieldRecID ."_stopbar' class='stopbar' style='display: none;'>
							<a>". lang(139) ."</a>
						</div>
						<div id='docs_". $FieldRecID ."_uploader' class='uploader'></div>
					</div>";
					*/
				echo "</div>
				<div id='tabs-images'>";
					//echo "<div id='images_". $FieldRecID ."_uploader_container' class='uploader_container'>";
						//echo "<div id='images_". $FieldRecID ."_dropzone' class='dropzone'><i class='fa fa-cloud-upload' aria-hidden='true'></i>". lang(140) ."</div>";
						echo "<div id='images_". $FieldRecID ."' class='images'></div>";       
						/*echo "<span style='display:none;'>
							<input id='images_". $FieldRecID ."_fileupload' class='fileupload' type='file' name='files[]' multiple=''>
						</span>
						<div id='images_". $FieldRecID ."_progress' class='progress'>
							<div class='progress-bar progress-bar-success'></div>
						</div>
						<div id='images_". $FieldRecID ."_files' class='files'></div>
						<div id='images_". $FieldRecID ."_uploadbar' class='uploadbar' style='display: block;'>
							<div id='images_". $FieldRecID ."_uploadinfo' class='uploadinfo'><a>". lang(141) ."</a></div>
							<div id='images_". $FieldRecID ."_docsinfo' class='docsinfo'></div>
						</div>
						<div id='images_". $FieldRecID ."_stopbar' class='stopbar' style='display: none;'>
							<a>". lang(139) ."</a>
						</div>
						<div id='images_". $FieldRecID ."_uploader' class='uploader'></div>
					</div>
				</div>";
				*/
			echo "</div>";
		echo "</div>";
	break;
	case "get_field_reporting":
		$FieldRecID=@$_GET["FieldRecID"];
		echo "<div class='s_table'>";
			echo "<div class='s_table_row s_table_head'>";
				echo "<div class='s_table_cell'>". lang(213) ."</div>";
				echo "<div class='s_table_cell'>". lang(212) ."</div>";
				echo "<div class='s_table_cell'>". lang(211) ."</div>";
				echo "<div class='s_table_cell'>". lang(210) ."</div>";
			echo "</div>";
			$query="SELECT r.RecID, ifnull(r.ReportTypeID,0) ReportTypeID, r.ReportSubject, r.ReportText, r.CenterLat, r.CenterLng, 
			r.GeoJson, r.RecUID, concat(u.Name,' ', u.Lastname) RecUser, r.RecTimestamp FROM reporting r
			LEFT OUTER JOIN users u ON u.UserID=r.RecUID 
			WHERE r.FieldID='". mysql_real_escape_string($FieldRecID) ."' ORDER BY r.RecTimestamp";
			$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				echo "<div class='s_table_row'>";
					echo "<div class='s_table_cell'><div class='s_table_cell_label'>". lang(213) .": </div>". (new DateTime($row["RecTimestamp"]))->format('d/m H:i') ."</div>";
					echo "<div class='s_table_cell'><div class='s_table_cell_label'>". lang(213) .": </div>". getNoticeTypeName($row["ReportTypeID"]) ."</div>";
					echo "<div class='s_table_cell'><div class='s_table_cell_label'>". lang(211) .": </div><a class='link' onclick='showFieldNotice(". $FieldRecID .",". $row["RecID"] .")'>". $row["ReportSubject"] ."</a></div>";
					echo "<div class='s_table_cell'><div class='s_table_cell_label'>". lang(210) .": </div>". $row["ReportText"] ."</div>";
				echo "</div>";
			}
		echo "</div>";
	break;
	case "saveFieldData":
		
		$FieldRecID=@$_GET["FieldRecID"];
		$FieldName=@$_GET["FieldName"];
		$SoilType=@$_GET["SoilType"];
		
		$query="UPDATE field SET name='". mysql_real_escape_string($FieldName) ."'";
		if($SoilType!=="" && $SoilType!==null){$query.=", SoilTypeID='". mysql_real_escape_string($SoilType) ."'";}
		$query.=" WHERE RecID='". mysql_real_escape_string($FieldRecID) ."'";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		
	break;
	case "getFieldCultures":
		
		$FieldRecID=@$_GET["FieldRecID"];
		echo "<div style='text-align: right;'><a onclick='showCultureForm(". $FieldRecID .");' href='#'>". lang(137) ."</a></div>";
			echo "<div class='s_table CultureTable' id='insertCultureForm_". $FieldRecID ."'>";
				echo "<div class='s_table_row s_table_head'>";
					echo "<div class='s_table_cell'>". lang(104) ."<sup><i class='fa fa-question-circle' style='margin-left: 5px;' title='". lang(188) ."'></i></sup></div>";
					echo "<div class='s_table_cell'>". lang(105) ."<sup><i class='fa fa-question-circle' style='margin-left: 5px;' title='". lang(189) ."'></i></sup></div>";
					echo "<div class='s_table_cell'>". lang(106) ."</div>";
					echo "<div class='s_table_cell'>". lang(107) ."</div>";
					//echo "<div class='s_table_cell'>". lang(144) ."</div>";
					echo "<div class='s_table_cell'>". lang(190) ."</div>";
					echo "<div class='s_table_cell'></div>";
					echo "<div class='s_table_cell'></div>";
				echo "</div>";
				//echo "</div>";
				$query="SELECT c.RecID, c.DateFrom, c.DateTo, c.CultureTypeID, ct.Name CultureTypeName, ct.LangID CultureTypeLangID,
				ifnull(c.Variety,'-') Variety, c.TillingTypeID, t.Name TillingName, c.yield_effective FROM fields_cultures c
				LEFT OUTER JOIN culturestypes ct ON ct.TypeID=c.CultureTypeID
				LEFT OUTER JOIN tillingtypes t ON t.TypeID=c.TillingTypeID
				WHERE isnull(isDeleted) AND FieldID='". mysql_real_escape_string($FieldRecID) ."' AND (c.DateTo<now() OR c.DateFrom>now()) AND c.DateFrom<now() ORDER BY DateFrom DESC";
				$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
				$num_rows = mysql_num_rows($result);
				if($num_rows>0){
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
						echo "<div class='s_table_row'>";
							echo "<div class='s_table_cell'><div class='s_table_cell_label'>". lang(104) .": </div>". (new DateTime($row["DateFrom"]))->format('d/m/Y') ."</div>";
							echo "<div class='s_table_cell'><div class='s_table_cell_label'>". lang(105) .": </div>". (new DateTime($row["DateTo"]))->format('d/m/Y') ."</div>";
							echo "<div class='s_table_cell'><div class='s_table_cell_label'>". lang(106) .": </div>". lang($row["CultureTypeLangID"]) ."</div>";
							echo "<div class='s_table_cell'><div class='s_table_cell_label'>". lang(107) .": </div>". $row["Variety"] ."</div>";
							/*echo "<div class='s_table_cell'><div class='s_table_cell_label'>". lang(144) .": </div>". $row["TillingName"] ."</div>";*/
							echo "<div class='s_table_cell'><div class='s_table_cell_label'>". lang(190) .": </div>". $row["yield_effective"] ."</div>";
							echo "<div class='s_table_cell' style='text-align:right;'><a class='deleteLink' id='deleteCultureButton_". $row["RecID"] ."' onclick='deleteCulture(\"". $FieldRecID ."\",\"". $row["RecID"] ."\")'>". lang(109) ."</a></div>";
						echo "</div>";
					}
				}
				echo "<div class='s_table_row CultureForm'>";
					echo "<div class='s_table_cell'><input type=text name='DateFrom' class='CultureDate datepicker_from form-control' placeholder='". lang(104) ."'></div>";
					echo "<div class='s_table_cell'><input type=text name='DateTo' class='CultureDate datepicker_to form-control' placeholder='". lang(105) ."'></div>";
					echo "<div class='s_table_cell'>
						<select name='CultureType' class='form-control'>";
							echo "<option value=''>". lang(126) ."</option>";
							$query="SELECT TypeID, Name, LangID FROM culturestypes ORDER BY Name";
							$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
							while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
								echo "<option value='". $row["TypeID"] ."'>". lang($row["LangID"]) ."</option>";
							}
						echo "</select>
					</div>";
					echo "<div class='s_table_cell'><input type=text name='Variety' class='form-control' placeholder='". lang(148) ."'></div>";
					/*
					echo "<div class='s_table_cell'>
						<select name='TillingType' class='form-control'>";
							echo "<option value=''>". lang(126) ."</option>";
							$query="SELECT TypeID, Name FROM tillingtypes ORDER BY Name";
							$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
							while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
								echo "<option value='". $row["TypeID"] ."'>". $row["Name"] ."</option>";
							}
						echo "</select>
					</div>";
					*/
					echo "<div class='s_table_cell'><input type=number name='yield_effective' class='form-control' placeholder='". lang(190) ."'></div>";
					echo "<div class='s_table_cell'><button class='btn' onclick='insertCulture(". $FieldRecID .");'>". lang(108) ."</button></div>";
					echo "<div class='s_table_cell' style='text-align:right;vertical-align: middle;'><a class='deleteLink' onclick='hideCultureForm(". $FieldRecID .");'><i class='fa fa-times fa-lg'></i></a></div>";
				echo "</div>";
			echo "</div>";
		
	break;
	case "saveCulture":
		
		$FieldRecID=@$_GET["FieldRecID"];
		$DateFrom=@$_GET["DateFrom"];
		$DateTo=@$_GET["DateTo"];
		$CultureType=@$_GET["CultureType"];
		$Variety=@$_GET["Variety"];
		$TillingType=@$_GET["TillingType"];
		$yield_effective=@$_GET["yield_effective"];
		
		$query="INSERT INTO fields_cultures SET RecUID='". $UID ."', FarmID='". $farmRecID ."', FieldID='". $FieldRecID ."'";
		if($DateFrom!=="" && $DateFrom!==null){$query.=", DateFrom='". todbdate($DateFrom) ."'";}
		if($DateTo!=="" && $DateTo!==null){$query.=", DateTo='". todbdate($DateTo) ."'";}
		if($CultureType!=="" && $CultureType!==null){$query.=", CultureTypeID='". mysql_real_escape_string($CultureType) ."'";}
		if($Variety!=="" && $Variety!==null){$query.=", Variety='". mysql_real_escape_string($Variety) ."'";}
		if($TillingType!=="" && $TillingType!==null){$query.=", TillingTypeID='". mysql_real_escape_string($TillingType) ."'";}
		if($yield_effective!=="" && $yield_effective!==null){$query.=", yield_effective='". mysql_real_escape_string(number_format($yield_effective,2,".","")) ."'";}
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		
	break;
	case "formSubscription":
		$FieldRecID=@$_GET["FieldRecID"];
		if($FieldRecID===null){$FieldRecID="";}
		
		//echo "<div class='PageTitle'>". lang(81) ."</div>";
		echo "<div id='FieldsBankPayInfo' style='display:none;'></div>";
		echo "<div id='FieldsBankPay' style='display:none;'>
			<p>". lang(245) ."</p>
			<div class='row' style='margin:0px; margin-bottom: 10px; font-weight: bold;'>
				<div class='col-xs-1 col-sm-1 col-md-1 col-lg-1 col-xl-1'></div>
				<div class='col-xs-4 col-sm-4 col-md-4 col-lg-4 col-xl-4'>". lang(247) ."</div>
				<div class='col-xs-7 col-sm-7 col-md-7 col-lg-7 col-xl-7'>". lang(248) ."</div>
			</div>
			<div class='SubscribeFieldRow row' style='margin-bottom: 5px;'>
				<div class='col-xs-1 col-sm-1 col-md-1 col-lg-1 col-xl-1'><input type=radio name=months value=6 style='vertical-align: middle;' checked></div>
				<div class='col-xs-4 col-sm-4 col-md-4 col-lg-4 col-xl-4'>". lang(249) ."</div>
				<div class='col-xs-7 col-sm-7 col-md-7 col-lg-7 col-xl-7'>&euro; <span class='months_price' data-months=6></span></div>
			</div>
			<div class='SubscribeFieldRow row' style='margin-bottom: 5px;'>
				<div class='col-xs-1 col-sm-1 col-md-1 col-lg-1 col-xl-1'><input type=radio name=months value=12 style='vertical-align: middle;'></div>
				<div class='col-xs-4 col-sm-4 col-md-4 col-lg-4 col-xl-4'>". lang(250) ."</div>
				<div class='col-xs-7 col-sm-7 col-md-7 col-lg-7 col-xl-7'>&euro; <span class='months_price' data-months=12></span></div>
			</div>
			<div class='SubscribeFieldRow row' style='margin-bottom: 5px;'>
				<div class='col-xs-1 col-sm-1 col-md-1 col-lg-1 col-xl-1'><input type=radio name=months value=18 style='vertical-align: middle;'></div>
				<div class='col-xs-4 col-sm-4 col-md-4 col-lg-4 col-xl-4'>". lang(251) ."</div>
				<div class='col-xs-7 col-sm-7 col-md-7 col-lg-7 col-xl-7'>&euro; <span class='months_price' data-months=18></span></div>
			</div>
			<div class='SubscribeFieldRow row' style='margin-bottom: 5px;'>
				<div class='col-xs-1 col-sm-1 col-md-1 col-lg-1 col-xl-1'><input type=radio name=months value=24 style='vertical-align: middle;'></div>
				<div class='col-xs-4 col-sm-4 col-md-4 col-lg-4 col-xl-4'>". lang(252) ."</div>
				<div class='col-xs-7 col-sm-7 col-md-7 col-lg-7 col-xl-7'>&euro; <span class='months_price' data-months=24></span></div>
			</div>";
			echo "<input type=hidden id=FieldsPrice>";
			echo "<input type=hidden id=FieldIDs>";
			echo "<div style='text-align:center;'><button class='btn form-control' style='width: fit-content;margin-top: 10px;' onclick='createBankPayOrder();'>". lang(181) ."</button></div>";
		echo "</div>";
		echo "<div id='SubscribeFields'>";
		
				echo "<p>". lang(244) ."</p>";

				echo "<div class='SubscribeFieldRow row' style='font-weight: bold;'>";
					echo "<div class='SubscribeFieldCol col-xs-6 col-sm-6 col-md-6 col-lg-6 col-xl-6'>". lang(124) ."</div>";
					echo "<div class='SubscribeFieldCol col-xs-3 col-sm-3 col-md-3 col-lg-3 col-xl-3'>". ucfirst(lang(113)) ."</div>";
					echo "<div class='SubscribeFieldCol col-xs-3 col-sm-3 col-md-3 col-lg-3 col-xl-3'>". lang(243) ." (". lang(116) .")</div>";
				echo "</div>";
		
		
			$query="SELECT RecID, uuid, name, FieldSizeMq, FORMAT((FieldSizeMq/10000),2) FieldSizeHt, FORMAT((FieldSizeMq/10000)*". $costPerHt .",2) Price, 
			if(s.SubscriptionStart<now() AND s.SubscriptionEnd>now(),1,0) IsActive, s.SubscriptionStart, s.SubscriptionEnd, if(isnull(sp.PaymentID),0,1) Payed FROM field 
			LEFT OUTER JOIN plans p ON p.PlanID=field.PlanID AND isnull(p.IsDeleted)
			LEFT OUTER JOIN subscriptions s ON s.PlanID=p.PlanID AND (NOT ISNULL(s.StripeSubscriptionID) OR p.Interval='BANK')
        	LEFT OUTER JOIN subscriptions_payments sp ON sp.SubscriptionID=s.SubscriptionID
			WHERE farmUuid='". mysql_real_escape_string($farmUuid) ."' AND isnull(field.isDeleted)";
			//echo htmlentities($query);
			$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				echo "<div class='SubscribeFieldRow row' id='SubscribeFieldRow_". $row["RecID"] ."'";
				if(intval($row["IsActive"])==1){
					echo " style='display:none;'";
				}
				echo ">";
					echo "<div class='SubscribeFieldCol col-xs-6 col-sm-6 col-md-6 col-lg-6 col-xl-6'>";
						if(intval($row["IsActive"])!==1){
							echo "<input type='checkbox' data-id='". $row["RecID"] ."' data-price='". $row["Price"] ."' style='vertical-align: sub;margin-right: 8px;'";
								if($FieldRecID!==""){
									if(intval($FieldRecID)==intval($row["RecID"])){
										echo " checked";
									}
								}
							echo ">";
						}
						echo "<span class='field_desc'>". $row["name"] ."</span>";
					echo "</div>";
					echo "<div class='SubscribeFieldCol col-xs-3 col-sm-3 col-md-3 col-lg-3 col-xl-3'>";
						echo number_format($row["FieldSizeHt"],2,",",".");
					echo "</div>";
					echo "<div class='SubscribeFieldCol col-xs-3 col-sm-3 col-md-3 col-lg-3 col-xl-3'>";
						echo "&euro; ". number_format($row["Price"],2,",",".");
					echo "</div>";
					if(intval($row["IsActive"])==1){
						echo "<div class='SubscribeFieldCol col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12'>";
							if(intval($row["Payed"])==1){
								echo "<span style='color:green;'>". lang(142) ."</span>";
							}else{
								echo "<span style='color:orange;'>". lang(239) ."</span>";
							}
						echo "</div>";
					}
				echo "</div>";
			}
			echo "<div style='text-align: center;'><button class='btn form-control' style='width: fit-content;margin-top: 20px;height: auto;text-align: center;line-height: 15px;padding-top: 5px;padding-bottom: 5px;min-width: 135px;' onclick='FieldsSubscription();'>". lang(81) ."<br><span style='font-size:12px;'>". lang(236) ."</span></button><span style='vertical-align: sub;margin: 25px;'>oppure</span><button class='btn form-control' style='width: fit-content;margin-top: 20px;height: auto;text-align: center;line-height: 15px;padding-top: 5px;padding-bottom: 5px;min-width: 135px;' onclick='FieldsBankPay();'>". lang(238) ."<br><span style='font-size:12px;'>". lang(237) ."</span></button></div>";
		echo "</div>";
		
	break;
	case "createBankPayOrder":
		$FieldIDs=$_GET["FieldIDs"];
		$months=intval($_GET["months"]);
		$FieldIDsArr=explode(",",$FieldIDs);
		
		$TotalPrice=0;
		$MonthTotalPrice=0;
		$query="SELECT RecID, uuid, name, FieldSizeMq, FORMAT((FieldSizeMq/10000),2) FieldSizeHt, FORMAT((FieldSizeMq/10000)*". $costPerHt .",2) Price, 
		if(s.SubscriptionStart<now() AND s.SubscriptionEnd>now(),1,0) IsActive, s.SubscriptionStart, s.SubscriptionEnd FROM field 
		LEFT OUTER JOIN plans p ON p.PlanID=field.PlanID AND isnull(p.IsDeleted)
		LEFT OUTER JOIN subscriptions s ON s.PlanID=p.PlanID AND not isnull(s.StripeSubscriptionID)
		WHERE farmUuid='". mysql_real_escape_string($farmUuid) ."' AND isnull(field.isDeleted)";
		$query.=" AND (";
			foreach ($FieldIDsArr as $ff=>$FieldID){
				if($ff>0){$query.=" OR ";}
				$query.=" RecID='". mysql_real_escape_string($FieldID) ."'";
			}
		$query.=")";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$MonthTotalPrice=$TotalPrice+floatval(number_format($row["Price"],2,".",""));
		}
		$TotalPrice=$MonthTotalPrice*$months;
		
		//Genero il codice dell'ordine
		$OrderCode="";
		$query="SELECT concat(year(now()),'/',(YearOrderNumber+1)) NewOrderCode FROM (
			SELECT CONVERT(replace(PlanDescription,concat(year(now()), '/'),''),UNSIGNED INTEGER) YearOrderNumber FROM plans 
			WHERE `Interval`='BANK' AND isnull(isDeleted) AND PlanDescription LIKE concat(year(now()), '/%')
		)d1 ORDER BY YearOrderNumber DESC LIMIT 1";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$OrderCode=$row["NewOrderCode"];
		}
		if($OrderCode==="" || $OrderCode===null){$OrderCode=date("Y")."/1001";}
		
		//Inserisco il piano
		$query="INSERT INTO plans SET `Interval`='BANK', PlanDescription='". $OrderCode ."', Price='". $TotalPrice ."', RecUID='". $UID ."', RecUIP='". $UIP ."'";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		$PlanID=mysql_insert_id();
		
		//Inserisco la subscription 
		$query="INSERT INTO subscriptions SET PlanID='". $PlanID ."', RecUIP='". $UIP ."', SubscriptionStart=date(now()), SubscriptionEnd=DATE_ADD(date(now()), INTERVAL 6 MONTH)";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		$SubscriptionID=mysql_insert_id();
		
		$query="UPDATE field SET PlanID='". $PlanID ."' WHERE (";
		foreach ($FieldIDsArr as $ff=>$FieldID){
			if($ff>0){$query.=" OR ";}
			$query.=" RecID='". mysql_real_escape_string($FieldID) ."'";
		}
		$query.=")";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		
		//Stampo il box di riepilogo delle istruzioni per il bonifico
		echo "
			<p>". lang(246)."</p>
			". lang(253).":
			<br>". lang(254).": Sensilize S.r.l.
			<br>". lang(255).": IT12D0200802837000105827911
			<br>". lang(256).": UNCRITM1F86
			<br>". lang(257).": UNICREDIT Spa - Agenzia di Via dei Vecchietti, 11, 50123 Firenze, Italia
			<br>". lang(258).": <span id='OrderCode'>". $OrderCode ."</span>
			<br>". lang(248) .": &euro; <span id='TotalPrice'>". number_format($TotalPrice,2,",",".") ."</span>
		";
	break;
	case "deleteField":
		$FieldRecID=@$_GET["FieldRecID"];
		
		$query="SELECT RecID, uuid, farmUuid, p.StripePlanID, s.StripeSubscriptionID,
		if(s.SubscriptionStart<now() AND s.SubscriptionEnd>now(),1,0) IsActive FROM field 
		LEFT OUTER JOIN plans p ON p.PlanID=field.PlanID AND isnull(p.IsDeleted)
		LEFT OUTER JOIN subscriptions s ON s.PlanID=p.PlanID AND not isnull(s.StripeSubscriptionID)
		WHERE farmUuid='". mysql_real_escape_string($farmUuid) ."' AND isnull(field.isDeleted) AND field.RecID='". mysql_real_escape_string($FieldRecID) ."'";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		$num_rows = mysql_num_rows($result);
		if($num_rows>0){
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				$StripeSubscriptionID="";
				$FieldUuid=$row["uuid"];
				if(intval($row["IsActive"])==1 && $row["StripeSubscriptionID"]!=="" && $row["StripeSubscriptionID"]!==null){
					$StripeSubscriptionID=$row["StripeSubscriptionID"];
				}
			}
			
			//Eliminazione del campo su Geopard
			$token=loginGeopard();
			$ch = curl_init();
			$HttpHeaders=array('Content-Type: application/json','Authorization: '.$token);
			$Post_data='{"query":"mutation DeleteField { \n deleteField(input: { \n uuid: \"'. $FieldUuid .'\" \n farmUuid: \"'. $farmUuid .'\" \n }) { \n uuid \n farmUuid \n name \n } \n}"}';
			curl_setopt($ch, CURLOPT_URL, 'https://api.geopard.tech/data');
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true); 
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $Post_data);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $HttpHeaders);
			$result=curl_exec($ch);
			curl_log($Post_data, $HttpHeaders, $result, curl_getinfo($ch), "");
			$arrayResult = json_decode($result, true);
			if(isset($arrayResult['errors'])){
				die();
			}
			
			if($StripeSubscriptionID!==""){
				//Elimino la subscription di Stripe
				$ch = curl_init();
				curl_setopt_array(
					$ch, array(
						CURLOPT_URL => 'https://api.stripe.com/v1/subscriptions/'.$StripeSubscriptionID,
						CURLOPT_USERPWD => $StripeSecretKey,
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_POST => true,
						CURLOPT_CUSTOMREQUEST => 'DELETE',
						CURLOPT_HEADER => false
					)
				);
				$result=curl_exec($ch);
			}
			$query="UPDATE field SET IsDeleted=now() WHERE RecID='". mysql_real_escape_string($FieldRecID) ."' AND farmUuid='". mysql_real_escape_string($farmUuid) ."' AND isnull(isDeleted)";
			mysql_query($query) or die ("errore connessione db ".mysql_error());
		}
		
	break;
	case "deleteDoc": 
		$DocID=$_GET["ID"];
		$query="UPDATE documents SET IsDeleted=now() WHERE DocID='". mysql_real_escape_string($DocID) ."'";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
	break;
	case "deleteImage":
		$AuthCode=$_GET["AuthCode"];
		$query="UPDATE images SET IsDeleted=now() WHERE Authcode='". mysql_real_escape_string($AuthCode) ."'";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
	break;
	case "get_field_docs":
			
		$FieldRecID=@$_GET["FieldRecID"];
		if($FieldRecID===null){$FieldRecID="";}
		if($FieldRecID!==""){
			$query="SELECT DocID, Filename, Filesize, Rectimestamp FROM documents WHERE isnull(isDeleted) AND FieldID='". mysql_real_escape_string($FieldRecID) ."'";
			$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
			$num_rows = mysql_num_rows($result);
			if($num_rows>0){
				/*echo "<div class='s_table'>";
					echo "<div class='s_table_row s_table_head'>";
						echo "<div class='s_table_cell'>Nome</div>";
						echo "<div class='s_table_cell'>Dimesione</div>";
						echo "<div class='s_table_cell'>Data di caricamento</div>";
						echo "<div class='s_table_cell'></div>";
					echo "</div>";*/
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
						$docIcon=docIcon($row["Filename"]);
						echo "<div id='DocBox_". $row["DocID"] ."' class='docbox'>
							<a class='doclink' href='/docs/". $row["Filename"] ."' title='". $row["Filename"] ."' target=_blank>". $docIcon ."</a>
							<div class='docsFilename' title='". $row["Filename"] ."'>
								<nobr>". $row["Filename"] ."</nobr>
							</div>
							<a class='docsButton' id='deleteDoc_". $row["DocID"] ."' onclick=\"deleteDoc('". $FieldRecID ."', '". $row["DocID"] ."');\">". lang(109) ."</a>
						</div>";
						/*
						echo "<div class='s_table_row' id='AttachmentRow_". $row["DocID"] ."'>";
							echo "<div class='s_table_cell'><a href='/docs/". $row["Filename"] ."' target=_blank>". $row["Filename"] ."</a></div>";
							echo "<div class='s_table_cell'>". formatSizeUnits(floatval($row["Filesize"])) ."</div>";
							echo "<div class='s_table_cell'>". (new DateTime($row["Rectimestamp"]))->format('d/m/Y H:i') ."</div>";
							echo "<div class='s_table_cell'>
								<a class='deleteButton' id='deleteAttachment_". $row["DocID"] ."' onclick=\"deleteAttachment('". $FieldRecID ."', '". $row["DocID"] ."');\"><i class='fa fa-trash'></i></a>
							</div>";
						echo "</div>";
						*/
					}
				//echo "</div>";
			}else{
				echo "<div class='noResults'>". lang(127) ."</div>";
			}
		}
	break;
	case "get_field_images":
		$FieldRecID=@$_GET["FieldRecID"];
		if($FieldRecID===null){$FieldRecID="";}
		if($FieldRecID!==""){
			$query="SELECT Filename, ImageSize, Authcode FROM images WHERE AsUploaded=1 AND FieldID='". mysql_real_escape_string($FieldRecID) ."'  AND isnull(isDeleted)";
			$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
			$num_rows = mysql_num_rows($result);
			if($num_rows>0){
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
					echo "<div id='". $row["Authcode"] ."' class='imgbox'>
						<a class='imglink' rel='gallery' data-id='". $row["Authcode"] ."' data-caption='". $row["Filename"] ."' href='". $HTTP_HOST ."/pics/". $row["Filename"] ."' title='". $row["Filename"] ."'>
							<div class='imgarea' style=\"background-image: url('/pics/". $row["Filename"] ."');\"></div>
						</a>
						<div class='picsFilename' title='". $row["Filename"] ."'>
							<nobr>". $row["Filename"] ."</nobr>
						</div>
						<div id='". $row["Authcode"] ."'>
							<div class='picsButton' id='deleteImage_". $row["Authcode"] ."' onclick=\"deletePic('". $row["Authcode"] ."');\">". lang(109) ."</div>
						</div>
					</div>";
				}
			}else{
				echo "<div class='noResults'>". lang(143) ."</div>";
			}
		}
	break;	
	case "deleteCulture":
		$RecID=@$_GET["RecID"];
		$query="UPDATE fields_cultures SET isDeleted=now() WHERE RecID='". mysql_real_escape_string($RecID) ."'";
		mysql_query($query) or die ("errore connessione db ".mysql_error());
	break;	
	case "cultureForm": 
		$fieldCreated=@$_GET["fieldCreated"];
		$FieldRecID=$_GET["fieldRecID"];
		$currentCulture=@$_GET["current"];
		$CultureID=@$_GET["CultureID"];
		if($currentCulture==="" || $currentCulture===null){$currentCulture=0;}else{$currentCulture=intval($currentCulture);}
		if($fieldCreated!=="" && $fieldCreated!==null){$fieldCreated=intval($fieldCreated);}else{$fieldCreated=0;}
		
		$DateFrom="";
		$DateTo="";
		$CultureTypeID="";
		$Variety="";
		$yield_estimated="";
		if($CultureID!=="" && $CultureID!==null){
			$query="SELECT c.RecID, c.DateFrom, c.DateTo, c.CultureTypeID, ct.Name CultureTypeName,
			ifnull(c.Variety,'') Variety, c.TillingTypeID, t.Name TillingName, c.yield_estimated FROM fields_cultures c
			LEFT OUTER JOIN culturestypes ct ON ct.TypeID=c.CultureTypeID
			LEFT OUTER JOIN tillingtypes t ON t.TypeID=c.TillingTypeID
			WHERE isnull(isDeleted) AND FieldID='". mysql_real_escape_string($FieldRecID) ."' AND c.RecID='". mysql_real_escape_string($CultureID) ."'";
			//echo $query;
			$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				$DateFrom=(new DateTime($row["DateFrom"]))->format('d/m/Y');
				$DateTo=(new DateTime($row["DateTo"]))->format('d/m/Y');
				$CultureTypeID=$row["CultureTypeID"];
				$Variety=$row["Variety"];
				$yield_estimated=$row["yield_estimated"];
				if($CultureTypeID===null){$CultureTypeID="";}
			}
		}
		echo "<div class='CultureForm' id='ModalCultureForm'>";
			if($fieldCreated==1){echo "<p class='cultureFormDesc' sstyle='font-size: 15px;'>". lang(241) ."</p>";} //Campo appena creato
			echo "<form>";
				echo "<div class='CultureFormRow row'>
					<div class='CultureFormCell col-xs-12 col-sm-12 colm-md-6 col-lg-6 col-xl-6'>
						<div class='CultureFormCellDesc'>". lang(104) ."<sup><i class='fa fa-question-circle' style='margin-left: 5px;' title='". lang(188) ."'></i></sup></div>
					</div>
					<div class='CultureFormCell col-xs-12 col-sm-12 colm-md-6 col-lg-6 col-xl-6'>
						<input type=text name='DateFrom' class='CultureDate datepicker_from form-control' placeholder='dd/mm/yyyy' value='". $DateFrom ."'>
					</div>
				</div>
				<div class='CultureFormRow row'>
					<div class='CultureFormCell col-xs-12 col-sm-12 colm-md-6 col-lg-6 col-xl-6'>
						<div class='CultureFormCellDesc'>". lang(230) ."<sup><i class='fa fa-question-circle' style='margin-left: 5px;' title='". lang(189) ."'></i></sup></div>
					</div>
					<div class='CultureFormCell col-xs-12 col-sm-12 colm-md-6 col-lg-6 col-xl-6'>
						<input type=text name='DateTo' class='CultureDate datepicker_to form-control' placeholder='dd/mm/yyyy' value='". $DateTo ."'>
					</div>
				</div>
				<div class='CultureFormRow row'>
					<div class='CultureFormCell col-xs-12 col-sm-12 colm-md-6 col-lg-6 col-xl-6'>
						<div class='CultureFormCellDesc'>". lang(229) ."</div>
					</div>
					<div class='CultureFormCell col-xs-12 col-sm-12 colm-md-6 col-lg-6 col-xl-6'>
						<input type=number name='yield_estimated' class='form-control' placeholder='00.0' min=0 value='". $yield_estimated ."'>". lang(240) ."
					</div>
				</div>
				<div class='CultureFormRow row'>
					<div class='CultureFormCell col-xs-12 col-sm-12 colm-md-6 col-lg-6 col-xl-6'>
						<div class='CultureFormCellDesc'>". lang(106) ."</div>
					</div>
					<div class='CultureFormCell col-xs-12 col-sm-12 colm-md-6 col-lg-6 col-xl-6'>
						<select name='CultureType' class='form-control'>";
							echo "<option value=''>". lang(126) ."</option>";
							$query="SELECT TypeID, Name, LangID FROM culturestypes ORDER BY Name";
							$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
							while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
								echo "<option value='". $row["TypeID"] ."'";
									if (strval($row["TypeID"])==strval($CultureTypeID)){echo " selected";}
								echo ">". lang($row["LangID"]) ."</option>";
							}
						echo "</select>
					</div>
				</div>
				<div class='CultureFormRow row'>
					<div class='CultureFormCell col-xs-12 col-sm-12 colm-md-6 col-lg-6 col-xl-6'>
						<div class='CultureFormCellDesc'>". lang(107) ."</div>
					</div>
					<div class='CultureFormCell col-xs-12 col-sm-12 colm-md-6 col-lg-6 col-xl-6'>
						<input type=text name='Variety' class='form-control' placeholder='". lang(148) ."' value='". $Variety ."'>
					</div>
				</div>
			</form>
			<div>
				<button class='btn' onclick=\"editCulture('". $FieldRecID ."', '". $CultureID ."', '". $fieldCreated ."');\">";
					if($CultureID!=="" && $CultureID!==null){
						echo lang(87);
					}else{
						echo lang(108);
					}
				echo "</button>";
				if($CultureID!=="" && $CultureID!==null && $currentCulture==1){
					echo "<div style='margin-top: 10px;font-size: 14px;color: #ab2632;'><a class='link' onclick=\"closeCurrentCulture('". $FieldRecID ."', '". $CultureID ."');\">". lang(231) ."</a></div>";
				}elseif($fieldCreated==1){
					echo "<a class='link swal-light-close-link' onclick='showSubscriptionModal(1, \"". lang(81) ."\", false, ". $FieldRecID .");'>". lang(242) ."</a>";
				}
			echo "</div>";
		echo "</div>";
	break;	
	case "editCulture":
		
		$FieldRecID=@$_GET["FieldRecID"];
		$CultureID=@$_GET["CultureID"];
		$DateFrom=@$_GET["DateFrom"];
		$DateTo=@$_GET["DateTo"];
		$CultureType=@$_GET["CultureType"];
		$Variety=@$_GET["Variety"];
		$TillingType=@$_GET["TillingType"];
		$yield_estimated=@$_GET["yield_estimated"];
		$yield_effective=@$_GET["yield_effective"];
		if($CultureID!=="" && $CultureID!==null){
			$query="UPDATE fields_cultures SET IsDeleted=null";
		}else{
			$query="INSERT INTO fields_cultures SET RecUID='". $UID ."', FarmID='". $farmRecID ."', FieldID='". $FieldRecID ."'";
		}
		if($DateFrom!=="" && $DateFrom!==null){$query.=", DateFrom='". todbdate($DateFrom) ."'";}
		if($DateTo!=="" && $DateTo!==null){$query.=", DateTo='". todbdate($DateTo) ."'";}
		if($CultureType!=="" && $CultureType!==null){$query.=", CultureTypeID='". mysql_real_escape_string($CultureType) ."'";}
		if($Variety!=="" && $Variety!==null){$query.=", Variety='". mysql_real_escape_string($Variety) ."'";}
		if($TillingType!=="" && $TillingType!==null){$query.=", TillingTypeID='". mysql_real_escape_string($TillingType) ."'";}
		if($yield_estimated!=="" && $yield_estimated!==null){$query.=", yield_estimated='". mysql_real_escape_string(number_format($yield_estimated,2,".","")) ."'";}
		if($yield_effective!=="" && $yield_effective!==null){$query.=", yield_effective='". mysql_real_escape_string(number_format($yield_effective,2,".","")) ."'";}
		if($CultureID!=="" && $CultureID!==null){
			$query.=" WHERE RecID='". mysql_real_escape_string($CultureID) ."'";
		}
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		
	break;
	case "closeCurrentCulture":
		
		$FieldRecID=@$_GET["FieldRecID"];
		$CultureID=@$_GET["CultureID"];
		$closingCultureDate=@$_GET["CultureID"];
		if($closingCultureDate!=="" && $closingCultureDate!==null){
			$query="UPDATE fields_cultures SET DateTo=date('". todbdate($closingCultureDate) ."') WHERE RecID='". mysql_real_escape_string($CultureID) ."'";
		}else{
			$query="UPDATE fields_cultures SET DateTo=date(now()) WHERE RecID='". mysql_real_escape_string($CultureID) ."'";
		}
		mysql_query($query) or die ("errore connessione db ".mysql_error());
		
	break;
}
?>