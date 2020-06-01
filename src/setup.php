<?php
	$config = json_decode(file_get_contents("config.json"));
	if (!($config->bridgeip == "unset")) {
		echo "<h1>Are you sure?</h1><button onclick='window.location.replace(" . '"/delete.php"' . ");'>Yes</button> <button onclick='window.location.replace(" . '"/"' . ");'>No</button>";
		die();
	}
	if (!isset($_POST['step'])) {
		echo "<h1>Welcome to your new installation of the Hue WebApp by GreenCobalt! Please select a bridge to begin!</h1><p>Options:</p><form action='/setup.php' method='post'><input type='hidden' name='step' value='2'>";
		$web = json_decode(file_get_contents("https://discovery.meethue.com/"));
		foreach($web as $current) {
			$ip = $current->internalipaddress;
			echo "<input type='radio' name='bridge' value='$ip'>$ip</input>";
		}
		echo "<br /><br /><input type='submit'></form>";
	} else if ($_POST['step'] == 2) {
		echo "<h1>Please press the button on the bridge to sync!</h1><p>Bridge selected: " . $_POST['bridge'] . "<br /><br />When done, click continue.</p>";
		echo "<form action='/setup.php' method='post'><input type='hidden' name='step' value='3'><input type='hidden' name='bridge' value='" . $_POST['bridge'] . "'><input type='submit'></form>";
	} else if ($_POST['step'] == 3) {
		$ip = $_POST['bridge'];
		$data = array(
			'devicetype' => "WebApp_GreenCobalt#App"
		);
		
		$ch = curl_init($ip . "/api/");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response_plain = curl_exec($ch);
		curl_close($ch);
		
		$response = json_decode($response_plain);
		if (isset($response[0]->error->type)) {
			if ($response[0]->error->type == 101) {
				echo "<h1>Please press the button on the bridge to sync!</h1><p>Bridge selected: " . $_POST['bridge'] . "<br /><br />When done, click continue.</p>";
				echo "<form action='/setup.php' method='post'><input type='hidden' name='step' value='3'><input type='hidden' name='bridge' value='" . $_POST['bridge'] . "'><input type='submit'></form><p style='color:red;'>The button on that bridge was not pressed!</p>";
			}
		} else {
			$config->bridgeip = $ip;
			$config->user = $response[0]->success->username;
			
			$path = "config.json";
			unlink($path);
			$file_handle = fopen($path, 'w'); 
			fwrite($file_handle, json_encode($config));
			fclose($file_handle);
			
			echo "<p style='color:green;'>Successfully set up! Starting in 2 seconds!</p>";
			header( "refresh:2; url=/" ); 
		}
	}
?>