<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Result | RECIPE GENERATOR</title>
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />
		<link rel="stylesheet" type="text/css" href="./css/style.css" />
		<script type="text/javascript" src="./js/jquery-3.3.1.min.js"></script>
		<script type="text/javascript" src="./js/tippy.all.min.js"></script>
		<script type="text/javascript" src="./js/jquery.onepage-scroll.js"></script>
	</head>
	<body>
		<div id="wrapper" class="background">

			<!-- header -->
			<header id="gen_header">
				<div id="headerInner">
					<h1 id="headTitle"><a href="./index.html">RECIPE GENERATOR</a></h1>
				</div>
			</header>
			<!-- /header -->

			<!-- main -->
			<main>

				<?php
					define('DB_HOST','');
					define('DB_USERNAME','');
					define('DB_PASSWORD','');
					
					// データベースへの接続
					$con = mysqli_connect(DB_HOST,DB_USERNAME,DB_PASSWORD);
					mysqli_set_charset($con,"utf8");
					mysqli_select_db($con,"recipe_generator");

					// 変数定義
					$people = $_POST["people"];
					settype($people,"integer");
					$term = $_POST["term"];
					$meal = $_POST["meal"];
					$allCost = 0;	// 全額費用
					$oldAllCost = 0; // 置換前金額保存用
					$orderCost = $_POST["money"];	// 予算
					$oldMinRecipe["id"] = 0;
					$checkSeak = TRUE; // 献立が作れた FALSE:NO TRUE:YES
					$checkInput = TRUE;	// 入力が正しい FALSE:NO TRUE:YES

					if(isset($_POST["category"])){	// カテゴリーがある場合

						// カテゴリーを配列に格納
						foreach($_POST["category"] as $val){
							settype($val,"integer");
							$category[] = $val;
						}


						// 品数を格納
						$meals = $term * $meal;

						// 期間＊品数のレシピIDを取得
						foreach($category as $cat){
							if(isset($cat)){
								$sql = "SELECT recipeID
								FROM recipes
								WHERE category = $cat
								ORDER BY RAND()
								LIMIT $meals";

								$result = mysqli_query($con,$sql);
								while($row = mysqli_fetch_row($result)){
									if($cat ==501){
										$morningID[] = $row[0];
									}elseif($cat == 502){
										$noonID[] = $row[0];
									}else{
										$eveningID[] = $row[0];
									}
								}
							}
						}


						// SQL操作用にレシピIDをカンマ区切りの文字列で格納
						$mergeID[0] = 0;
						if(isset($morningID)){
							$mergeID = array_merge($mergeID,$morningID);
						}
						if(isset($noonID)){
							$mergeID = array_merge($mergeID,$noonID);
						}
						if(isset($eveningID)){
							$mergeID = array_merge($mergeID,$eveningID);
						}
						$commaID = implode(",",$mergeID);


						// 合計金額を取得（人数に依存）
						$sql = "SELECT SUM(cost*($people/people))
								FROM recipes
								WHERE recipeID IN ($commaID)";
						$result = mysqli_query($con,$sql);
						$row = mysqli_fetch_row($result);
						$allCost = $row[0];



						// 予算が決まっている場合最適な金額になるまで入れ替え
						// 予算内に収まらなければ $checkSeak=FALSE にしてループを抜け出す
						$time_start = microtime(TRUE);	// 処理時間で抜け出す
						if(!empty($orderCost) && is_numeric($orderCost)){
							while($orderCost <= $allCost){
								// 該当レシピIDを単価の降順に並び替えて最大値を取り出す
								$sql = "SELECT DISTINCT recipeID,
														category,
														(cost/people) AS perCost
										FROM recipes
										WHERE recipeID IN ($commaID)
										ORDER BY perCost DESC";

								$result = mysqli_query($con,$sql);
								$row = mysqli_fetch_row($result);
								$maxID = $row[0];
								$maxCategory = $row[1];
								$maxCost = floor($row[2]);


								// 最大値より安いレシピIDを取り出す
								$sql = "SELECT DISTINCT recipeID,
														(cost/people) AS perCost
										FROM recipes
										WHERE category = $maxCategory
										AND (cost/people) < $maxCost
										AND recipeID NOT IN($commaID)
										ORDER BY perCost DESC";

								$result = mysqli_query($con,$sql);
								if($row = mysqli_fetch_row($result)){
									$minRecipe = array(
										"id" => $row[0],
										"perCost" => $row[1]
									);
								}
								if($oldMinRecipe["id"] == $minRecipe["id"]){
									// より安いレシピが見つからなかった場合ループを抜け出す
									$checkSeak = FALSE;
									break;
								}
								$oldMinRecipe["id"] = $minRecipe["id"];


								// 最大値のレシピIDとより安いレシピIDを置換する
								if($maxCategory == 501){
									if(isset($morningID)){
										for($i=0; $i<count($morningID); $i++){
											if($morningID[$i] == $maxID){
												$morningID[$i] = $minRecipe["id"];
											}
										}
									}
								}elseif($maxCategory == 502){
									if(isset($noonID)){
										for($i=0; $i<count($noonID); $i++){
											if($noonID[$i] == $maxID){
												$noonID[$i] = $minRecipe["id"];
											}
										}
									}
								}else{
									for($i=0; $i<count($eveningID); $i++){
										if($eveningID[$i] == $maxID){
											$eveningID[$i] = $minRecipe["id"];
										}
									}
								}


								// SQL操作用にレシピIDをカンマ区切りの文字列で格納
								unset($commaID);
								unset($mergeID);	// $mergeIDをリセット
								$mergeID[0] = 0;
								if(isset($morningID)){
									$mergeID = array_merge($mergeID,$morningID);
								}
								if(isset($noonID)){
									$mergeID = array_merge($mergeID,$noonID);
								}
								if(isset($eveningID)){
									$mergeID = array_merge($mergeID,$eveningID);
								}
								$commaID = implode(",",$mergeID);


								// 合計金額を取得（人数に依存）
								$sql = "SELECT SUM(cost*($people/people))
										FROM recipes
										WHERE recipeID IN ($commaID)";
								$result = mysqli_query($con,$sql);
								$row = mysqli_fetch_row($result);
								$allCost = $row[0];

								$time = microtime(TRUE) - $time_start;
								if($oldAllCost == $allCost){
									// より安いレシピが見つからなかった場合ループを抜け出す
									$checkSeak = FALSE;
									break;
								}
								$oldAllCost = $allCost;
							}
						}


						// 入力チェック
						if(is_numeric($orderCost) || empty($orderCost)){



							// 最適化された献立を出力

							// 朝昼夜表示用ボックス
							echo 	"<div id='whenTable'>
										<table>
											<tr><td></td></tr>
											<tr><th>朝</th></tr>
											<tr><th>昼</th></tr>
											<tr><th>夜</th></tr>
										</table>
									</div>\n";

							// レシピコンテンツラッパー
							echo "<div class='listWrapper'>";


							// レシピコンテンツインナー
							echo "<section class='listInner'>\n";


							for($i=1; $i<=$term; $i++){
								// 1日分のレシピを囲むボックス
								echo "<div class='listBox'>\n";

								echo "<h3 class='listDate'>{$i}日目</h3>";

								for($j=0; $j<3; $j++){
									// レシピボックス
									echo "<div class='recipeBox'>\n";

									if(isset($morningID) && $j == 0){
										// 朝のレシピリストボックス
										for($k=$meal*($i-1); $k<($meal*$i); $k++){
											echo "<div class='popBox' id='recipeListBox_$morningID[$k]' style='color:#fff' style='width:200px'>";

											$sql = "SELECT recipeName,cost,people
											FROM recipes
											WHERE recipeID = $morningID[$k]";
											$result = mysqli_query($con,$sql);
											$row = mysqli_fetch_row($result);

											$title = $row[0];

											// タイトル
											echo "<p class='popTitle'>$title</p>";

											// 画像
											echo "<p class='popImage'><img src='./images/$morningID[$k].jpg' alt='$row[0]' /></p>";


											// 予想金額
											echo "<p>金額:".round($row[1]/$row[2]*$people)."円</p>";

											// 材料
											echo "<p>材料($row[2]人前)</p>";
											echo "<table>";

											$sql = "SELECT ingredientName,ingredientAmount
											FROM amount,ingredients
											WHERE amount.ingredientID = ingredients.ingredientID
											AND recipeID = $morningID[$k]";
											$result = mysqli_query($con,$sql);
											while($row = mysqli_fetch_row($result)){
												echo "<tr><th>$row[0]</th><td>$row[1]<td></tr>";
											}
											echo "</table>";

											// リンクボタン
											echo "<a href='https://recipe.rakuten.co.jp/recipe/$morningID[$k]' target='_blank'>作り方を楽天レシピで見る</a>";

											echo "</div>\n";

											// 結果画面表示部
											echo "<p id='recipeButton_$morningID[$k]' class='showRecipe'><img class='showImage clear' src='./images/$morningID[$k].jpg' alt='$title'/>$title</p>\n";

											// ツールチップ化Javascript
											echo "<script type='text/javascript'>
													//<![CDATA[
														tippy('#recipeButton_$morningID[$k]',{
															html: document.querySelector('#recipeListBox_$morningID[$k]'),
															trigger: 'click',
															animation: 'scale',
															arrow: 'sharp',
															size: 'small',
															duration: '[200,200]',
															placement: 'top'
														})

													//]]
													</script>\n";
										}

									}elseif(isset($noonID) && $j == 1){
										// 昼のレシピリストボックス
										for($k=$meal*($i-1); $k<($meal*$i); $k++){
											echo "<div class='popBox' id='recipeListBox_$noonID[$k]' style='color:#fff' style='width:200px'>";

											$sql = "SELECT recipeName,cost,people
											FROM recipes
											WHERE recipeID = $noonID[$k]";
											$result = mysqli_query($con,$sql);
											$row = mysqli_fetch_row($result);

											$title = $row[0];

											// タイトル
											echo "<p class='popTitle'>$title</p>";

											// 画像
											echo "<p class='popImage'><img src='./images/$noonID[$k].jpg' alt='$row[0]' /></p>";


											// 予想金額
											echo "<p>金額:".round($row[1]/$row[2]*$people)."円</p>";

											// 材料
											echo "<p>材料($row[2]人前)</p>";
											echo "<table>";

											$sql = "SELECT ingredientName,ingredientAmount
											FROM amount,ingredients
											WHERE amount.ingredientID = ingredients.ingredientID
											AND recipeID = $noonID[$k]";
											$result = mysqli_query($con,$sql);
											while($row = mysqli_fetch_row($result)){
												echo "<tr><th>$row[0]</th><td>$row[1]<td></tr>";
											}
											echo "</table>";

											// リンクボタン
											echo "<a href='https://recipe.rakuten.co.jp/recipe/$noonID[$k]' target='_blank'>作り方を楽天レシピで見る</a>";

											echo "</div>\n";

											// 結果画面表示部
											echo "<p id='recipeButton_$noonID[$k]' class='showRecipe'><img class='showImage clear' src='./images/$noonID[$k].jpg' alt='$title'/>$title</p>\n";

											// ツールチップ化Javascript
											echo "<script type='text/javascript'>
													//<![CDATA[
														tippy('#recipeButton_$noonID[$k]',{
															html: document.querySelector('#recipeListBox_$noonID[$k]'),
															trigger: 'click',
															animation: 'scale',
															arrow: 'sharp',
															size: 'small',
															duration: '[200,200]',
															placement: 'top'
														})

													//]]
													</script>\n";
										}

									}elseif(isset($eveningID) && $j == 2){
										// 夜のレシピリストボックス
										for($k=$meal*($i-1); $k<($meal*$i); $k++){
											echo "<div class='popBox' id='recipeListBox_$eveningID[$k]' style='color:#fff' style='width:200px'>";

											$sql = "SELECT recipeName,cost,people
											FROM recipes
											WHERE recipeID = $eveningID[$k]";
											$result = mysqli_query($con,$sql);
											$row = mysqli_fetch_row($result);

											$title = $row[0];

											// タイトル
											echo "<p class='popTitle'>$title</p>";

											// 画像
											echo "<p class='popImage'><img src='./images/$eveningID[$k].jpg' alt='$row[0]' /></p>";


											// 予想金額
											echo "<p>金額:".round($row[1]/$row[2]*$people)."円</p>";

											// 材料
											echo "<p>材料($row[2]人前)</p>";
											echo "<table>";

											$sql = "SELECT ingredientName,ingredientAmount
											FROM amount,ingredients
											WHERE amount.ingredientID = ingredients.ingredientID
											AND recipeID = $eveningID[$k]";
											$result = mysqli_query($con,$sql);
											while($row = mysqli_fetch_row($result)){
												echo "<tr><th>$row[0]</th><td>$row[1]<td></tr>";
											}
											echo "</table>";

											// リンクボタン
											echo "<a href='https://recipe.rakuten.co.jp/recipe/$eveningID[$k]' target='_blank'>作り方を楽天レシピで見る</a>";

											echo "</div>\n";

											// 結果画面表示部
											echo "<p id='recipeButton_$eveningID[$k]' class='showRecipe'><img class='showImage clear' src='./images/$eveningID[$k].jpg' alt='$title'/>$title</p>\n";

											// ツールチップ化Javascript
											echo "<script type='text/javascript'>
													//<![CDATA[
														tippy('#recipeButton_$eveningID[$k]',{
															html: document.querySelector('#recipeListBox_$eveningID[$k]'),
															trigger: 'click',
															animation: 'scale',
															arrow: 'sharp',
															size: 'small',
															duration: '[200,200]',
															placement: 'top'
														})

													//]]
													</script>\n";
										}
									}


									echo "</div>\n";
								}



							echo "</div>";
							// 7日目でボックス区切り
							if($i%7 == 0 && $i != $term){
								echo "</section><section class='listInner'>\n";
								}
							}
							echo "</div>\n";

							echo "</div>";


						}else{
							// 数値以外が入力された場合
							echo "<div id='falseInput'>";
							echo "<p>半角で数値を入力してね<br/><a href='./index.html'>入力画面へ戻る</a></p>";
							echo "</div>";
							$checkInput = FALSE;
						}

					}else{	// カテゴリーがなかったら
						echo "<div id='falseInput'>";
						echo "<p>未入力の欄があります<br/><a href='./index.html'>入力画面へ戻る</a></p>";
						echo "</div>";

						$checkInput = FALSE;
					}

					// データベース接続終了
					mysqli_close($con);

				 ?>

			</main>
			<!-- /main -->

			<?php if($checkInput){?>
			<!-- footer -->
			<footer id="gen_footer">
				<div id="footerInner">

					<button id="formPop">条件を変更する</button>
					<div id="hideForm">
						<form action="./generate.php" method="post">
							<div id="inputMoney">
								<label for="moneyBox">¥</label>
								<input id="moneyBox" type="text" name="money" maxlength="100" placeholder="予算を入力してください"/>
							</div>
							<label>期間：</label>
							<select class="optSelect" name="term" value="7">
								<option value="1" >1日</option>
								<option value="2">2日</option>
								<option value="3">3日</option>
								<option value="4">4日</option>
								<option value="5">5日</option>
								<option value="6">6日</option>
								<option value="7" selected="selected">1週間</option>
								<option value="14">2週間</option>
								<option value="21">3週間</option>
								<option value="30">1か月</option>
							</select>
							<br />
							<label>いつ：</label>
							<input class="optCheck" type="checkbox" name="category[]" value="501" checked="checked" />朝
							<input class="optCheck" type="checkbox" name="category[]" value="502" checked="checked" />昼
							<input class="optCheck" type="checkbox" name="category[]" value="503" checked="checked" />夜
							<br />
							<label>品数：</label>
							<input class="optRadio" type="radio" name="meal" value="1" />1品
							<input class="optRadio" type="radio" name="meal" value="2" />2品
							<input class="optRadio" type="radio" name="meal" value="3" checked="checked" />3品
							<br />
							<label>人数：</label>
							<select class="optSelect" name="people" value="1">
								<option value="1" selected="selected">1人</option>
								<option value="2">2人</option>
								<option value="3">3人</option>
								<option value="4">4人</option>
								<option value="5">5人</option>
								<option value="6">6人</option>
								<option value="7">7人</option>
								<option value="8">8人</option>
								<option value="9">9人</option>
								<option value="10">10人</option>
							</select>
							<br />

							<input type="submit" value="作成" />
						</form>
					</div>
					<?php echo "<div class='bigWhite'>予想金額".round($allCost)."円</div>"; ?>

				</div>
			</footer>
			<!-- /footer -->
			<?php } ?>
		</div>
		<!-- Javascript -->
		<script type="text/javascript">
		//<![CDATA[
			$(function(){

				$('#formPop').click(function(){
					$('#hideForm').stop().fadeToggle(200);
				});

			});

			$('.listWrapper').onepage_scroll({
				sectionContainer: 'section',
				easing: 'ease-in-out',
				animationTime: 500,
				pagination: false,
				direction: "vertical"
			});

			$(document).ready(function() {
				$('#formPop').each(function() {
					var elements = $(this);
					var count = 0;
					var defaultText = elements.text();
					elements.click(function() {
					if ( count === 0 ){
						elements.text('閉じる');
						count = 1;
					} else{
						elements.text(defaultText);
						count = 0;
					}
			});
		});
	});
		//]]>
		</script>
		<!-- /Javascript -->
		<?php
		if(!$checkSeak){
			// 条件通りに作成できなかった場合
			echo "<script type='text/javascript'>
			//<![CDATA[
				alert('選択した条件には当てはまりませんでした');
				//]]
				</script>\n";
			}

			?>
	</body>
</html>