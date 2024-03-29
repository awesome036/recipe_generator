# RECIPE_GENERATOR  

## はじめに  
プログラミングをやり始めて4か月の時に作ったWEBシステムです。
そのため**スーパーハードスパゲッティコード**になっているので大変読みにくいモノになっていますがお許しください。  
また、制作当時はレスポンシブという概念を持ち合わせていなかったため、16：9の画面でのみピッタリ表示されるようになっています。実行環境によって表示が崩れる場合もありますがお許しください。

## システム概要  
予算・期間・品数・人数分をもとに献立表を一覧で出力するシステムです。

## 開発の経緯  
1人暮らしを始めた兄が、  

>「毎日献立を考えるのは面倒くさい...スーパーで総菜買うのがベストオブベスト」  

と、ぼやいていたのを聞き、献立をまとめて出力してくれるシステムを作ることに至った。  

## 環境  
- php7.2.7  
- mysql5.7  

## 実行手順  
1. `recipe.sql`をmysqlで実行してデータベースを作成。  
2. `app/recipe_scraping.php`を実行してレシピをスクレイピング＆画像を取得。  
3. `app/index.html`を開く。  

## 注意事項  
- レシピのデータは楽天レシピからスクレイピングしているので個人利用のみにとどめてください。  
- スクレイピングは1件取得ごとに3秒sleepさせているので、すべてのデータを取得するのに７，８時間かかります。取得数を少なくしたい場合は、`app/recipe_scraping.php`内の書き込み決定表にある*page*の値を小さくしてください。  