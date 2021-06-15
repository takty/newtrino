<?php
namespace nt;
/**
 *
 * Newtrino Config (Sample)
 *
 * @author Takuto Yanagida
 * @version 2021-06-15
 *
 */


/*
 * Overwriting Mode Settings
 *
 * This value is used for creating directories and files.
 */
define( 'NT_MODE_DIR',  0770 );
define( 'NT_MODE_FILE', 0660 );

/*
 * Debug mode
 *
 * If this value is set to true, notices will be displayed during development.
 */
define( 'NT_DEBUG', true );

/*
 * Authentication Key
 *
 * This key will be exposed in the HTML of the login screen.
 */
define( 'NT_AUTH_KEY', 'newtrino' );
