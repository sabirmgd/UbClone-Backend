<?php

class Firebase{
	
	public static function sendData($data,$registrationID,$to){
	if ($to == 'driver')
	{
			$apiKey = "AIzaSyAzMqsMU9Lfu2Yx5Dc-JgSSgMrrqZdysQQ";
	}
	else 
	{
			$apiKey = "AIzaSyDUbsNrE7v_YlWQLE-2ZEQ3GO0WMZikAK4" ;
	}
	//"registration_token
	//$apiKey = "AIzaSyAJAYksJUwHGCR2RC7WLatF7mb5Ow_08lM";
	
    // Replace with the real client registration IDs
    $registrationIDs = array($registrationID);

    // Message to be sent
    $message = "driver canceled the trip";

    // Set POST variables
    $url = 'https://android.googleapis.com/gcm/send';

    $fields = array(
        'registration_ids' => $registrationIDs,
		'priority' => 'high',
        'data' => $data,
    );
    $headers = array(
        'Authorization: key=' . $apiKey,
        'Content-Type: application/json'
    );

    // Open connection
    $ch = curl_init();

    // Set the URL, number of POST vars, POST data
    curl_setopt( $ch, CURLOPT_URL, $url);
    curl_setopt( $ch, CURLOPT_POST, true);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields));

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_POST, true);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode( $fields));

    // Execute post
    $result = curl_exec($ch);

    // Close connection
    curl_close($ch);
    // print the result if you really need to print else neglate thi
    echo $result;
    //print_r($result);
    //var_dump($result);
	
	}
	
}

?>