<?php

	// SQL操作用にレシピIDをカンマ区切りの文字列で格納
	function mergeID(){
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
	}

 ?>