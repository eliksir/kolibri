<?php
/**
 * This is an abstract convenience class for interceptors. It implements empty
 * <code>init()</code> and <code>destroy()</code> methods so subclasses only have to
 * implement <code>intercept()</code>.
 */
abstract class AbstractInterceptor implements Interceptor {
	/**
	 * Creates an interceptor instance. If any configuration is supplied, matching properties
	 * are set on the instance. This requires that the concrete interceptor implementation
	 * defines the properties as protected or public.
	 *
	 * @param array $conf Configuration for this interceptor, if any.
	 */
	public function __construct (array $conf = null) {
		if ($conf !== null) {
			foreach ($conf as $param => $value) {
				$this->$param = $value;
			}
		}
	}

	/**
	 * Do nothing.
	 */
	public function init () {}
	
	/**
	 * Do nothing.
	 */
	public function destroy () {}
}
?>
