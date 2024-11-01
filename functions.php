<?php
add_action( 'admin_post_ycwp_migrate', 'ycwp_handle_migrate' );
add_action( 'admin_post_nopriv_ycwp_migrate', 'ycwp_handle_migrate' );

const YCWP_API_SERVER   = 'https://api.yogeta.com';
const YCWP_API_KEY_PAGE = 'https://api.yogeta.com/migrate/apikey';


function ycwp_check_zip_size() {
	return ycwp_get_directory_size( ABSPATH ) < disk_free_space( ABSPATH );
}

function ycwp_command_exists( $command ) {
	$whereIsCommand = ( PHP_OS == 'WINNT' ) ? 'where' : 'which';

	/**
	 * Check if $command exits in shell
	 */
	$process = proc_open(
		"$whereIsCommand $command",
		array(
			0 => array( "pipe", "r" ), //STDIN
			1 => array( "pipe", "w" ), //STDOUT
			2 => array( "pipe", "w" ), //STDERR
		),
		$pipes
	);
	if ( $process !== false ) {
		$stdout = stream_get_contents( $pipes[1] );
		$stderr = stream_get_contents( $pipes[2] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		proc_close( $process );

		return $stdout != '';
	}

	return false;
}

function ycwp_handle_migrate() {
	$username      = sanitize_text_field( $_POST['username'] );
	$password      = sanitize_text_field( $_POST['password'] );
	$apikey        = sanitize_text_field( $_POST['apikey'] );
	$cmpType       = sanitize_text_field( $_POST['type'] );
	$site          = sanitize_url( str_replace( 'https://', '', get_site_url() ) );
	$site          = sanitize_url( str_replace( 'http://', '', $site ) );
	$HAS_ZIP       = class_exists( 'ZipArchive' );
	$HAS_PHAR_GZ   = class_exists( 'PharData' );
	$HAS_MYSQLDUMP = ycwp_command_exists( 'mysqldump' );
	$HAS_CURL      = extension_loaded( 'curl' );

	$hasAvailableSize = ycwp_check_zip_size();
//    $serverData = CallAPI('POST', YCWP_API_SERVER . '/migrate', ['site' => $site, 'api_key' => $apikey, 'username' => $username, 'password' => $password]);
	global $wpdb;

	$backup_dir = plugin_dir_path( __FILE__ ) . 'backup/';

	if ( ! file_exists( $backup_dir ) ) {
		mkdir( $backup_dir, 0777, true );
	}


	/**
	 * Backup Sql Server
	 */
	ycwp_update_migrate_process( $apikey, 10, 'creating db backup' );
	if ( file_exists( $backup_dir . "ymg_back.sql" ) ) {
		unlink( $backup_dir . "ymg_back.sql" );
	}

	$dbPath = [ 'path' => $backup_dir . 'ymg_back.sql', 'type' => '' ];
	if ( $HAS_MYSQLDUMP ) {
		exec( "mysqldump --user=$wpdb->dbuser --password=$wpdb->dbpassword $wpdb->dbname >" . $backup_dir . "ymg_back.sql" );
		$dbPath['type'] = 'sqlFile';
	} else {
		$sql = ycwp_export_database( 'localhost', $wpdb->dbuser, $wpdb->dbpassword, $wpdb->dbname, false, 'ymg_back.sql' );
		$fp  = fopen( __DIR__ . '/backup/ymg_back.sql', 'a' );
		fwrite( $fp, $sql );
		fclose( $fp );
		$dbPath['type'] = 'textFile';

	}
	ycwp_update_migrate_process( $apikey, 20, 'creating db backup complete' );
	ycwp_update_migrate_process( $apikey, 22, 'selecting migrate method' );
//    echo "Wordpress db backup done..<br>";
	$migrate_type = 'CMP';
	$path         = '';
	if ( $hasAvailableSize ) {
		try {
			$path    = ycwp_compress_to_gzip_archive( $backup_dir );
			$cmpType = 'tar';
		} catch ( PharException $e ) {
			try {
				$path    = ycwp_compress_to_zip_archive( $backup_dir );
				$cmpType = 'zip';
			} catch ( Exception $e ) {
				$migrate_type = 'OBO';
			}
		}
	} else {
		$migrate_type = 'OBO';
	}
	global $wp_version;
	ycwp_update_migrate_process( $apikey, 30, 'migrate method selected' );
	echo json_encode( [
		'code' => 200,
		'data' => [
			'apikey'         => $apikey,
			'username'       => $username,
			'password'       => $password,
			'migration_type' => $migrate_type,
			'cmpType'        => $cmpType,
			'path'           => $path,
			'dbPath'         => $dbPath,
			'filesList'      => $migrate_type == 'OBO' ? ycwp_get_dir_contents( ABSPATH ) : '',
			'rest_path'      => sanitize_text_field( get_rest_url() ),
			'wp_admin_url'   => sanitize_text_field( get_admin_url() ),
			'main_dir_path'  => sanitize_text_field( get_home_path() ),
			'wp_prefix'      => sanitize_text_field( $wpdb->prefix ),
			'wp_version'     => sanitize_text_field( $wp_version )
		]
	] );
	ycwp_update_migrate_process( $apikey, 35, 'starting migrate process' );
	wp_die(); // ajax call must die to avoid trailing 0 in your response
}

function ycwp_get_hostname( $address ) {
	$parseUrl = parse_url( trim( $address ) );

	return $parseUrl['host'];
}

function ycwp_update_migrate_process( $token, $progress, $message, $hasError = false, $error = null ) {
	$body = array(
		'progress'  => $progress,
		'token'     => $token,
		'message'   => $message,
		'hasErrors' => $hasError,
		'error'     => $error
	);
	wp_remote_post( YCWP_API_SERVER . '/migrate/update-migrate-process', [ 'body' => $body ] );
}

function ycwp_compress_to_gzip_archive( $path ): string {
	ini_set( 'memory_limit', '-1' );
	if ( file_exists( $path . 'backup.tar.gz' ) ) {
		unlink( $path . 'backup.tar.gz' );
	}
	$archive = new PharData( $path . 'backup.tar' );
	$file    = $archive->buildFromDirectory( ABSPATH );
	$archive->compress( Phar::GZ );
	unlink( $path . 'backup.tar' );


	return $path . 'backup.tar.gz';
}

function ycwp_compress_to_zip_archive( $path ): string {
	ini_set( 'memory_limit', '-1' );
	$archive = new ZipArchive();
	$archive->open( $path . 'backup.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE );
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( ABSPATH ),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ( $files as $name => $file ) {
		// Skip directories (they would be added automatically)
		if ( ! $file->isDir() ) {
			// Get real and relative path for current file
			$filePath     = $file->getRealPath();
			$relativePath = substr( $filePath, strlen( ABSPATH ) );
			// Add current file to archive
			$archive->addFile( $filePath, $relativePath );
		}
	}

// Zip archive will be created only after closing object
	$archive->close();

	return $path . '/backup.zip';
}

function ycwp_get_dir_contents( $path ) {
	$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ), RecursiveIteratorIterator::LEAVES_ONLY,
		RecursiveIteratorIterator::CATCH_GET_CHILD );

	$files = array();
	foreach ( $rii as $file ) {
		if ( ! $file->isDir() ) {
			$files[] = [
				'name'        => sanitize_text_field( $file->getPathname() ),
				'extension'   => sanitize_text_field( pathinfo( $file->getPathname(), PATHINFO_EXTENSION ) ),
				'base_name'   => pathinfo( $file->getPathname(), PATHINFO_BASENAME ),
				'size'        => filesize( $file->getPathname() ),
				'last_update' => $file->getMtime()
			];
		}
	}

	return $files;
}

function ycwp_export_database( $host, $user, $pass, $name, $tables = false, $backup_name = false, $backup_dir = false ): string {
	set_time_limit( 3000 );
	$mysqli = new mysqli( $host, $user, $pass, $name );
	$mysqli->select_db( $name );
	$mysqli->query( "SET NAMES 'utf8'" );
	$queryTables   = $mysqli->query( 'SHOW TABLES' );
	$target_tables = [];
	while ( $row = $queryTables->fetch_row() ) {
		$target_tables[] = $row[0];
	}
	if ( $tables !== false ) {
		$target_tables = array_intersect( $target_tables, $tables );
	}
	$content = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--\r\n-- Database: `" . $name . "`\r\n--\r\n\r\n\r\n";
	foreach ( $target_tables as $table ) {
		if ( empty( $table ) ) {
			continue;
		}
		$result        = $mysqli->query( 'SELECT * FROM `' . $table . '`' );
		$fields_amount = $result->field_count;
		$rows_num      = $mysqli->affected_rows;
		$res           = $mysqli->query( 'SHOW CREATE TABLE ' . $table );
		$TableMLine    = $res->fetch_row();
		$content       .= "\n\n" . $TableMLine[1] . ";\n\n";
		$TableMLine[1] = str_ireplace( 'CREATE TABLE `', 'CREATE TABLE IF NOT EXISTS `', $TableMLine[1] );
		for ( $i = 0, $st_counter = 0; $i < $fields_amount; $i ++, $st_counter = 0 ) {
			while ( $row = $result->fetch_row() ) { //when started (and every after 100 command cycle):
				if ( $st_counter % 100 == 0 || $st_counter == 0 ) {
					$content .= "\nINSERT INTO " . $table . " VALUES";
				}
				$content .= "\n(";
				for ( $j = 0; $j < $fields_amount; $j ++ ) {
					$row[ $j ] = str_replace( "\n", "\\n", addslashes( $row[ $j ] ) );
					if ( isset( $row[ $j ] ) ) {
						$content .= '"' . $row[ $j ] . '"';
					} else {
						$content .= '""';
					}
					if ( $j < ( $fields_amount - 1 ) ) {
						$content .= ',';
					}
				}
				$content .= ")";
				//every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
				if ( ( ( $st_counter + 1 ) % 100 == 0 && $st_counter != 0 ) || $st_counter + 1 == $rows_num ) {
					$content .= ";";
				} else {
					$content .= ",";
				}
				$st_counter = $st_counter + 1;
			}
		}
		$content .= "\n\n\n";
	}
	$content     .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
	$backup_name = $backup_name ? $backup_name : $name . '___(' . date( 'H-i-s' ) . '_' . date( 'd-m-Y' ) . ').sql';

	return $content;
	exit;
}


add_action( "wp_ajax_ycwp_migrate", "ycwp_handle_migrate" );

function ycwp_wp_ajax_function() {
	//DO whatever you want with data posted
	//To send back a response you have to echo the result!

	echo json_encode( [ 'code' => 200, 'data' => [] ] );
	wp_die(); // ajax call must die to avoid trailing 0 in your response
}


function ycwp_get_files_list( WP_REST_Request $request ) {

	// You can access parameters via direct array access on the object:
	$param = $request['fp'];

	return [ 'code' => 200, 'data' => ycwp_get_dir_contents( ABSPATH ) ];
}

function ycwp_get_wp_cmp_file( WP_REST_Request $request ) {
	$param = $request['fp'];

	return [ 'code' => 200, 'data' => ycwp_get_dir_contents( ABSPATH ) ];
}

function ycwp_get_file( WP_REST_Request $request ) {
	ini_set( 'memory_limit', '-1' );
	// You can access parameters via direct array access on the object:
	$param = urldecode( $request['file'] );
	header( 'Content-Description: File Transfer' );
	header( 'Content-Type: application/octet-stream' );
	header( 'Content-Disposition: attachment; filename=' . basename( $param ) );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Pragma: public' );
	header( 'Content-Length: ' . filesize( $param ) );
	ob_clean();
	flush();
	readfile( $param );

	return [ 'code' => 200, 'data' => '' ];
}

function ycwp_get_db( WP_REST_Request $request ) {
	// You can access parameters via direct array access on the object:
	$param = $request['fp'];

	return [ 'code' => 200 ];
}

function ycwp_delete_backup_file( WP_REST_Request $request ) {
	unlink( plugin_dir_path( __FILE__ ) . './backup/backup.tar.gz' );

//    unlink(plugin_dir_path(__FILE__) . './backup/ymg_back.sql');
	return [ 'code' => 200 ];
}

function ycwp_get_directory_size( $directory ) {
	$size = 0;
	$rii  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory ), RecursiveIteratorIterator::LEAVES_ONLY,
		RecursiveIteratorIterator::CATCH_GET_CHILD );
	foreach ( $rii as $file ) {
		$size += $file->getSize();
	}

	return $size;
}

function ycwp_update_site_status( $site, $status ) {
	$body = array(
		'domain' => $site,
		'status' => $status
	);

	if ( $status ) {
		$body['rest_url'] = sanitize_url( get_rest_url() );
		$response         = wp_remote_post( YCWP_API_SERVER . '/migrate/add-installed-domain', [ 'body' => $body ] );
	} else {
		$response = wp_remote_post( YCWP_API_SERVER . '/migrate/remove-installed-domain', [ 'body' => $body ] );
	}
}

function ycwp_check_blocking_plugins() {
	$plugins         = get_plugins();
	$BlockingPlugins = array( 'malcare-security' );
	$blockingFound   = [];
	foreach ( $plugins as $source => $value ) {
		if ( in_array( $value['TextDomain'], $BlockingPlugins ) && is_plugin_active( $source ) ) {
			$blockingFound[] = $value;
		}
	}

	return $blockingFound;
}

function ycwp_escape_and_sanitize( $data, $type = 'text' ) {
	switch ( $type ) {
		case 'url':
		{
			echo esc_url( sanitize_url( $data ) );
			break;
		}
		default:
		{
			echo esc_attr( sanitize_text_field( $data ) );
		}
	}
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'ycwp_rest/v1', '/get-file', array(
		'methods'  => 'GET',
		'callback' => 'ycwp_get_file',
	) );
	register_rest_route( 'ycwp_rest/v1', '/get-db', array(
		'methods'  => 'GET',
		'callback' => 'ycwp_get_db',
	) );
	register_rest_route( 'ycwp_rest/v1', '/get-files-list', array(
		'methods'  => 'GET',
		'callback' => 'ycwp_get_files_list',
	) );
	register_rest_route( 'ycwp_rest/v1', '/del-backup-file', array(
		'methods'  => 'GET',
		'callback' => 'ycwp_delete_backup_file',
	) );
} );