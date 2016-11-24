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
	$oldStatus = 'accepted';
	Request::setRequestStatusInRequest_DriverTable ($requestID, null,$oldStatus,$newStatus,$App);
	}
	
	public static function arrivedRequestInRequests($requestID,$App){
	$status = 'completed';
	
	Request::setRequestStatusInRequestsTable($requestID,$status,$App);	
	}
	
	
	public static function arrivedRequestInRequests_driver($requestID,$App){
	$newStatus = 'completed';
	$oldStatus = 'accepted';
	Request::setRequestStatusInRequest_DriverTable ($requestID, null,$oldStatus,$newStatus,$App);
	}
}
		
	


?>