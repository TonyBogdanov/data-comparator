<?php

chdir( __DIR__ . '/../coverage' );

rename( '_css', 'css' );
rename( '_js', 'js' );
rename( '_icons', 'icons' );

foreach ( new RecursiveIteratorIterator(

    new RecursiveDirectoryIterator( './' ),
    RecursiveIteratorIterator::SELF_FIRST

) as $name => $file ) {

    if ( 'html' !== pathinfo( $name, PATHINFO_EXTENSION ) ) {

        continue;

    }

    file_put_contents( $name, str_replace( [

        '_css/',
        '_js/',
        '_icons/',

    ], [

        'css/',
        'js/',
        'icons/',

    ], file_get_contents( $name ) ) );

}
