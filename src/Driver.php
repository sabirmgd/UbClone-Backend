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
		
	}
	public static function acceptOrRejecctRequestInRequests($requestID,$driverID,$acceptOrReject,$App){
	if ($acceptOrReject == '1' )
	{
	$status = 'accepted';
	Request::setRequestStatusInRequestsTable($requestID,$status,$App);
	Driver::setDriverIDinRequests($requestID,$driverID,$App);
	
	}
	else if (acceptOrReject == '0'){
	$status = 'missed';}
	}
	
	// by driver
	public static function acceptOrRejectRequestInRequests_driver($driverID,$requestID,$acceptOrReject,$App){
	
	if ($acceptOrReject == '1' )
	{
		$status = 'accepted';
	}
	else if ($acceptOrReject == '0')
	{
		$status = 'missed';
		
	}
	
	$acceptOrRejectRequestSql ="UPDATE `request_driver` 
							   SET `status`=:status
							   WHERE 
						       requestID = :requestID
						       AND driverID = :driverID";
	$acceptOrRejectRequestStatement= $App->db->prepare($acceptOrRejectRequestSql);
	$acceptOrRejectRequestStatement->bindParam(':requestID',$requestID,PDO::PARAM_INT);
	$acceptOrRejectRequestStatement->bindParam(':driverID',$driverID,PDO::PARAM_INT);
	$acceptOrRejectRequestStatement->bindParam(':status',$status,PDO::PARAM_STR);
	try{
		$acceptOrRejectRequestStatement->execute();
		return $status;
	}catch(PDOException $ex)
	{
		return $ex->getMessage();
	}	
						
	}

	
	public static function getIdOfDriverWhoAcceptedTheRequest($requestID,$App){
	 $getDriverIdSql=	"SELECT `driverID` FROM `request_driver` 
						 WHERE 
						 requestID = :requestID";
	 $getDriverIdStatement = $App->db->prepare($getDriverIdSql);
	 $getDriverIdStatement->bindParam(':requestID',$requestID,PDO::PARAM_INT);
	 $getDriverIdStatement->execute();
	 $driverID=$getDriverIdStatement->fetch()['driverID'];
	 return $driverID;
	}
	
	
	
	
	
}




?>