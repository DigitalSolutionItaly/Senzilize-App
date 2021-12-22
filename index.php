<div id="map-popup">
	<a onclick="$('#map-popup').slideUp();" id="map-popup-close"><i class="fa fa-times" aria-hidden="true"></i></a>
	<div id="map-popup-title"></div>
	<div id="map-popup-content"></div>
	<div id="map-popup-button"></div>
</div>
<div id="map-loading">
	<i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
</div>
<div id="map"></div>
<div id='map-floating-level-draw' class='map-floating-level' style='text-align: center;'>
  	<div class="map-floating-level-title">
		<span><?=lang(17)?></span>
		<br><span style="font-size:12px;"><?=lang(216)?></span>
	</div>
</div>
<div id="map-floating-close-draw-mobile" class="map-floating-level">
	<a onclick='initiatePolygon("<?=$farmUuid?>","");' style="cursor: pointer;"><i class="fa fa-times" aria-hidden="true"></i></a>
</div>
<div id='map-floating-level-field' class='map-floating-level'>
  	<div class="map-floating-level-title">
		<span><?=lang(18)?></span>
		<a onclick="hideNewFieldForm();resetMap();removeDrawing();" style="float:right;cursor: pointer;text-decoration: underline;font-size: 14px;"><?=lang(19)?></a>
  	</div>
	<div class="map-floating-level-form">
		<input type=hidden id="new-field-fieldUuid">
		<input type=hidden id="new-field-farmUuid">
		<input type="text" id="new-field-name" class="form-control" placeholder="<?=lang(21)?>">
		<button class="form-control btn" onclick="saveField()"><?=lang(20)?></button>
	</div>
</div>
<div id='map-floating-level-zoom' class='map-floating-level'>
	<a onclick="ZoomIn();"><i class="fa fa-plus" aria-hidden="true"></i></a>
	<a onclick="ZoomOut();" style="margin-bottom: 3px;padding-top: 3px;"><i class="fa fa-minus" aria-hidden="true"></i></a>
</div>
<div id='map-floating-level-search-icon' class='map-floating-level'>
	<a onclick="openMapSearch();" style="cursor: pointer;" title="<?=lang(22)?>"><i class="fa fa-search" aria-hidden="true"></i></a>
</div>
<div id='map-floating-level-search-address' class='map-floating-level'>
	<img src="/images/navigation.svg" onclick="geoLocateCurrentPosition();" class="navigation-icon" title="<?=lang(23)?>">
	<div class='SearchAddress' id='SearchAddress_<?=$farmUuid?>'>
		<input type=text class='form-control' id='SearchAddressField' placeholder='<?=lang(24)?>' autocomplete='off'>
	</div>
	<i class="fa fa-times" aria-hidden="true" style="margin-left: 15px;cursor: pointer;margin-right: 7px;" onclick="closeMapSearch();" title="<?=lang(25)?>"></i>
</div>
<div id="map-floating-level-select-index" class="map-floating-level">
	<div id="selectIndex">
		<?php
		foreach ($index_codes as $i=>$code) {
			echo "<a target='_blank' onclick='loadFieldImage(window.selected_RecID,window.selected_fieldUuid,window.selected_imageUuid,window.selected_acquisitionDate,window.selected_imageSat,\"". $code ."\")' title='". $index_descs[$i] ."' id='selectIndex_". $code ."_button' class='selectIndex'>". $code ."</a>";
		}
		?>
	</div>
</div>
<div id="marker_notice_msg" class="map-floating-level"><?=lang(314)?></div>
<div id="draw_notice_msg" class="map-floating-level"><?=lang(315)?></div>
<script>
google.maps.event.addDomListener(window, 'load', initMap(<?=$DefaultCenterLat?>,<?=$DefaultCenterLng?>,<?=$DefaultCenterZoom?>));
$(document).ready(function() {
	SearchAddressField = new google.maps.places.Autocomplete((document.getElementById('SearchAddressField')), {
		types: ['geocode']
	});
	SearchAddressField.setComponentRestrictions({
		'country': ['it','ru','ua']
	});
	SearchAddressField.addListener('place_changed', function() {
		var pos = new google.maps.LatLng(SearchAddressField.getPlace().geometry.location.lat(), SearchAddressField.getPlace().geometry.location.lng());
		var marker = new google.maps.Marker({
			position: pos,
			map: window.map,
			disableDefaultUI: true
		});
		map.setCenter(pos);
		map.setZoom(15);
	});
	loadFieldsMarkers();
	<?php if($FieldsCounter==0){ ?>
		geoLocateCurrentPosition();
	<?php } ?>
	<?php if(@$_GET["a"]!=="" && @$_GET["a"]!==null){ ?>
		<?php if(substr($_GET["a"],0,8)=="success_"){ ?>
			$("#map-popup-title").html("<?=lang(26)?>");
			$("#map-popup-content").html("<?=lang(27)?>");
			$("#map-popup-button").html("");
			$("#map-popup").slideDown();
			showFieldImages("Temp_<?=str_replace("cancel_","",str_replace("success_","",$_GET["a"]))?>","<?=str_replace("cancel_","",str_replace("success_","",$_GET["a"]))?>");
		<?php } ?>
		<?php if(substr($_GET["a"],0,7)=="cancel_"){ ?>
			$("#map-popup-title").html("<?=lang(28)?>");
			$("#map-popup-content").html("<?=lang(29)?>");
			$("#map-popup-button").html("");
			$("#map-popup").slideDown();
			<?php 
			$query="UPDATE field SET PlanID=null WHERE RecID='". mysql_real_escape_string(str_replace("cancel_","",str_replace("success_","",$_GET["a"]))) ."'";	
			mysql_query($query) or die ("errore connessione db ".mysql_error());
		} ?>
		history.pushState({}, null, "/");
	<?php } ?>
});
</script>