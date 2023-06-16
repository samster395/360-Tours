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
					<button onclick="togRota()">Toggle Rotation</button> <button id="full">Open Fullscreen</button>
					<br>
					<h3>Drag to move around</h3>
				</div>
				
				<div id="container"></div>
				<!-- Import maps polyfill -->
				<!-- Remove this when import maps will be widely supported -->
				<!--<script async src="https://unpkg.com/es-module-shims@1.6.3/dist/es-module-shims.js"></script>-->
				<script type="importmap">
					{
						"imports": {
							"three": "https://unpkg.com/three@0.153.0/build/three.module.js",
							"three/addons/": "https://unpkg.com/three@0.153.0/examples/jsm/"
						}
					}
				</script>
				<script>
					let rotate;
					if ( window.location !== window.parent.location ) {
						rotate = false; // The page is in an iframe - probably best to keep rotation off
					} else { 
						rotate = true; // The page is not in an iframe
					}
					
					function togRota() {
						rotate = !rotate;
					}
					
					const queryString = window.location.search;
					const urlParams = new URLSearchParams(queryString);
					let area = urlParams.get('a')
					if(area === null){
						area = "<?php echo $firstFile; ?>"
					}
					
					let myDocument = document.documentElement;
					let btn = document.getElementById("full");
					
					//if( window.innerHeight == screen.height) {
					//	btn.textContent = "Exit Fullscreen"
					//}
					
					btn.addEventListener("click", ()=>{
						if(btn.textContent == "Open Fullscreen"){
							if (myDocument.requestFullscreen) {
								myDocument.requestFullscreen();
							} 
							else if (myDocument.msRequestFullscreen) {
								myDocument.msRequestFullscreen();
							} 
							else if (myDocument.mozRequestFullScreen) {
								myDocument.mozRequestFullScreen();
							}
							else if(myDocument.webkitRequestFullscreen) {
								myDocument.webkitRequestFullscreen();
							}
							btn.textContent = "Exit Fullscreen";
						}
						else{
							if(document.exitFullscreen) {
								document.exitFullscreen();
							}
							else if(document.msexitFullscreen) {
								document.msexitFullscreen();
							}
							else if(document.mozexitFullscreen) {
								document.mozexitFullscreen();
							}
							else if(document.webkitexitFullscreen) {
								document.webkitexitFullscreen();
							}
							btn.textContent = "Open Fullscreen";
						}
					});
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
	InvalidData(); // Display Error page
	}	

} else { // no URL parameters given
	InvalidData(); // Display Error page
}

function InvalidData(){ // Error page
?>	
	<style>
	@media only screen and (max-width: 800px) {
		#ifr{
			width: 100%;
		}
	}
	</style>
	<body style="background-color: #3d5464; color: white; text-align:center; font-family: 'Montserrat', sans-serif;">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sam's Stills - 360 Tours</title>
	<h2>Invalid Client ID or Property ID.</h2>
	<h2>If you would like to contact me about making a 360 tour of your property, please <a style="color:yellow" href="mailto:samsstills@outlook.com">get in touch</a>.</h2>
	<h2>Below is an example tour.</h2>
	<iframe width="70%" height="80%" id="ifr" src="https://samsstills.co.uk/tours/?c=1&p=1" title=""></iframe>
	</body>
<?php	
}
?>