<?php
// Routes
date_default_timezone_set("Africa/Khartoum");
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
	$ExpectedParametersArray = array ('registration_token','version_code');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	  
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$tableName = 'passengers';
	$version = User::getValueOftheKey ("passenger_version",$this);
	
	if ($version > $data['version_code'] )
	{
		$data = array('status' => '3', 'error_msg' => "Outdated version" );
	    return $this->response->withJson($data, 200);
		
		
	}
	$GCMID = $data['registration_token'];
	
	$oldGCMID = User::getRegistrationTokenUsingEmail ($email,$tableName,$this);
	
	if ($GCMID != $oldGCMID)// means user logged in from another phone 
	{	$firebaseData = array("status" => "5");
		Firebase::sendData($firebaseData,$oldGCMID,"passenger", 2419200);
	}
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
	$data["request_id"]="";
	$passengerID= $userRow['ID'];
	$doesPassengerHaveOldRequest = Passenger::doesPassengerHavePendingOrAcceptedRequest ($passengerID,$this) ;
    // status 1 means yes he has 
	 if ($doesPassengerHaveOldRequest ['status'] == '1'  ){
		//$requestID =  $doesPassengerHaveOldRequest['ID'];
		$status = $doesPassengerHaveOldRequest['rideStatus'];
		$data["on_going_request"]=$status;		
		$requestID = $doesPassengerHaveOldRequest['ID'];
		$data["request_id"] = $requestID;
	}
	$newResponse = $response->withJson($data, 200);

	return $newResponse;
});

$app->get('/passenger_api/update/', function($request, $response, $args){
 
 
    global $userInfo;
    $email = $userInfo['email'];
	$data = $request->getQueryParams();
	$ExpectedParametersArray = array ('fullname','password','phone');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	$passenger=$data;
	$phoneStatement = $this->db->prepare('SELECT * FROM passengers where phone = ? and NOT (email = ?) ');
	$phoneStatement->execute(array($passenger['phone'],$email));
	$numberOfRows = $phoneStatement->fetchColumn(); 
	if ($numberOfRows != 0) {
		$data = array('status' => '4', 'error_msg' => 'User already exist with this phone number');
		return $response->withJson($data, 200);
	}
	
	
	$hash = password_hash($passenger['password'], PASSWORD_DEFAULT);
    // Check if user exist by checking the email field:
	$passengerStatement = $this->db->prepare('UPDATE passengers SET fullname=?, phone=?, password=? where email = ?');
	$passengerStatement->execute(array($passenger['fullname'],$passenger['phone'],$hash,$email));
	$count = $passengerStatement->rowCount();
	if ($count != 0)
    return returnSuccessResponse($this);
else {
	$data = array('status' => '2', 'error_msg' => "Unknown error occurred" );
	return $App->response->withJson($data, 500);
}

});


$app->post('/passenger_api/register/', function($request, $response, $args){

	$data = $request->getParsedBody();
	$ExpectedParametersArray = array ('email','password','gender','fullname','phone','registration_token');
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
	User::send_mail($passenger['email'],$randomCode,'welcome to Uber');
	//mail($passenger['email'], 'welcome to Uber', $randomCode);
	// Insert new user:
	$hash = password_hash($passenger['password'], PASSWORD_DEFAULT);
	$insertStatement = $this->db->prepare('INSERT INTO `passengers`(`email`, `gender`, `fullname`, `password`,`phone`,`verificationCode`,`GCMID`)  VALUES(?,?,?,?,?,?,?)');
	
	try {
		$insertStatement->execute(array( $passenger['email'],$passenger['gender'] , $passenger['fullname'],$hash,$passenger['phone'] , $randomCode,$passenger['registration_token'] ));
	} catch(PDOException $ex) {return returnDatabaseErrorResponse ($this,$ex);}
	
	User::Null_allGCMID_exceptLoggedInUser ($passenger['email'],$passenger['registration_token'],"passengers",$this);
	
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
	list($latitude ,$longitude) = explode(',',$locationString);
	
	
	
	// get the n closest drivers 
	
	$getCloseDriversSQL = 'SELECT longitude,latitude,
	111045 * DEGREES(ACOS(COS(RADIANS(?)) * COS(RADIANS(latitude))
	* COS(RADIANS(longitude) - RADIANS(?))
	+ SIN(RADIANS(?))
	* SIN(RADIANS(latitude))))
	AS distance_in_m
	FROM drivers
	WHERE 
	active= 1
	AND adminActive = 1
	AND TIMESTAMPDIFF(MINUTE,lastUpdated, UTC_TIMESTAMP()) < 5
	
	ORDER BY distance_in_m ASC
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
	 
	 $ExpectedParametersArray = array ('pickup','dest','female_driver','notes','price','request_id','time','pickup_text','dest_text');
	 
	 $areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	 
	 if (!$areSet){return returnMissingParameterDataResponse($this);}
	 
	 $data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$Request=$data;
	
	// get request parameters
	$requestID =$Request['request_id'];
	$passengerID= User::getUserID($email,$tableName,$this); //($email,$this,'passengers');	
	$passengerGender =  User::getUserGender($email,$tableName,$this);
	list($pickupLatitude ,$pickupLongitude) = explode(',',$Request['pickup']);
	list($destinationLatitude,$destinationLongitude) = explode(',',$Request['dest']);
	$time=Request::getTime ($Request['time']);
	$lastUpdatedMinute = 5;
	$genderBool= $Request['female_driver'];
	$price =  $Request['price'];
	
	// if the passenger already has a pending or accepted request, return its id 
	 
	 $doesPassengerHaveOldRequest = Passenger::doesPassengerHavePendingOrAcceptedRequest ($passengerID,$this) ;

	 if ($doesPassengerHaveOldRequest ['status'] == '1'  ){
		//$requestID =  $doesPassengerHaveOldRequest['ID'];
			//$status = $doesPassengerHaveOldRequest['rideStatus'];
					//echo $statos;
	}
	if ( Request::isAnewRequest($requestID))
	{ // does driver have a pending or accepted request
		
		$requestID= Request::insert_aRequestInRequestsTable($pickupLongitude,$pickupLatitude,$destinationLatitude,$destinationLongitude,$time,$Request['female_driver'],$Request['notes'],$Request['price'],$passengerID,$Request['pickup_text'],$Request['dest_text'],$this);
	}	
		$requestStatus = Request::getRequestStatusInRequestsTable($requestID,$this);
		
		if ($requestStatus == 'pending' )
			
		{	
		
		$driverID = Request::getClosestDriver($pickupLatitude,$pickupLongitude,$requestID,$time,$genderBool,$lastUpdatedMinute,$passengerGender,$this);
		
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
			"pickup_text" => $data['pickup_text'] ,
			"dest" => $Request['dest'],
			"dest_text" => $data['dest_text'],
			 "time" => $Request['time'],
			 "notes" => $data['notes'] , 
			 "passenger_name" => $passengerInfo['fullname'],
			 "passenger_phone" => $passengerInfo['phone'],
			 "price" =>  $price
			
			);
			//var_dump($firebaseData);
			
			Firebase::sendData($firebaseData,$GCMID,"driver",25);
			$data = array ('status' => '0', 'request_id' => $requestID );
			return $response->withJson($data,200);
		}
		else if ($requestStatus == 'accepted' )
		{
			$data = array ('status' => '6', 'error_msg' => $requestID );
			return $response->withJson($data,200);
			
		}
		else if ($requestStatus == 'completed' || $requestStatus == 'canceled')// if request is not pending, return its status 
			{
				$data = array ('status' => '5', 'error_msg' => $requestStatus );
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
 // pending -> change db, return success
 // accepted -> change db, inform driver, return success
 // completed by driver -> return success
 // if its completed 
 
	$data=$request->getQueryParams();
	
	$ExpectedParametersArray = array ('request_id');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	 
	$requestID = $data['request_id'];
	$status='canceled';
	//Request::setRequestStatusInRequestsTable($requestID,$status,$this);
	
	//Request::getRequestStatusInRequest_DriverTable
	// check if there is a driver that accepted the request 
	 $driverID = Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$this);
	if ($driverID == null) // no driver accepted can be pending(it should be pending, nodriver)
	{	
		//$driverID = Driver::getIdOfDriverWhoCompletedTheRequest($requestID,$this);
		Passenger::cancelRequestInRequests($requestID,$this);
		//Passenger::cancelRequestInRequest_Driver($requestID,$this);
		//echo "no driver accepted";
		$data = array ('status' => '0');
	return $response->withJson($data,200);
	}
	else { // some one only accepted  nigga
	Passenger::cancelRequestInRequests($requestID,$this);
	Passenger::cancelRequestInRequest_Driver($requestID,$this);
	//echo ' some nigga accepted ' ;// if there is a driver accepted the request
		// get the driver GCM using his ID
	$GCMID = User::getRegistrationTokenUsingID ($driverID,"drivers",$this);
	$firebaseData = array ("status" => "1","request_id" => $requestID);
	Firebase::sendData($firebaseData,$GCMID,"driver",2419200);
	Driver::activateDriverAfterComletingTheTrip ($driverID,$this);
	$data = array ('status' => '0');
	return $response->withJson($data,200);
	}
});

// 


$app->post('/passenger_api/arrived/', function($request, $response, $args){
	//authentication header
	
	// requestStatus = accepted -> change the DB, Firebase to driver, return success
	// requestStatus = completed -> do nothing to DB, do nothing to driver, return success 
	
	// the Firebase should only be sent when the status is only accepted but of its completed, then no need 
	// what if the trip got canceled by driver, and this user pressed arrived 
	
	$data=$request->getParsedBody();
	
	
	if (! isset ($data['request_id']))
	{
		$data = array('status' => '2', 'error_msg' => '"Unknown error occurred');
		return $response->withJson($data, 400);
	}
	
	$requestID = filter_var($data['request_id'], FILTER_SANITIZE_STRING);
	$status = Request::getRequestStatusInRequestsTable($requestID,$this);
	
	if ($status == 'completed') // by driver
	{
		return returnSuccessResponse($this);
		
	}
	else if ($status == 'canceled' || $status == 'noDriver' )
	{
		return returnSuccessResponse($this);
	}
	
	else 
	{ // accepted, canceled , noDriver, pending 
	// if its pending, he cant get to this arrived request
	// if its canceled or noDriver(can he ? do nothing as arrived: do nothing as arrived )
	// if its accepted, inform the driver, change db
    	
	$driverID = Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$this);
	//var_dump( $driverID);
	
	
	Passenger::arrivedRequestInRequests($requestID,$this);
	Passenger::arrivedRequestInRequests_driver($requestID,$this);
	
	$GCMID = User::getRegistrationTokenUsingID ($driverID,"drivers",$this);
	//var_dump($GCMID);
	$firebaseData = array ("status" => "2","request_id" => $requestID);
	Firebase::sendData($firebaseData,$GCMID,"driver",2419200);
	
	$activeBool = '1';
	$locationString = '-1';
	Driver::activateDriverAfterComletingTheTrip ($driverID,$this);
		return returnSuccessResponse($this);
	}
	
	
		
	
	
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
$unix= (int) time() ;
	$data = array("time" => $unix);
	return $response->withJson($data, 200);
	
});

$app->get('/price/', function($request, $response, $args){
	/*
	SELECT *
	FROM `prices`
	WHERE ('00:30:00' BETWEEN startTime AND endTime)
 */
 
 $data=$request->getQueryParams();

	$ExpectedParametersArray = array ('time');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	
	$time = ($data['time'] == "now") ?  date("H:i:s") : $data['time'];
	//var_dump(date("H:i:s") );
    //$time = '00:00:00';
	$priceRow = User::getPrice($time,$this);
	//$perkmKey = "perkm";
	//$perkm = User::getValueOftheKey ($perkmKey ,$this) ;
	//$perminKey = "permin";
	//$permin = User::getValueOftheKey ($perminKey  ,$this) ;
	
	//$minKey = "min";
	//$min = User::getValueOftheKey ($minKey  ,$this) ;
	$perkm = $priceRow['perkm'];
	$permin = $priceRow['permin'];
	$min = $priceRow['min'];
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
	$ExpectedParametersArray = array ('registration_token','version_code');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	$version = User::getValueOftheKey ("driver_version",$this);
	
	if ($version > $data['version_code'] )
	{
		$data = array('status' => '3', 'error_msg' => "Outdated version" );
	    return $this->response->withJson($data, 200);
		
		
	}
	
	
	$tableName = 'drivers';
	$GCMID = $data['registration_token'];
	
	
	
	$oldGCMID = User::getRegistrationTokenUsingEmail ($email,$tableName,$this);
	
	if ($GCMID != $oldGCMID)// means user logged in from another phone 
	{	$firebaseData = array("status" => "3");
		Firebase::sendData($firebaseData,$oldGCMID,"driver",2419200);
	}
	User::updateRegistrationToken ($email,$tableName,$GCMID,$this);
	User::Null_allGCMID_exceptLoggedInUser ($email,$GCMID,$tableName,$this);
	
	
	

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
	$getRidesSql='SELECT 
  rd.driverID,
  r.ID AS request_id,
  r.pickupLongitude,
  r.pickupLatitude,
  r.destinationLongitude,
  r.destinationLatitude,
  UNIX_TIMESTAMP(r.requestTime) AS requestTime,
  r.price,
  r.pickup_text,
  r.dest_text,
  r.notes,
  rd.status,
  p.fullname AS passenger_name,
  p.phone AS passenger_phone
  
FROM request_driver AS rd
INNER JOIN requests AS r ON r.ID  = rd.requestID
INNER JOIN passengers    AS p ON p.ID = r.passengerID
WHERE rd.driverID = :driverID ';
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
		$ride['request_id']= $requestRow ['request_id'];
		$ride['pickup'] = $requestRow['pickupLatitude'] . ',' . $requestRow['pickupLongitude'];
		$ride['dest'] = $requestRow['destinationLatitude'] . ',' . $requestRow['destinationLongitude'];
		$ride['time'] = $requestRow['requestTime'];
		$ride['price'] = $requestRow['price'];
		$ride['status'] = $requestRow['status'];
		$ride['notes'] = $requestRow['notes'];
		$ride['pickup_text'] = $requestRow['pickup_text'];
		$ride['dest_text'] = $requestRow['dest_text'];
		$ride['passenger_name'] = $requestRow['passenger_name']; 
		$ride['passenger_phone'] = $requestRow['passenger_phone']; 
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
	
	$requestStatus = Request::getRequestStatusInRequestsTable($requestID,$this);
	//echo $requestStatus;
	if ($requestStatus ==  'canceled' )
	{
		
			$data=array('status' => '3', 'error_msg' => 'seems as request has been accepted by another driver or canceled');
			return $response->withJson($data,400);
		
	}
	
	$driverID = User::getUserID($email,$tableName,$this);
	$PassengerId = Passenger::getPassengerID_whoMadeRequest($requestID,$this);
	//var_dump($PassengerId);
	$GCMID = User::getRegistrationTokenUsingID ($PassengerId,"passengers",$this);
	//var_dump($GCMID);
	// check if there is another driver accepted this request
	
		//$here = " am here ";
		$driverAcceptedRequestID=Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$this);
		
		// if there is no driver accepted, insert into the database and inform the passenger
		if ($driverAcceptedRequestID == null ) // if no one accepted the request before
		{
			//var_dump($here);
			// inform the passenger, firebase code 
			
			//echo $PassengerId;
			if ($acceptOrReject == "1"	){
				//var_dump($here);
				$carInfo= Driver::getDriverVehicle_Plate($driverID,$this);
	
			$driverRow = User::getNamePhoneUsingEmail ($email,"drivers",$this);
			$firebaseData = array(
			"status" => "1",
			"name" => $driverRow['fullname'] ,			
			"phone" => $driverRow['phone'],
			"vehicle" => $carInfo['model'],  
			"plate" => $carInfo['plateNumber']  , 
			"request_id" => $requestID );
			
			Firebase::sendData($firebaseData,$GCMID,"passenger",300);
			Driver::acceptRequestInRequests($requestID,$driverID,$this);
			
			Request::setTime('driverAcceptedTime',$requestID,$this);
			Driver::acceptRequestInRequests_driver($driverID,$requestID,$this);
			$activeBool = '0';
			$locationString = '-1';
			//Driver::activateDriver($email,$activeBool,$locationString,$this);
			return returnSuccessResponse($this);
			}
			else if ($acceptOrReject == "0")
			{//var_dump($here);
			// if the driver rejected the request
		    $firebaseData = array(
			"status" => "0",
			"request_id" => $requestID );
			Firebase::sendData($firebaseData,$GCMID,"passenger",30);
			return returnSuccessResponse($this);
			} 
				
			
		}
		else if ($driverAcceptedRequestID == $driverID) //if the same person accepted the request
		{
			
			
			$data=array('status' => '0', 'message' => 'you have already accepted this request');
			return $response->withJson($data,400);
			
				
		}	
		else if ($driverAcceptedRequestID != $driverID && $driverAcceptedRequestID ) // if a different person accepting the request
		{
			if ($acceptOrReject == "1"	){
			
			$data=array('status' => '3', 'error_msg' => 'seems as request has been accepted by another driver or canceled');
			return $response->withJson($data,400);
			}
			else 
			return returnSuccessResponse($this);
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
	// 
	
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
	Driver::activateDriverAfterComletingTheTrip ($driverID,$this);
	$PassengerId = Passenger::getPassengerID_whoMadeRequest($requestID,$this);
	$GCMID = User::getRegistrationTokenUsingID ($PassengerId,"passengers",$this);
		
	$firebaseData = array ("status" => "4",
	"request_id" => $requestID);
	Firebase::sendData($firebaseData,$GCMID,"passenger",2419200);
	
	
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
		 Firebase::sendData($firebaseData,$GCMID,"passenger",15);
		
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
	
	if ($status == 'completed')
	{
		// if passenger completed from his phone 
	$driverID = Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$this);
	Passenger::arrivedRequestInRequests($requestID,$this);
	Passenger::arrivedRequestInRequests_driver($requestID,$this);

	$activeBool = '1';
	$locationString = '-1';
	Driver::activateDriverAfterComletingTheTrip ($driverID,$this);
	Request::setTime('requestCompletionTime',$requestID,$this);	
	}
	
	else if ($status == 'passenger_onboard')
	{
		
	Request::setTime('passengerOnBoardTime',$requestID,$this);
	}
	else if ($status == 'on_the_way')
	{
	Request::setTime('driverOnTheWayTime',$requestID,$this);
	}
	

	
	$firebaseData = array("status" => "3", "message" => $status, "request_id" => $requestID);
	Firebase::sendData($firebaseData,$GCMID,"passenger",300);
	
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




$app->post('/driver_api/sendemail/', function($request, $response, $args){
	$email = "sabirmgd@gmail.com"; 
	$code = "123123123";
	$message= "welcome  to Uber";
	User::send_mail($email,$code,$message);
	
	
	
});

$app->post('/driver_api/testplate/', function($request, $response, $args){
	
	
	
	global $userInfo;
	$email= $userInfo['email'];
	
	$driverID = User::getUserID($email,'drivers',$this);
	
	$carInfo= Driver::getDriverVehicle_Plate($driverID,$this);
	
	var_dump($carInfo);
	
});


$app->post('/admin/update_map/', function($request, $response, $args){
	



  $sql = "SELECT 
  r.ID AS requestID,
  r.pickupLatitude,
  r.pickupLongitude,
  r.destinationLatitude,
  r.destinationLongitude,
  UNIX_TIMESTAMP(r.requestTime),
  r.requestTime,
  r.notes,
  r.price,
  r.status,
  p.ID AS passengerID,
  p.fullname AS passengerName,
  p.phone As passengerPhone,
  d.ID AS driverID,
  d.fullname AS driverName,
  d.phone AS driverPhone,
  r.driverAcceptedTime,
  r.driverOnTheWayTime,
  r.passengerOnBoardTime,
  r.requestCompletionTime,
  c.plateNumber,
  c.model
FROM requests AS r
INNER JOIN passengers AS p ON r.passengerID  = p.ID
LEFT JOIN drivers    AS d ON d.ID = r.driverID
LEFT JOIN cars As c on c.driverID = d.ID";
$sth = $this->db->prepare($sql );
$sth->execute();
$dataAraay = $sth->fetchAll();

$sql2 = " SELECT

d.ID,
d.latitude,
d.longitude,
d.fullname,
c.model,
c.plateNumber,
d.phone


from drivers as d 
INNER join cars as c where 
d.ID = c.driverID AND
	d.active= 1
	AND d.adminActive = 1
	AND TIMESTAMPDIFF(MINUTE,d.lastUpdated, UTC_TIMESTAMP()) < 5"; 

$stmt2= $this->db->prepare($sql2);
$stmt2->execute();
$drivers = $stmt2->fetchAll();



	//$tableDatabaseColumns = $tableHeader;


	// make the body 
	foreach ($dataAraay as $row) {
       
//echo " i am updated";
	//var_dump ($row);
				 $time = strtotime($row['requestTime'].' UTC');
				 $dateInLocal = date("Y/m/d H:i:s", $time);
				 list ($day,$time) = explode(" ", $dateInLocal ,2 );
				 $str_time = $time;

				$str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $str_time);

				sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);

				$time_seconds = $hours * 3600 + $minutes * 60 + $seconds;
				$day = date("d/m/Y", strtotime($day))   ;
				 
				if ($row['driverOnTheWayTime'] != null && $row['status'] != "canceled" && $row['status'] != "pending" )
			  
				{
					if ($row['passengerOnBoardTime'] != null){
						$status = "arrived_at_pickup";
						if ($row['requestCompletionTime'] != null  || $row['status'] == "completed"){
						$status='completed';
						}
					}
						
					else 
						$status = "on_the_way";
				}
				else 
					$status =$row['status'];
					
					if ($row['passengerOnBoardTime'] == null )
					{
						$passengerOnBoardTime = "";
					}
		
			      else $passengerOnBoardTime = $row['passengerOnBoardTime'];
				//echo " here";
				//echo $row['passengerOnBoardTime'];
				if ($status == "accepted" || $status == "on_the_way" || $status == "arrived_at_pickup" || $status == "noDriver" || $status == "pending")
				{
	 $output['requests'][] = array(
    "request_id" =>   $row['requestID'],
	"pickup_lat" =>	$row['pickupLatitude'],
	"pickup_lng" =>	$row['pickupLongitude'],
	"dest_lat" =>	$row['destinationLatitude'],
	"dest_long" =>	$row['destinationLongitude'],
    "time" =>   ($row['UNIX_TIMESTAMP(r.requestTime)']),
    "notes" =>   $row['notes'],
	"price" =>	$row['price'],
	"status" =>	$status,   // $status,
	"passenger_id" =>	$row['passengerID'],
	"passenger_name" =>	$row['passengerName'],
	"passenger_phone"	 => $row['passengerPhone'],
	"driver_id" =>	$row['driverID'],
	"driver_name" =>	$row['driverName'],
	"driver_phone" =>	$row['driverPhone'],
	"driver_plate" => $row['plateNumber'],
	"driver_vehicle" => $row['model']
		//$time_seconds,
		//$row['notes'],
		//$status,
		// ($row['driverAcceptedTime'] == null ) ? "" :  date("Y/m/d H:i:s", strtotime($row['driverAcceptedTime'].' UTC'))    ,
		//($row['driverOnTheWayTime'] == null) ? "" :   date("Y/m/d H:i:s", strtotime($row['driverOnTheWayTime'].' UTC'))  ,
		//($row['passengerOnBoardTime'] == null) ? "" :  date("Y/m/d H:i:s", strtotime($row['passengerOnBoardTime'].' UTC'))   ,
		///($row['requestCompletionTime']== null ) ? "" :  date("Y/m/d H:i:s", strtotime($row['requestCompletionTime'].' UTC'))  
    );
	
				}
			
    }
	
	






 
 foreach ($drivers as $driver)
 {
	 
	 $output['drivers'][]= array (
	"id" => $driver['ID'],
	"lat" => $driver['latitude'],
	 "lng" =>$driver['longitude'],
	 "name" => $driver['fullname'],
	 "vehicle" =>$driver['model'],
	 "plate" => $driver['plateNumber'],
     "phone"=> $driver['phone'],
	 
	 );
	 
	 
 }
 
 $output['status'] = "0";
  $output['time'] = "0";
  
  $gtm = (gmdate("Y-m-d H:i:s", time())); 
	$unix = strtotime ($gtm);
	
	// $unix = strtotime (gmdate("Y-m-d H:i:s", time()));
 $output['status'] = "0";
  $output['time'] = $unix;
	//$output['time'] = "test";
	
 $response->withJson($output,200);;


	
});



$app->post('/admin/direct/', function($request, $response, $args){
	
	$data=$request->getParsedBody();

	$ExpectedParametersArray = array ('request_id','driver_id');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	$requestID = $data['request_id'];
	
	
	if ( Request::isAnewRequest($requestID))
	{ // does driver have a pending or accepted request
		
		$requestID= Request::insert_aRequestInRequestsTable($pickupLongitude,$pickupLatitude,$destinationLatitude,$destinationLongitude,$time,$Request['female_driver'],$Request['notes'],$Request['price'],$passengerID,$Request['pickup_text'],$Request['dest_text'],$this);
	}	
	
		$requestStatus = Request::getRequestStatusInRequestsTable($requestID,$this);
		
		//echo "status   " . $requestStatus ;
		
		//var_dump($Request);
		if ($requestStatus == 'accepted' || $requestStatus == 'noDriver' )
			
		{	
		// get the request info 
		$sqlr = "SELECT *, UNIX_TIMESTAMP(requestTime) FROM requests WHERE ID = ? ";
		$requestStatement = $this->db->prepare($sqlr);
		$requestStatement->execute(array($requestID));
	    $Request = $requestStatement->fetch();
		
		
		$driverIDWhoAccepted = Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$this);
		
		// change the database, to pending and missed
		Request::setRequestStatusInRequestsTable($requestID,'noDriver',$this);
		Request::setRequestStatusInRequest_DriverTable($requestID,$driverIDWhoAccepted ,'missed',$this);
		
		// send a cancel GCM to the driver who accepted 
		$DriverGCMID = User::getRegistrationTokenUsingID ($driverIDWhoAccepted,"drivers",$this);
	    $firebaseData = array ("status" => "1","request_id" => $requestID);
		Firebase::sendData($firebaseData,$DriverGCMID,"driver",2419200);
		Driver::activateDriverAfterComletingTheTrip ($driverIDWhoAccepted,$this);
		
		// send to the new driver 
		$driverID = $data['driver_id'];
		
			// in case no driver 
			if ($driverID == null)
			{
				$status='noDriver';
				Request::setRequestStatusInRequestsTable($requestID,$status,$this);
				$data = array ('status' => '3', 'error_msg' => 'No driver found' );
				return $response->withJson($data,200);
			}
			
			
			// then add it to request_driver table with missed status 
			// check if this guy already got this request before 
			
			if (Request::getRequestStatusInRequest_DriverTable($requestID,$driverID,$this) == null)
			{
				Request::insert_aRequestInRequests_DriverTable($requestID,$driverID,$this);
				
                             
			}
			else 
				Request::setRequestStatusInRequest_DriverTable($requestID,$driverID ,'accepteed',$this);
			if ( (int) $Request['UNIX_TIMESTAMP(requestTime)'] < time())
			{
				$requestTime= "now";
				//var_dump("am now");
			}
			else 
			{
				$requestTime= (int)$Request['UNIX_TIMESTAMP(requestTime)'] *1000;
			}
			
			// null acceptanceTime, driver on the way time 
			  Request::nullTime('driverAcceptedTime',$requestID,$this);
			  Request::nullTime('driverOnTheWayTime',$requestID,$this);
			  Request::nullTime('passengerOnBoardTime',$requestID,$this);
			// send the notification to the guy new guy
			$tableName = "drivers";
			$GCMID = User::getRegistrationTokenUsingID ($driverID,$tableName,$this);
			$passengerInfo = User::getNamePhoneUsingID ($Request['passengerID'],"passengers",$this);
			$firebaseData = array("status" => "0",
			"request_id" => $requestID,
			"pickup" => $Request['pickupLatitude'] . "," . $Request['pickupLongitude']  ,
			"pickup_text" => $Request['pickup_text'] ,
			"dest" => $Request['destinationLatitude'] . "," . $Request['destinationLongitude'],
			"dest_text" => $Request['dest_text'],
			"time" => $requestTime,
			"notes" => $Request['notes'], 
			"passenger_name" => $passengerInfo['fullname'],
			"passenger_phone" => $passengerInfo['phone'],
			"price" =>  $Request['price']
			
			);
		//	var_dump($firebaseData);
			
			Firebase::sendData($firebaseData,$GCMID,"driver",60);
			$data = array ('status' => '0', 'request_id' => $requestID );
			return $response->withJson($data,200);
		}
		else if ($requestStatus == 'noDriver' || $requestStatus == 'pending')
		{
			$data = array ('status' => '2', 'error_msg' => $requestStatus  );
			return $response->withJson($data,500);
			
		}
		else if ($requestStatus == 'completed' || $requestStatus == 'canceled')// if request is not pending, return its status 
			{
				$data = array ('status' => '2', 'error_msg' => $requestStatus );
				return $response->withJson($data,500);
			}

	
});

$app->post('/admin/cancel/', function($request, $response, $args){

	$data=$request->getParsedBody();
	
	$ExpectedParametersArray = array ('request_id');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	 
	$requestID = $data['request_id'];
	$status='canceled';
	//Request::setRequestStatusInRequestsTable($requestID,$status,$this);
	
	//Request::getRequestStatusInRequest_DriverTable
	// check if there is a driver that accepted the request 
	 $driverID = Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$this);
	 
	 // if no driver accepted
	if ($driverID == null) // no driver accepted can be pending(it should be pending, nodriver)
	{	
		//$driverID = Driver::getIdOfDriverWhoCompletedTheRequest($requestID,$this);
		Passenger::cancelRequestInRequests($requestID,$this);
		//Passenger::cancelRequestInRequest_Driver($requestID,$this);
		//echo "no driver accepted";
		// tell the passenger 
		$PassengerId = Passenger::getPassengerID_whoMadeRequest($requestID,$this);
	$GCMID = User::getRegistrationTokenUsingID ($PassengerId,"passengers",$this);
		
	$firebaseData = array ("status" => "4",
	"request_id" => $requestID);
	Firebase::sendData($firebaseData,$GCMID,"passenger",2419200);
	
	
		$data = array ('status' => '0');
	return $response->withJson($data,200);
	}
	
	else { 
	
	// change the database 
	
	Passenger::cancelRequestInRequests($requestID,$this);
	Passenger::cancelRequestInRequest_Driver($requestID,$this);
	//echo ' some nigga accepted ' ;// if there is a driver accepted the request
		// get the driver GCM using his ID
		
		// send the driver a notification
	$GCMID = User::getRegistrationTokenUsingID ($driverID,"drivers",$this);
	$firebaseData = array ("status" => "1","request_id" => $requestID);
	Firebase::sendData($firebaseData,$GCMID,"driver",2419200);
	Driver::activateDriverAfterComletingTheTrip ($driverID,$this);
	
	
	// send the passenger a notification
	$PassengerId = Passenger::getPassengerID_whoMadeRequest($requestID,$this);
	$GCMID = User::getRegistrationTokenUsingID ($PassengerId,"passengers",$this);
		
	$firebaseData = array ("status" => "4",
	"request_id" => $requestID);
	Firebase::sendData($firebaseData,$GCMID,"passenger",2419200);
	
	
	$data = array ('status' => '0');
	return $response->withJson($data,200);
	}


});

$app->post('/admin/null/', function($request, $response, $args){
    Request::nullTime('driverAcceptedTime',212,$this);
	
	
});
?>



	