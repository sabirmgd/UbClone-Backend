<?php 

require_once('Request.php');

class Passenger extends User {
	
		public static function getRides($passengerID,$App){
		$getRidesSql='SELECT `ID`, `pickupLongitude`, `pickupLatitude`, `destinationLongitude`, `destinationLatitude`, UNIX_TIMESTAMP(`requestTime`) AS requestTime,  `price`, `status` , `passengerID` FROM `requests` WHERE  `passengerID`= :passengerID';
	
		$getRidesStatement = $App->db->prepare($getRidesSql);
		$getRidesStatement->bindParam(':passengerID',$passengerID,PDO::PARAM_INT);

		$getRidesStatement->execute();
	
	
		$rides = [];
		while ($requestRow =  $getRidesStatement->fetch())
		{   
			$ride['request_id']= $requestRow ['ID'];
			$ride['pickup'] = $requestRow['pickupLongitude'] . ',' . $requestRow['pickupLatitude'];
			$ride['dest'] = $requestRow['destinationLongitude'] . ',' . $requestRow['destinationLatitude'];
			$ride['time'] = $requestRow['requestTime'];
			$ride['price'] = $requestRow['price'];
			$ride['status'] = $requestRow['status'];
			array_push($rides,$ride);
		}
		return $rides;
	}
	
	
	public static function cancelRequestInRequests($requestID,$App){
	$status = 'canceled';
	Request::setRequestStatusInRequestsTable($requestID,$status,$App);	
	}
	
	public static function cancelRequestInRequest_Driver($requestID,$App){
	$newStatus = 'canceled';
	$driverID = Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$App);
	Request::setRequestStatusInRequest_DriverTable($requestID,$driverID ,$newStatus,$App);
	}
	
	public static function arrivedRequestInRequests($requestID,$App){
	$status = 'completed';
	Request::setRequestStatusInRequestsTable($requestID,$status,$App);	
	}
	
	
	public static function arrivedRequestInRequests_driver($requestID,$App){
	$newStatus = 'completed';
	$driverID = Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$App);
	Request::setRequestStatusInRequest_DriverTable($requestID,$driverID ,$newStatus,$App);
	}
	
	public static function doesPassengerHavePendingOrAcceptedRequest($passengerID,$App)
	{
		$getStatusOfPendingOrAcceptedRequestsSql="SELECT COUNT(*),ID,status FROM `requests` WHERE 
		`passengerID`=" .$passengerID . " AND
		`status` IN ('pending','accepted') ";
	
		$getStatusOfPendingOrAcceptedRequestsSqlStatement = $App->db->prepare($getStatusOfPendingOrAcceptedRequestsSql);
		
		$getStatusOfPendingOrAcceptedRequestsSqlStatement->execute();
		$resultRow =  $getStatusOfPendingOrAcceptedRequestsSqlStatement->fetch();
		//var_dump( $resultRow);
		 $number_of_rows = $resultRow["COUNT(*)"]; 
		$ID = $resultRow['ID'];
		$status = $resultRow['status'];
		
		if ( $number_of_rows == "0")
		{
			return (array ('status' => '0'));
		}
		else {
			//, 'status' => $status

			return (array('status' => '1' , 'ID' => $ID , 'rideStatus' => $status));
		}
		// if no pending or accepted request, return 0
		// if yes, return the status 
		
		
		
	}
	public static function getPassengerID_whoMadeRequest($requestID,$App)
	{
		$getIdSql = "SELECT PassengerID FROM requests WHERE ID = ?";
		$getSqlStatement = $App->db->prepare($getIdSql);
		$getSqlStatement->execute(array($requestID));
		$result = $getSqlStatement->fetch();
		return $result['PassengerID'];
	}
	
	
}
		
	


?>