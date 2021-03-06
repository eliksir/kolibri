<?php
/**
 * This class is a validateable model proxy. This model proxy is used for <code>Validateable</code>
 * models in order to add support for validation.
 */
class ValidateableModelProxy extends ModelProxy {

	/**
	 * @var Validator
	 */
	private $validator;

	/**
	 * Creates a <code>ValidateableModelProxy</code> instance for the model supplied. It is assumed
	 * that the model has been verified <code>Validateable</code>.
	 *
	 * @param object $model		Model to proxy.
	 */
	public function __construct ($model) {
		parent::__construct($model);
	}

	/**
	 * Overrides ModelProxy::save() by making sure contained models are valid before they
	 * are saved. Contained models that have already been validated are not validated again.
	 *
	 * @return mixed	 Number of saved rows in the database, or <code>false</code> if a
	 *					 a preSave() method on a model returned false.
	 * @throws Exception If a model is invalid.
	 *
	 */
	public function save () {
		if (!$this->validate()) {
			throw new Exception('Attempted to save an invalid model.');
		}
		return parent::save();
	}

	/**
	 * Validates the contained models and returns <code>true</code> if all are valid or
	 * <code>false</code> if one or more are invalid.
	 *
	 * @return bool		<code>true</code> if all models are valid, <code>false</code> if not.
	 */
	public function validate () {
		$this->proxifyInnerModels();
		$this->initValidator();
		$isValid = true; // We start out with valid state

		foreach ($this->models as $model) {
			// And set invalid for invalid objects, but never again valid
			$isValid = ($this->validateModel($model) ? $isValid : false);
			foreach ($model as $property) {
				if ($property instanceof ValidateableModelProxy) {
					// Recurse to validate inner models
					$isValid = $property->validate() && $isValid;
				}
			}
		}

		return $isValid;
	}

	/**
	 * Alias of validate() to accommodate for more readable code (i.e. for tests).
	 *
	 * @return bool
	 */
	public function isValid () {
		return $this->validate();
	}

	/**
	 * Validates the supplied model. If the model has already been validated (and is unchanged
	 * since) its previous result is returned, else the Validator is invoked to validate the model.
	 *
	 * @param object $model	The model to validate.
	 * @return bool
	 */
	protected function validateModel ($model) {
		if (property_exists($model, 'isValid')) {
			return $model->isValid;
		}
		return $this->validator->validate($model);
	}

	/**
	 * Remove validated flag, as changes have been made to its state and it's unknown whether it
	 * is valid or not.
	 *
	 * @param object $model   The model whose state has changed.
	 * @param mixed $newValue Optional value that was set on a property of the model. If
	 *                        <code>NULL</code>, an object or an array we assume proxifying
	 *                        inner models may be required.
	 */
	protected function modelChanged ($model, $newValue = null) {
		parent::modelChanged($model, $newValue);
		unset($model->isValid);
	}

	/**
	 * Initialized the validator if not already initialized.
	 */
	private function initValidator () {
		if (!isset($this->validator)) {
			$conf = Config::getValidationConfig();
			$this->validator = new Validator($conf['classes'], $conf['messages']);
		}
	}
}
?>
