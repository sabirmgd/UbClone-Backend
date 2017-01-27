<?php

class Request{
	public static function isAnewRequest($requestID){
				if ($requestID == '-1')
		{return 1;}
		else {return 0;}
	}
			
	public static function getRequestStatusInRequestsTable($requestID,$App){
		
		$getRequestStatusSql = "SELECT `status` FROM `requests` 
							WHERE 
							ID = :requestID";
							
		$getRequestStatusStatement= $App->db->prepare($getRequestStatusSql);
		$getRequestStatusStatement->bindParam(":requestID",$requestID,PDO::PARAM_INT);
		$getRequestStatusStatement->execute();
		$requestStatus= $getRequestStatusStatement->fetch()['status'];
		return $requestStatus;
	}
	
	public static function setRequestStatusInRequestsTable($requestID,$newStatus,$App){
		$setStatusSql =  " UPDATE `requests` SET `status`= :newStatus WHERE
			        ID = :requestID ";
		$setRequestStatusStatement= $App->db->prepare($setStatusSql);
		$setRequestStatusStatement->bindParam(":requestID",$requestID,PDO::PARAM_INT);	
		$setRequestStatusStatement->bindParam(":newStatus",$newStatus,PDO::PARAM_STR);	
		$setRequestStatusStatement->execute();		
			}
			
			
	public static function getRequestStatusInRequest_DriverTable($requestID,$driverID,$App){
		
		$getRequestStatusSql = "SELECT `status` FROM `request_driver` 
							WHERE 
							requestID = :requestID AND
							driverID = :driverID";
							
		$getRequestStatusStatement= $App->db->prepare($getRequestStatusSql);
		$getRequestStatusStatement->bindParam(":requestID",$requestID,PDO::PARAM_INT);
		$getRequestStatusStatement->bindParam(":driverID",$driverID,PDO::PARAM_INT);
		$getRequestStatusStatement->execute();
		$requestStatus= $getRequestStatusStatement->fetch()['status'];
		return $requestStatus;
	}
	
	
	public static function setRequestStatusInRequest_DriverTable($requestID,$driverID ,$newStatus,$App){
		

		$setStatusSql = " UPDATE request_driver SET `status`=  :newStatus
		WHERE requestID = :requestID 
		AND driverID = :driverID ";
		
	
		//echo $setStatusSql;
		$setRequestStatusStatement= $App->db->prepare($setStatusSql);
		$setRequestStatusStatement->bindParam(":requestID",$requestID,PDO::PARAM_INT);
		$setRequestStatusStatement->bindParam(":newStatus",$newStatus,PDO::PARAM_STR);
		$setRequestStatusStatement->bindParam(":driverID",$driverID,PDO::PARAM_INT);
		
		$setRequestStatusStatement->execute();
			}
			
	public static function isRequestPendingInRequestsTable($requestID,$App){
		   $status = Request::getRequestStatusInRequestsTable($requestID,$App);
		   if ($status == 'pending' )
		   {
			   return 1;
		   }
		   
		   else 
			   return 0;
	}
	
	public static function isRequestCanceledInRequestsTable($requestID,$App){
		$status = Request::getRequestStatusInRequestsTable($requestID,$App);
		   if ($status == 'canceled' )
		   {
			   return 1;
		   }
		   
		   else 
			   return 0;
		
	}
	
	public static function isRequestAcceptedInRequestsTable($requestID,$App){
		$status = Request::getRequestStatusInRequestsTable($requestID,$App);
		   if ($status == 'accepted' )
		   {
			   return 1;
		   }
		   
		   else 
			   return 0;
		
		
	}
	


	public static function getClosestDriver($pickupLatitude,$pickupLongitude,$requestID,$time,$genderBool,$lastActiveMinutes,$passengerGender,$App){
		$getCloseDriversDidntMissSql=' SELECT  * ,
		111.045 * DEGREES(ACOS(COS(RADIANS(:latitude)) * COS(RADIANS(latitude))
			* COS(RADIANS(longitude) - RADIANS(:longitude))
			+ SIN(RADIANS(:latitude))
			* SIN(RADIANS(latitude))))
			AS distance_in_km 
			FROM drivers
			WHERE   
			ID NOT IN (SELECT driverID FROM request_driver
                  WHERE status IN ("missed") AND
                  requestID = :requestID)
			AND active= 1
			AND adminActive = 1
			AND TIMESTAMPDIFF(MINUTE,lastUpdated, UTC_TIMESTAMP()) < :lastActiveMinutes
			AND ID NOT IN (SELECT driverID FROM requests WHERE ABS (TIMESTAMPDIFF(MINUTE,:timeo,requestTime) ) < 60
						  AND status="accepted")';
			
			$genderSql = "";
			
			if ($genderBool == "1" || (  $genderBool == "0" && $passengerGender =="male")              )
			{
			$genderSql = " AND gender =	:female_driver";	
			}
			
			$orderAndLimtQuery = " ORDER BY distance_in_km ASC LIMIT 0,1";
			
			$getClosestDriverQuery=$getCloseDriversDidntMissSql . $genderSql . $orderAndLimtQuery;
			
			$getCloseDriverStatment=$App->db->prepare($getClosestDriverQuery);
			
			$getCloseDriverStatment->bindParam(':latitude', $pickupLatitude, PDO::PARAM_STR);
			$getCloseDriverStatment->bindParam(':longitude', $pickupLongitude, PDO::PARAM_STR);
			$getCloseDriverStatment->bindParam(':requestID', $requestID, PDO::PARAM_INT);
			$getCloseDriverStatment->bindParam(':timeo', $time, PDO::PARAM_STR);
			$lastActiveMinutes=intval($lastActiveMinutes);
			$getCloseDriverStatment->bindParam(':lastActiveMinutes', $lastActiveMinutes, PDO::PARAM_INT);
			if ($genderBool == "1"){
				$gender ='female';
			$getCloseDriverStatment->bindParam(':female_driver', $gender, PDO::PARAM_STR);
			}
			if ($genderBool == "0" && $passengerGender =="male"){
				$gender ='male';
			$getCloseDriverStatment->bindParam(':female_driver', $gender, PDO::PARAM_STR);
			}
			
			try{
			$getCloseDriverStatment->execute();
			$driverID=$getCloseDriverStatment->fetch()['ID'];
			return $driverID;
			}catch (PDOException $ex)
			{
				return $ex->getMessage();
			}
		
	}
	
	public static function getTime($time){ //either 'now' or a time 
		if ($time == 'now')
		{  
			//$date = date_create();
			//$time =	 date_format($date, 'Y-m-d H:i:s');
			$time = (gmdate("Y-m-d H:i:s", time()));
			return $time;
		}
		else {
			$time =(int) ($time/1000);
			$time = gmdate('Y-m-d H:i:s', $time);
			return $time ;
			}
	}
	
	public static function insert_aRequestInRequestsTable($pickupLongitude,$pickupLatitude,$destinationLatitude,$destinationLongitude,$time,$femaleDriver,$notes,$price,$passengerID,$pickup_text,$dest_text,$App){
	
		$createRequestSql= "INSERT INTO `requests`(`pickupLongitude`, `pickupLatitude`, `destinationLongitude`, `destinationLatitude`, `requestTime`, `femaleDriver`, `notes`, `price`, `passengerID` , `pickup_text` ,`dest_text`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
		$createRequestStatement = $App->db->prepare($createRequestSql);
		
		try // insert into the requests table 
		{	
			$createRequestStatement->execute(array ($pickupLongitude,$pickupLatitude,$destinationLongitude ,$destinationLatitude,$time,$femaleDriver,$notes,$price,$passengerID,$pickup_text,$dest_text));
			$requestID = $App->db->lastInsertId();
			return $requestID ;
			
		}catch(PDOException $ex) {return $ex->getMessage();}
	}
	
	public static function insert_aRequestInRequests_DriverTable($requestID,$driverID,$App){
		    $insertToDriverRequestSql='INSERT INTO `request_driver`(`requestID`, `driverID`) VALUES (:requestID,:driverID)';
			$insertToDriverRequestStatement= $App->db->prepare($insertToDriverRequestSql);
		
			$insertToDriverRequestStatement->bindParam(':requestID' ,$requestID , PDO::PARAM_INT);
			$insertToDriverRequestStatement->bindParam(':driverID' ,$driverID ,PDO::PARAM_INT);
		
			$insertToDriverRequestStatement->execute();
	}
	

public static function setTime ($columnName,$requestID,$App)
{
$time = (gmdate("Y-m-d H:i:s", time()));
//echo $time;
	$sql = "UPDATE requests SET  $columnName = ? WHERE ID = $requestID ";
	$stmt = $App->db->prepare($sql);
	$stmt->execute(array( $time));
	
}	

public static function nullTime ($columnName,$requestID,$App)
{
$time = null;
//echo $time;
	$sql = "UPDATE requests SET $columnName = ? WHERE ID = $requestID ";
	$stmt = $App->db->prepare($sql);
	$stmt->execute(array( $time));
	
}	
    	
	
}


?>