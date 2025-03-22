<?php

namespace Zuno\Http\Validation;

use Zuno\Session\MessageBag;
use Zuno\Http\Support\ValidationRules;
use Zuno\Http\Response;
use Zuno\Http\Exceptions\HttpResponseException;

trait Rule
{
    use ValidationRules;

    /**
     * Validate the input data against the given rules.
     *
     * @access public
     * @param array $rules Associative array of field names and their validation rules.
     * @return null|array|\Zuno\Http\Response
     */
    public function sanitize(array $rules): array|Response
    {
        $errors = [];
        $input = $this->all();
        MessageBag::flashInput();

        if (is_array($input)) {
            foreach ($rules as $fieldName => $value) {
                $fieldRules = explode("|", $value);
                foreach ($fieldRules as $rule) {
                    $ruleValue = $this->_getRuleSuffix($rule);
                    $rule = $this->_removeRuleSuffix($rule);
                    $errorMessage = $this->sanitizeUserRequest($input, $fieldName, $rule, $ruleValue);
                    if ($errorMessage) {
                        $errors[$fieldName][] = $errorMessage;
                    }
                }
            }
        }

        if (!empty($errors)) {
            if (request()->isAjax()) {
                throw new HttpResponseException(
                    $errors,
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $this->setErrors($errors);
            foreach ($errors as $key => $error) {
                flash()->set($key, implode(',', (array)$error));
            }

            redirect()->back()->withInput()->withErrors($errors)->send();
            exit;
        }

        $this->setPassedData($input);
        return $input;
    }
}
