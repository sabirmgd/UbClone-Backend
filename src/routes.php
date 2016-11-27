<?php
// Routes
require_once('User.php');
require_once('Request.php');
require_once('Passenger.php');
require_once('Driver.php');
require_once('Firebase.php');
function areAllParametersSet ($data,$parametersArray)
{	$areSet = 1;
	$parameterArrayLength = count ($parametersArray);
	
	for($i = 0; $i < $parameterArrayLength; $i++) {
    if (! isset ($data[$parametersArray[$i]])){$areSet = 0; break; }
	}
	
return $areSet; 	
}

function returnMissingParameterDataResponse($App)
{
	$data = array('status' => '4', 'error_msg' => 'Invalid request');
	return $App->response->withJson($data,400);
	
}

function returnDatabaseErrorResponse ($App,$error)
{
	$data = array('status' => '2', 'error_msg' => "Unknown error occurred" );
	return $App->response->withJson($data, 500);
}

function returnSuccessResponse ($App)
{
	$data = array('status' => '0' );
	return $App->response->withJson($data, 200);
}

function filterRequestParameters ($data,$parametersArray)
{
	$filteredData=[];
	$parameterArrayLength = count ($parametersArray);

	for($i = 0; $i < $parameterArrayLength; $i++) {
    $filteredData[$parametersArray[$i]] = filter_var($data[$parametersArray[$i]] ,FILTER_SANITIZE_STRING);
	}

	return $filteredData;
}




$app->post('/passenger_api/login/', function($request, $response, $args){

	global $userInfo;
     $email = $userInfo['email'];
	$data = $request->getParsedBody();
	$ExpectedParametersArray = array ('registration_token');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$tableName = 'passengers';
	$GCMID = $data['registration_token'];
	User::updateRegistrationToken ($email,$tableName,$GCMID,$this);
	User::Null_allGCMID_exceptLoggedInUser ($email,$GCMID,$tableName,$this);
	$token= User::getRegistrationTokenUsingEmail ($email,$tableName,$this);
	//echo $token;
	$userStatement = $this->db->prepare("SELECT * FROM passengers WHERE email = ?");
	$userStatement->execute(array($userInfo['email']));

	$data = ['status' => '0' ];

	$userRow = $userStatement->fetch();
	$passenger = [
	'fullname' => $userRow['fullname'],
	'email' => $userRow['email'],
	'phone' => $userRow['phone'],
	'gender' => $userRow['gender'],
	];
	
	$data['user'] = $passenger;
	$data["on_going_request"]="";
	
	$passengerID= $userRow['ID'];
	$doesPassengerHaveOldRequest = Passenger::doesPassengerHavePendingOrAcceptedRequest ($passengerID,$this) ;
    // status 1 means yes he has 
	 if ($doesPassengerHaveOldRequest ['status'] == '1'  ){
		//$requestID =  $doesPassengerHaveOldRequest['ID'];
		$status = $doesPassengerHaveOldRequest['rideStatus'];
		$data["on_going_request"]=$status;		
	}
	$newResponse = $response->withJson($data, 200);

	return $newResponse;
});

$app->post('/passenger_api/register/', function($request, $response, $args){

	$data = $request->getParsedBody();
	$ExpectedParametersArray = array ('email','password','gender','fullname','phone');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$passenger=$data;
	
    // Check if user exist by checking the email field:
	$passengerStatement = $this->db->prepare('SELECT * FROM passengers where email = ?');
	$passengerStatement->execute(array($passenger['email']));
	$numberOfRows = $passengerStatement->fetchColumn(); 
	if ($numberOfRows != 0) {
		$data = array('status' => '3', 'error_msg' => 'User already exist with this email');
		return $response->withJson($data, 200);
	}

    // Check if phone exist:
	$phoneStatement = $this->db->prepare('SELECT * FROM passengers where phone = ?');
	$phoneStatement->execute(array($passenger['phone']));
	$numberOfRows = $phoneStatement->fetchColumn(); 
	if ($numberOfRows != 0) {
		$data = array('status' => '4', 'error_msg' => 'User already exist with this phone number');
		return $response->withJson($data, 200);
	}

	// if no user then generate a Random Code and send it to the user and insert it into database
	
	$randomCode = User::generateRandomCode (6);
	
	// send the user an email 
	send_mail($passenger['email'],$randomCode,'welcome to Uber');
	//mail($passenger['email'], 'welcome to Uber', $randomCode);
	// Insert new user:
	$hash = password_hash($passenger['password'], PASSWORD_DEFAULT);
	$insertStatement = $this->db->prepare('INSERT INTO `passengers`(`email`, `gender`, `fullname`, `password`,`phone`,`verificationCode`)  VALUES(?,?,?,?,?,?)');
	
	try {
		$insertStatement->execute(array( $passenger['email'],$passenger['gender'] , $passenger['fullname'],$hash,$passenger['phone'] , $randomCode));
	} catch(PDOException $ex) {return returnDatabaseErrorResponse ($this,$ex);}

	$data = array('status' => '0');
	$newResponse = $response->withJson($data, 201);

	return $newResponse;
});




$app->post('/passenger_api/email_verification/', function($request, $response, $args){
	
	/* email verification compares the code sent to the code generated when registering
	if the code is identical, 
	change email verified to 1;
	*/
	global $userInfo;
	
	$data = $request->getParsedBody();
	
	$ExpectedParametersArray = array ('code');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$email= $userInfo['email'];
	$verificationCodeSent=  filter_var($data['code'], FILTER_SANITIZE_STRING);
	
	// check if the user has already verified his account
	$isVerifiedStatement = $this->db->prepare('SELECT verified,verificationCode FROM passengers WHERE email = ?');
	//echo $email;
	
	
	try {$isVerifiedStatement->execute(array($email));}
	catch(PDOException $ex) {return returnDatabaseErrorResponse ($this,$ex);}
	
	$resultRow = $isVerifiedStatement->fetch();
	
	$verificationStatus = $resultRow ['verified'];
	if ($verificationStatus == 1)
	{
		$data= array( 'status' => '3' , 'error_msg' => 'User has already verified this account');
		return $response->withJson($data, 202);
	}
	else 
	{		//echo "\n";
		$verificationCode = $resultRow['verificationCode'];
		//echo $verificationCode;
		
		if ($verificationCode == $verificationCodeSent ){
			$stmt=$this->db->prepare("UPDATE passengers SET verified = 1 WHERE email= ? ");
			$stmt->execute(array($email));
			$data= array( 'status' => '0');
			return $response->withJson($data, 200);
			
			
			
		}
		else 
			{
				$data= array( 'status' => '1' ,"error_msg" => "Authentication failure" );
			return $response->withJson($data, 401);
				
				
			}
		
		
	}
	
	
	
});



$app->get('/passenger_api/get_drivers/', function($request, $response, $args){

	global $userInfo;
	$data=$request->getQueryParams();

	$ExpectedParametersArray = array ('location','count');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$locationString=  filter_var($data['location'], FILTER_SANITIZE_STRING);
	$count = filter_var($data['count'], FILTER_SANITIZE_STRING);
	$count=intval ($count);
	list($longitude,$latitude) = explode(',',$locationString);
	
	
	
	// get the n closest drivers 
	
	$getCloseDriversSQL = 'SELECT longitude,latitude,111.045 * DEGREES(ACOS(COS(RADIANS(?)) * COS(RADIANS(latitude))
	* COS(RADIANS(longitude) - RADIANS(?))
	+ SIN(RADIANS(?))
	* SIN(RADIANS(latitude))))
	AS distance_in_km
	FROM drivers
	WHERE active=1
	ORDER BY distance_in_km ASC
	LIMIT 0,?';
	
	$getCloseDriversStatement = $this->db->prepare($getCloseDriversSQL);
	try {
		
		$getCloseDriversStatement->bindParam(1, $latitude, PDO::PARAM_STR);
		$getCloseDriversStatement->bindParam(2, $longitude, PDO::PARAM_STR);
		$getCloseDriversStatement->bindParam(3, $latitude, PDO::PARAM_STR);
		$getCloseDriversStatement->bindParam(4, $count, PDO::PARAM_INT);
		$getCloseDriversStatement->execute();
		
	}catch(PDOException $ex) {return returnDatabaseErrorResponse ($this,$ex);}
	
	
	$data = array('status' => '0', 'drivers' => []);
	$drivers = [];
	while ($row = $getCloseDriversStatement->fetch())
	{
		$longitude= $row['longitude'];
		$latitude= $row['latitude'];
		$driver = array("lat" =>  $latitude , "lng" => $longitude ) ;
		
		
		array_push($drivers, $driver);
		
	}
	
	$data['drivers']= $drivers;
	return $response->withJson($data, 200);
	
});








$app->get('/passenger_api/driver/', function ($request, $response, $args) {
	 
	 global $userInfo;
	 $email = $userInfo['email'];
	 
	 $tableName='passengers';
	 
	 $data=$request->getQueryParams();
	
	 $ExpectedParametersArray = array ('pickup','dest','female_driver','notes','price','request_id','time');
	 
	 $areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	 
	 if (!$areSet){return returnMissingParameterDataResponse($this);}
	 
	 $data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$Request=$data;
	
	// get request parameters
	$requestID =$Request['request_id'];
	$passengerID= User::getUserID($email,$tableName,$this); //($email,$this,'passengers');	
	list($pickupLongitude,$pickupLatitude) = explode(',',$Request['pickup']);
	list($destinationLatitude,$destinationLongitude) = explode(',',$Request['dest']);
	$time=Request::getTime ($Request['time']);
	$lastUpdatedMinute = 5;
	$genderBool= $Request['female_driver'];
	$price =  $Request['price'];
	
	// if the passenger already has a pending or accepted request, return its id 
	 
	 $doesPassengerHaveOldRequest = Passenger::doesPassengerHavePendingOrAcceptedRequest ($passengerID,$this) ;

	 if ($doesPassengerHaveOldRequest ['status'] == '1'  ){
		$requestID =  $doesPassengerHaveOldRequest['ID'];
			//$status = $doesPassengerHaveOldRequest['rideStatus'];
					//echo $statos;
	}
	if ( Request::isAnewRequest($requestID))
	{ // does driver have a pending or accepted request
		
		$requestID= Request::insert_aRequestInRequestsTable($pickupLongitude,$pickupLatitude,$destinationLatitude,$destinationLongitude,$time,$Request['female_driver'],$Request['notes'],$Request['price'],$passengerID,$this);
	}	
		$requestStatus = Request::getRequestStatusInRequestsTable($requestID,$this);
		
		if ($requestStatus == 'pending')
			
		{	
		
		$driverID = Request::getClosestDriver($pickupLatitude,$pickupLongitude,$requestID,$time,$genderBool,$lastUpdatedMinute,$this);
		
			// in case no driver 
			if ($driverID == null)
			{
				$status='noDriver';
				Request::setRequestStatusInRequestsTable($requestID,$status,$this);
				$data = array ('status' => '3', 'error_msg' => 'No driver found' );
				return $response->withJson($data,200);
			}
			
			
			// then add it to request_driver table with missed status 
			
			Request::insert_aRequestInRequests_DriverTable($requestID,$driverID,$this);
			// send the notification to the guy,
			$tableName = "drivers";
			$GCMID = User::getRegistrationTokenUsingID ($driverID,$tableName,$this);
			$passengerInfo = User::getNamePhoneUsingEmail ($email,"passengers",$this);
			$firebaseData = array("status" => "0",
			"request_id" => $requestID,
			"pickup" => $Request['pickup'] ,
			"pickup_text" => $Request['pickup'] ,
			"dest" => $Request['dest'],
			"dest_text" => $Request['dest'],
			 "time " => $Request['time'],
			 "notes" => $Request['notes'] , 
			 "passenger_name" => $passengerInfo['fullname'],
			 "passenger_phone" => $passengerInfo['phone'],
			 "price" =>  $price
			
			);
			//var_dump($firebaseData);
			
			Firebase::sendData($firebaseData,$GCMID,"driver");
			$data = array ('status' => '0', 'request_id' => $requestID );
			return $response->withJson($data,200);
		}
		else if ($requestStatus == 'accepted' )
		{
			$data = array ('status' => '6', 'request_id' => $requestID );
			return $response->withJson($data,200);
			
		}
		else if ($requestStatus == 'completed' || $requestStatus == 'canceled')// if request is not pending, return its status 
			{
				$data = array ('status' => '5', 'request_status' => $requestStatus );
				return $response->withJson($data,200);
			}
			
});

$app->get('/passenger_api/requests/', function($request, $response, $args){

	global $userInfo;
	$email= $userInfo['email'];
	$tableName='passengers';
	
	$passengerID= User::getUserID($email,$tableName,$this);
	$rides = Passenger::getRides($passengerID,$this);
	
	$data = array('status' => '0', 'rides' => $rides);
	return $response->withJson($data,200);

});

$app->get('/passenger_api/cancel/', function($request, $response, $args){

	$data=$request->getQueryParams();
	
	$ExpectedParametersArray = array ('request_id');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	 
	$requestID = $data['request_id'];
	$status='canceled';
	//Request::setRequestStatusInRequestsTable($requestID,$status,$this);
	
	
	// check if there is a driver that accepted the request 
	 $driverID = Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$this);
	if ($driverID == null)
	{
		Passenger::cancelRequestInRequests($requestID,$this);
		Passenger::cancelRequestInRequest_Driver($requestID,$this);
		//echo "no driver accepted";
		$data = array ('status' => '0');
	return $response->withJson($data,200);
	}
	else { 
	Passenger::cancelRequestInRequests($requestID,$this);
	Passenger::cancelRequestInRequest_Driver($requestID,$this);
	//echo ' some nigga accepted ' ;// if there is a driver accepted the request
		// get the driver GCM using his ID
	$GCMID = User::getRegistrationTokenUsingID ($driverID,"drivers",$this);
	$firebaseData = array ("status" => "1","request_id" => $requestID);
	Firebase::sendData($firebaseData,$GCMID,"driver");
	
	$data = array ('status' => '0');
	return $response->withJson($data,200);
	}
});

// 


$app->post('/passenger_api/arrived/', function($request, $response, $args){
	//authentication header
	$data=$request->getParsedBody();
	
	
	if (! isset ($data['request_id']))
	{
		$data = array('status' => '2', 'error_msg' => '"Unknown error occurred');
		return $response->withJson($data, 400);
	}
	
	$requestID = filter_var($data['request_id'], FILTER_SANITIZE_STRING);
	 $driverID = Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$this);
	var_dump( $driverID);
	Passenger::arrivedRequestInRequests($requestID,$this);
	Passenger::arrivedRequestInRequests_driver($requestID,$this);
	
	$GCMID = User::getRegistrationTokenUsingID ($driverID,"drivers",$this);
	var_dump($GCMID);
	$firebaseData = array ("status" => "2","request_id" => $requestID);
	Firebase::sendData($firebaseData,$GCMID,"driver");
	
	$activeBool = '1';
	$locationString = '-1';
	Driver::activateDriverAfterComletingTheTrip ($driverID,$this);
	return returnSuccessResponse($this);
	
	
});



$app->post('/passenger_api/token/', function($request, $response, $args){

	global $userInfo;
	$email= $userInfo['email'];
	$data=$request->getParsedBody();

	$ExpectedParametersArray = array ('registration_token');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$tableName = 'passengers';
	$GCMID = $data['registration_token'];
	User::updateRegistrationToken ($email,$tableName,$GCMID,$this);
	$data = array("status" => "0");
	return $response->withJson($data, 200);
	
});

$app->get('/time/', function($request, $response, $args){

	$gtm = (gmdate("Y-m-d H:i:s", time())); 
	$unix = strtotime ($gtm);
	$data = array("time" => $unix);
	return $response->withJson($data, 200);
	
});

$app->get('/price/', function($request, $response, $args){
	$perkmKey = "perkm";
	$perkm = User::getValueOftheKey ($perkmKey ,$this) ;
	$perminKey = "permin";
	$permin = User::getValueOftheKey ($perminKey  ,$this) ;
	
	$minKey = "min";
	$min = User::getValueOftheKey ($minKey  ,$this) ;
	
	$data = array("perkm" => $perkm , "permin" => $permin , "min" => $min );
	return $response->withJson($data, 200);
	
});



$app->post('/driver_api/register/', function($request, $response, $args){
	
	//get the data from the request
	$data = $request->getParsedBody();
	// set the request excpected paramaters 
	$ExpectedParametersArray = array ('email','password','gender','fullname','phone');
	// check if all the paramaters are set 
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	// if they are not set return the response
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	
	// if they are all set, filter them
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$driver = $data;
	
    // Check if user exist by checking the email field:
	$driverStatement = $this->db->prepare('SELECT * FROM drivers where email = ?');
	$driverStatement->execute(array($driver['email']));
	$numberOfRows = $driverStatement->fetchColumn(); 
	if ($numberOfRows != 0) {
		$data = array('status' => '2', 'error_msg' => 'User already exist with this email');
		return $response->withJson($data, 200);
	}

    // Check if phone exist:
	$phoneStatement = $this->db->prepare('SELECT * FROM drivers where phone = ?');
	$phoneStatement->execute(array($driver['phone']));
	$numberOfRows = $phoneStatement->fetchColumn(); 
	if ($numberOfRows != 0) {
		$data = array('status' => '3', 'error_msg' => 'User already exist with this phone number');
		return $response->withJson($data, 200);
	}

	// if no user then generate a Random Code and send it to the user and insert it into database
	
	$randomCode = User::generateRandomCode (6);
	
	// send the user an email 
	//send_mail($driver['email'],$randomCode,'welcome to Uber');
	//mail($driver['email'], 'welcome to Uber', $randomCode);
	// Insert new user:
	$hash = password_hash($driver['password'], PASSWORD_DEFAULT);
	$insertStatement = $this->db->prepare('INSERT INTO `drivers`(`email`, `gender`, `fullname`, `password`,`phone` , `active`)  VALUES(?,?,?,?,?,?)');
	
	try {
		$insertStatement->execute(array( $driver['email'],$driver['gender'] , $driver['fullname'],$hash,$driver['phone'] ,1));
	} catch(PDOException $ex) {
		$data = array('status' => '1', 'error_msg' => $ex->getMessage());
		return $response->withJson($data, 500);
	}

	$data = array('status' => '0');
	$newResponse = $response->withJson($data, 201);

	return $newResponse;
});


$app->post('/driver_api/login/', function($request, $response, $args){
	// auth 
	global $userInfo;
    $email = $userInfo['email'];
	$data = $request->getParsedBody();
	$ExpectedParametersArray = array ('registration_token');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$tableName = 'drivers';
	$GCMID = $data['registration_token'];
	User::updateRegistrationToken ($email,$tableName,$GCMID,$this);
	
	$token= User::getRegistrationTokenUsingEmail ($email,$tableName,$this);
	//echo "\n $token";
	$userStatement = $this->db->prepare('SELECT * FROM drivers WHERE email = ?');
	$userStatement->execute(array($userInfo['email']));

	$data = ['status' => '0' ];

	$userRow = $userStatement->fetch();
	$driver = [
	'fullname' => $userRow['fullname'],
	'email' => $userRow['email'],
	'phone' => $userRow['phone'],
	'gender' => $userRow['gender'],
	];
	$data['user'] = $driver;
	$newResponse = $response->withJson($data, 200);

	return $newResponse; 
	

});

$app->post('/driver_api/requests/', function($request, $response, $args){
	
	global $userInfo;
	$email= $userInfo['email'];
	$tableName='drivers';
	$driverID=User::getUserID($email,$tableName,$this);
	
	$rides=[];
	$getRidesSql='SELECT `ID`, `pickupLongitude`, `pickupLatitude`, `destinationLongitude`, `destinationLatitude`, UNIX_TIMESTAMP(`requestTime`) AS requestTime,  `price`, `status` , `driverID` FROM `requests` WHERE  `driverID`= :driverID';
	
	$getRidesStatement = $this->db->prepare($getRidesSql);
	$getRidesStatement->bindParam(':driverID',$driverID,PDO::PARAM_INT);
	try{
		
		$getRidesStatement->execute();
	}
	catch(PDOException $ex)
	{
		$data = array ('status' => '1', 'error_msg' => $ex->getMessage() );
		return $response->withJson($data,500);
	}
	
	//Passenger::getPassengerID_whoMadeRequest($requestID,$this)
	//$passengerRow = User::getNamePhoneUsingEmail ($email,"passengers",$this);
	$data = array('status' => '0', 'rides' => []);
	$rides = [];
	while ($requestRow =  $getRidesStatement->fetch())
	{   
		$ride['request_id']= $requestRow ['ID'];
		$ride['pickup'] = $requestRow['pickupLongitude'] . ',' . $requestRow['pickupLatitude'];
		$ride['dest'] = $requestRow['destinationLongitude'] . ',' . $requestRow['destinationLatitude'];
		$ride['time'] = $requestRow['requestTime'];
		$ride['price'] = $requestRow['price'];
		$ride['status'] = $requestRow['status'];
		$ride['passenger_name'] =  "name" ;
		$ride['passenger_phone'] = "0912300000" ;
		array_push($rides,$ride);
	}
	$data = array('status' => '0', 'rides' => $rides);
	return $response->withJson($data,200);
	

});

$app->post('/driver_api/accept/', function($request, $response, $args){

	$data=$request->getParsedBody();
	//print($data);
	$ExpectedParametersArray = array ('request_id','accepted');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$requestID = $data['request_id'];
	$acceptOrReject = $data['accepted'];
	
	global $userInfo;
	$email= $userInfo['email'];
	$tableName='drivers';

	$driverID = User::getUserID($email,$tableName,$this);
	$PassengerId = Passenger::getPassengerID_whoMadeRequest($requestID,$this);
	//var_dump($PassengerId);
	$GCMID = User::getRegistrationTokenUsingID ($PassengerId,"passengers",$this);
	//var_dump($GCMID);
	// check if there is another driver accepted this request
	if ($acceptOrReject == "1"	)
	{	
		$driverAcceptedRequestID=Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$this);
		// if there is no driver accepted, insert into the database and inform the passenger
		if ($driverAcceptedRequestID == null ) // if 
		{
			
			// inform the passenger, firebase code 
			
			//echo $PassengerId;
			
			
			$driverRow = User::getNamePhoneUsingEmail ($email,"drivers",$this);
			$firebaseData = array(
			"status" => "1",
			"name" => $driverRow['fullname'] ,			
			"phone" => $driverRow['phone'],
			"vehicle" => "",  
			"plate" => ""  , 
			"request_id" => $requestID );
			Firebase::sendData($firebaseData,$GCMID,"passenger");
			Driver::acceptRequestInRequests($requestID,$driverID,$this);
			Driver::acceptRequestInRequests_driver($driverID,$requestID,$this);
			$activeBool = '0';
			$locationString = '-1';
			Driver::activateDriver($email,$activeBool,$locationString,$this);
			return returnSuccessResponse($this);
		}
		else if ($driverAcceptedRequestID == $driverID)
		{
			$data=array('status' => '0', 'message' => 'you have already accepted this request');
			return $response->withJson($data,400);
		}	
		else if ($driverAcceptedRequestID != $driverID)
		{
			$data=array('status' => '3', 'error_msg' => 'Request has been accepted by another driver');
			return $response->withJson($data,400);
		}	
	}
	else if ($acceptOrReject == "0")
	{
		$firebaseData = array(
			"status" => "0",
			"request_id" => $requestID );
			Firebase::sendData($firebaseData,$GCMID,"passenger");

	}
	

	
	
});

$app->post('/driver_api/active/', function($request, $response, $args){
	
	global $userInfo;
	$email= $userInfo['email'];
	
	
	$data=$request->getParsedBody();
	
	$ExpectedParametersArray = array ('active','location');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	
	Driver::activateDriver ($email,$data['active'] ,$data['location'],$this);
	return returnSuccessResponse($this);
	
});




$app->get('/driver_api/cancel/', function($request, $response, $args){
	$data=$request->getQueryParams();
	//$data['request_id']
	
	
	$ExpectedParametersArray = array ('request_id');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	global $userInfo;
	$email= $userInfo['email'];
	$tableName='drivers';

	$driverID = User::getUserID($email,$tableName,$this);
	$requestID=$data['request_id'];
	
	Driver::cancelRequestInRequestsTable ($requestID,$this);
	Driver::cancelRequestInRequests_DriverTable ($requestID,$driverID,$this);
	
	$PassengerId = Passenger::getPassengerID_whoMadeRequest($requestID,$this);
	$GCMID = User::getRegistrationTokenUsingID ($PassengerId,"passengers",$this);
		
	$firebaseData = array ("status" => "4",
	"request_id" => $requestID);
	Firebase::sendData($firebaseData,$GCMID,"passenger");
	
	
	return returnSuccessResponse($this);
	
});

$app->post('/driver_api/location/', function($request, $response, $args){
	// auth 
	
	global $userInfo;
	$email= $userInfo['email'];
	
	
	$data=$request->getParsedBody();
	
	
	$ExpectedParametersArray = array ('request_id','location');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$requestID = $data['request_id'];
	$LocationString = $data['location'];
	
	//echo $LocationString;
	Driver::updateDriverLocationInDriversTable ($email,$LocationString,$this);
	if ( $requestID != '-1') // send the location to the passenger
	{
		$PassengerId = Passenger::getPassengerID_whoMadeRequest($requestID,$this);
		$GCMID = User::getRegistrationTokenUsingID ($PassengerId,"passengers",$this);
		
		$firebaseData = array ("status" => "2",
		"location" => $LocationString ,
		"request_id" => $requestID);
		 Firebase::sendData($firebaseData,$GCMID,"passenger");
		
	}
	return returnSuccessResponse($this);
	
});


$app->post('/driver_api/status/', function($request, $response, $args){

	global $userInfo;
	$email= $userInfo['email'];
	$data=$request->getParsedBody();

	$ExpectedParametersArray = array ('status','request_id');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$status = $data ['status'];
	$requestID = $data ['request_id'];
	
	$passengerID = Passenger::getPassengerID_whoMadeRequest($requestID,$this);
	$GCMID= User::getRegistrationTokenUsingID($passengerID,"passengers",$this);
	
	$firebaseData = array("status" => "3", "message" => $status);
	Firebase::sendData($firebaseData,$GCMID,"passenger");
	
	$data = array("status" => "0");
	return $response->withJson($data, 200);
	
});





$app->post('/driver_api/token/', function($request, $response, $args){

	global $userInfo;
	$email= $userInfo['email'];
	$data=$request->getParsedBody();

	$ExpectedParametersArray = array ('registration_token');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$tableName = 'drivers';
	$GCMID = $data['registration_token'];
	User::updateRegistrationToken ($email,$tableName,$GCMID,$this);
	$data = array("status" => "0");
	return $response->withJson($data, 200);
	
});
$app->post('/driver_api/testpsh/', function($request, $response, $args){
	// auth 
	//$data=$request->getParsedBody();
	//$data['request_id']
	$data = array ('status' => '0' , 'test result' => 'succeeded' );
		return $response->withJson($data,200);
});

$app->get('/driver_api/testpush/', function($request, $response, $args){
  $registrationID = "eRurufTwDO8:APA91bHVAVK-iVO9IRLoDYnb-nEoKheSJRISmg56-Vbrk_vmkMe1-CTJOxwDxEoTwMi42j4G86VJXzRxEDONJ8F43XGWnFzg9J-i5Xa6qfaI2Fo2zTjEN9z0k3Nf0PZQCHjfm7JOT88L";
  $data= array ('message' => ' you made it');
  Firebase::sendData($data,$registrationID);

});



$app->post('/driver_api/sendemail/', function($request, $response, $args){
	$email = "sabirmgd@gmail.com"; 
	$code = "123123123";
	$message= "welcome  to Uber";
	User::send_mail($email,$code,$message);
	
	
	
});



?>



