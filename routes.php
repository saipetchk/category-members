<?php

	function call( $controllerName, $action ) {
		$controllerName = $controllerName . 'Controller';
		require_once( "controllers/" . $controllerName . ".class.php" );

		$controller = new $controllerName();
		$controller->{ $action }();
	}

	$controllers = [
		'ArticleList' => [ 'index', 'show' ]
	];

	if ( array_key_exists( $controllerName, $controllers ) && in_array( $methodName, $controllers[$controllerName] ) ) {
		call( $controllerName, $methodName );
	} else {
		call( 'ArticleList', 'index' );
	}

?>
