<?php
/*
 * Defines the availible interceptors. The keys defined here are used to define stacks below, or reference
 * interceptors directly in your <code>config.php</code>. Interceptors whose names are wrapped as a key in
 * an array passes those settings on to the interceptor.
 */
$interceptors = array(
		'message'     => 'MessageInterceptor',
		'validation'  => 'ValidationInterceptor',
		'error'       => array('ErrorInterceptor' => array(
			'result' => 'PhpResult',
			'view' => '/error'
		)),
		'session'     => 'SessionInterceptor',
		'auth'        => 'AuthInterceptor',
		'model'       => 'ModelInterceptor',
		'params'      => 'ParametersInterceptor',
		'upload'      => 'UploadInterceptor',
		'utils'       => 'UtilsInterceptor',
		'transaction' => 'TransactionInterceptor'
);

/*
$interceptorConfigs = array(
	'ErrorInterceptor' => array(
		'result' => 'PhpResult',
		'view' => '/error'
	),
	'AuthInterceptor'  => array(
			'userModel' => 'AuthUser',
			'userKey'   => 'user',
			'loginUri'  => '/login'
	)	
);
*/

/*
 * Defines stacks of interceptors. This makes it easy to group together several interceptors, which just as
 * interceptor names, can be referenced directly in <code>main.php</code> or your own
 * <code>config.php</code>.
 */
$interceptorStacks = array(
		'defaultStack' => array('session', 'message', 'error', 'transaction', 'model', 'validation'),
		'authStack'    => array('session', 'message', 'error', 'transaction', 'auth', 'model', 'validation'),
);
?>
