<?php

//Windows = "COM1" Linux = "/dev/ttyS0"
$portName = 'COM1';
$baudRate = 9600;
$bits = 8;
$spotBit = 1;

header( 'Content-type: application/json' ); 

?>

<?php

function echoFlush($string)
{
	echo $string . "\n";
	flush();
	ob_flush();
}

if(!extension_loaded('dio'))
{
	echoFlush( "DIO is missing" );
	exit;
}

try 
{
	//the serial port resource
	$bbSerialPort;
	
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
	{ 
		$bbSerialPort = dio_open($portName, O_RDWR );
		//we're on windows configure com port from command line
		exec("mode {$portName} baud={$baudRate} data={$bits} stop={$spotBit} parity=n xon=on");
	} 
	else //'nix
	{
		$bbSerialPort = dio_open($portName, O_RDWR | O_NOCTTY | O_NONBLOCK );
		dio_fcntl($bbSerialPort, F_SETFL, O_SYNC);
		//we're on 'nix configure com from php direct io function
		dio_tcsetattr($bbSerialPort, array(
			'baud' => $baudRate,
			'bits' => $bits,
			'stop'  => $spotBit,
			'parity' => 0
		));
	}
	
	if(!$bbSerialPort)
	{
		echoFlush( "Could not open Serial port {$portName} ");
		exit;
	}

	$runForSeconds = new DateInterval("PT10S"); //10 seconds
	$endTime = (new DateTime())->add($runForSeconds);
	
	
	while (new DateTime() < $endTime) {
	
		$data = dio_read($bbSerialPort, 256); //this is a blocking call
		if ($data) {
			$result = str_replace(array("w", "k", "g"), '', $data);
			//print_r($result);
			$manage = json_decode($result, true);
			echo "Weight: $manage";
			echo "\n";
		}
		

	}

	echoFlush(  "Closing Port" );
	
	dio_close($bbSerialPort);

} 
catch (Exception $e) 
{
	echoFlush(  $e->getMessage() );
	exit(1);
} 

?>
