<?php

namespace Zuno\Http;

use Zuno\Session\Input;
use Zuno\Http\Response;
use Zuno\Http\Support\ValidationRules;

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

        // Flash input requested data
        Input::flashInput();

        if (is_array($input)) {
            foreach ($rules as $fieldName => $value) {
                $fieldRules = explode("|", $value);

                foreach ($fieldRules as $rule) {
                    $ruleValue = $this->_getRuleSuffix($rule);
                    $rule = $this->_removeRuleSuffix($rule);

                    $errorMessage = $this->sanitizeUserRequest($input, $fieldName, $rule, $ruleValue);

                    if ($errorMessage) {
                        $errors[$fieldName][$rule] = $errorMessage;
                    }
                }
            }
        }

        if (!empty($errors)) {
            if (method_exists($this, 'setErrors')) {
                $this->setErrors($errors);
            }

            foreach ($errors as $key => $error) {
                flash()->set($key, implode(',', (array)$error));
            }

            redirect()->back()->withInput()->withErrors($errors)->send();
            exit;
        }

        if (method_exists($this, 'setPassedData')) {
            $this->setPassedData($input);
        }

        return $input;
    }
}
