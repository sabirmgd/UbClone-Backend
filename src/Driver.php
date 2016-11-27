<?php
require_once('Request.php');

class Driver {
	
	// setters and getters 
	var $tableName = 'drivers';
	public static function setDriverIDinRequests ($requestID,$driverID,$App)
	{
		$setDriverIDinRequestsSql = " UPDATE `requests` 
								 SET  
									 `driverID` = :driverID
								WHERE
								ID = :requestID";
		$setDriverIDinRequestsStatement= $App->db->prepare($setDriverIDinRequestsSql);
		$setDriverIDinRequestsStatement->bindParam(':requestID',$requestID,PDO::PARAM_INT);
		$setDriverIDinRequestsStatement->bindParam(':driverID',$driverID,PDO::PARAM_INT);
		$setDriverIDinRequestsStatement->execute();
	}
	public static function acceptRequestInRequests($requestID,$driverID,$App){
	$status = 'accepted';
	Request::setRequestStatusInRequestsTable($requestID,$status,$App);
	Driver::setDriverIDinRequests($requestID,$driverID,$App);
	}
	
	// by driver
	public static function acceptRequestInRequests_driver($driverID,$requestID,$App){
	
	$status = 'accepted';
	$acceptRequestSql ="UPDATE `request_driver` 
							   SET `status`=:status
							   WHERE 
						       requestID = :requestID
						       AND driverID = :driverID";
	$acceptRequestStatement= $App->db->prepare($acceptRequestSql);
	$acceptRequestStatement->bindParam(':requestID',$requestID,PDO::PARAM_INT);
	$acceptRequestStatement->bindParam(':driverID',$driverID,PDO::PARAM_INT);
	$acceptRequestStatement->bindParam(':status',$status,PDO::PARAM_STR);
	try{
		$acceptRequestStatement->execute();
		return $status;
	}catch(PDOException $ex)
	{
		return $ex->getMessage();
	}	
						
	}

	
	public static function getIdOfDriverWhoAcceptedTheRequest($requestID,$App){
	 $getDriverIdSql=	"SELECT `driverID` FROM `request_driver` 
						 WHERE 
						 requestID = :requestID
						 AND status IN ('accepted' , 'completed') ";
	 $getDriverIdStatement = $App->db->prepare($getDriverIdSql);
	 $getDriverIdStatement->bindParam(':requestID',$requestID,PDO::PARAM_INT);
	 $getDriverIdStatement->execute();
	 $row=$getDriverIdStatement->fetch();
	 $driverID = $row['driverID'];
	 return $driverID;
	}
	
	
	public static function activateDriver ($driverEmail,$activeBool,$locationString,$App){
		// get the driver with the ID
		// SET active to 1 or 0
		
		// if its 1, SET active to 1, update his last updated to now;
		
		$activateDriverSql = "UPDATE `drivers` SET 
							 `active`= :activeBool ";
		
		
		if ($activeBool == '1'){
			list($longitude,$latitude) = explode(',',$locationString);
			$lonitudeLatidudeSql = " ,`longitude`= :longitude ,
									`latitude`= :latitude " ;		
			$activateDriverSql = $activateDriverSql . $lonitudeLatidudeSql;
			$lastUpdatedSql =	" ,`lastUpdated`= CURRENT_TIMESTAMP ";
			$activateDriverSql = $activateDriverSql . $lastUpdatedSql  ;
			}
		$emailSql = " WHERE `email` = :email " ;
		$activateDriverSql = $activateDriverSql . $emailSql ;
		
		$activateDriverStatement = $App->db->prepare($activateDriverSql);
		$activateDriverStatement->bindParam(':activeBool',$activeBool,PDO::PARAM_INT);
		$activateDriverStatement->bindParam(':email',$driverEmail,PDO::PARAM_STR);
		if ($activeBool == '1'){
		$activateDriverStatement->bindParam(':longitude',$longitude,PDO::PARAM_STR);
		$activateDriverStatement->bindParam(':latitude',$latitude,PDO::PARAM_STR);
		}
		$activateDriverStatement->execute();
		
	}
	
	
	public static function activateDriverAfterComletingTheTrip ($ID,$App){

		$activateDriverSql = "UPDATE `drivers` SET 
							 `active`= 1 ";

		$IDSql = " WHERE `ID` = :ID " ;
		$activateDriverSql = $activateDriverSql . $IDSql ;
		$activateDriverStatement = $App->db->prepare($activateDriverSql);
		$activateDriverStatement->bindParam(':ID',$ID,PDO::PARAM_STR);
		$activateDriverStatement->execute();
		
	}
	
	public static function cancelRequestInRequestsTable ($requestID,$App){
		$status='canceled';
		Request::setRequestStatusInRequestsTable($requestID,$status,$App);
	}
	
	public static function cancelRequestInRequests_DriverTable ($requestID,$driverID,$App){
	$newStatus = 'canceled';
	$driverID = Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$App);
	//echo $driverID ;
	Request::setRequestStatusInRequest_DriverTable($requestID,$driverID ,$newStatus,$App);
		
	}
	
	
	public static function updateDriverLocationInDriversTable ($email,$LocationString,$App)
	{
		
	list($longitude,$latitude) = explode(',',$LocationString);
	$updateDriverLocationSql = " UPDATE `drivers` SET 
								`longitude`= :longitude ,
								`latitude`= :latitude
								 WHERE `email` = :email " ;
								 
		$updateDriverLocationStatement = $App->db->prepare($updateDriverLocationSql);
	    $updateDriverLocationStatement->bindParam(':email',$driverEmail,PDO::PARAM_STR);
		$updateDriverLocationStatement->bindParam(':longitude',$longitude,PDO::PARAM_STR);
		$updateDriverLocationStatement->bindParam(':latitude',$latitude,PDO::PARAM_STR);
		$updateDriverLocationStatement->execute();
	
	}
	
	public static function getDriverVehicle_Plate($driverID,$App)
	{
		$Sql = "SELECT `model`,`plateNumber` FROM cars 
				WHERE driverID = ? " ;
		$statement = $App->db->prepare($Sql);
		$statement->execute(array ($driverID));
		$result = $statement->fetch();
		return $result;
		
		
	}
	
	

	
}




?>