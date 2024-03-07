<?php
/**
 * Функция автозагрузки классов в пространстве имен \Cronman и доп.классы без репозитория
 *
 * @param $class
 */
spl_autoload_register( function($class) {

	$rootpath = realpath( __DIR__.'/../' )."/php/Class";

	$name = yexplode( "\\", $class );

	if ( $name[0] == 'Cronman' && !class_exists( $class, false ) ) {

		unset($name[0]);
		$name1 = array_values($name);
		$class = yimplode( "//", $name1 );

		if( file_exists($rootpath."/{$class}.php") )
			require_once $rootpath."/{$class}.php";

	}

} );