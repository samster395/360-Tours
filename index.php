<?php
if (isset($_GET['cid']) && isset($_GET['pid'])) { // URL parameter exists

$folder = "./360s/c".$_GET['cid']."/p".$_GET['pid']."/";

if(file_exists($folder)) { // folder exists

$info_json = file_get_contents($folder.'info.json');
$decoded_json = json_decode($info_json, false);

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>360 Tour - <?php echo $decoded_json->title; ?></title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
		<link type="text/css" rel="stylesheet" href="./includes/main.css">
		<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@1,500&amp;display=swap" rel="stylesheet">
	</head>
	<meta content="width=device-width, initial-scale=1" name="viewport" />
	<body id="bod">

		<div id="info">
			<h3><a href="<?php echo $decoded_json->estateAlink; ?>" target="_blank"><?php echo $decoded_json->estateA; ?></a></h3>
			<h2><?php echo $decoded_json->title; ?></h2>
			<span id="room">Area: </span> 
			<?php
				$fileList = glob($folder.'*.jpg');
				$firstFile = pathinfo($fileList[0])['filename'];
				echo '<select onchange="roomChange()" id="select_room">';
				foreach($fileList as $filename){
					if(is_file($filename)){
						//echo $filename, '<br>'; 
						$path_parts = pathinfo($filename);
						//echo $path_parts['filename'], "\n";
						$url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']. '&a='.$path_parts['filename'].'">'.$path_parts['filename'];
						$my_new_string = strstr($url, '&a=', true);
						//echo $my_new_string;
						//echo '<span>&#8226;</span> <a href="'.$my_new_string.'&r='.$path_parts['filename'].'">'.$path_parts['filename'].'</a> ';
						if($_GET['a'] == $path_parts['filename']){
						?>
							<option value="<?php echo $path_parts['filename']; ?>" selected><?php echo $path_parts['filename']; ?></option>
						<?php
						} else {
						?>
							<option value="<?php echo $path_parts['filename']; ?>"><?php echo $path_parts['filename']; ?></option>
						<?php
						}
						//echo '<span>&#8226;</span> <a href="'.$my_new_string.'&r='.$path_parts['filename'].'">'.$path_parts['filename'].'</a> ';
					}   
				}
				echo "</select>";
				//echo "<br>";
			?>
			<!--<a href="?r=lounge">Lounge</a> - <a href="?r=bed2">Bedroom 2</a> <br>-->
			<button onclick="togRota()">Toggle Rotation</button><button onclick="openFullscreen();">Open In Fullscreen</button>
			<br>
			Drag to move around
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
			let rotate = true;
			function togRota() {
  				rotate = !rotate;
				console.log("ss")
			}
			
			const queryString = window.location.search;
			//console.log(queryString);
			const urlParams = new URLSearchParams(queryString);
			let room = urlParams.get('a')
			//console.log(room);
			if(room === null){
				room = "<?php echo $firstFile; ?>"
			}
			//document.getElementById("room").innerHTML = "Area: " + room;
			function roomChange(){
				d = document.getElementById("select_room").value;
				let thisPage = new URL(window.location.href);
				thisPage.searchParams.set('a', d);
				console.log(thisPage);
				window.location.href = thisPage;
				//location.reload();
			}
			
			var elem = document.getElementById("bod");
			/* When the openFullscreen() function is executed, open in fullscreen.
			Note that we must include prefixes for different browsers, as they don't support the requestFullscreen method yet */
			function openFullscreen() {
				if (elem.requestFullscreen) {
					elem.requestFullscreen();
				} else if (elem.webkitRequestFullscreen) { /* Safari */
					elem.webkitRequestFullscreen();
				} else if (elem.msRequestFullscreen) { /* IE11 */
					elem.msRequestFullscreen();
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

				const texture = new THREE.TextureLoader().load( '<?php echo $folder ?>'+ room + '.jpg' );
				texture.colorSpace = THREE.SRGBColorSpace;
				const material = new THREE.MeshBasicMaterial( { map: texture } );

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
} else { // Folder does not exist
	InvalidData();
}	

} else { // no URL parameters given
	InvalidData();
}

function InvalidData(){
?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sam's Stills - 360 Tours</title>
	<h1>Invalid Client ID or Property ID.</h1>
	<h2>If you would like to contact me about making a 360 tour of your property, please <a href="mailto:samsstills@outlook.com">get in touch</a>.</h2>
<?php	
}
?>