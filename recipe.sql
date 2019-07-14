DROP DATABASE auto_recipe;

/* 文字コードセット */
SET character_set_client=sjis;
SET character_set_connection=utf8;
SET character_set_server=utf8;
SET character_set_results=sjis;

CREATE DATABASE auto_recipe;
USE auto_recipe;

DROP TABLE recipes;
CREATE TABLE recipes(
	id INT AUTO_INCREMENT PRIMARY KEY,
	recipeID INT,
	recipeName VARCHAR(50),
	category INT,
	cost INT,
	people INT
);

DROP TABLE ingredients;
CREATE TABLE ingredients(
	ingredientID INT AUTO_INCREMENT PRIMARY KEY,
	ingredientName VARCHAR(30)
);

DROP TABLE amount;
CREATE TABLE amount(
	recipeID INT,
	ingredientID INT,
	ingredientAmount VARCHAR(20),
	PRIMARY KEY(recipeID,ingredientID)
);