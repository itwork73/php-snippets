<?

// Экспортирует данные из БД Wordpress в файлы
// используя пагинатор запросов

// dumpers
function d($array, $varDump = false){
    if ($varDump) {
        echo '<pre>';var_dump($array);echo '</pre>';
    } else {
        echo '<pre>';print_r($array);echo '</pre>';
    }
}

function dd($array, $varDump = false){
    if ($varDump) {
        echo '<pre>';var_dump($array);echo '</pre>';
    } else {
        echo '<pre>';print_r($array);echo '</pre>';
    }
    die();
}

global $wpdb;

$langs = ['ru','en','gr','fr','it','es'];
$output = [];

// limits
$limitVolume = 200;
$limitFrom = 0;
if(!empty($_REQUEST["limit"])) { $limitFrom = (int)$_REQUEST["limit"]; }
$limitTo = $limitFrom + $limitVolume;


//$query = "SELECT ID FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT 14000, 100";
$query = "SELECT ID FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT ".$limitFrom.", " . $limitVolume;
$rows = $wpdb->get_results($query);
foreach($rows as $row){

	// post data
	$id = $row->ID;
	$post = get_post($id, "ARRAY_A");

	// category data
	$catsRaw = get_the_category($id);
	$cats = [];
	foreach($catsRaw as $catItem){
		$cats[] = [
			"ID"=>$catItem->term_id,
			"NAME"=>$catItem->name,
			"CODE"=>$catItem->slug,
		];
		
	}

	foreach ($langs as $thisLang){

		if(!qtranxf_isAvailableIn($id, $thisLang)) { continue; }

		// post detail
		$postDetail = qtrans_use($thisLang, $post["post_content"], false);

		// post detail has image
		$imagesID = [];
		preg_match('/\[gallery.*ids=.(.*).\]/', $postDetail, $gallery);
		
		if(!empty($gallery)){
			$imagesID = explode(",", $gallery[1]);
			$postDetail = str_replace($gallery[0], "", $postDetail);
		}


		// images id to uri
		$imagesURI = [];
		foreach($imagesID as $thisImage){
			$imagesURI[] = wp_get_attachment_image_url($thisImage, 'full');
		}


		$output[] = [
			"EID"=>$id,
			"LANG"=>$thisLang,
			"NAME"=>qtrans_use($thisLang, $post["post_title"], false),
			"DATE"=>$post["post_date"],
			"PREVIEW"=>qtrans_use($thisLang, $post["post_excerpt"], false),
			"DETAIL"=>$postDetail,
			"CATEGORY"=>$cats,
			"IMAGES"=>$imagesURI
		];

	}







}


// store to file
$fileName = $_SERVER['DOCUMENT_ROOT'] . '/datastore/' . 'data-' . $limitFrom . '-' . $limitTo . '.bin';
file_put_contents($fileName, serialize($output));

// view
$originExe = "https://##################/?dev=Y&limit=" . $limitTo;
$scriptString = "setTimeout(function(){ location.href = '".$originExe."'; }, 500);";


?>

<?if(!empty($output)):?>

	<div>
		Limit: from <?=$limitFrom?> to <?=$limitTo?><br/>
		Count: <?=count($output)?><br/>
		Delay: <span id="sec">0</span> sec
	</div>
	<script><?=$scriptString?></script>
    <script>var s = 0; setInterval(function(){ s++; document.getElementById("sec").innerHTML = s; }, 1000);</script>

<?else:?>
	
	<div>
		Done!
	</div>

<?endif?>
