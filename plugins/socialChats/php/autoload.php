<?php

//require_once realpath( __DIR__.'/../' )."/vendor/autoload.php";

/**
 * Функция автозагрузки классов в пространстве имен \Chats и доп.классы без репозитория
 *
 * @param $class
 */
spl_autoload_register( static function($class) {

	$rootpath = dirname( __DIR__ )."/php/Class";

	$name = yexplode( "\\", $class );

	if ( $name[0] == 'Chats' && !class_exists( $class, false ) ) {

		unset($name[0]);
		$name1 = array_values($name);
		$class = yimplode( "//", $name1 );

		if( file_exists($rootpath."/{$class}.php") )
			require_once $rootpath."/{$class}.php";

		if ( strpos($class, 'Provider') !== false && !class_exists( $class, false ) ) {

			if(file_exists($rootpath."/Providers/{$class}.php")) {
				require_once $rootpath."/Providers/{$class}.php";
			}

		}

	}

	/*if ( $name[0] == 'Vk' && !class_exists( $class, false ) ) {

		require_once realpath( __DIR__.'/../' )."/vendor/VK-master/src/VK/VK.php";
		require_once realpath( __DIR__.'/../' )."/vendor/VK-master/src/VK/VKException.php";

	}*/

} );