<?php 

$servername = "37.187.50.206";
$username = "cafenoir";
$password = "AmbASyuz8ApurKdu";
$dbName = "cafenoir";
$conn = new mysqli($servername, $username, $password, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function welcome($value='')
{
	global $queryResult,$project_id,$session_id,$conn;

	$data=array();

	$no_ordering=$mobile_valid=true; $order_yes_extraitem_followup=true; $order_yes_extraitem_no_followup=true;
	if($queryResult->outputContexts){
		for($i=0; $i<count($queryResult->outputContexts);$i++) {
			if(strpos($queryResult->outputContexts[$i]->name, "mobile_valid") !== false) {
				$mobile_valid=false;
			}
			else if(strpos($queryResult->outputContexts[$i]->name, "ordering") !== false) {
				$no_ordering=false;
			}
			else if(strpos($queryResult->outputContexts[$i]->name, "order-yes-extraitem-followup") !== false){
				$order_yes_extraitem_followup=false;
			}
			else if(strpos($queryResult->outputContexts[$i]->name, "order-yes-extraitem-no-followup") !== false){
				$order_yes_extraitem_no_followup=false;
			}
		}
	}
	if($mobile_valid && $no_ordering  && $order_yes_extraitem_followup  && $order_yes_extraitem_no_followup){
		$res_message="Hello. My name is Albert, I am the bot of Café Noir, if you have any issues don't hesitate to contact our customer manager. Please, confirm your phone no to order.";
		
	}else if($mobile_valid){
		$res_message="What would you like to order?";
	}else{
		$res_message="What would you like to order?";
	}
	$response['fulfillmentText'] = $res_message;
	return $response; 
}

function mobile_verification()
{
	global $queryResult,$project_id,$session_id,$conn,$parameters;

	$mobile_valid=true;
	if($queryResult->outputContexts){
		for($i=0; $i<count($queryResult->outputContexts);$i++) {
			if(strpos($queryResult->outputContexts[$i]->name, "mobile_valid") !== false) {
				$mobile_valid=false;
				$user_mob = $queryResult->outputContexts[$i]->parameters->parameter_name;
			}
			
		}
	}
	
	if($mobile_valid){
		$user_mob=$parameters->phone_number;
		$sql = "SELECT * FROM client WHERE phone = '$user_mob'";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			
			/*Creating contexts for mobile variviation*/
			$context = array(array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/mobile_valid', "lifespanCount" => 5, "parameters" => array("parameter_name" => $user_mob)));	
			$res_message="Hello, Welcome ".$row['fname'].' '.$row['lname'].", What would you like to order ?";
			
			/*Deleting previous temp order records*/
			$delete = "DELETE FROM `temp_order` WHERE phone = '".$user_mob."'";
			$conn->query($delete);
			
		}else{

			//$response['resetContexts'] = true;
			$context = array(array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/mobile_no', "lifespanCount" => 1));
			$res_message="Sorry! Invalid user phone no. Please try with a valide phone no.";
		}			

		
	}else{
		$context=destroyContent(array('order-yes-extraitem-followup','order-no-extraitem-followup','item-price-order-followup','item-order-name-followup','ItemOrder-followup','order-followup','ordering-no','order-followup-2','item-order-followup','itemorder-followup'));

		$context[] = array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/mobile_valid', "lifespanCount" => 1, "parameters" => array("parameter_name" => $user_mob));
		$res_message="Sorry! I don't understant, What would you like to order? ";
	}
	if($context)$response['outputContexts'] = $context;
	$response['fulfillmentText'] = $res_message;
	return $response;
}


function not_matched()
{
	global $queryResult,$project_id,$session_id,$conn,$parameters;

	$mobile_valid=false;
	if($queryResult->outputContexts){
		for($i=0; $i<count($queryResult->outputContexts);$i++) {
			if(strpos($queryResult->outputContexts[$i]->name, "mobile_valid") !== false) {
				$mobile_valid=true;
				$user_mob = $queryResult->outputContexts[$i]->parameters->parameter_name;
			}
		}
	}
	
	if($mobile_valid){
		$context = array(array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/mobile_valid', "lifespanCount" => 1, "parameters" => array("parameter_name" => $user_mob)));

		//$res_message="Sorry! I don't understant, What would you like to order? ";
	}else{
		$res_message="Sorry! I don't understant, Could you please confirm your mobile no ?";
		$response['fulfillmentText'] = $res_message;
	}
	if($context)$response['outputContexts'] = $context;

	return $response;

}

function check_item_exist($item_name='' ,$is_veg_or_non='')
{
	global $conn;
	$item_name = trim(rtrim(ltrim($item_name,','),'?'),'');
	if($item_name && $item_name!=''){

		$cond=($is_veg_or_non!='')?" AND LOWER(type)='". strtolower($is_veg_or_non)."'":"";

		$sql = "SELECT * FROM product WHERE LOWER(name) = '".strtolower($item_name)."' || LOWER(name) = '".strtolower($item_name)."s' || LOWER(name) = '".strtolower($item_name)."es'".$cond;
		$result = $conn->query($sql);

		if ($result) {
			if($result->num_rows==1){
				return $result->fetch_object();
			}else if($result->num_rows>1){
				$data=array();
				while($obj=$result->fetch_object())
			    {
			    	$data[]=$obj;
			    }
				return $data;
			}
			
		}
		else{
			return false;
		}
	}else{
		return false;
	}
}

function check_item_like_exist($item_name='' ,$is_veg_or_non='')
{
	global $conn;
	$item_name = trim(rtrim(ltrim($item_name,','),'?'),'');
	if($item_name && $item_name!=''){

		$cond=($is_veg_or_non!='')?" AND LOWER(type)='". strtolower($is_veg_or_non)."'":"";

		$sql = "SELECT * FROM product WHERE LOWER(name) LIKE '%".strtolower($item_name)."%' ".$cond;
		$result = $conn->query($sql);

		if ($result) {
			if($result->num_rows==1){
				return $result->fetch_object();
			}else if($result->num_rows>1){
				$data=array();
				while($obj=$result->fetch_object())
			    {
			    	$data[]=$obj;
			    }
				return $data;
			}
			
		}
		else{
			return false;
		}
	}else{
		return false;
	}
}


function checkItemExist($item_name='' , $all_like=0,$is_veg_or_non='')
{
	global $conn;
	$item_name = trim(rtrim(ltrim($item_name,','),'?'),'');
	if($item_name && $item_name!=''){

		$cond=($is_veg_or_non!='')?" AND LOWER(type)='". strtolower($is_veg_or_non)."'":"";

		$sql = "SELECT * FROM product WHERE LOWER(name) = '".strtolower($item_name)."' || LOWER(name) = '".strtolower($item_name)."s' || LOWER(name) = '".strtolower($item_name)."es'".$cond;
		$result = $conn->query($sql);

		$sql2 = "SELECT * FROM product WHERE LOWER(name) LIKE '".strtolower($item_name)."%' ".$cond;
		$result2 = $conn->query($sql2);

		$sql3 = "SELECT * FROM product WHERE LOWER(section)='".strtolower($item_name)."' ".$cond;
		$result3 = $conn->query($sql3);

		if ($result->num_rows> 0) {
			return $result->fetch_object();
		}else if($result2->num_rows==1){
			return $result2->fetch_object();
		}else if($result2->num_rows>1 && $all_like==1){
			$data=array();
			while($obj=$result2->fetch_object())
		    {
		    	$data[]=$obj;
		    }
			return $data;
		}
		else if($result3->num_rows>0 && $all_like==1 ){
			$data=array();
			while($obj=$result3->fetch_object())
		    {
		    	$data[]=$obj;
		    }
			return $data;
		}
		else{
			return false;
		}
	}else{
		return false;
	}
}

function getItemByCategory($category_nm='',$item_type='')
{
	global $conn;

	if($category_nm!=""){

		$cond=($item_type!='')?" AND LOWER(type)='". strtolower($item_type)."'":"";

		$sql = "SELECT * FROM product WHERE LOWER(section)='".strtolower($category_nm)."' ".$cond;
		$result = $conn->query($sql);
		if($result->num_rows>0 ){
			$data=array();
			while($obj=$result->fetch_object())
		    {
		    	$data[]=$obj;
		    }
			return $data;
		}
	}
}

function getOrderCost($user_mob='')
{
	global $conn;
	$resultData=array();
	if($user_mob){
		$query = "SELECT SUM( quantity ) as quantity, temp_order.name, price FROM `temp_order`
					LEFT JOIN `product` ON product.`name` = temp_order.`name`
				 	WHERE phone = '".$user_mob."' GROUP BY name";
		$result = $conn->query($query);
		$order_details = '';
		$total=0;
		$sub_total=0;
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$total=(float)($row["quantity"]*$row["price"]);
				$resultData['orderList']['item_quantity'][]= $row["quantity"];
				$resultData['orderList']['item_price'][]= $row["price"];
				$resultData['orderList']['item_name'][]= ucfirst(strtolower(trim($row["name"])));
				$resultData['orderList']['item_quantity_name_price'][]=$row["quantity"].' '.ucfirst(strtolower(trim($row["name"]))).': ('.$row["price"].'x'.$row["quantity"].') ='.$total.' INR';
				$sub_total +=$total;
			}
			$resultData['total_amount']=$sub_total;
		}
	}
	return $resultData;
}


function destroyContent($content=array()){
	global $project_id,$session_id;
	
	if(count($content)>0){
		foreach ($content as $key => $value) {
			$context[] = array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/'.$value, "lifespanCount" => 0);	
		}
		return $context;
	}
}

function getItemDetails($item_name='')
{
	global $conn;
	$item_name = trim(rtrim(ltrim($item_name,','),'?'));
	if($item_name && $item_name!=''){
		$sql = "SELECT * FROM product WHERE LOWER(name) = '".strtolower($item_name)."'";

		$result = $conn->query($sql);
		if ($result->num_rows> 0) {
			return $result->fetch_object();
		}else{
			return false;
		}
	}
}



function item_order_name()
{
	global $queryResult,$project_id,$session_id,$conn,$parameters;
	$product_item=$parameters->product_item;
	      
	if($queryResult->outputContexts){
		for($i=0; $i<count($queryResult->outputContexts);$i++) {
			if(strpos($queryResult->outputContexts[$i]->name, "mobile_valid") !== false) {
				$user_mob = $queryResult->outputContexts[$i]->parameters->parameter_name;
			}

		}
	}

	$resultData=getItemDetails($product_item);
   	

    if($user_mob && $resultData){
    	$context=destroyContent(array('order-yes-extraitem-followup','order-no-extraitem-followup','item-price-order-followup'));
        $category= ucfirst(strtolower($resultData->section));
        $res_message = " How many ".$category.' '.$product_item." you want to order?";

        $context[]=array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/item-order-name-followup ', "lifespanCount" => 5, "parameters" => array("product_item" =>array($product_item)));
		$context[]=array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/item-order-followup', "lifespanCount" => 5, "parameters" => array("product_item" =>array($product_item)));
		

    }else{
    	$context=destroyContent(array('order-yes-extraitem-followup','order-no-extraitem-followup','item-price-order-followup','item-order-name-followup','ItemOrder-followup'));

        $res_message = " Sorry! ".$product_item." not available.What would like to order?";

    	$context[]=array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/mobile_valid', "lifespanCount" => 5, "parameters" => array("parameter_name" =>$user_mob));
    }



	if($context)$response['outputContexts'] = $context;
	$response['fulfillmentText'] = $res_message;
	return $response;
}


function item_order_quantity()
{
	global $queryResult,$project_id,$session_id,$conn,$parameters;
	$product_item='';
	$quantity=$parameters->number;

	if($queryResult->outputContexts){
		for($i=0; $i<count($queryResult->outputContexts);$i++) {
			if(strpos($queryResult->outputContexts[$i]->name, "mobile_valid") !== false) {
				$user_mob = $queryResult->outputContexts[$i]->parameters->parameter_name;
				$product_item = $queryResult->outputContexts[$i]->parameters->product_item[0];
			}

		}
	}
	

	if($user_mob && $product_item){
		$resultData=getItemDetails($product_item);

       $category= ucfirst(strtolower($resultData->section));

		$context[]=array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/mobile_valid', "lifespanCount" => 5, "parameters" => array("number" =>array($quantity),"product_item" =>array($product_item)));

		$context[]=array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/ordering', "lifespanCount" => 5, "parameters" => array("number" =>array($quantity),"product_item" =>array($product_item)));
		$context[]=array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/order-followup', "lifespanCount" => 5, "parameters" => array("number" =>array($quantity),"product_item" =>array($product_item)));
		$context[]=array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/order-followup-2', "lifespanCount" => 5, "parameters" => array("number" =>array($quantity),"product_item" =>array($product_item)));

		$res_message = "Can you confirm your order : ".$quantity.' '.$category.' '.$product_item."?";

	}


	if($context)$response['outputContexts'] = $context;
	$response['fulfillmentText'] = $res_message;
	return $response;
}

function like_to_order_yes()
{
	global $queryResult,$project_id,$session_id,$conn,$parameters;
	$product_item=$parameters->product_item;
	      
	if($queryResult->outputContexts){
		for($i=0; $i<count($queryResult->outputContexts);$i++) {
			if(strpos($queryResult->outputContexts[$i]->name, "mobile_valid") !== false) {
				$user_mob = $queryResult->outputContexts[$i]->parameters->parameter_name;
			}
			else if(strpos($queryResult->outputContexts[$i]->name, "like_to_order_yes") !== false) {
				$product_item = $queryResult->outputContexts[$i]->parameters->product_item[0];
				$cont_nm_arr=explode('/',$queryResult->outputContexts[$i]->name);
				$destroyContent[] = $cont_nm_arr[6];
			}

			else{
				$cont_nm_arr=explode('/',$queryResult->outputContexts[$i]->name);
				$destroyContent[] = $cont_nm_arr[6];
			}

		}
	}

	$resultData=getItemDetails($product_item);
   
    if($user_mob && $resultData){
        $category= ucfirst(strtolower($resultData->section));
        $res_message = " How many ".$category.' '.$product_item." you want to order?";
    }else{
        $res_message = " Sorry! ".$product_item." not available.What would like to order?";
    }

	$context=destroyContent($destroyContent);

	$context[]=array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/item-order-name-followup ', "lifespanCount" => 5, "parameters" => array("product_item" =>array($product_item)));
	$context[]=array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/item-order-followup', "lifespanCount" => 5, "parameters" => array("product_item" =>array($product_item)));
	$context[]=array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/ItemOrder-followup', "lifespanCount" => 5, "parameters" => array("product_item" =>array($product_item)));
	$context[]=array("name" => 'projects/'.$project_id.'/agent/sessions/'.$session_id.'/contexts/mobile_valid', "lifespanCount" => 5, "parameters" => array("product_item" =>array($product_item)));
	

	if($context)$response['outputContexts'] = $context;
	$response['fulfillmentText'] = $res_message;
	return $response;
}



?>