<?php 

class User 
{
	public $id;
	public $name; 
	public $type; 
	public $email;
	public $phone;
	public $gender;
	public $locationString; 
	public $token; 
	public $verified;
	public $verificationCode;
	
	private $password;
	
	function set_id($new_id) { $this->id =  $new_id ; }
   	function get_id() {return $this->id;}
	
	function set_name($new_name) { $this->name = $new_name;}
   	function get_name() {return $this->name;}
	
	function set_email($new_email) { $this->email = $new_email; }
   	function get_email() {return $this->email;}
	
	function set_phone($new_phone) { $this->phone = $new_phone; }
   	function get_phone() {return $this->phone;}
	
	function set_gender($new_gender) { $this->gender = $new_gender; }
   	function get_gender() {return $this->gender;}
	
	function set_locationString($new_locationString) { $this->locationString =$new_locationString; }
   	function get_locationString() {return $this->locationString;}
	
	function set_token($new_token) { $this->token = $new_token; }
   	function get_token() {return $this->token;}
	
	function set_verified($new_verified) { $this->verified = $new_verified; }
   	function get_verified() {return $this->verified;}
	
	function set_verificationCode($new_verificationCode) { $this->verificationCode = $new_verificationCode; }
   	function get_verificationCode() {return $this->verificationCode;}
	
	function set_password($new_password) { $this->password = $new_password; }
   	function get_password() {return $this->password;}
	
	function set_type($new_type) { $this->type = $new_type; }
   	function get_type() {return $this->type;}
	
	function set_app($new_app) { $this->type = $new_app; }
   	function get_app() {return $this->app;}
	
	
	
	public static function  send_mail($email,$message,$subject)
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
           }else {
				echo 'Message has been sent';
				}
		}
	 
	// make the constructer
	
	//
	public static function  generateRandomCode($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}
		/*
	public static function getUserID($userEmail,$App,$userType){
	//getPassengerID
	// userType is tha table name ie drivers,passengers
		$getUserIDSql = "SELECT ID FROM  $userType WHERE email=?";
		$getUserIDStatement = $App->db->prepare($getUserIDSql);
		try{
			$getUserIDStatement->execute(array($userEmail));
			$userID= $getUserIDStatement->fetch()['ID'];
			return $userID;
		}catch (PDOException $ex){
			return $ex->getMessage();
		}
	
			}	
	*/
	public static function getUserID($email,$tableName,$App){

	$getUserIDSql = "SELECT ID FROM  $tableName WHERE email=?";
	$getUserIDStatement = $App->db->prepare($getUserIDSql);
	try{
		$getUserIDStatement->execute(array($email));
		$userID= $getUserIDStatement->fetch()['ID'];
		return $userID;
	}catch (PDOException $ex){
		return $ex->getMessage();
		}
	
			}
	
}




?>