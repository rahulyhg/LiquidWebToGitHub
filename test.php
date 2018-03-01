<?php
//phpinfo();
ini_set('session.save_path',realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/sessions'));
ini_set('session.gc_probability', 1);
ini_set('session.gc_maxlifetime ', 1200);
// ini_set( 'upload_max_filesize', '100M' );//2
// ini_set( 'post_max_size', '100M' );//8
// ini_set( 'max_input_time', '259200' );
// ini_set( 'max_execution_time', '259200' );
// ini_set('memory_limit','300M');
phpinfo();
?>
