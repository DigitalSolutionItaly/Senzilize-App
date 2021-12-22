<?php
include ($_SERVER['DOCUMENT_ROOT'] .'\include\engine.php');

$action=$_GET["a"];
switch ($action) {
	case "getFields":
		echo getSidebarFields();
	break;
	case "getFieldsImages":
		echo getSidebarFieldsImages();
	break;
	case "getFieldsNotices":
		echo getSidebarFieldsNotices();
	break;
	case "getFieldsWeather":
		echo getSidebarWeatherFields();
	break;
	case "showFieldNotice":
		
		$coordinates="";
		$NoticeRecID=$_GET["RecID"];
		
		$fieldRecID="";
		$NoticesData="";
		$query="SELECT r.FieldID, f.GeoJson FieldGeoJson, r.ReportTypeID, r.ReportSubject, r.ReportText, r.CenterLat, r.CenterLng, 
		ifnull(r.GeoJson,'') GeoJson, r.RecUID, concat(u.Name,' ', u.Lastname) RecUser, r.RecTimestamp FROM reporting r
		LEFT OUTER JOIN field f ON f.RecID=r.FieldID AND isnull(f.IsDeleted)
		LEFT OUTER JOIN users u ON u.UserID=r.RecUID WHERE r.RecID='". mysql_real_escape_string($NoticeRecID) ."' ORDER BY r.RecTimestamp";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			if($row["FieldGeoJson"]!=="" && $row["FieldGeoJson"]!==null){
				$coordinates=$row["FieldGeoJson"];
			}else{
				$coordinates=$row["GeoJson"];
			}
			$NoticesData='{"RecID":"'. $NoticeRecID .'","type":"'. getReportTypeObj(intval($row["ReportTypeID"]))->Name .'","name":"'. $row["ReportSubject"] .'","desc":"'. $row["ReportText"] .'","CenterLat":"'. $row["CenterLat"] .'","CenterLng":"'. $row["CenterLng"] .'","GeoJson":"'. $row["GeoJson"] .'"}';
		}
		echo '{"coords": ['. $coordinates .'],"notices": [['. $NoticesData .']]}';
		
	break;
	case "getGeoJson":
		
		$coordinates="";
		$fieldUuid=$_GET["fieldUuid"];
		
		$query="SELECT GeoJson FROM field WHERE uuid='". mysql_real_escape_string($fieldUuid) ."' AND isnull(isDeleted)";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$coordinates=$row["GeoJson"];
		}
		
		echo '{"coords": ['. $coordinates .']}';
		
	break;
	case "getImages":
		
		$fieldRecID="";
		$fieldUuid=$_GET["fieldUuid"];
		$query="SELECT RecID FROM field WHERE uuid='". mysql_real_escape_string($fieldUuid) ."' AND isnull(isDeleted)";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$fieldRecID=$row["RecID"];
		}
		
		$results=0;
		$LastYear="";
		$LastMonth="";
		$query="SELECT i.RecID, i.uuid, i.acquisitionDate, fi.ndviAverage, i.boundingBox, g.url FROM satelliteimage i
		LEFT OUTER JOIN fieldsatelliteimage fi ON fi.uuid=i.uuid
		LEFT OUTER JOIN (SELECT satelliteimageUuid, url FROM geomap GROUP BY satelliteimageuuid) g ON g.satelliteimageUuid=i.uuid
		WHERE i.fieldUuid='". mysql_real_escape_string($fieldUuid) ."' GROUP BY i.acquisitionDate ORDER BY i.acquisitionDate DESC";
		$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		$num_rows = mysql_num_rows($result);
		if($num_rows==0){
			$query="SELECT i.RecID, i.uuid, i.acquisitionDate, fi.ndviAverage, i.boundingBox, g.url FROM satelliteimage i
			LEFT OUTER JOIN fieldsatelliteimage fi ON fi.uuid=i.uuid
			LEFT OUTER JOIN (SELECT satelliteimageUuid, url FROM geomap GROUP BY satelliteimageuuid) g ON g.satelliteimageUuid=i.uuid
			WHERE i.fieldUuid='Temp_". mysql_real_escape_string($fieldRecID) ."' GROUP BY i.acquisitionDate ORDER BY i.acquisitionDate DESC";
			$result = mysql_query($query) or die ("errore connessione db ".mysql_error());
		}
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
			$results++;
			$boundingBox=$row["boundingBox"];
			$acquisitionDate=DateTime::createFromFormat('Y-m-d H:i:s', str_replace("T", " ", explode(".",$row["acquisitionDate"])[0]));

			$ImageSat="";
			$UrlArr=explode("&",$row["url"]);
			foreach ($UrlArr as $UrlParam) {
				if(urldecode(explode("=",$UrlParam)[0])=="LAYERS"){
					$ImageSat=explode(":",urldecode(explode("=",$UrlParam)[1]))[1];
				}
			}

			$CurrMonth=mb_convert_encoding(ucfirst(strftime("%B",strtotime($row["acquisitionDate"]))), "utf-8", "Windows-1251");
			$CurrYear=$acquisitionDate->format('Y');
			if($CurrMonth!==$LastMonth){
				if($LastMonth!==""){echo "<br>";}
				echo "<div class='imageMonth'>". $CurrMonth ." ". $CurrYear ."</div>";
			}
			$LastYear=$CurrYear;
			$LastMonth=$CurrMonth;

			$acquisitionDateToUrl=$row["acquisitionDate"]; //Deve avere il seguente formato -> 2019-09-24T18:50:09Z
			$acquisitionDateToUrl=explode(".",$acquisitionDateToUrl)[0]."Z";
			$ndviAverage=$row["ndviAverage"];
			if($ndviAverage=="" || $ndviAverage===null){
				$ndviAverageText="<i class='fa fa-cloud' aria-hidden='true' style='color: gray;'></i>";
			}else{
				$ndviAverageText=$ndviAverage ." ndvi";
			}
				//$ndviAverage //-> Se 0 è nuvoloso
			if($ImageSat!==""){
				echo "<div id='selectAcquisitionDate_". $row["RecID"] ."_button' onclick=\"loadFieldImage('". $row["RecID"] ."','". $fieldUuid ."','". $row["uuid"] ."','". $acquisitionDateToUrl ."','". $ImageSat ."')\" class='selectAcquisitionDate'>";
			}else{
				echo "<div id='selectAcquisitionDate_". $row["RecID"] ."_button' onclick=\"loadFieldImage('". $row["RecID"] ."','". $fieldUuid ."','". $row["uuid"] ."','". $acquisitionDateToUrl ."','". $ImageSat ."')\" class='selectAcquisitionDate'>". lang(30) ." ";
			}
			
			echo $acquisitionDate->format('d') ." ". mb_convert_encoding(ucfirst(strftime("%B",strtotime($row["acquisitionDate"]))), "utf-8", "Windows-1251");
			/*
			if($ImageSat!==""){
				echo "<span class='selectAcquisitionDateInfos'>". $ndviAverageText ."<div class='imageSat'>". strtoupper($ImageSat[0]).strtoupper($ImageSat[strlen($ImageSat)-1]) ."</div></span>";
			}*/
			echo "</div>";
		}
		if($results==0){echo "Nessun risultato";}
	break;
}
?>