<?php

include '../config.php';

$filePath = $IS_LOCAL ? "../" : "../../info/";

include $filePath. "review.php";

if(!isset($_GET['id']) || !isset($_GET['course']) || !isset($_GET['review'])) {
	header("Location: " . $ROOT_SITE);
}
$conn = new mysqli($cfg['db_host'], $cfg['db_user'], $cfg['db_password'], $cfg['db_name']);
if ($conn->connect_error) {
	die("Database connection failed: " . $conn->connect_error);
}
include $filePath. 'check_auth.php';
include $filePath. 'profile_picture.php';

$course = $_GET['course'];
$reviewId = $_GET['review'];
$contains = false;

foreach (getReviewTargets($conn, $_SESSION['info']['user_id'], $course, $reviewId) as $tar) {
	if($tar['id'] == $_GET['id']) {
		$contains = true;
	}
}

if (!$contains) {
	header("Location: " . $ROOT_SITE);
	exit;
}
$script = getScript($conn,$_GET['id'], $course, $reviewId);
$target = array(
	"id" => $_GET['id'],
	"name" => getName($conn,$_GET['id']),
	"code" => is_null($script["script"]) ? getCode($conn,$_GET['id'], $course, $reviewId) : $script
);

include $filePath. "header.php";
?>
<body>
    <script type="text/javascript" src="js/jquery.ns-autogrow.js"></script>
    <script type="text/javascript">
	    $(document).ready(function() {
	    	$('textarea').css("overflow", "hidden").autogrow();
	    });
	</script>

	<header class="cd-main-header">
		<a class="cd-logo"><img src="img/cd-logo.svg" alt="Logo"></a>

		<a href="#0" class="cd-nav-trigger"><span></span></a>

		<nav class="cd-nav">
			<ul class="cd-top-nav">
				<li><a href="scripts">Scripts</a></li>
				<li class="has-children account">
					<a href="#0"><?php
				  		$path = getPicName($_SESSION["info"]["user_id"], $IS_LOCAL, $ROOT_SITE);
				  		echo '<img src="'.$path.'" alt="Avatar">'; ?>
						Account
					</a>
					<ul>
						<li><a href="settings.php">Einstellungen</a></li>
						<li><a href="logout.php">Logout</a></li>
					</ul>
				</li>
			</ul>
		</nav>
	</header> <!-- .cd-main-header -->

	<div class="container-fluid">
		<div class="row">
	    <div class="sidebar cd-main-content">
			<nav class="cd-side-nav">
				<ul class="nav nav-sidebar">
					<li class="cd-label">Main</li>
					<li class="has-children overview">
						<a href="index.php"><i class="fa fa-users" aria-hidden="true"></i>Kurse</a>
						<ul>
							<?php
								$courses = getCoursesOfUser($conn, $_SESSION['info']['user_id']);
								foreach ($courses as $course) {
									echo '<li><a href="?course='.$course.'">'.getCourseName($conn, $course).'</a></li>';
								}
							?>
						</ul>
					</li>
					<li>
						<a href="signup.php"><i class="fa fa-user-plus" aria-hidden="true"></i>In Kurs eintragen</a>
					</li>
				</ul>
			</nav>
		</div>
		    <div class="right-col">
				<div class="row">
					<div class="col-12">

						<ol class="breadcrumb">
							<li class="breadcrumb-item"><a href="<?php echo $ROOT_SITE; ?>">Startseite</a></li>
							<li class="breadcrumb-item">Kurs <a href="<?php echo $ROOT_SITE . "?course=$course"; ?>">
								<?php
								echo getCourseName($conn, $course);
								?>
								</a>
							</li>
							<li class="breadcrumb-item">Review <a href="<?php echo $ROOT_SITE . "?course=$course&review=$reviewId"; ?>">
								<?php
								echo getReviewNameForID($conn, $reviewId);
								?>
								</a>
								</h2>
							</li>
							<li class="breadcrumb-item">Review <a href="<?php echo "$_SERVER[REQUEST_URI]"; ?>">
								<?php
								echo "Für " . $target['name'];
								?>
								</a>
								</h2>
							</li>
						</ol>
					</div>
				</div>
		    	<div class="row equal">
		    		<div class="col-md-6">
		    			<div class="admin-cart">
					  		<h1>Review für <span class="red-text lighten-2"><?php echo $target["name"]?></span> verfassen</h1>
					  		<span>
					  			Hier kannst du dein Review für den angegeben Benutzer bearbeiten oder verfassen.<br>
					  			Bitte halte dich an die Beschreibung der (Kritik-)Punkte und <i>bewerte ernsthaft</i>.
					  		</span>
		    			</div>
		    		</div>
		    		<div class="col-md-6">
		    			<div class="admin-cart">
							<h3>Link zum Code</h3>
							<?php
							if(is_null($target["code"]) or empty($target["code"])) {
								echo "<span class=\"red-text darken-4\">".$target["name"]." hat noch keinen Link angegeben</span>";
				    	    } else {
				    	    	if(isset($target["code"]["script"])) {
									echo "<span>Link zum Programm von ".$target["name"].": <a class=\"red-text darken-4\" href=\"" . $ROOT_SITE . "script/?id=" . $target["code"]["script_id"] ."\" target=\"_blank\">Hier klicken</a></span>";
				    	    	} else {
									echo "<span>Link zum Code von ".$target["name"].": <a class=\"red-text darken-4\" href=\"".$target["code"]."\" target=\"_blank\">Hier klicken</a></span>";
				    	    	}
				    	    }
							?>
		    			</div>
		    		</div>
		    	</div>
		    	<div class="row">
		    		<div class="col-md-12">
		    			<div class="admin-cart">
		    				<h3>Review verfassen</h3>
						  	<?php
							if(isset($_POST['save-review'])) {
								$i = $target['id'];
								$a = $_SESSION['info']['user_id'];
								$review = array();
								$idx = 0;
								$jdx = 0;

								$json = json_decode(getReviewSchemeForID($conn, $course, $reviewId), JSON_UNESCAPED_UNICODE);
								foreach ($_POST as $name => $value) {
									if($name == "save-review" || startsWith($name, "comment")) {
										continue;
									}
									$n = str_replace("points_", "", $name);
									$idx = ((int) explode("_", $n)[0]);
									$jdx = ((int) explode("_", $n)[1]);
									if(!isset($review[$idx])) {
										$review[] = array();
									}
									if(!isset($review[$idx]['reviews'])) {
										$review[$idx]['reviews'] = array();
									}
									if(!isset($review[$idx]['reviews'][$jdx])) {
										$review[$idx]['reviews'][] = array();
									}
									$review[$idx]['comment'] = htmlspecialchars($_POST['comment_'.$idx]); // use the special chars in order to prevent html injection
									$v = ((int)$value);
									$v = max($v, 0);
									$v = min($v, $json[$idx]['categories'][$jdx]['max_points']);
									$review[$idx]['reviews'][$jdx] = array("points" => ($v));
								}
								setReview($conn, $i, $a, $course, json_encode($review), $reviewId);
								?>
								<span class="alert alert-success">Vielen Dank, dass du deine Bewertung abgegeben hast!</span>
								<?php
							}
						  	?>
						  	<form action="" method="post" class="form-horizontal">
								<?php
								$rv = json_decode(
									getReview($conn, $target["id"], $_SESSION['info']['user_id'], $course, $reviewId)['review'],
									JSON_UNESCAPED_UNICODE);
								$review = Review::fromJSON(getReviewNameForID($conn, $reviewId), getReviewSchemeForID($conn, $course, $reviewId) );
								$itemcount = 0;

								foreach ($review->objects as $object) {
									$idx = 0;

									echo '<div class="sect"><p>'.$object->name.'</p>';

									foreach ($object->categories as $cat) {
										echo '<div class="cat"><span class="desc">'	.$cat->description.	'</span>';
										echo '<div class="points"><input type="number" min="0" max="' .$cat->max_points. '" ';
										echo 'value="';
										$currentValue = $rv[$itemcount]['reviews'][$idx]['points'];
										if(!is_numeric($currentValue)) $currentValue = 0;
										echo $currentValue;
										echo '" name="points_'.$itemcount.'_'.$idx.'">';
										echo '<span> / ' .$cat->max_points. '</span></div></div>';
										$idx = $idx + 1;
									}
									echo '<div class="cat"><span>Kommentar ' .$object->name. '</span>';
									echo '<textarea class="comment-textarea" name="comment_'.$itemcount.'" ';
									echo 'placeholder="Kommentar">'.$rv[$itemcount]['comment'].'</textarea></div></div>';
									$itemcount = $itemcount + 1;
								}
								?>
								<br>
								<input id="save-review-button" type="submit" value="Bewertung speichern" class="btn btn-primary centered" name="save-review">
							</form>
		    			</div>
		    		</div>
		    	</div>  		
			</div>
		</div>
	</div>
</body>
</html>
<?php
$conn->close();
?>