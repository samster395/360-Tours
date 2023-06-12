<?php
if (isset($_GET['cid']) && isset($_GET['pid'])) {
  // URL parameter exists
  //echo 'URL parameter exists';

$folder = "./360s/c".$_GET['cid']."/p".$_GET['pid']."/";
//echo $folder;

if(file_exists($folder)) {

$people_json = file_get_contents($folder.'info.json');
$decoded_json = json_decode($people_json, false);
//echo $decoded_json->title;
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
	<body>

		<div id="info">
			<h3><a href="<?php echo $decoded_json->estateAlink; ?>" target="_blank"><?php echo $decoded_json->estateA; ?></a></h3>
			<h2><?php echo $decoded_json->title; ?></h2>
			<span id="room">Room: </span> 
			<?php
				$fileList = glob($folder.'*.jpg');
				foreach($fileList as $filename){
					if(is_file($filename)){
						//echo $filename, '<br>'; 
						$path_parts = pathinfo($filename);
						//echo $path_parts['filename'], "\n";
						$url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']. '&r='.$path_parts['filename'].'">'.$path_parts['filename'];
						$my_new_string = strstr($url, '&r=', true);
						//echo $my_new_string;
						echo '<span>&#8226;</span> <a href="'.$my_new_string.'&r='.$path_parts['filename'].'">'.$path_parts['filename'].'</a> ';
					}   
				}
				echo "<br>";
			?>
			<!--<a href="?r=lounge">Lounge</a> - <a href="?r=bed2">Bedroom 2</a> <br>-->
			<button onclick="togRota()">Toggle Rotation</button>
			<br>
			Click and drag to move around
		</div>
		
		<div id="container"></div>

		<!-- Import maps polyfill -->
		<!-- Remove this when import maps will be widely supported -->
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
			let rotate = false;
			function togRota() {
  				rotate = !rotate;
				console.log("ss")
			}
			
			const queryString = window.location.search;
			//console.log(queryString);
			const urlParams = new URLSearchParams(queryString);
			let room = urlParams.get('r')
			//console.log(room);
			if(room === null){
				room = "Lounge"
			}
			document.getElementById("room").innerHTML = "Room: " + room;
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
} else {
  echo 'Invalid Client ID or Property ID';
}	


} else {
  // URL parameter does not exist
  echo 'Invalid Client ID or Property ID';
}

?>