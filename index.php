<?php 
	$method = $_SERVER['REQUEST_METHOD'];
	if($method == "POST") {
		$response = array();

		$requestbody = file_get_contents('php://input');

		$json_data = json_decode($requestbody); 

		$responseId= $json_data->responseId;
		$queryResult= $json_data->queryResult;

		$answer= strtolower($queryResult->queryText);
		$action=$queryResult->action;

		$parameters = $queryResult->parameters;
		$intent= $queryResult->intent;
		$intentNameArr=explode('/',$intent->name);

		$project_id=$intentNameArr[1];
		$session_id=$intentNameArr[4];



		require_once 'includes/common.php';
		


		if($action=='welcome'){
			$response=welcome();
		}
		else if($action=='mobile_verification'){
			$response=mobile_verification();
		}
		else if($action=='input.unknown'){
			$response=not_matched();
		}
		
		elseif ($action=='check-product-item-available') {
			$response=check_product_item_available();
		}
		elseif ($action=='CheckItemAvailable.CheckItemAvailable-order') {
			$response=check_item_available_order();
		}


		//print_r($response); die;


		$response['source'] = "webhook";
		echo json_encode($response); die;

	}
 ?>