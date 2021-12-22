<?php
include ($_SERVER['DOCUMENT_ROOT'] .'\include\engine.php');
$action=$_GET["a"];
$FieldID=@$_GET["FieldID"];
switch ($action) {
	case "getDailyWeather":
		$dayFrom="";
		$dayTo="";
		$day=$_GET["day"];
		$time=@$_GET["time"];
		if($day=="today"){
			$day=date("Y-m-d");
		}elseif($day=="tomorrow"){
			$day=date("Y-m-d", strtotime(date("Y-m-d"). " + 1 days"));
		}elseif($day=="aftertomorrow"){
			$day=date("Y-m-d", strtotime(date("Y-m-d"). " + 2 days"));
		}else{
			if($day=="" || $day==null){
				$day=date("Y-m-d");
			}else{
				if(substr($day,0,4)=="next"){
					$dayFrom=date("Y-m-d", strtotime(date("Y-m-d"). " + 1 days"));
					$dayTo=date("Y-m-d", strtotime(date("Y-m-d"). " + ". str_replace("next","",$day) ." days"));
				}else{
					$dayArr=explode("/",$day);
					if(count($dayArr)==3){
						$day=$dayArr[2]."-".$dayArr[1]."-".$dayArr[0];
					}
					$day=(new DateTime($day))->format('Y-m-d H:i:s');
				}
			}
		}
		//echo "<br>". $dayFrom ." - ". $dayTo;
		
		$WeatherLocationID="";
		$WeatherLocationLat="";
		$WeatherLocationLng="";
		$query="SELECT f.WeatherLocationID, wl.Lat, wl.Lng FROM field f
		LEFT OUTER JOIN weather_locations wl ON wl.LocationID=f.WeatherLocationID
		WHERE f.RecID='". mysql_real_escape_string($FieldID) ."'";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$WeatherLocationID=$row["WeatherLocationID"];
			$WeatherLocationLat=$row["Lat"];
			$WeatherLocationLng=$row["Lng"];
		}
		
		$w_counter=0;
		$query="SELECT Date, Time, Temperature, WindSpeed, WindDirection, Humidity, Cloudiness, Pressure, weather_icon FROM weather_data 
		WHERE LocationID='". $WeatherLocationID ."' AND isnull(isDeleted)";
		if($dayFrom!=="" && $dayTo!==""){
			$query.=" AND Date>='". $dayFrom ."' AND Date<='". $dayTo ."' AND DataType='forecast-daily'";
		}else{	
			$query.=" AND Date='". mysql_real_escape_string($day) ."'";
			if($time!=="" && $time!==null){
				if($_GET["day"]=="today"){
					$query.=" AND Time>now() AND DataType='forecast-hourly'";
				}else{
					$query.=" AND DataType='forecast-hourly'";
				}
				$timeArr=explode("-",$time);
				if(count($timeArr)>=1){
					$query.=" AND Time>='". $timeArr[0] ."'";
					if(count($timeArr)==2){
						$query.=" AND Time<='". $timeArr[1] ."'";
					}
				}
			}else{
				$query.=" AND DataType='forecast-hourly'";
			}
		}
		$query.=" GROUP BY Time";
		if(substr($_GET["day"],0,4)=="next"){
			$query.=", Date";
		}
		//echo $query;
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		$num_rows = mysql_num_rows($result);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$w_counter++;
			echo '<div class="content-col" style="width:'. (100/$num_rows) .'%;">
				<div class="content-time">';
					if(substr($_GET["day"],0,4)=="next"){
						echo (new DateTime($row["Date"]))->format('d/m/Y');
					}else{
						echo substr($row["Time"],0,5);
					}
				echo '</div>
				<div class="content-box">
					<div class="content-weather">
						<!--<img src="/assets/img/sun2.svg" alt="sun" class="content-icon icon-sun">-->
						<img src="http://openweathermap.org/img/wn/'. $row["weather_icon"] .'@2x.png" alt="sun" class="content-icon" style="width: 40px;height: 40px;margin: 0px;">
						<p class="content-text">';
							if(substr($_GET["day"],0,4)=="next"){
								//echo $row["Temperature"];
								$TemperatureArr=explode(",",$row["Temperature"]);
								$TempMin=floatval(explode(":",$TemperatureArr[0])[1]);
								$TempMax=floatval(explode(":",$TemperatureArr[count($TemperatureArr)-1])[1]);
								foreach ($TemperatureArr as $temp) {
									$temp=floatval(explode(":",$temp)[1]);
									if($temp<$TempMin){$TempMin=$temp;}
									if($temp>$TempMax){$TempMax=$temp;}
								}
								echo "<span class='min_temp'>+". $TempMin ."&deg;</span> / <span class='max_temp'>+". $TempMax ."&deg;</span>";
							}else{
								echo '+'. number_format($row["Temperature"], 1, '.', '') .'&deg;';
							}
						echo '</p>
					</div>
					<div class="content-weather">
						<img src="/assets/img/wind.svg" alt="wind" class="content-icon icon-wind">
						<p class="content-text">'. $row["WindSpeed"] .' m/s <img src="/assets/img/weather-share.svg" title="'. $row["WindDirection"] .'&deg;" class="weather-share" style="transform: rotate('. $row["WindDirection"] .'deg);"></p>
					</div>
					<div class="content-weather">
						<img src="/assets/img/humidity.svg" alt="humidity" class="content-icon icon-humidity">
						<p class="content-text">'. $row["Humidity"] .' %</p>
					</div>
					<div class="content-weather">
						<img src="/assets/img/cloud.svg" alt="cloud" class="content-icon icon-cloud">
						<p class="content-text">'. $row["Cloudiness"] .' %</p>
					</div>
					<div class="content-weather">
						<img src="/assets/img/pressure.svg" alt="pressure" class="content-icon icon-pressure">
						<p class="content-text">'. $row["Pressure"] .' mb</p>
					</div>
				</div>
			</div>';
		}
		if ($w_counter==0){
			echo "<div class='noResults'>No results</div>";
		}
	break;
	case "weather-chart-0":
		$DateFrom=@$_GET["from"];
		$DateTo=@$_GET["to"];
		
		//Data di inizio coltura dell'ultima stagione (attiva attualmente)
		$WeatherChart0From="";
		$query2="SELECT DateFrom FROM fields_cultures WHERE isnull(isDeleted) AND FieldID='". mysql_real_escape_string($FieldID) ."' ORDER BY DateTo DESC LIMIT 1";
		$result2 = mysql_query($query2) or die ("errore connessione db ".mysql_error());
		while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)){
			$WeatherChart0From=$row2["DateFrom"];
		}
		if($WeatherChart0From===null){$WeatherChart0From="";}
		
		//Imposto la variabile per il conteggio dei giorni
		$query2="SET @position := 0;";
		$result2 = mysql_query($query2) or die ("errore connessione db ".mysql_error());
		
		//Ciclo i giorni per creare i JSON
		$wc=0;
		$somma_termica=0;
		$precipitazioni=0;				
		$somma_termica_json='{"color":"#b22222","weather_dates": [';
		$precipitazioni_json='{"color":"#00bfff","weather_dates": [';
		$ndvi_json='{"color":"#87dc48","weather_dates": [';
		$query2="SELECT Date, ifnull(Temp, 0) Temp, ifnull(Rain,0) Rain, ifnull(ndviAverage,0) ndviAverage FROM (
			SELECT d.Date, w.Temp, w.Rain, i1.ndviAverage FROM(
				SELECT dd.CurrDate Date FROM (
					SELECT DATE_ADD(DATE_ADD('". $WeatherChart0From ."', INTERVAL -1 DAY), INTERVAL ((@position := ifnull(@position, 0) + 1)) DAY) CurrDate FROM fieldsatelliteimage
				) dd
				WHERE dd.CurrDate<=now()
			)d
			LEFT OUTER JOIN (
				SELECT Date, (Round((TempMin+TempMax)/2,2))-10 Temp, if(isnull(Rain) OR Rain='',0,Rain) Rain FROM weather_data
				WHERE isnull(isDeleted) AND LocationID='40' AND Date>=DATE_ADD('". $WeatherChart0From ."', INTERVAL -1 DAY) AND DataType='forecast-daily' GROUP BY Date ORDER BY Date ASC
			) w ON w.Date=d.Date
			LEFT OUTER JOIN (
				SELECT STR_TO_DATE(i.acquisitionDate, '%Y-%m-%d') Date, FORMAT(AVG(IFNULL(IF(fi.ndviAverage = '', NULL, fi.ndviAverage), 0)), 2) ndviAverage
				FROM satelliteimage i
				LEFT OUTER JOIN fieldsatelliteimage fi ON fi.uuid = i.uuid
				LEFT OUTER JOIN (
					SELECT satelliteimageUuid, url FROM geomap GROUP BY satelliteimageuuid
				) g ON g.satelliteimageUuid = i.uuid
				WHERE i.fieldUuid = 'fac6cfe8-9cd5-46a6-972d-35354f76e4cc' AND i.acquisitionDate>=DATE_ADD('". $WeatherChart0From ."', INTERVAL -1 DAY)
				GROUP BY i.acquisitionDate ORDER BY i.acquisitionDate DESC
			) i1 ON i1.Date=d.Date
		) d1";
		$query2.=" WHERE 1=1";
		if(($DateFrom!=="" && $DateFrom!==null) || ($DateTo!=="" && $DateTo!==null)){
			if($DateFrom!=="" && $DateFrom!==null){$query2.=" AND Date>='". mysql_real_escape_string(todbdate($DateFrom)) ."'";}
			if($DateTo!=="" && $DateTo!==null){$query2.=" AND Date<='". mysql_real_escape_string(todbdate($DateTo)) ."'";}
		}
		$result2 = mysql_query($query2) or die ("errore connessione db ".mysql_error());
		while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)){
			$weather_date=mb_convert_encoding(strftime("%d",strtotime($row2["Date"])), "utf-8", "Windows-1251");
			$weather_date.=" ".mb_convert_encoding(ucfirst(strftime("%B",strtotime($row2["Date"]))), "utf-8", "Windows-1251");
			if($wc>0){
				$somma_termica_json.=',';
				$precipitazioni_json.=',';
				$ndvi_json.=',';
			}
			$somma_termica=$somma_termica+$row2["Temp"];
			$precipitazioni=$precipitazioni+floatval($row2["Rain"]);
			$somma_termica_json.='{"weather_date": "'. $weather_date .'", "weather_val": "'. $somma_termica .'"}';
			$precipitazioni_json.='{"weather_date": "'. $weather_date .'", "weather_val": "'. $precipitazioni .'"}';
			$ndvi_json.='{"weather_date": "'. $weather_date .'", "weather_val": "'. $row2["ndviAverage"] .'"}';
			$wc++;
		}
		$somma_termica_json.=']}';
		$precipitazioni_json.=']}';
		$ndvi_json.=']}';
		
		header('Content-Type: application/json');
		echo '['. $somma_termica_json .','. $precipitazioni_json .','. $ndvi_json .']';		
		
	break;
}