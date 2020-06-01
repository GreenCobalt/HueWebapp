<?php
$config = json_decode(file_get_contents("config.json"));
if ($config->bridgeip == "unset") {
	header("Location: /setup.php");
} else {
	echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script><script src="cie.js"></script>';
	
	$base = "http://" . $config->bridgeip . "/api/" . $config->user;
	$groups = json_decode(file_get_contents($base . "/groups"));
	$lights = json_decode(file_get_contents($base . "/lights"));
	echo "<table>";
	foreach ($groups as $id => $group) {
		echo "<tr><td>" . $group->name . "</td><td><button value='$id' class='g_on'>On</button> <button value='$id' class='g_off'>Off</button> <button value='$id' class='g_normal'>Incandescent</button> <input type='color' class='$id' value='#000000' onchange='gColorChange(this)'></td></tr>";
		$light_list = [];
		foreach($group->lights as $light){$light_list[$light] = $lights->$light->name;}
		asort($light_list);
		foreach($light_list as $light => $name){echo "<tr><td><p title='" . $lights->$light->productname . "'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $name . "</p></td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button value='$light' class='on'>On</button> <button value='$light' class='off'>Off</button> <button value='$light' class='normal'>Incandescent</button> <input type='color' class='$light' value='" . cie_to_rgb($lights->$light->state->xy[0], $lights->$light->state->xy[1], $lights->$light->state->bri) . "' onchange='colorChange(this)'><img style='height:20px;vertical-align:middle;' src='/assets/" . ($lights->$light->state->on ? "on" : "off") . ".png'></td>";}
	}
	echo '</table><br /><a href="/setup.php">Redo Install</a><script type="text/javascript">
		var offstate = {on: false};
		var onstate = {on: true};
		$(".on").click(function(e) {var req = $.ajax({type: "PUT",url: "' . $base . '/lights/" + $(this).val() + "/state",data: JSON.stringify(onstate),dataType: "json"});setTimeout(() => {req.abort()}, 100);location.reload();});
		$(".off").click(function(e) {var req = $.ajax({type: "PUT",url: "' . $base . '/lights/" + $(this).val() + "/state",data: JSON.stringify(offstate),dataType: "json"});setTimeout(() => {req.abort()}, 100);location.reload();});
		$(".normal").click(function(e) {var colorstate = `{"xy":[${parseFloat(0.4555)},${parseFloat(0.4095)}],"bri":254}`;var req = $.ajax({type: "PUT",url: "' . $base . '/lights/" + $(this).val() + "/state",data: colorstate,dataType: "json"});setTimeout(() => {req.abort()}, 100);location.reload();});
		$(".g_on").click(function(e) {var req = $.ajax({type: "PUT",url: "' . $base . '/groups/" + $(this).val() + "/action",data: JSON.stringify(onstate),dataType: "json"});setTimeout(() => {req.abort()}, 100);location.reload();});
		$(".g_off").click(function(e) {
			var req = $.ajax({type: "PUT",url: "' . $base . '/groups/" + $(this).val() + "/action",data: JSON.stringify(offstate),dataType: "json"});
			setTimeout(() => {req.abort()}, 100);
			location.reload();
		});
		$(".g_normal").click(function(e) {
			var colorstate = `{"xy":[${parseFloat(0.4555)},${parseFloat(0.4095)}],"bri":254}`;
			var req = $.ajax({type: "PUT",url: "' . $base . '/groups/" + $(this).val() + "/action",data: colorstate,dataType: "json"});
			setTimeout(() => {req.abort()}, 100);
			location.reload();
		});
		function colorChange(element) {var colors = element.value.substr(1).match(/.{1,2}/g);var colorstate = `{"xy":[${parseFloat(rgb_to_cie(parseInt("0x" + colors[0]),parseInt("0x" + colors[1]),parseInt("0x" + colors[2]))[0])},${parseFloat(rgb_to_cie(parseInt("0x" + colors[0]),parseInt("0x" + colors[1]),parseInt("0x" + colors[2]))[1])}],"bri":${Math.round(hexToL(element.value) * 254)}}`;var req = $.ajax({type: "PUT",url: "' . $base . '/lights/" + element.className.split(/\s+/)[0] + "/state",data: colorstate,dataType: "json"});setTimeout(() => {req.abort()}, 100);}
		function gColorChange(element) {var colors = element.value.substr(1).match(/.{1,2}/g);var colorstate = `{"xy":[${parseFloat(rgb_to_cie(parseInt("0x" + colors[0]),parseInt("0x" + colors[1]),parseInt("0x" + colors[2]))[0])},${parseFloat(rgb_to_cie(parseInt("0x" + colors[0]),parseInt("0x" + colors[1]),parseInt("0x" + colors[2]))[1])}],"bri":${Math.round(hexToL(element.value) * 254)}}`;var req = $.ajax({type: "PUT",url: "' . $base . '/groups/" + element.className.split(/\s+/)[0] + "/action",data: colorstate,dataType: "json"});setTimeout(() => {req.abort()}, 100);location.reload();}
		function hexToL(H) {
			  let r = 0, g = 0, b = 0;if (H.length == 4) {r = "0x" + H[1] + H[1];g = "0x" + H[2] + H[2];b = "0x" + H[3] + H[3];} else if (H.length == 7) {r = "0x" + H[1] + H[2];g = "0x" + H[3] + H[4];b = "0x" + H[5] + H[6];}
			  r /= 255;g /= 255;b /= 255;
			  let cmin = Math.min(r,g,b),cmax = Math.max(r,g,b),delta = cmax - cmin,h = 0,s = 0,l = 0;
			  if (delta == 0) return r;
			  else if (cmax == r) return r;
			  else if (cmax == g) return g;
			  else return b;
		}
	</script>';
}
function cie_to_rgb($x,$y,$b) {
	$z = 1.0 - $x - $y;
	$Y = round($b / 254, 2);
	@$X = ($Y / $y) * $x;
	@$Z = ($Y / $y) * $z;

	//Convert to RGB using Wide RGB D65 conversion
	$red 	=  $X * 1.656492 - $Y * 0.354851 - $Z * 0.255038;
	$green 	= ($X*-1) * 0.707196 + $Y * 1.655397 + $Z * 0.036152;
	$blue 	=  $X * 0.051713 - $Y * 0.121364 + $Z * 1.011530;

	//If red, green or blue is larger than 1.0 set it back to the maximum of 1.0
	if ($red > $blue && $red > $green && $red > 1.0) {

		$green = $green / $red;
		$blue = $blue / $red;
		$red = 1.0;
	}
	else if ($green > $blue && $green > $red && $green > 1.0) {

		$red = $red / $green;
		$blue = $blue / $green;
		$green = 1.0;
	}
	else if ($blue > $red && $blue > $green && $blue > 1.0) {

		$red = $red / $blue;
		$green = $green / $blue;
		$blue = 1.0;
	}

	//Reverse gamma correction
	$red 	= $red <= 0.0031308 ? 12.92 * $red : (1.0 + 0.055) * pow($red, (1.0 / 2.4)) - 0.055;
	$green 	= $green <= 0.0031308 ? 12.92 * $green : (1.0 + 0.055) * pow($green, (1.0 / 2.4)) - 0.055;
	$blue 	= $blue <= 0.0031308 ? 12.92 * $blue : (1.0 + 0.055) * pow($blue, (1.0 / 2.4)) - 0.055;


	//Convert normalized decimal to decimal
	$red 	= round($red * 255);
	$green 	= round($green * 255);
	$blue 	= round($blue * 255);

	if (is_integer($red)) {
		$red = 0;
	}
	if (is_integer($green)) {
		$green = 0;
	}
	if (is_integer($blue)) {
		$blue = 0;
	}

	return sprintf("#%02x%02x%02x", $red, $green, $blue);
}
#{"name":"TV Room","lights":["21","22","23","24","26","27","29"],"sensors":[],"type":"Room","state":{"all_on":true,"any_on":true},"recycle":false,"class":"Recreation","action":{"on":true,"bri":254,"hue":8418,"sat":140,"effect":"none","xy":[0.4573,0.41],"ct":366,"alert":"none","colormode":"ct"}}
?>