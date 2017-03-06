<?php ?>
<!DOCTYPE html>
<html>
<head>
<title>hiker.serwr.com</title>

<script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.js"></script>
<script src="https://code.createjs.com/easeljs-0.8.2.min.js"></script>
<link rel="stylesheet" href="https://hiker.serwr.com/index.css" />
<!-- <link rel="stylesheet" type="text/css" href="index.css"></link> -->
<script>
var windowSize = 0;
var scale = 1.2;
var stage, timeCircle, tickCircle;
var ajaxResponse;
var highestLat = 0;
var highestLng = 0;
var lowestLat = 9999;
var lowestLng = 9999;
var update = false;
var username = "user";
var password = "null";


window.addEventListener('resize', resize, false);

function init() 
{
	stage = new createjs.Stage("canvas");
	// enable touch interactions if supported on the current device:
	createjs.Touch.enable(stage);

	// enabled mouse over / out events
	stage.enableMouseOver(10);
	stage.mouseMoveOutside = true; // keep tracking the mouse even when it leaves the canvas
	
	resize()
	createjs.Ticker.addEventListener("tick", tick);
}

function tick(event) 
{
	if(update)
	{
		update = false;
		stage.update(event);
	}
}

function resize() 
{	
		stage.canvas.width = window.innerWidth;
		stage.canvas.height = window.innerHeight;
		getMap();
}

function getMap()
{
	$.ajax({
		url: "https://hiker.serwr.com/api.php/all"
	}).then(function(response) {
		ajaxResponse = JSON.parse(response);
		drawMap();
		drawForms();
	});
}

function addPlaces(places)
{
	jQuery.each(places, function(placeId, place)
	{
		var latitude = parseFloat(place.position.lat);
		var longitute = parseFloat(place.position.lng);
		var radius = parseFloat(place.radius);
		radius = (radius*(windowSize/scale))/200;
		longitute = ((longitute-lowestLng) / (highestLng-lowestLng))*(windowSize/scale)+((window.innerWidth-(windowSize/scale))/2);
		latitude = ((latitude-lowestLat) / (highestLat-lowestLat))*(windowSize/scale)+((window.innerHeight-(windowSize/scale))/2);
		
		addPlace(latitude, longitute, radius/2, getColor(), place.name, place.info);
		var imagePlace = 0;
		addImage(latitude, longitute, 0.1*((windowSize/scale)/2000), "7lMkBSY.jpg", place.name, imagePlace++);
		jQuery.each(place.media, function(mediaId, med)
		{
			switch(med.type)
			{
				case "text":
					addText(latitude, longitute, med.name, med.content, imagePlace++);
				case "image":
					addImage(latitude, longitute, 0.1*((windowSize/scale)/2000), "7lMkBSY.jpg", place.name, imagePlace++);
					break;
			}
		});
	});	
}

function addText(latitude, longitude, name, text, imagePlace)
{
	var text = new createjs.Text(name, "20px Arial", "#ff7700");
	text.x = imageX(longitude, imagePlace);
	text.y = imageY(latitude, imagePlace);
	update = true;
}

function addPlace(latitude, longitude, radius, color, name, info)
{
	var circle = new createjs.Shape();
		circle.graphics.setStrokeStyle(15, 'round', 'round').beginStroke(color).drawCircle(0, 0, radius);
		circle.x = longitude;
		circle.y = latitude;
		circle.addEventListener("mouseover", function(event) { 
			var circle2 = new createjs.Shape();
			circle2.graphics.beginFill("Black").drawCircle(0, 0, radius/2);
			circle2.x = longitude;
			circle2.y = latitude;
			stage.addChild(circle2);
			stage.circle2 = circle2;
			var placeBackground = new createjs.Shape();
			placeBackground.graphics.beginFill("#ffffff");
			placeBackground.graphics.drawRoundRect (event.stageX-5, event.stageY-5, (info.length*10)+10, 50, 10, 10, 10, 10);
			stage.addChild(placeBackground);
			stage.placeBackground = placeBackground;
			var placeName = new createjs.Text(name, "15px Arial", getColor());
				placeName.x = event.stageX;
				placeName.y = event.stageY;
			var placeInfo = new createjs.Text(info, "15px Arial", getColor()); 
				placeInfo.x = event.stageX;
				placeInfo.y = event.stageY+20;
			stage.addChild(placeName);
			stage.addChild(placeInfo);
			stage.placeName = placeName;
			stage.placeInfo = placeInfo;
			update = true;
		})
		circle.addEventListener("mouseout", function(event) { 
			stage.removeChild(stage.circle2);
			stage.removeChild(stage.placeName);
			stage.removeChild(stage.placeInfo);
			stage.removeChild(stage.placeBackground);
		})
		stage.addChild(circle);
		update = true;
}
function addImage(latitude, longitude, width, url, name, imagePlace)
{
	var image = new Image();
	image.src = url;
	image.onload = handleImageLoad;
	bitmap = new createjs.Bitmap(image);
	bitmap.x = imageX(longitude, imagePlace);
	bitmap.y = imageY(latitude, imagePlace);
	bitmap.scaleX = bitmap.scaleY = bitmap.scale = width;
	bitmap.cursor = "pointer";
	stage.addChild(bitmap); 
	
	bitmap.on("click", function (evt) {
		window.open(url, name, '');
	});
}

function imageX(number, imagePlace)
{
	switch(imagePlace)
	{
		case 1: return number + 50;
		case 2: return number + 100;
		case 3: return number + 50;
		case 5: return number - 50;
		case 6: return number - 100;
		case 7: return number - 50;
		default:
		return number;
	}
}

function imageY(number, imagePlace)
{
		switch(imagePlace)
		{
		case 0: return number - 100;
		case 1: return number - 50;
		case 3: return number + 50;
		case 4: return number + 100;
		case 5: return number + 50;
		case 7: return number - 50;
		default:
		return number;
		}
}

function handleImageLoad()
{
	update = true;
}

function addPath(polylines, name, info, length, duration)
{
	var line = new createjs.Shape();
	var firststep = true;
	jQuery.each(polylines, function(polylineId, pl)
	{
		var latitude = parseFloat(pl.lat);
		var longitute = parseFloat(pl.lng);
		if(firststep)
		{
			line.graphics.setStrokeStyle(15, 'round', 'round').beginStroke(getColor());
			
			firststep = false;
			
			longitute = ((longitute-lowestLng) / (highestLng-lowestLng))*(windowSize/scale)+((window.innerWidth-(windowSize/scale))/2);
			latitude = ((latitude-lowestLat) / (highestLat-lowestLat))*(windowSize/scale)+((window.innerHeight-(windowSize/scale))/2);
			line.graphics.mt(longitute, latitude);
				
			
			var nameText = new createjs.Text(name, "15px Arial", getColor());
				nameText.x = longitute;
				nameText.y = latitude;
				nameText.alpha = 0;
			var infoText = new createjs.Text(info, "10px Arial", getColor());
				infoText.x = nameText.x
				infoText.y = nameText.y + 20;
				infoText.alpha = 0;
			var durationText = new createjs.Text("Duration: " + duration, "10px Arial", getColor());
				durationText.x = nameText.x
				durationText.y = nameText.y + 30;
				durationText.alpha = 0;
			var lengthText = new createjs.Text("Length: " + length, "10px Arial", getColor());
				lengthText.x = nameText.x
				lengthText.y = nameText.y + 40;
				lengthText.alpha = 0;
			 
			 stage.addChild(nameText);
			 stage.addChild(infoText);
			 stage.addChild(durationText);
			 stage.addChild(lengthText);
			 stage.nameText = nameText;
			 stage.infoText = infoText;
			 stage.durationText = durationText;
			 stage.lengthText = lengthText;
		}
		else
		{
			longitute = ((longitute-lowestLng) / (highestLng-lowestLng))*(windowSize/scale)+((window.innerWidth-(windowSize/scale))/2);
			latitude = ((latitude-lowestLat) / (highestLat-lowestLat))*(windowSize/scale)+((window.innerHeight-(windowSize/scale))/2);
			line.graphics.lt(longitute, latitude);
		}
	});
	line.addEventListener("mouseover", function(event) { 
			stage.nameText.x = event.stageX;
			stage.infoText.x = event.stageX;
			stage.durationText.x = event.stageX;
			stage.lengthText.x = event.stageX;
			stage.nameText.y = event.stageY;
			stage.infoText.y = event.stageY + 20;
			stage.durationText.y = event.stageY + 40;
			stage.lengthText.y = event.stageY + 60;
			stage.nameText.alpha = 1;
			stage.infoText.alpha = 1;
			stage.durationText.alpha = 1;
			stage.lengthText.alpha = 1;
			update = true;
	});	
	line.addEventListener("mouseout", function(event) { 
			stage.nameText.alpha = 0;
			stage.infoText.alpha = 0;
			stage.durationText.alpha = 0;
			stage.lengthText.alpha = 0;
			update = true;
	});
	stage.addChild(line);
	update = true;
}

function drawMap() {
	stage.removeAllChildren();
   if(window.innerWidth < window.innerHeight)
   {
	 windowSize = window.innerWidth;
   }
   else
   {
	   windowSize = window.innerHeight;
   }
   
   
   jQuery.each(ajaxResponse, function(bundleId, value)
   {
	   jQuery.each(value.paths, function(pathId, path)
		{
			
		   jQuery.each(path.places, function(placeId, place)
			{
				jQuery.each(place.position, function(positionId, pos)
				{
					var latitude = parseFloat(pos.lat);
					var longitute = parseFloat(pos.lng);
					if(latitude < lowestLat)
					{
						lowestLat = latitude;
					}
					if(latitude > highestLat)
					{
						highestLat = latitude;
					}
					if(longitute < lowestLng)
					{
						lowestLng = longitute;
					}
					if(longitute > highestLng)
					{
						highestLng = longitute;
					}
				});
				jQuery.each(place.media, function(mediaId, med)
				{
					
				});
			});	
			jQuery.each(path.polyline, function(polylineId, pl)
			{
				var latitude = parseFloat(pl.lat);
				var longitute = parseFloat(pl.lng);
				if(latitude < lowestLat)
				{
					lowestLat = latitude;
				}
				if(latitude > highestLat)
				{
					highestLat = latitude;
				}
				if(longitute < lowestLng)
				{
					lowestLng = longitute;
				}
				if(longitute > highestLng)
				{
					highestLng = longitute;
				}
			});						
		});
   });
   
   jQuery.each(ajaxResponse, function(bundleId, value)
   {
	   jQuery.each(value.paths, function(pathId, path)
		{
			
		    addPlaces(path.places);
			addPath(path.polyline, path.name, path.info, path.length, path.duration);
			
		});
   });
}	
function getColor() {
    var r = 100*Math.random()|0,
        g = 100*Math.random()|0,
        b = 100*Math.random()|0;
    return 'rgb(' + r + ',' + g + ',' + b + ')';
}
function drawEmptyFormBundle() {
	var html = '<ul data-role="listview" data-inset="true">';
		html += '<li data-role="list-divider">New Bundle</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="newBundle">Id:</label>';
		html += '<input type="text" name="id" id="newBundle" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="newBundle">Name:</label>';
		html += '<input type="text" name="name" id="newBundle" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newBundle">Image:</label>';
		html += '<input type="text" name="image" id="newBundle" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newBundle">Info:</label>';
		html += '<input type="text" name="info" id="newBundle" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<div data-role="controlgroup" data-type="horizontal">';
		    html += '<a onclick=\'deleteDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=bundle")\' class="ui-btn ui-corner-all ui-icon-delete ui-btn-icon-left">Delete</a>';
			html += '<a onclick=\'drawEmptyFormBundle();\' class="ui-btn ui-corner-all ui-icon-recycle ui-btn-icon-left">New</a>';
			html += '<a onclick=\'updateDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=bundle")\' class="ui-btn ui-corner-all ui-icon-check ui-btn-icon-left">Update</a>';
	   html += '</div>';
	   html += '</li></ul>';	   
	   $(html).appendTo("#bundles_table").trigger("create");
}

function drawFormBundle(bundleId, value) {
	var html = '<ul data-role="listview" data-inset="true">';
	html += '<li data-role="list-divider">' + value.name + '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="bundle' + bundleId + '">Id:</label>';
		html += '<input type="text" name="id" id="bundle' + bundleId + '" value="' + value.id + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="bundle' + bundleId + '">Name:</label>';
		html += '<input type="text" name="name" id="bundle' + bundleId + '" value="' + value.name + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="bundle' + bundleId + '">Image:</label>';
		html += '<input type="text" name="image" id="bundle' + bundleId + '" value="' + value.image + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="bundle' + bundleId + '">Info:</label>';
		html += '<input type="text" name="info" id="bundle' + bundleId + '" value="' + value.info + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<div data-role="controlgroup" data-type="horizontal">';
		    html += '<a onclick=\'deleteDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=bundle")\' class="ui-btn ui-corner-all ui-icon-delete ui-btn-icon-left">Delete</a>';
			html += '<a onclick=\'drawEmptyFormBundle();\' class="ui-btn ui-corner-all ui-icon-recycle ui-btn-icon-left">New</a>';
			html += '<a onclick=\'updateDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=bundle")\' class="ui-btn ui-corner-all ui-icon-check ui-btn-icon-left">Update</a>';
	   html += '</div>';
	   html += '</li></ul>';	   
	   $(html).appendTo("#bundles_table").trigger("create");
}

function drawEmptyFormPath() {
	var html = '<ul data-role="listview" data-inset="true">';
	html += '<li data-role="list-divider">New Path</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="path">Id:</label>';
		html += '<input type="text" name="id" id="newPath" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="newPath">Bundle ID:</label>';
		html += '<input type="text" name="bundle_id" id="newPath" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="newPath">Name:</label>';
		html += '<input type="text" name="name" id="newPath" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPath">Image:</label>';
		html += '<input type="text" name="image" id="newPath" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPath">Info:</label>';
		html += '<input type="text" name="info" id="newPath" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPath">Length:</label>';
		html += '<input type="text" name="length" id="newPath" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPath">Duration:</label>';
		html += '<input type="text" name="duration" id="newPath" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<div data-role="controlgroup" data-type="horizontal">';
		    html += '<a onclick=\'deleteDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=path")\' class="ui-btn ui-corner-all ui-icon-delete ui-btn-icon-left">Delete</a>';
			html += '<a onclick=\'drawEmptyFormPath();\' class="ui-btn ui-corner-all ui-icon-recycle ui-btn-icon-left">New</a>';
			html += '<a onclick=\'updateDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=path")\' class="ui-btn ui-corner-all ui-icon-check ui-btn-icon-left">Update</a>';
	   html += '</div>';
	   html += '</li></ul>';	   
	   $(html).appendTo("#paths_table").trigger("create");
}

function drawFormPath(pathId, value, bundleId) {
	var html = '<ul data-role="listview" data-inset="true">';
	 html += '<li data-role="list-divider">' + value.name + '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="path' + pathId + '">Id:</label>';
		html += '<input type="text" name="id" id="path' + pathId + '" value="' + value.id + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="path' + pathId + '">Bundle ID:</label>';
		html += '<input type="text" name="bundle_id" id="path' + pathId + '" value="' + bundleId + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="path' + pathId + '">Name:</label>';
		html += '<input type="text" name="name" id="path' + pathId + '" value="' + value.name + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="path' + pathId + '">Image:</label>';
		html += '<input type="text" name="image" id="path' + pathId + '" value="' + value.image + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="path' + pathId + '">Info:</label>';
		html += '<input type="text" name="info" id="path' + pathId + '" value="' + value.info + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="path' + pathId + '">Length:</label>';
		html += '<input type="text" name="length" id="path' + pathId + '" value="' + value.length + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="path' + pathId + '">Duration:</label>';
		html += '<input type="text" name="duration" id="path' + pathId + '" value="' + value.duration + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<div data-role="controlgroup" data-type="horizontal">';
		    html += '<a onclick=\'deleteDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=path")\' class="ui-btn ui-corner-all ui-icon-delete ui-btn-icon-left">Delete</a>';
			html += '<a onclick=\'drawEmptyFormPath();\' class="ui-btn ui-corner-all ui-icon-recycle ui-btn-icon-left">New</a>';
			html += '<a onclick=\'updateDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=path")\' class="ui-btn ui-corner-all ui-icon-check ui-btn-icon-left">Update</a>';
	   html += '</div>';
	   html += '</li></ul>';	   
	   $(html).appendTo("#paths_table").trigger("create");
}

function drawEmptyFormPlace() {
	var html = '<ul data-role="listview" data-inset="true">';
	html += '<li data-role="list-divider">New Place</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="newPlace">Name:</label>';
		html += '<input type="text" name="name" id="newPlace" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="newPlace">Path ID:</label>';
		html += '<input type="text" name="path_id" id="newPlace" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPlace">Image:</label>';
		html += '<input type="text" name="image" id="newPlace" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPlace">Info:</label>';
		html += '<input type="text" name="info" id="newPlace" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPlace">Radius:</label>';
		html += '<input type="text" name="radius" id="newPlace" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPlace">Longitude:</label>';
		html += '<input type="text" name="position_lng" id="newPlace" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPlace">Latitude:</label>';
		html += '<input type="text" name="position_lat" id="newPlace" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<div data-role="controlgroup" data-type="horizontal">';
		    html += '<a onclick=\'deleteDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=place")\' class="ui-btn ui-corner-all ui-icon-delete ui-btn-icon-left">Delete</a>';
			html += '<a onclick=\'drawEmptyFormPlace();\' class="ui-btn ui-corner-all ui-icon-recycle ui-btn-icon-left">New</a>';
			html += '<a onclick=\'updateDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=place")\' class="ui-btn ui-corner-all ui-icon-check ui-btn-icon-left">Update</a>';
	   html += '</div>';
	   html += '</li></ul>';	   
	   $(html).appendTo("#places_table").trigger("create");
}

function drawFormPlace(placeId, value, pathId) {
	var html = '<ul data-role="listview" data-inset="true">';
	html += '<li data-role="list-divider">' + value.name + '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="place' + placeId + '">Name:</label>';
		html += '<input type="text" name="name" id="place' + placeId + '" value="' + value.name + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="place' + placeId + '">Path ID:</label>';
		html += '<input type="text" name="path_id" id="place' + placeId + '" value="' + pathId + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="place' + placeId + '">Image:</label>';
		html += '<input type="text" name="image" id="place' + placeId + '" value="' + value.image + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="place' + placeId + '">Info:</label>';
		html += '<input type="text" name="info" id="place' + placeId + '" value="' + value.info + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="place' + placeId + '">Radius:</label>';
		html += '<input type="text" name="radius" id="place' + placeId + '" value="' + value.radius + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="place' + placeId + '">Longitude:</label>';
		html += '<input type="text" name="position_lng" id="place' + placeId + '" value="' + value.position.lng + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="place' + placeId + '">Latitude:</label>';
		html += '<input type="text" name="position_lat" id="place' + placeId + '" value="' + value.position.lat + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<div data-role="controlgroup" data-type="horizontal">';
		    html += '<a onclick=\'deleteDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=place")\' class="ui-btn ui-corner-all ui-icon-delete ui-btn-icon-left">Delete</a>';
			html += '<a onclick=\'drawEmptyFormPlace();\' class="ui-btn ui-corner-all ui-icon-recycle ui-btn-icon-left">New</a>';
			html += '<a onclick=\'updateDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=place")\' class="ui-btn ui-corner-all ui-icon-check ui-btn-icon-left">Update</a>';
	   html += '</div>';
	   html += '</li></ul>';	   
	   $(html).appendTo("#places_table").trigger("create");
}

function drawEmptyFormPolyline() {
	var html = '<ul data-role="listview" data-inset="true">';
	html += '<li data-role="list-divider">New geographical location</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="newPoly">Path ID:</label>';
		html += '<input type="text" name="bundle_id" id="newPoly" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPoly">order:</label>';
		html += '<input type="text" name="order" id="newPoly" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPoly">Longitude:</label>';
		html += '<input type="text" name="position_lng" id="newPoly" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="newPoly">Latitude:</label>';
		html += '<input type="text" name="position_lat" id="newPoly" value="" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<div data-role="controlgroup" data-type="horizontal">';
		    html += '<a onclick=\'deleteDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=polyline")\' class="ui-btn ui-corner-all ui-icon-delete ui-btn-icon-left">Delete</a>';
			html += '<a onclick=\'drawEmptyFormPolyline();\' class="ui-btn ui-corner-all ui-icon-recycle ui-btn-icon-left">New</a>';
			html += '<a onclick=\'updateDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=polyline")\' class="ui-btn ui-corner-all ui-icon-check ui-btn-icon-left">Update</a>';
	   html += '</div>';
	   html += '</li></ul>';	   
	   $(html).appendTo("#polylines_table").trigger("create");
}

function drawFormPolyline(polylineId, value, pathId) {
	var html = '<ul data-role="listview" data-inset="true">';
	html += '<li data-role="list-divider">' + 'Path ' + pathId + ':' + value.lng + ' - ' + value.lat + '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="polyline' + polylineId + '">Path ID:</label>';
		html += '<input type="text" name="bundle_id" id="polyline' + polylineId + '" value="' + pathId + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="polyline' + polylineId + '">order:</label>';
		html += '<input type="text" name="order" id="polyline' + polylineId + '" value="' + polylineId + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="polyline' + polylineId + '">Longitude:</label>';
		html += '<input type="text" name="position_lng" id="polyline' + polylineId + '" value="' + value.lng + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="polyline' + polylineId + '">Latitude:</label>';
		html += '<input type="text" name="position_lat" id="polyline' + polylineId + '" value="' + value.lat + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<div data-role="controlgroup" data-type="horizontal">';
		    html += '<a onclick=\'deleteDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=polyline")\' class="ui-btn ui-corner-all ui-icon-delete ui-btn-icon-left">Delete</a>';
			html += '<a onclick=\'drawEmptyFormPolyline();\' class="ui-btn ui-corner-all ui-icon-recycle ui-btn-icon-left">New</a>';
			html += '<a onclick=\'updateDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=polyline")\' class="ui-btn ui-corner-all ui-icon-check ui-btn-icon-left">Update</a>';
	   html += '</div>';
	   html += '</li></ul>';	   
	   $(html).appendTo("#polylines_table").trigger("create");
}

function drawFormMedia(mediaId, value, placeName) {
	var html = '<ul data-role="listview" data-inset="true">';
	html += '<li data-role="list-divider">' + placeName + ': ' + value.name + '</li>';
	   html += '<li class="ui-field-contain">';
		html += '<label for="media' + mediaId + '">Name:</label>';
		html += '<input type="text" name="name" id="media' + mediaId + '" value="' + value.name + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="media' + mediaId + '">Type:</label>';
		html += '<input type="text" name="order" id="type' + mediaId + '" value="' + value.type + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="media' + mediaId + '">Image:</label>';
		html += '<input type="text" name="image" id="media' + mediaId + '" value="' + media.image + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<label for="media' + mediaId + '">contents:</label>';
		html += '<input type="text" name="contents" id="media' + mediaId + '" value="' + value.contents + '" data-clear-btn="true">';
	   html += '</li>';
	   html += '<li class="ui-field-contain">';	
		html += '<div data-role="controlgroup" data-type="horizontal">';
		    html += '<a onclick=\'deleteDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=media")\' class="ui-btn ui-corner-all ui-icon-delete ui-btn-icon-left">Delete</a>';
			html += '<a onclick=\'drawEmptyFormMedia();\' class="ui-btn ui-corner-all ui-icon-recycle ui-btn-icon-left">New</a>';
			html += '<a onclick=\'updateDatabase($(this).parent().parent().parent().parent().find("input").serialize() + "&type=media")\' class="ui-btn ui-corner-all ui-icon-check ui-btn-icon-left">Update</a>';
	   html += '</div>';
	   html += '</li></ul>';	   
	   $(html).appendTo("#medias_table").trigger("create");
}

function drawForms() {
	$("#bundles_table").html("");
	$("#paths_table").html("");
	$("#places_table").html("");
	$("#polylines_table").html("");
	$("#medias_table").html("");
	jQuery.each(ajaxResponse, function(bundleId, value)
   {
	   drawFormBundle(bundleId, value);
	   jQuery.each(value.paths, function(pathId, path)
		{
		   drawFormPath(pathId, path, value.id);
		   jQuery.each(path.places, function(placeId, place)
			{
				drawFormPlace(placeId, place, path.id);
				jQuery.each(place.position, function(positionId, pos)
				{
				
				});
				jQuery.each(place.media, function(mediaId, med)
				{
					
				});
			});	
			jQuery.each(path.polyline, function(polylineId, pl)
			{
				drawFormPolyline(polylineId, pl, path.id);
			});						
		});
   });
}

function edit() {
	if(username === 'user') {
		$( "#popupLogin" ).popup( "open" );
	}
	
	$( "#editDiv" ).toggle();
}



function updateDatabase(params) {
	$.ajax({
    url: "https://hiker.serwr.com/api.php/update",
    type: 'POST',
    data: params + "&username=" + username + "&password=" + password,
    success: function (data) {
        console.log(data);
    }
});
}

function deleteDatabase(params) {
	$.ajax({
    url: "https://hiker.serwr.com/api.php/delete",
    type: 'POST',
    data: params + "&username=" + username + "&password=" + password,
    success: function (data) {
        console.log(data);
    }
});
}

function authenticator() {
	username = $( "#un" ).val();
	password = $( "#pw" ).val();
}
</script>
</head>
<body onload="init();">
<div id="editDiv">
<div data-role="popup" id="popupLogin" data-theme="a" class="ui-corner-all">
    <form>
        <div style="padding:10px 20px;">
            <h3>Please sign in</h3>
            <label for="un" class="ui-hidden-accessible">Username:</label>
            <input type="text" name="user" id="un" value="" placeholder="username" data-theme="a">
            <label for="pw" class="ui-hidden-accessible">Password:</label>
            <input type="password" name="pass" id="pw" value="" placeholder="password" data-theme="a">
            <button onclick="authenticator()" class="ui-btn ui-corner-all ui-shadow ui-btn-b ui-btn-icon-left ui-icon-check">Sign in</button>
        </div>
    </form>
</div>
<div class="ui-corner-all custom-corners">
  <div class="ui-bar ui-bar-a">
    <h3>Heading</h3>
  </div>
  <div class="ui-body ui-body-a">
	<div data-role="tabs" id="tabs">
	  <div data-role="navbar">
	    <ul>
	      <li><a href="#bundles_tab"  class="ui-btn-active" data-ajax="false">Bundles</a></li>
	      <li><a href="#path_tab" data-ajax="false">Paths</a></li>
		  <li><a href="#place_tab" data-ajax="false">Places</a></li>
	      <li><a href="#polyline_tab" data-ajax="false">Polylines</a></li>
		  <li><a href="#media_tab" data-ajax="false">Media</a></li>
	    </ul>
	  </div>
	  <div id="bundles_tab" class="ui-body-d ui-content">
	    <h3>Bundles</h3>
			<div id="bundles_table">
			
			</div>
	  </div>
	  <div id="path_tab">
	    <h3>Paths</h3>
			<div id="paths_table">
			
			</div>
	  </div>
	  <div id="place_tab">
	    <h3>Places</h3>
			<div id="places_table">
			
			</div>
	  </div>
	  <div id="polyline_tab">
	    <h3>Polylines</h3>
			<div id="polylines_table">
			
			</div>
	  </div>
		<div id="media_tab">
	    <h3>Medias</h3>
			<div id="medias_table">
			
			</div>
	  </div>
	</div>
  </div>
</div>

</div>
<button id="edit" onclick="edit()" class="ui-btn ui-icon-edit ui-btn-icon-notext ui-corner-all">edit</button>
<canvas id="canvas" width="1700" height="1000"></canvas>

