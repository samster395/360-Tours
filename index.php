<?php
if(isset($_GET['c']) && isset($_GET['p'])) { // URL parameters exists

	$Cfolder = "./360s/c".$_GET['c']."/"; // Client Folder

	$Pfolder = "./360s/c".$_GET['c']."/p".$_GET['p']."/"; // Propery Folder 

	if(file_exists($Cfolder) && file_exists($Pfolder)) { // Folders exist

		$c_info_json = file_get_contents($Cfolder.'client_info.json'); // Read the info from the client_info.json file
		$c_decoded_json = json_decode($c_info_json, false);
	
		$p_info_json = file_get_contents($Pfolder.'info.json'); // Read the info from the info.json file
		$p_decoded_json = json_decode($p_info_json, false);
?>
		<!DOCTYPE html>
		<html lang="en">
			<head>
				<title>360 Tour - <?php echo $p_decoded_json->title; ?></title>
				<meta charset="utf-8">
				<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
				<link type="text/css" rel="stylesheet" href="./includes/main.css">
				<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@1,500&amp;display=swap" rel="stylesheet">
			</head>
			<meta content="width=device-width, initial-scale=1" name="viewport" />
			<body id="bod">
				<div id="info">
					<h3><a href="<?php echo $c_decoded_json->estateAlink; ?>" target="_blank"><?php echo $c_decoded_json->estateA; ?></a></h3> <!-- Display the data from the client_info.json file -->
					<h2><?php echo $p_decoded_json->title; ?></h2> 
					<span id="area">Area: </span> 
					<?php
						$fileList = glob($Pfolder.'*.jpg'); // Search the Pfolder for .jpg files
						$firstFile = pathinfo($fileList[0])['filename'];
						echo '<select id="select_area">';
						foreach($fileList as $filename){ // Add each photo as an option in a drop down list
							if(is_file($filename)){
								$path_parts = pathinfo($filename);
								if($_GET['a'] == $path_parts['filename']){
								?>
									<option value="<?php echo $path_parts['filename']; ?>" selected><?php echo $path_parts['filename']; ?></option>
								<?php
								} else {
								?>
									<option value="<?php echo $path_parts['filename']; ?>"><?php echo $path_parts['filename']; ?></option>
								<?php
								}
							}   
						}
						echo "</select>";
					?>
					<button onclick="togRota()">Rotation On/Off</button> <button id="fullBtn" onclick="toggleFullScreen()">Open Fullscreen</button>
					<br>
					<h3>Drag to move around</h3>
				</div>
				
				<div id="container"></div>
				<!-- Import maps polyfill -->
				<!-- Remove this when import maps will be widely supported -->
				<!-- This is required for IOS to work currently(17/06/2023) -->
				<script async src="https://unpkg.com/es-module-shims@1.6.3/dist/es-module-shims.js"></script>
				
				<script type="importmap">
					{
						"imports": {
							"three": "https://unpkg.com/three@0.153.0/build/three.module.js",
							"three/addons/": "https://unpkg.com/three@0.153.0/examples/jsm/"
						}
					}
				</script>
				<script>
					const queryString = window.location.search;
					const urlParams = new URLSearchParams(queryString);
					
					let rot = urlParams.get('r'); // Add &r to URL to rotate on start
					
					let rotate;
					if ( window.location !== window.parent.location && rot === null) { 
						rotate = false; // The page is in an iframe - probably best to keep rotation off
					} else { 
						rotate = true; // The page is not in an iframe
					}
					
					function togRota() {
						rotate = !rotate;
					}
					
					let area = urlParams.get('a');
					if(area === null){
						area = "<?php echo $firstFile; ?>"
					}
					
					if(navigator.userAgent.includes("iPhone")){ // Fullscreen not supported on iPhone https://developer.mozilla.org/en-US/docs/Web/API/Element/requestFullscreen#browser_compatibility
						console.log("Fullscreen not supported on iOS");
						document.getElementById("fullBtn").style.display = "none";
					}
					
					function toggleFullScreen() {
						if (!document.fullscreenElement &&    // alternative standard method
							!document.mozFullScreenElement && !document.webkitFullscreenElement) {  // current working methods
							if (document.documentElement.requestFullscreen) {
							document.documentElement.requestFullscreen();
							} else if (document.documentElement.mozRequestFullScreen) {
							document.documentElement.mozRequestFullScreen();
							} else if (document.documentElement.webkitRequestFullscreen) {
							document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
							}
							document.getElementById("fullBtn").innerText = "Exit Fullscreen";
						} else {
							if (document.cancelFullScreen) {
								document.cancelFullScreen();
							} else if (document.mozCancelFullScreen) {
								document.mozCancelFullScreen();
							} else if (document.webkitCancelFullScreen) {
								document.webkitCancelFullScreen();
							}
						}
					}
					
					if (document.addEventListener){
						document.addEventListener('fullscreenchange', exitHandler, false);
						document.addEventListener('mozfullscreenchange', exitHandler, false);
						document.addEventListener('MSFullscreenChange', exitHandler, false);
						document.addEventListener('webkitfullscreenchange', exitHandler, false);
					}
					
					function exitHandler(){
					if (!document.webkitIsFullScreen && !document.mozFullScreen && !document.msFullscreenElement){
						document.getElementById("fullBtn").innerText = "Open Fullscreen";
					}
					}
				</script>
		
				<script type="module">
		
					import * as THREE from 'three';
		
					let camera, scene, renderer;
					
					let isUserInteracting = false,
						onPointerDownMouseX = 0, onPointerDownMouseY = 0,
						lon = 0, onPointerDownLon = 0,
						lat = 0, onPointerDownLat = 0,
						phi = 0, theta = 0;
		
					init();
					animate();
		
					
		
					function init() {
		
						const container = document.getElementById( 'container' );
		
						camera = new THREE.PerspectiveCamera( 75, window.innerWidth / window.innerHeight, 1, 1100 );
		
						scene = new THREE.Scene();
		
						const geometry = new THREE.SphereGeometry( 500, 60, 40 );
						// invert the geometry on the x-axis so that all of the faces point inward
						geometry.scale( - 1, 1, 1 );
		
						const texture = new THREE.TextureLoader().load( '<?php echo $Pfolder ?>'+ area + '.jpg' );
						texture.colorSpace = THREE.SRGBColorSpace;
						const material = new THREE.MeshBasicMaterial( { map: texture } );
						
						let selector = document.getElementById("select_area"); 

						selector.addEventListener("change", () => {
							const texture2 = new THREE.TextureLoader().load( '<?php echo $Pfolder ?>'+ selector.value + '.jpg' );
							texture2.colorSpace = THREE.SRGBColorSpace;
							material.map = texture2;
							material.needsUpdate=true;
							
							let thisPage = new URL(window.location.href);
							thisPage.searchParams.set('a', selector.value);
							window.history.pushState(null, '', thisPage.toString());
						});
		
						const mesh = new THREE.Mesh( geometry, material );
		
						scene.add( mesh );
		
						renderer = new THREE.WebGLRenderer();
						renderer.setPixelRatio( window.devicePixelRatio );
						renderer.setSize( window.innerWidth, window.innerHeight );
						container.appendChild( renderer.domElement );
		
						container.style.touchAction = 'none';
						container.addEventListener( 'pointerdown', onPointerDown );
		
						document.addEventListener( 'wheel', onDocumentMouseWheel );
		
						window.addEventListener( 'resize', onWindowResize );
		
					}
		
					function onWindowResize() {
		
						camera.aspect = window.innerWidth / window.innerHeight;
						camera.updateProjectionMatrix();
		
						renderer.setSize( window.innerWidth, window.innerHeight );
		
					}
		
					function onPointerDown( event ) {
		
						if ( event.isPrimary === false ) return;
		
						isUserInteracting = true;
		
						onPointerDownMouseX = event.clientX;
						onPointerDownMouseY = event.clientY;
		
						onPointerDownLon = lon;
						onPointerDownLat = lat;
		
						document.addEventListener( 'pointermove', onPointerMove );
						document.addEventListener( 'pointerup', onPointerUp );
		
					}
		
					function onPointerMove( event ) {
		
						if ( event.isPrimary === false ) return;
		
						lon = ( onPointerDownMouseX - event.clientX ) * 0.1 + onPointerDownLon;
						lat = ( event.clientY - onPointerDownMouseY ) * 0.1 + onPointerDownLat;
		
					}
		
					function onPointerUp() {
		
						if ( event.isPrimary === false ) return;
		
						isUserInteracting = false;
		
						document.removeEventListener( 'pointermove', onPointerMove );
						document.removeEventListener( 'pointerup', onPointerUp );
		
					}
		
					function onDocumentMouseWheel( event ) {
		
						const fov = camera.fov + event.deltaY * 0.05;
		
						camera.fov = THREE.MathUtils.clamp( fov, 10, 75 );
		
						camera.updateProjectionMatrix();
		
					}
		
					function animate() {
		
						requestAnimationFrame( animate );
						update();
		
					}
		
					function update() {
		
						if ( isUserInteracting === false && rotate === true ) {
		
							lon += 0.2;
		
						}
						
						if ( isUserInteracting === true) {
								rotate = false;
						}	
		
						lat = Math.max( - 85, Math.min( 85, lat ) );
						phi = THREE.MathUtils.degToRad( 90 - lat );
						theta = THREE.MathUtils.degToRad( lon );
		
						const x = 500 * Math.sin( phi ) * Math.cos( theta );
						const y = 500 * Math.cos( phi );
						const z = 500 * Math.sin( phi ) * Math.sin( theta );
		
						camera.lookAt( x, y, z );
		
						renderer.render( scene, camera );
		
					}
		
				</script>
			</body>
		</html>

<?php
	} else { // Pfolder does not exist
	?>	
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sam's Stills - 360 Tours</title>
	<style>
	body {
		background-color: #3d5464;
		color: white;
		text-align:center;
		font-family: 'Montserrat', sans-serif;
	}
	a {
		color:yellow
	}
	@media only screen and (max-width: 800px) {
		#ifr{
			width: 100%;
		}
	}
	</style>
	<body>
<?php
	ErrorPageStart();
	echo "<h1>Invalid Client or Property ID.</h1>";
	ErrorPageFinish();	
	}	

} else { // no URL parameters given
	ErrorPageStart();
	echo "<h1>360 Tours</h1>";
	ErrorPageFinish();

}

function ErrorPageStart(){ // Error page
?>	
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sam's Stills - 360 Tours</title>
	<style>
	body {
		background-color: #3d5464;
		color: white;
		text-align:center;
		font-family: 'Montserrat', sans-serif;
		margin: 0;
	}
	a {
		color:yellow
	}
	@media only screen and (max-width: 800px) {
		#ifr{
			width: 100%;
		}
	}
	</style>
	<!--<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.css'>-->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="../includes/css/style.css">
	<body>
	<div class="topnav" id="myTopnav">
		<a href="https://samsstills.co.uk/" class="active">Sam's Stills</a>
		<a href="https://samsstills.co.uk/?about" >About me / Services</a>
		<a href="mailto:samsstills@outlook.com">Get in touch</a>
		<!--<a href="https://samsstills.co.uk/?testimonials" >Nice words</a>-->
		<a href="https://samsstills.pixieset.com/" target="_blank">Shop Prints</a>
		<a href="https://www.redbubble.com/people/SamsPhotographs/explore?asc=u&page=1&sortOrder=recent" target="_blank">Shop Clothes</a>
		<a href="https://www.instagram.com/sams.stills/" target="_blank">Instagram</a>
		<a href="https://www.facebook.com/sams.stills" target="_blank">Facebook</a>
		<!--<a href="https://www.tiktok.com/@samsstills" target="_blank">TikTok</a>-->
		<a href="javascript:void(0);" class="icon" onclick="navResp()">
		<i class="fa fa-bars"></i>
		</a>
	</div>
<?php } 

function ErrorPageFinish(){ // Error page
?>	
	<h2>If you would like to contact me about making a 360 tour of your property, please <a style="color:yellow" href="mailto:samsstills@outlook.com">get in touch</a>.</h2>
	<h2>Below is an example tour and <a href="https://samsstills.co.uk/tours/listing.html" target="_blank">here</a> is a property listing example.</h2>
	<iframe style="border: 0;" id="ifr" width="70%" height="80%" src="https://samsstills.co.uk/tours/?c=1&p=1&r" title="" allow="fullscreen"></iframe>
	</body>
	<script type="text/javascript">	
	function navResp() {
	var x = document.getElementById("myTopnav");
	if (x.className === "topnav") {
		x.className += " responsive";
	} else {
		x.className = "topnav";
	}
	}
	</script>
<?php } ?>	