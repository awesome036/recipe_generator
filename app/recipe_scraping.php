<?php
	require_once('./simple_html_dom.php');
	ini_set('user_agent', 'OreOreAgent');
	ini_set("max_execution_time",0);
	ini_set('display_errors', 0);

	define('DB_HOST','');
	define('DB_USERNAME','');
	define('DB_PASSWORD','');

	// データベースへの接続
	$con = mysqli_connect(DB_HOST,DB_USERNAME,DB_PASSWORD);
	mysqli_set_charset($con,"utf8");
	mysqli_select_db($con,"auto_recipe");

	// 書き込み決定表
	$params[] = array("category" => 501, "costNum" => 1, "page" => 50);
	$params[] = array("category" => 501, "costNum" => 2, "page" => 50);
	$params[] = array("category" => 501, "costNum" => 3, "page" => 5);
	$params[] = array("category" => 502, "costNum" => 1, "page" => 50);
	$params[] = array("category" => 502, "costNum" => 2, "page" => 75);
	$params[] = array("category" => 502, "costNum" => 3, "page" => 15);
	$params[] = array("category" => 503, "costNum" => 1, "page" => 50);
	$params[] = array("category" => 503, "costNum" => 2, "page" => 100);
	$params[] = array("category" => 503, "costNum" => 3, "page" => 50);

	foreach($params as $prm){
		for($i = 1; $i <= $prm["page"]; $i++){
			$url = "https://recipe.rakuten.co.jp/category/38-".$prm["category"]."/{$i}/?s=0&v=0&cost=".$prm["costNum"];
			$html = file_get_html($url);

			// ページ内のレシピID全件取得
			foreach($html->find('.contentsBox ul li #recipe_detail_link') as $element){
				$str = $element->href;
				$recipesID[] = preg_replace('/[^0-9]/','',$str);
			}

			// ページ内のレシピIDごとにデータベースに書き込み
			foreach($recipesID as $value){
				$url = "https://recipe.rakuten.co.jp/recipe/{$value}/";
				$html = file_get_html($url);

				// レシピID
				foreach($html->find('.rcpId') as $element){
				 	$recipeID = $element->plaintext;
					$recipeID = preg_replace('/[^0-9]/','',$recipeID);
				}

				// レシピ名
				foreach($html->find('h1') as $element){
					$recipeName = $element->plaintext;
				}

				// 金額
				foreach($html->find('.icnMoney') as $element){
					$cost = $element->plaintext;
					$cost = preg_replace('/[^0-9]/','',$cost);
				}


				// 人数
				foreach($html->find('.materialBox .materialTit h3 span span') as $element){
				 	$people = $element->plaintext;
					$people = preg_replace('/[^0-9]+/','',$people);
					if (empty($people)) {
						$people = $element->plaintext;
						$people = preg_replace('/[^０-９]+/','',$people);
						$people = substr($people,0,3);
						$people = settype($people,'integer');
					}else{
						$people = substr($people,0,1);
					}
				}

				// 材料
				foreach($html->find('.materialBox ul li') as $element){
					$amount = NULL;
					$name = $element->find('.name',0)->plaintext;
					$amount = $element->find('.amount',0)->plaintext;
					if(!empty($amount) or isset($amount)){
						$ingredients[] = array("name" => $name, "amount" => $amount);
					}
				}

				// 画像URL
				foreach($html->find('.rcpPhotoBox img') as $element){
					$imgURL = $element->src;
				}
				// 画像をローカルに保存
				$data = file_get_contents($imgURL);
				file_put_contents('./images/'.$value.'.jpg',$data);

				// $data->clear();
				// unset($data);


				/*-------------------- recipes table -------------------*/
				$sql = "INSERT INTO recipes(recipeID,recipeName,category,cost,people)
						VALUES($value,'$recipeName',{$prm["category"]},$cost,$people)";

				mysqli_query($con,$sql);
				/*--- recipes table end ---*/

				/*-------------------- ingredients table -------------------*/
				foreach($ingredients as $val){
					$ingredientName = $val["name"];
					$sql = "SELECT ingredientName
							FROM ingredients
							WHERE ingredientName
								LIKE '$ingredientName'";
					$result = mysqli_query($con,$sql);
					$row = mysqli_fetch_array($result);
					if(!$row){
						$sql = "INSERT INTO ingredients(ingredientName)
								VALUES('$ingredientName')";
						mysqli_query($con,$sql);
					}
				}
				/*--- ingredients table end ---*/

				/*-------------------- amount table -------------------*/
				foreach($ingredients as $val){
					$ingredientName = $val["name"];
					$ingredientAmount = $val["amount"];
					$sql = "SELECT ingredientID
							FROM ingredients
							WHERE ingredientName
								LIKE '$ingredientName'";
					$result = mysqli_query($con,$sql);
					$ingredientID = mysqli_fetch_row($result);
					$sql = "INSERT INTO amount(recipeID,ingredientID,ingredientAmount)
							VALUES($value,$ingredientID[0],'$ingredientAmount')";
					mysqli_query($con,$sql);
				}
				/*--- amount table end ---*/

				// print "書き込み完了!";

				unset($ingredients);
				unset($element);
				unset($val);
				$html->clear();
				unset($html);

				sleep(3);
			}

			unset($recipesID);
			unset($html);
		}
	}
	
	unset($prm);

	





	// データベース接続終了
	mysqli_close($con);
 ?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>データベース書き込み</title>
	</head>
	<body onload="window.alert('書き込み完了！');">

	</body>
</html>