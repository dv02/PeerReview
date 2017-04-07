<?php 

include '../check_auth.php';
include '../config.php';
include "../review.php";

if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] > 1) {
	http_response_code(401);
	exit;
}

$conn = new mysqli($cfg['db_host'], $cfg['db_user'], $cfg['db_password'], $cfg['db_name']);

if ($conn->connect_error) {
	die("Database connection failed: " . $conn->connect_error);
}


$section = 0;
$course = -1;

$objs = array();
$reviewName = "";
foreach ($_POST as $name => $value) {
	if($name === "course") {
		$course = ((int)$value);
		continue;
	} elseif ($name === "name") {
		$reviewName = $value;
		continue;
	}

	$d = explode("_", $name);
	$section = ((int) $d[1]);

	if (startsWith($name, "name")) {
		$categories = array();
		for ($i=0; $i < sizeof($_POST); $i++) {

			if(!isset( $_POST['cat_' . $section . "_" . $i ."_0"] )) {
				break;
			}
			$desc = ((string) $_POST['cat_' . $section . "_" . $i ."_0"]);
			$points = ((int) $_POST['cat_' . $section . "_" . $i ."_1"]);
			
			$cat = new ReviewCategory($desc, $points);
			$categories[] = $cat; 	// add the category to the array of categories (per section)
		}
		$obj = new ReviewObject($categories, $value);
		$objs[] = $obj;

	}

}

if($course === -1) {
	http_response_code(400);
}

$scheme = ReviewScheme::fromJSON( getReviewScheme($conn, $course));
$scheme->addReview( new Review($reviewName, $objs) );
setReviewScheme( $conn, $course, json_encode($scheme));

?>