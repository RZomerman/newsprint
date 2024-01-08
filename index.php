<?php
// Don't cache this page.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

date_default_timezone_set('Etc/GMT+4');  // adjust for server timezone

// Let PHP create the archive folder
 $dir = 'archive';

 // create new directory with 744 permissions if it does not exist yet
 // owner will be the user/group the PHP script is run under
 if ( !file_exists($dir) ) {
     	echo "create dir";
	mkdir ($dir, 0744);
 }

// path to paper is base url  +day of month + state + paper + .pdf
// https://cdn.freedomforum.org/dfp/pdf16/MA_BG.pdf

$news = array();
//$paper['prefix']="WSJ";$paper['style']="width:98%;margin:-70px 0px 0px -15px";array_push($news,$paper); // WSJ -broken 2021
$paper['prefix']="DC_WP";$paper['style']="width:108%;margin:-5% -5% 0px -5%";array_push($news,$paper);  // Washington Post
$paper['prefix']="MA_BG";$paper['style']="width:98%;margin:5px 10px 0px 8px";array_push($news,$paper);	  // Boston Globe
$paper['prefix']="NY_NYT";$paper['style']="width:99%;margin:-28px 14px 0px 3px";array_push($news,$paper); // New York Times
$paper['prefix']="CA_LAT";$paper['style']="width:94%;margin:-2% 0px 0px 0px";array_push($news,$paper);  // L.A. Times
$paper['prefix']="CAN_TS";$paper['style']="width:90%;margin:-70px 0px 0px 0px";array_push($news,$paper);// Toronto Star
$paper['prefix']="CA_SFC";$paper['style']="width:96%;margin:-20px 0px 0px 0px";array_push($news,$paper);  // SF Chronical

$paper['prefix']="NET_DV";$paper['style']="width:96%;margin:-20px 0px 0px 0px";array_push($news,$paper);  // De Volkskrant
$paper['prefix']="NET_AD2";$paper['style']="width:96%;margin:-20px 0px 0px 0px";array_push($news,$paper);  // AD
$paper['prefix']="PHI_BW";$paper['style']="width:96%;margin:-70px 0px 0px 0px";array_push($news,$paper);  // Business Work Phillipines
$paper['prefix']="NET_HP";$paper['style']="width:96%;margin:-20px 0px 0px 0px";array_push($news,$paper);  // Het Parool

$paper['prefix']="UAE_TN";$paper['style']="width:96%;margin:-20px 0px 0px 0px";array_push($news,$paper);  // The National
$paper['prefix']="UAE_GN";$paper['style']="width:96%;margin:-20px 0px 0px 0px";array_push($news,$paper);  // Gulf News


$maxPapers = count($news) -1;

// Loop a counter without a DB.
// allows us to get a different newspaper
// each load by asking for the counter
function getCounter() {
	global $maxPapers;
	$fp = fopen("counter.txt", "r");
	if ($fp) {
	   $x= intval(fread($fp,1024));
	   fclose($fp);
	} else {
	   $x = 0;
	}
	if ($x > $maxPapers) {
		$x = 0;
	}
	if (!empty($_REQUEST['index'])) { // Override the counter if there
	  $x = $_REQUEST['index'];	  // is a URL parameter for index
	}
	return($x);
}
function incrementCounter(int $counter){
	// increment the paper for next time
	$counter++;
	$fp = fopen("counter.txt", "w");
	fwrite($fp, $counter);
	fclose($fp);
}

// fetch a paper and cache in as a JPG. Return the path to the JPG if we found it.
// We can pass in an offset in days to get yesterday or two days ago
function fetchPaper($prefix, $offset=0){
	$pathToPdf = "https://cdn.freedomforum.org/dfp/pdf" . date('j',strtotime("-" . $offset . " days")) . "/" . $prefix . ".pdf";
	$pdffile = "archive/" . $prefix . "_" . date('Ymd',strtotime("-" . $offset . " days")) . ".pdf";
	$pngfile = "archive/" . $prefix . "_" . date('Ymd',strtotime("-" . $offset . " days")) . ".png";
	$webfile = "/image.png";
	$rootpath = getcwd() . "/";
	// check if a jpg has already been created
	// if not we start checking for the PDF and converting
	if (!file_exists($pngfile)){
		$file_headers = @get_headers($pathToPdf);
		if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
			$exists = false;
		}
		else {
			$ch = curl_init($pathToPdf);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$data = curl_exec($ch);
			curl_close($ch);
			$exists = true;
			$result = file_put_contents($pdffile, $data);
		}
		if ($exists) {
			$command =  "convert -density 300 -background white -alpha remove '" . $rootpath . $pdffile .
				   "' -colorspace Gray -resize 825 '" . $rootpath . $pngfile . "'";
			exec($command, $output, $response);

		}
	} else {
		 $exists = true;
	}
	if ($exists) {
		return $pngfile;
	} else {
		return false;
	}
}
$currentIndex = getCounter();
if (isset($_REQUEST['index'])) {
	$currentIndex = $_REQUEST['index'];
}


$imageresult = fetchPaper($news[$currentIndex]['prefix'],0);  // Fetch today

if (empty($imageresult)) {
	$imageresult =  fetchPaper($news[$currentIndex]['prefix'],1); // yesterday
}
if (empty($imageresult)) {
	$imageresult =  fetchPaper($news[$currentIndex]['prefix'],2); // twesterday
}

echo $imageresult;

			// delete old image file (removing symbolic link)
			$command =  "rm archive/image.png";
			// echo $command;
			exec($command, $output, $response);

			// create symbolic link to image.png for InkPlate10
			$command =  "cp $imageresult archive/image.png";
			// echo $command;
			exec($command, $output, $response);

			// rotate the image to fit InkPlate 10 vertical
			$command =  "convert archive/image.png -rotate 270 archive/image.png";
			// echo $command;
			exec($command, $output, $response);


			// crop the image to fit InkPlate 10 vertical/horizontal
			$command =  "convert archive/image.png -crop 1200x+0+0 archive/image.png";
			// echo $command;
			exec($command, $output, $response);



			




?>
<!DOCTYPE html>
<html>
<head>
<style>
  body   { text-align:center; }
  .paper {
	background-color:white;
 	<?php echo $news[$currentIndex]['style'] ?>
  }
</style>
</head>
<body>
<?php if (strlen($imageresult) < 1) {
   echo "Newspaper File Not Found. " . $imageresult. " Will keep looking. Checking again in another hour.";
} else {
   echo "<img src='" . $imageresult . "' class='paper' >";
 }
?>
</div>
</body>
</html>
<?php
incrementCounter($currentIndex);
?>
