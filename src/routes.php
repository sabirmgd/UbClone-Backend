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
	$data = array('status' => '1', 'error_msg' => $error );
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
      // echo $userInfo['email'];
	$data = $request->getParsedBody();
	$ExpectedParametersArray = array ('registration_token');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	echo $data['registration_token'];
	$userStatement = $this->db->prepare('SELECT * FROM passengers WHERE email = ?');
	$userStatement->execute(array($userInfo['email']));

	$data = ['status' => '0' ];

	$userRow = $userStatement->fetch();
	$passenger = [
	'name' => $userRow['fullname'],
	'email' => $userRow['email'],
	'phone' => $userRow['phone'],
	'gender' => $userRow['gender'],
	];
	$data['passenger'] = $passenger;
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
		$data = array('status' => '2', 'error_msg' => 'User already exist with this email');
		return $response->withJson($data, 200);
	}

    // Check if phone exist:
	$phoneStatement = $this->db->prepare('SELECT * FROM passengers where phone = ?');
	$phoneStatement->execute(array($passenger['phone']));
	$numberOfRows = $phoneStatement->fetchColumn(); 
	if ($numberOfRows != 0) {
		$data = array('status' => '3', 'error_msg' => 'User already exist with this phone number');
		return $response->withJson($data, 200);
	}

	// if no user then generate a Random Code and send it to the user and insert it into database
	
	$randomCode = User::generateRandomCode (6);
	
	// send the user an email 
	//send_mail($passenger['email'],$randomCode,'welcome to Uber');
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
	$verificationCode=  filter_var($data['code'], FILTER_SANITIZE_STRING);
	
	// check if the user has already verified his account
	$isVerifiedStatement = $this->db->prepare('SELECT verified FROM passengers WHERE email = ?');
	//echo $userEmail;
	
	
	try {$isVerifiedStatement->execute(array($email));}
	catch(PDOException $ex) {return returnDatabaseErrorResponse ($this,$ex);}
	
	$verificationStatus= $isVerifiedStatement->fetch()['verified'];
	if ($verificationStatus == 1)
	{
		$data= array( 'status' => '2' , 'error_msg' => 'User has already verified this account');
		return $response->withJson($data, 202);
	}
	$data= array( 'status' => '0');
	return $response->withJson($data, 200);
	
	//else // if user hasn't verified his account, check the code with the generated code
	//{
		
		
		
//	}
	
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
		$driver = $longitude . ',' . $latitude;
		
		
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
	$lastUpdatedMinute = 1000;
	$genderBool= $Request['female_driver'];
	
	// if the passenger already has a pending or accepted request, return its id 
	 
	 $doesPassengerHaveOldRequest = Passenger::doesPassengerHavePendingOrAcceptedRequest ($passengerID,$this) ;

	 if ($doesPassengerHaveOldRequest ['status'] == '1'  ){
		$requestID =  $doesPassengerHaveOldRequest['ID'];
		
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
				$data = array ('status' => '3', 'error_msg' => 'it seems like drivers are not available not, please try again shortly' );
				return $response->withJson($data,200);
			}
			
			
			// then add it to request_driver table with missed status 
			
			Request::insert_aRequestInRequests_DriverTable($requestID,$driverID,$this);
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

$app->post('/passenger_api/requests/', function($request, $response, $args){

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
	Passenger::cancelRequestInRequests($requestID,$this);
	Passenger::cancelRequestInRequest_Driver($requestID,$this);
	
	$data = array ('status' => '0');
	return $response->withJson($data,200);

});

// 


$app->post('/passenger_api/arrived/', function($request, $response, $args){
	//authentication header
	$data=$request->getParsedBody();
	
	
	if (! isset ($data['request_id']))
	{
		$data = array('status' => '4', 'error_msg' => 'Invalid request');
		return $response->withJson($data, 400);
	}
	
	$requestID = filter_var($data['request_id'], FILTER_SANITIZE_STRING);
	Passenger::arrivedRequestInRequests($requestID,$this);
	Passenger::arrivedRequestInRequests_driver($requestID,$this);
	$data = array ('status' => '0');
	return $response->withJson($data,200);
});

$app->get('/time', function($request, $response, $args){
	// to be added by Islam 
	
});

$app->get('/price', function($request, $response, $args){
	
	
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
       //echo $userInfo['email'];

	$userStatement = $this->db->prepare('SELECT * FROM drivers WHERE email = ?');
	$userStatement->execute(array($userInfo['email']));

	$data = ['status' => '0' ];

	$userRow = $userStatement->fetch();
	$driver = [
	'name' => $userRow['fullname'],
	'email' => $userRow['email'],
	'phone' => $userRow['phone'],
	'gender' => $userRow['gender'],
	];
	$data['driver'] = $driver;
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
	//$driverID,$requestID,$acceptOrReject,$this
	
	$driverAcceptedRequestID=Driver::getIdOfDriverWhoAcceptedTheRequest($requestID,$this);
	echo $driverAcceptedRequestID;
	if ($driverAcceptedRequestID == null )
	{
	Driver::acceptOrRejecctRequestInRequests($requestID,$driverID,$acceptOrReject,$this);
	Driver::acceptOrRejectRequestInRequests_driver($driverID,$requestID,$acceptOrReject,$this);
	// deactivate driver 
	$activeBool = '0';
	$locationString = '-1';
	Driver::activateDriver($email,$activeBool,$locationString,$this);
	return returnSuccessResponse($this);
	}
	else if ($driverAcceptedRequestID == $driverID){
		$data=array('status' => '0', 'message' => 'you have already accepted this request');
		return $response->withJson($data,400);
		
	}
	else if ($driverAcceptedRequestID != $driverID){
		
		$data=array('status' => '3', 'error_msg' => 'Request has been accepted by another driver');
		return $response->withJson($data,400);
		
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

$app->post('/driver_api/status/', function($request, $response, $args){
	
	$data=$request->getParsedBody();
	
	$ExpectedParametersArray = array ('status','request_id');
	$areSet =  areAllParametersSet($data,$ExpectedParametersArray);
	
	if (!$areSet){return returnMissingParameterDataResponse($this);}
	
	$data = filterRequestParameters ($data,$ExpectedParametersArray);
	
	

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
	//echo $driverID;
	$requestID=$data['request_id'];
	//echo "\n";
	//echo $requestID;
	Driver::cancelRequestInRequestsTable ($requestID,$this);
	Driver::cancelRequestInRequests_DriverTable ($requestID,$driverID,$this);
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
	
	$LocationString = $data['location'];
	//echo $LocationString;
	Driver::updateDriverLocationInDriversTable ($email,$LocationString,$this);
	return returnSuccessResponse($this);
	
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



