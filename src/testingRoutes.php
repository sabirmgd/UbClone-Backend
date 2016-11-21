<?php
function cancelRequestInRequests($requestID,$App){
	$cancelRequestSql = " UPDATE `requests` SET `status`= 'canceled' WHERE ID = :requestID";
	$cancelRequestStatement= $App->db->prepare($cancelRequestSql);
	$cancelRequestStatement->bindParam(':requestID',$requestID,PDO::PARAM_INT);
	
	try{
		$cancelRequestStatement->execute();
		return "canceled";
	}catch(PDOException $ex)
	{
		return $ex->getMessage();
	}
	
	
}


	$data=$request->getParsedBody();
	
	if (! isset ($data['request_id']))
	{
		$data = arra y('status' => '4', 'error_msg' => 'Invalid request');
		return $response->withJson($data, 400);
	}
	
	$requestID = filter_var($data['request_id'], FILTER_SANITIZE_STRING);
	
	$cancelRequestResult = cancelRequestInRequests ($requestID,$this);
	if ($cancelRequestResult == 'canceled')
	{
		  $data = array ('status' => '0');
		return $response->withJson($data,200);
		
	}
	
	else 
	{
		$data = array ('status' => '0' , 'error_msg' => $cancelRequestResult );
		return $response->withJson($data,400);
	}
?>