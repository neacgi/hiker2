<?php
 
include 'config.php';
 
// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = serialize(json_decode(file_get_contents('php://input'),true));
 
// connect to the mysql database
$link = mysqli_connect($mysql_url, $mysql_username, $mysql_password, $mysql_username);
mysqli_set_charset($link,'utf8');
 
// retrieve the table and key from the path
$table = preg_replace('/[^a-z0-9_]+/i','',array_shift($request));
$key = array_shift($request)+0;
 
if($table == "all") 
{
	$all = array();
	$result = mysqli_query($link,"SELECT * FROM  `hiker_bundle` ");
	$i = 0;
	while($bundle = $result->fetch_row())
	{
		$all[$bundle[0]]=array("id"=>$bundle[0], "name"=>$bundle[1], "image"=>$bundle[2], "info"=>$bundle[3], "paths"=>array());
		$path_result = mysqli_query($link,"SELECT * FROM hiker_path");
		$pathId = 0;
		while($path = $path_result->fetch_row())
		{
			$all[$bundle[0]]['paths'][$pathId]=array("id"=>$path[0], "name"=>$path[2], "info"=>$path[3], "length"=>$path[4], "duration"=>$path[5], "image"=>$path[6], "places"=>array(), "polyline"=>array());
			$poly_result = mysqli_query($link,"SELECT * FROM hiker_polyline");
			$polyId = 0;
			while($poly = $poly_result->fetch_row())
			{
				$all[$bundle[0]]['paths'][$pathId]['polyline'][$polyId]=array("lng"=>$poly[2], "lat"=>$poly[3]);
				$polyId++;
			}
			$place_result = mysqli_query($link,"SELECT * FROM hiker_place WHERE path_id = " . $path[0]);
			$placeId = 0;
			while($place = $place_result->fetch_row())
			{
				$all[$bundle[0]]['paths'][$pathId]['places'][$placeId]=array("name"=>$place[1], "info"=>$place[2], "radius"=>$place[3], "position"=>array("lng"=>$place[5], "lat"=>$place[4]), "media"=>array());
				$placeId++;
			}
			$pathId++;
		}
	}
		echo json_encode(array_values($all));
}
else if($table == "update")
{
 
//$result = mysqli_query($link,$sql);
//print_r($data);
$username = mysqli_real_escape_string($link, $_POST["username"]);
$password = mysqli_real_escape_string($link, $_POST["password"]);
if(validateUser($username, $password)) {
	if($_POST["type"] == "bundle") {
		$id = mysqli_real_escape_string($link, $_POST["id"]);
		$name = mysqli_real_escape_string($link, $_POST["name"]);
		$image = mysqli_real_escape_string($link, $_POST["image"]);
		$info = mysqli_real_escape_string($link, $_POST["info"]);
		
		$result = mysqli_query($link,"SELECT * FROM  `hiker_bundle` WHERE id LIKE('" . $id . "')");
		if($results->num_rows === 0)
		{
			if(mysqli_query($link,"INSERT IGNORE INTO `hiker_bundle`(`id`, `name`, `image`, `info`) VALUES (" . $id . ", '" . $name . "', '" . $image . "', '" . $info . "')")) {
				echo "Added bundle " . $name . " sucessfully";
			} else {
				echo "Failed to create new bundle, contact administrator!" . mysqli_error($link);
			}
		} else {
			if(mysqli_query($link,"UPDATE IGNORE`hiker_bundle` SET `id`=" . $id . ",`name`='" . $name . "',`image`='" . $image . "',`info`='" . $info . "' WHERE id LIKE('" . $id . "')")) {
				echo "Updated bundle " . $name . " sucessfully";
			} else {
				echo "Failed to create new bundle, contact administrator!" . mysqli_error($link);
			}
		}
	} else if($_POST["type"] == "path") {
		$id = mysqli_real_escape_string($link, $_POST["id"]);
		$bundle_id = mysqli_real_escape_string($link, $_POST["bundle_id"]);
		$name = mysqli_real_escape_string($link, $_POST["name"]);
		$info = mysqli_real_escape_string($link, $_POST["info"]);
		$length = mysqli_real_escape_string($link, $_POST["length"]);
		$duration = mysqli_real_escape_string($link, $_POST["duration"]);
		$image = mysqli_real_escape_string($link, $_POST["image"]);
		
		$result = mysqli_query($link,"SELECT * FROM  `hiker_path` WHERE id LIKE('" . $id . "')");
		if($results->num_rows === 0)
		{
			if(mysqli_query($link,"INSERT IGNORE INTO `hiker_path`(`id`, `bundle_id`, `name`, `info`, `length`, `duration`, `image`) VALUES ('" . $id . "', '" . $bundle_id . "', '" . $name . "', '" . $info . "', '" . $length . "', '" . $duration . "','" . $image . "')")) {
				echo "Added path " . $name . " sucessfully";
			} else {
				echo "Failed to create new path, contact administrator!" . mysqli_error($link);
			}
		} else {
			if(mysqli_query($link,"UPDATE IGNORE`hiker_path` SET `id`='" . $id . "',`bundle_id`='" . $bundle_id . "',`name`='" . $name . "',`info`='" . $info . "',`length`='" . $length . "',`duration`='" . $duration . "',`image`='" . $image . "' WHERE id LIKE('" . $id . "')")) {
				echo "Updated path " . $name . " sucessfully";
			} else {
				echo "Failed to UPDATE IGNOREpath, contact administrator!" . mysqli_error($link);
			}
		}
	} else if($_POST["type"] == "place") {
		$path_id = mysqli_real_escape_string($link, $_POST["path_id"]);
		$name = mysqli_real_escape_string($link, $_POST["name"]);
		$info = mysqli_real_escape_string($link, $_POST["info"]);
		$radius = mysqli_real_escape_string($link, $_POST["radius"]);
		$duration = mysqli_real_escape_string($link, $_POST["duration"]);
		$position_lng = mysqli_real_escape_string($link, $_POST["position_lng"]);
		$position_lat = mysqli_real_escape_string($link, $_POST["position_lat"]);
		
		$result = mysqli_query($link,"SELECT * FROM  `hiker_place` WHERE name LIKE('" . $name . "')");
		if($results->num_rows === 0)
		{
			if(mysqli_query($link,"INSERT IGNORE INTO `hiker_place`(`path_id`, `name`, `info`, `radius`, `position_lng`, `position_lat`) VALUES ('" . $path_id . "','" . $name . "','" . $info . "','" . $radius . "','" . $position_lng . "','" . $position_lat . "')")) {
				echo "Added place " . $result . " sucessfully";
			} else {
				echo "Failed to add new place, contact administrator!" . mysqli_error($link);
			}
		} else {
			if(mysqli_query($link,"UPDATE IGNORE`hiker_place` SET `path_id`=" . $path_id . ",`name`='" . $name . "',`info`='" . $info . "',`radius`='" . $radius . "',`position_lng`='" . $position_lng . "',`position_lat`='" . $position_lat . "' WHERE name LIKE('" . $name . "')")) {
				echo "Updated place " . $name . " sucessfully";
			} else {
				echo "Failed to UPDATE IGNOREplace, contact administrator!" . mysqli_error($link);
			}
		}
	} else if($_POST["type"] == "polyline") {
		$path_id = mysqli_real_escape_string($link, $_POST["path_id"]);
		$order = mysqli_real_escape_string($link, $_POST["order"]);
		$position_lng = mysqli_real_escape_string($link, $_POST["position_lng"]);
		$position_lat = mysqli_real_escape_string($link, $_POST["position_lat"]);
		
		$result = mysqli_query($link,"SELECT * FROM `hiker_polyline` WHERE path_id LIKE ('" . $path_id . "') AND order = " . $order);
		if($results->num_rows === 0)
		{
			if(mysqli_query($link,"INSERT IGNORE INTO `hiker_polyline`(`path_id`, `order`, `position_lng`, `position_lat`) VALUES ('" . $path_id . "','" . $order . "','" . $position_lng . "','" . $position_lat . "')")) {
				echo "Updated path " . $result . " sucessfully";
			} else {
				echo "Failed to update, contact administrator!" . mysqli_error($link);
			}
		} else {
			if(mysqli_query($link,"UPDATE IGNORE`hiker_polyline` SET `path_id`='" . $path_id . "',`order`='" . $path_id . "',`position_lng`='" . $path_id . "',`position_lat`='" . $path_id . "' WHERE path_id LIKE ('" . $path_id . "') AND order = " . $order)) {
				echo "Updated bundle " . $name . " sucessfully";
			} else {
				echo "Failed to create new bundle, contact administrator!" . mysqli_error($link);
			}
		}
	} else if($_POST["type"] == "media") {
		$id = mysqli_real_escape_string($link, $_POST["id"]);
		$path_id = mysqli_real_escape_string($link, $_POST["path_id"]);
		$name = mysqli_real_escape_string($link, $_POST["name"]);
		$info = mysqli_real_escape_string($link, $_POST["info"]);
		$length = mysqli_real_escape_string($link, $_POST["length"]);
		$duration = mysqli_real_escape_string($link, $_POST["duration"]);
		$image = mysqli_real_escape_string($link, $_POST["image"]);
		
		$result = mysqli_query($link,"SELECT * FROM  `hiker_bundle` WHERE id LIKE('" . $id . "')");
		if($results->num_rows === 0)
		{
			if(mysqli_query($link,"INSERT IGNORE INTO `hiker_place`(`path_id`, `name`, `info`, `radius`, `position_lng`, `position_lat`) VALUES ([value-1],[value-2],[value-3],[value-4],[value-5],[value-6])")) {
				echo "Updated path " . $result . " sucessfully";
			} else {
				echo "Failed to update, contact administrator!" . mysqli_error($link);
			}
		} else {
			if(mysqli_query($link,"UPDATE IGNORE`hiker_bundle` SET `id`=" . $id . ",`name`='" . $name . "',`image`='" . $image . "',`info`='" . $info . "' WHERE id LIKE('" . $id . "')")) {
				echo "Updated bundle " . $name . " sucessfully";
			} else {
				echo "Failed to create new bundle, contact administrator!" . mysqli_error($link);
			}
		}
	}
} else {
	echo "We do not recognize your authority!";
}
	
}
// create SQL based on HTTP method
/*
switch ($method) {
  case 'GET':
    $sql = "SELECT value FROM $table".($key?" WHERE id=$key":''); break;
  case 'PUT':
    $sql = "UPDATE IGNORE$table SET value=$input WHERE id=$key"; break;
  case 'POST':
    $sql = "INSERT IGNORE INTO $table VALUES(NULL, $input)"; break;
  case 'DELETE':
    $sql = "DELETE $table WHERE id=$key"; break;
}
 */
// excecute SQL statement
//$result = mysqli_query($link,$sql);
 
// die if SQL statement failed

 
// print results, INSERT IGNORE id or affected row count
/*
if ($method == 'GET') {
	foreach($result->fetch_array(MYSQLI_NUM) AS $row)
	{
		echo $row;
	}
} elseif ($method == 'POST') {
  echo mysqli_insert_id($link);
} else {
  echo mysqli_affected_rows($link);
}
} 
*/
// close mysql connection
mysqli_close($link);

function validateUser($username, $password) {
	// connect to the mysql database
	$validate = mysqli_connect($mysql_url, $mysql_username, $mysql_password, $mysql_username);
	mysqli_set_charset($validate,'utf8');
	$result = mysqli_query($validate,"SELECT * FROM `hiker_user` WHERE username LIKE ('" . $username . "')");
	mysqli_close($validate);
	while($userRow = $result->fetch_row()) {
		// userRow[3] is password salt
		if(password_verify($password . $userRow[3], $userRow[4]))
		{
			return true;
		}
	}
	return false;
}