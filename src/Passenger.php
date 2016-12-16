<?php 

require_once('Request.php');

class Passenger extends User {
	
		public static function getRides($passengerID,$App){
		$getRidesSql='SELECT 
					r.ID,
					r.pickupLongitude,
					r.pickupLatitude,
					r.destinationLongitude, 
					r.destinationLatitude,
					UNIX_TIMESTAMP(r.requestTime) AS requestTime,
					r.price,
					r.status ,
					r.passengerID ,
					r.pickup_text,
					r.dest_text, 
					c.plateNumber,
					d.fullname
					FROM requests AS r 
					LEFT JOIN drivers AS d ON r.driverID = d.ID
					LEFT JOIN cars AS c on c.driverID = d.ID
				WHERE r.passengerID = :passengerID';
	
		$getRidesStatement = $App->db->prepare($getRidesSql);
		$getRidesStatement->bindParam(':passengerID',$passengerID,PDO::PARAM_INT);

		$getRidesStatement->execute();
	
	
		$rides = [];
		while ($requestRow =  $getRidesStatement->fetch())
		{   
			$ride['request_id']= $requestRow ['ID'];
			$ride['pickup'] = $requestRow['pickupLatitude'] . ',' . $requestRow['pickupLongitude'];
			$ride['dest'] = $requestRow['destinationLatitude'] . ',' . $requestRow['destinationLongitude'];
			$ride['time'] = $requestRow['requestTime'];
			$ride['price'] = $requestRow['price'];
			$ride['status'] = $requestRow['status'];
			$ride['pickup_text'] = $requestRow['pickup_text'];
			$ride['dest_text'] = $requestRow['dest_text'];
			$ride['driver_name'] = ($requestRow['fullname'] == null ) ? "" :  $requestRow['fullname'];
			$ride['driver_plate'] = ($requestRow['plateNumber'] == null ) ? "" : $requestRow['plateNumber'] ;
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