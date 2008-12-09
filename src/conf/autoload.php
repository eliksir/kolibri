<?php
/*
 * Defines classes that may be loaded upon its first use by __autoload(). They are not automatically
 * loaded unless they are actually used. See <code>UtilsInterceptor</code> for automatic loading of utils.
 */
$autoloadClasses = array(
		'DefaultActionMapper'    => '/core/DefaultActionMapper.php',
		'ActionSupport'          => '/actions/ActionSupport.php',
		'AuthAware'              => '/actions/AuthAware.php',
		'MessageAware'           => '/actions/MessageAware.php',
		'ModelAware'             => '/actions/ModelAware.php',
		'ParametersAware'        => '/actions/ParametersAware.php',
		'SessionAware'           => '/actions/SessionAware.php',
		'UploadAware'            => '/actions/UploadAware.php',
		'ValidationAware'        => '/actions/ValidationAware.php',
		'DatabaseException'      => '/database/DatabaseException.php',
		'DatabaseFactory'        => '/database/DatabaseFactory.php',
		'ResultSet'              => '/database/ResultSet.php',
		'ResultSetArray'         => '/database/ResultSetArray.php',
		'SqlException'           => '/database/SqlException.php',
		'AbstractInterceptor'    => '/interceptors/AbstractInterceptor.php',
		'AuthInterceptor'        => '/interceptors/AuthInterceptor.php',
		'ErrorInterceptor'       => '/interceptors/ErrorInterceptor.php',
		'Interceptor'            => '/interceptors/Interceptor.php',
		'MessageInterceptor'     => '/interceptors/MessageInterceptor.php',
		'ModelInterceptor'       => '/interceptors/ModelInterceptor.php',
		'ParametersInterceptor'  => '/interceptors/ParametersInterceptor.php',
		'SessionInterceptor'     => '/interceptors/SessionInterceptor.php',
		'UploadInterceptor'      => '/interceptors/UploadInterceptor.php',
		'UtilsInterceptor'       => '/interceptors/UtilsInterceptor.php',
		'ValidatorInterceptor'   => '/interceptors/ValidatorInterceptor.php',
		'TransactionInterceptor' => '/interceptors/TransactionInterceptor.php',
		'DataProvided'           => '/models/DataProvided.php',
		'ModelProxy'             => '/models/ModelProxy.php',
		'Models'                 => '/models/Models.php',
		'Validateable'           => '/models/Validateable.php',
		'ValidateableModelProxy' => '/models/ValidateableModelProxy.php',
		'AbstractResult'         => '/results/AbstractResult.php',
		'FileResult'             => '/results/FileResult.php',
		'ForwardResult'          => '/results/ForwardResult.php',
		'JsonResult'             => '/results/JsonResult.php',
		'NotFoundResult'         => '/results/NotFoundResult.php',
		'PhpResult'              => '/results/PhpResult.php',
		'RedirectResult'         => '/results/RedirectResult.php',
		'Result'                 => '/results/Result.php',
		'SmartyResult'           => '/results/SmartyResult.php',
		'TextResult'             => '/results/TextResult.php',
		'XsltResult'             => '/results/XsltResult.php',
		'Validator'              => '/validation/Validator.php',
		'ValidationHelper'       => '/validation/ValidationHelper.php');
?>