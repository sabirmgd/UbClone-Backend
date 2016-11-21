<?php
// Routes
function generateRandomCode($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}


function send_mail($email,$message,$subject)
{      
$mail = new PHPMailer;
$mail->SMTPDebug = 3;                               // Enable verbose debug output
$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = "smtp.gmail.com"; // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username='sabirmgd@gmail.com';  
$mail->Password='kooora.com100plusfuck';                         // SMTP password
$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = 465;                                    // TCP port to connect to
$mail->SetFrom('sabirmgd@gmail.com','Uber');
$mail->AddReplyTo('sabirmgd@gmail.com',"Uber");
$mail->Subject    = $subject;
$mail->MsgHTML($message);
$mail->addAddress($email, 'sabir');
$mail->isHTML(true);                                  // Set email format to HTML
$mail->Subject= $subject;
$mail->MsgHTML($message);

if(!$mail->send()) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    echo 'Message has been sent';
}
}

function getPassengerID($userEmail,$App){
		$getUserIDSql = 'SELECT ID FROM passengers WHERE email=?';
		$getUserIDStatement = $App->db->prepare($getUserIDSql);
		try{
			$getUserIDStatement->execute(array($userEmail));
			$passengerID= $getUserIDStatement->fetch()['ID'];
			return $passengerID;
		}catch (PDOException $ex){
			return $ex->getMessage();
		}
	
}

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

function cancelRequestInRequests_driver($requestID,$App){
	$cancelRequestSql ="UPDATE `request_driver` SET `status`='canceled'
						WHERE 
						requestID = :requestID
						AND
						status ='accepted'";
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


function arrivedRequestInRequests($requestID,$App){
	$cancelRequestSql = " UPDATE `requests` SET `status`= 'arrived' WHERE ID = :requestID";
	$cancelRequestStatement= $App->db->prepare($cancelRequestSql);
	$cancelRequestStatement->bindParam(':requestID',$requestID,PDO::PARAM_INT);
	
	try{
		$cancelRequestStatement->execute();
		return "arrived";
	}catch(PDOException $ex)
	{
		return $ex->getMessage();
	}
	
	
}


function arrivedRequestInRequests_driver($requestID,$App){
	$cancelRequestSql ="UPDATE `request_driver` SET `status`='arrived'
						WHERE 
						requestID = :requestID
						AND
						status ='accepted'";
	$cancelRequestStatement= $App->db->prepare($cancelRequestSql);
	$cancelRequestStatement->bindParam(':requestID',$requestID,PDO::PARAM_INT);
	
	try{
		$cancelRequestStatement->execute();
		return "arrived";
	}catch(PDOException $ex)
	{
		return $ex->getMessage();
	}	
						
}



$app->post('/passenger_api/login/', function($request, $response, $args){

	global $userInfo;
      // echo $userInfo['email'];

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
	
	if (!(isset($data['email']) 
		&& isset($data['password']) 
	    && isset($data['gender']) 
		&& isset($data['phone'])
		&& isset($data['fullname'])
		)){
		$data = array('status' => '4', 'error_msg' => 'Invalid request');
		return $response->withJson($data, 400);
	}
	
	
	$passenger = [];
	$passenger['email'] = filter_var($data['email'], FILTER_SANITIZE_STRING);
	$passenger['password'] = filter_var($data['password'], FILTER_SANITIZE_STRING);
	$passenger['gender'] = filter_var($data['gender'], FILTER_SANITIZE_STRING);
	$passenger['phone'] = filter_var($data['phone'], FILTER_SANITIZE_STRING);
	$passenger['fullname'] = filter_var($data['fullname'], FILTER_SANITIZE_STRING);

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
	
	$randomCode = generateRandomCode (6);
	
	// send the user an email 
	//send_mail($passenger['email'],$randomCode,'welcome to Uber');
	//mail($passenger['email'], 'welcome to Uber', $randomCode);
	// Insert new user:
	$hash = password_hash($passenger['password'], PASSWORD_DEFAULT);
	$insertStatement = $this->db->prepare('INSERT INTO `passengers`(`email`, `gender`, `fullname`, `password`,`phone`,`verificationCode`)  VALUES(?,?,?,?,?,?)');
	
	try {
		$insertStatement->execute(array( $passenger['email'],$passenger['gender'] , $passenger['fullname'],$hash,$passenger['phone'] , $randomCode));
	} catch(PDOException $ex) {
		$data = array('status' => '1', 'error_msg' => $ex->getMessage());
		return $response->withJson($data, 500);
	}

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
	
	if (!(isset($data['code']) 
		)){
		$data = array('status' => '4', 'error_msg' => 'Invalid request');
		return $response->withJson($data, 400);
	}
	
	
	
	$email= $userInfo['email'];
	$verificationCode=  filter_var($data['code'], FILTER_SANITIZE_STRING);
	
	// check if the user has already verified his account
	$isVerifiedStatement = $this->db->prepare('SELECT verified FROM passengers WHERE email = ?');
	//echo $userEmail;
	
	
	try {
		$isVerifiedStatement->execute(array($email));
	}
	catch (PDOException $ex){
		$data = array('status' => '1', 'error_msg' => $ex->getMessage());
		return $response->withJson($data, 500);
		
	}
	
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
	if (! (isset($data['location'])&& 
		   isset($data['count'])
	   )){
		$data= array('status' => '4', 'error_msg' => 'Invalid request');
		return $response->withJson($data,400);
	}
	
	$locationString=  filter_var($data['location'], FILTER_SANITIZE_STRING);
	$count = filter_var($data['count'], FILTER_SANITIZE_STRING);
	$count=intval ($count);
	list($longitude,$latitude) = explode(',',$locationString);
	//echo $longitude, " ", $latitude;
	
	
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
		
	}catch (PDOException $ex)
	{
		$data = array('status' => '1', 'error_msg' => $ex->getMessage());
		return $response->withJson($data, 500);
		
	}
	
	
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
	 $email= $userInfo['email'];
	 //echo  $email;
   // check that all the data is there
	$data=$request->getQueryParams();
	if (!(isset($data['pickup']) 
		&& isset($data['dest']) 
	    && isset($data['female_driver']) 
		&& isset($data['notes'])
		&& isset($data['price'])
		&& isset($data['request_id'])
		&& isset($data['time'])
		)){
		$data = array('status' => '4', 'error_msg' => 'Invalid request');
		return $response->withJson($data, 400);
	}
	
	// filter the request strings
	
	$CarRequest = [];
	$CarRequest['request_id'] = filter_var($data['request_id'], FILTER_SANITIZE_STRING);
	$CarRequest['pickup'] = filter_var($data['pickup'], FILTER_SANITIZE_STRING);
	$CarRequest['dest'] = filter_var($data['dest'], FILTER_SANITIZE_STRING);
	$CarRequest['female_driver'] = filter_var($data['female_driver'], FILTER_SANITIZE_STRING);
	$CarRequest['notes'] = filter_var($data['notes'], FILTER_SANITIZE_STRING);
	$CarRequest['price'] = filter_var($data['price'], FILTER_SANITIZE_STRING);
	$CarRequest['time'] = filter_var($data['time'], FILTER_SANITIZE_STRING);
	
	//echo $CarRequest['time'];
	// check if the ID is equal -1, if its  -1, then that means this is the first time this request is sent 
	// first time request
	$requestID =$CarRequest['request_id'];
	if ( $CarRequest['request_id'] == '-1')
	{
		// get the ID of the passenger
	
		$passengerID= getPassengerID ($email,$this);
		
		list($pickupLongitude,$pickupLatitude) = explode(',',$CarRequest['pickup']);
		list($destinationLatitude,$destinationLongitude) = explode(',',$CarRequest['dest']);
		
		if ($CarRequest['time'] == 'now')
		{  // date_default_timezone_set("Asia/Kuala_Lumpur");
			$date = date_create();
			$time =	 date_format($date, 'Y-m-d H:i:s');
			//echo $time;
		}
		else {
			$time = date('Y-m-d H:i:s', $CarRequest['time']);
		
			//echo $time;
			}
		
	
		//$time = strtotime($CarRequest['time']);
		
		$createRequestSql= "INSERT INTO `requests`(`pickupLongitude`, `pickupLatitude`, `destinationLongitude`, `destinationLatitude`, `requestTime`, `femaleDriver`, `notes`, `price`, `passengerID`) VALUES (?,?,?,?,?,?,?,?,?)";
		//$getLastIDSql='SELECT LAST_INSERT_ID();';
		$createRequestStatement = $this->db->prepare($createRequestSql);
		
		try // insert into the requests table 
		{	
			$createRequestStatement->execute(array ($pickupLongitude,$pickupLatitude,$destinationLatitude,$destinationLongitude,$time,$CarRequest['female_driver'],$CarRequest['notes'],$CarRequest['price'],$passengerID));
			$requestID = $this->db->lastInsertId();
			//$data = array ('status' => '0', 'request_id' => $requestID );
			
		}catch (PDOException $ex){
			$data = array ('status' => '1', 'error_msg' => $ex->getMessage() );
			return $response->withJson($data,500);
		}
		
	}
	 
	 
	 
	 // check if the request is still pending 
	 
		$isStatusPendingSql = "SELECT `status` FROM `requests` 
							WHERE 
							ID = :requestID";
							
		$isStatusPendingStatement= $this->db->prepare($isStatusPendingSql);
		$isStatusPendingStatement->bindParam(":requestID",$requestID,PDO::PARAM_INT);
		
		try{
			$isStatusPendingStatement->execute();
			$requestStatus= $isStatusPendingStatement->fetch()['status'];
			
		}
		catch(PDOException $ex){
				$data = array ('status' => '1', 'error_msg' => $ex->getMessage() );
				return $response->withJson($data,500);
				}
		
		
		if ($requestStatus == 'pending'){
		
	 // if this is not the first time for this request 
	 //  find the driver, and insert an entery in the request_driver 
			
			$getCloseDriversDidntMissSql=' SELECT  * , 111.045 * DEGREES(ACOS(COS(RADIANS(:latitude)) * COS(RADIANS(latitude))
			* COS(RADIANS(longitude) - RADIANS(:longitude))
			+ SIN(RADIANS(:latitude))
			* SIN(RADIANS(latitude))))
			AS distance_in_km 
			FROM drivers
			WHERE   
			ID NOT IN (SELECT driverID FROM request_driver
                  WHERE status IN ("missed","rejected") AND
                  requestID = :requestID)
			AND active= 1
			AND TIMESTAMPDIFF(MINUTE,lastUpdated, NOW()) < 1000
			AND ID NOT IN (SELECT driverID FROM requests WHERE ABS (TIMESTAMPDIFF(MINUTE,:timeo,requestTime) ) < 60
						  AND status="accepted")';
			
			$genderSql = "";
			if ($CarRequest['female_driver'] == "1")
			{
			$genderSql = " AND gender =	:female_driver";	
			}
			
			$orderAndLimtQuery = " ORDER BY distance_in_km ASC LIMIT 0,1";
			
			$getClosestDriverQuery=$getCloseDriversDidntMissSql . $genderSql . $orderAndLimtQuery;
			
			$getCloseDriverStatment=$this->db->prepare($getClosestDriverQuery);
			
			$getCloseDriverStatment->bindParam(':latitude', $pickupLatitude, PDO::PARAM_STR);
			$getCloseDriverStatment->bindParam(':longitude', $pickupLongitude, PDO::PARAM_STR);
			$getCloseDriverStatment->bindParam(':requestID', $requestID, PDO::PARAM_INT);
			$getCloseDriverStatment->bindParam(':timeo', $time, PDO::PARAM_STR);
			if ($CarRequest['female_driver'] == "1"){
				$gender ='female';
			$getCloseDriverStatment->bindParam(':female_driver', $gender, PDO::PARAM_STR);
			}
			
			try{
			$getCloseDriverStatment->execute();
			$driverID=$getCloseDriverStatment->fetch()['ID'];
			// in case no driver 
			if ($driverID == null)
			{
				
				$noDriverSql = ' UPDATE requests SET `status`= "noDriver"
								WHERE 
								ID = :requestID';
				$noDriverStatement = $this->db->prepare($noDriverSql);
				$noDriverStatement->bindParam(':requestID',$requestID,PDO::PARAM_INT);
				
				try{
					$noDriverStatement->execute();
					$data = array ('status' => '3', 'error_msg' => 'it seems like drivers are not available not, please try again shortly' );
					return $response->withJson($data,200);
				}
				catch(PDOException $ex){
					$data = array ('status' => '1', 'error_msg' => $ex->getMessage() );
					return $response->withJson($data,500);
			
					}
				
				
			}
			
			}
			
			catch(PDOException $ex){
			$data = array ('status' => '1', 'error_msg' => $ex->getMessage() );
			return $response->withJson($data,500);
			
		   }
			// then add it to request_driver table with missed status 
			
			$insertToDriverRequestSql='INSERT INTO `request_driver`(`requestID`, `driverID`) VALUES (:requestID,:driverID)';
			$insertToDriverRequestStatement= $this->db->prepare($insertToDriverRequestSql);
		
			$insertToDriverRequestStatement->bindParam(':requestID' ,$requestID , PDO::PARAM_INT);
			$insertToDriverRequestStatement->bindParam(':driverID' ,$driverID ,PDO::PARAM_INT);
		
			try{
			$insertToDriverRequestStatement->execute();
			$data = array ('status' => '0', 'request_id' => $requestID );
			return $response->withJson($data,200);
			}
			catch(PDOException $ex){
			$data = array ('status' => '1', 'error_msg' => $ex->getMessage() );
			return $response->withJson($data,500);
			
		}
		
		}
		else 
			
			{
				$data = array ('status' => '5', 'request_status' => $requestStatus );
				return $response->withJson($data,200);
				
			}
			
});

$app->get('/passenger_api/requests/', function($request, $response, $args){

	global $userInfo;
	$email= $userInfo['email'];
	$passengerID=getPassengerID($email,$this);
	
	$rides=[];
	$getRidesSql='SELECT `ID`, `pickupLongitude`, `pickupLatitude`, `destinationLongitude`, `destinationLatitude`, UNIX_TIMESTAMP(`requestTime`) AS requestTime,  `price`, `status` , `passengerID` FROM `requests` WHERE  `passengerID`= :passengerID';
	
	$getRidesStatement = $this->db->prepare($getRidesSql);
	$getRidesStatement->bindParam(':passengerID',$passengerID,PDO::PARAM_INT);
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

$app->get('/passenger_api/cancel/', function($request, $response, $args){
	
	$data=$request->getQueryParams();
	//print($data);
	if (! isset ($data['request_id']))
	{
		$data = array('status' => '4', 'error_msg' => 'Invalid request');
		return $response->withJson($data, 400);
	}
	$requestID = filter_var($data['request_id'], FILTER_SANITIZE_STRING);
	$cancelRequestResult = cancelRequestInRequests ($requestID,$this);
	if ($cancelRequestResult == 'canceled')
	{	$cancelRequestResult = cancelRequestInRequests_driver($requestID,$this);
		if ($cancelRequestResult == 'canceled')
		{
			$data = array ('status' => '0');
			return $response->withJson($data,200);
		}
		else {
			$data = array ('status' => '1' , 'error_msg' => $cancelRequestResult );
			return $response->withJson($data,400);
		}
	}
	else {
		$data = array ('status' => '1' , 'error_msg' => $cancelRequestResult );
		return $response->withJson($data,400);
	}
	
});

// 


$app->post('/passenger_api/arrived/', function($request, $response, $args){
	//authentication header
	$data=$request->getParsedBody();
	//$data=$request->getQueryParams();
	
	if (! isset ($data['request_id']))
	{
		$data = array('status' => '4', 'error_msg' => 'Invalid request');
		return $response->withJson($data, 400);
	}
	
	$requestID = filter_var($data['request_id'], FILTER_SANITIZE_STRING);
	$arrivedRequestResult = arrivedRequestInRequests ($requestID,$this);
	if ($arrivedRequestResult == 'arrived')
	{	$arrivedRequestResult = arrivedRequestInRequests_driver($requestID,$this);
		if ($arrivedRequestResult == 'arrived')
		{
			$data = array ('status' => '0');
			return $response->withJson($data,200);
		}
		else {
			$data = array ('status' => '1' , 'error_msg' => $arrivedRequestResult );
			return $response->withJson($data,400);
		}
	}
	else {
		$data = array ('status' => '1' , 'error_msg' => $arrivedRequestResult );
		return $response->withJson($data,400);
	}
	
});

$app->get('/time', function($request, $response, $args){
	// to be added by Islam 
	
});

$app->get('/price', function($request, $response, $args){
	// to be added by Islam 
	
});

$app->post('/driver_api/register/', function($request, $response, $args){

	$data = $request->getParsedBody();
	
	if (!(isset($data['email']) 
		&& isset($data['password']) 
	    && isset($data['gender']) 
		&& isset($data['phone'])
		&& isset($data['fullname'])
		)){
		$data = array('status' => '4', 'error_msg' => 'Invalid request');
		return $response->withJson($data, 400);
	}
	
	
	$driver = [];
	$driver['email'] = filter_var($data['email'], FILTER_SANITIZE_STRING);
	$driver['password'] = filter_var($data['password'], FILTER_SANITIZE_STRING);
	$driver['gender'] = filter_var($data['gender'], FILTER_SANITIZE_STRING);
	$driver['phone'] = filter_var($data['phone'], FILTER_SANITIZE_STRING);
	$driver['fullname'] = filter_var($data['fullname'], FILTER_SANITIZE_STRING);

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
	
	$randomCode = generateRandomCode (6);
	
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
	/* auth 
	global $userInfo;
      // echo $userInfo['email'];

	$userStatement = $this->db->prepare('SELECT * FROM drivers WHERE email = ?');
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
	
*/
});

$app->post('/driver_api/requests/', function($request, $response, $args){
	// auth 
	$data=$request->getParsedBody();
	// return the requests and their statuses
	

});

$app->post('/driver_api/accept/', function($request, $response, $args){
	// auth 
	$data=$request->getParsedBody();
	//$data['request_id']
	//$data['accepted'] 
	
	
});

$app->post('/driver_api/active/', function($request, $response, $args){
	// auth 
	$data=$request->getParsedBody();
	//$data['active']
	//$data['location'] 
	
	
});

$app->post('/driver_api/status/', function($request, $response, $args){
	// auth 
	$data=$request->getParsedBody();
	//$data['status']
	//$data['request_id']

});



$app->get('/driver_api/cancel/', function($request, $response, $args){
	$data=$request->getQueryParams();
	//$data['request_id']
});

$app->post('/driver_api/location/', function($request, $response, $args){
	// auth 
	$data=$request->getParsedBody();
	//$data['request_id']
	//$data['location']
});


$app->post('/driver_api/testAuth/', function($request, $response, $args){
	// auth 
	//$data=$request->getParsedBody();
	//$data['request_id']
	$data = array ('status' => '0' , 'test result' => 'succeeded' );
		return $response->withJson($data,200);
});







