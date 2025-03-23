<?php

namespace Zuno\Http\Support;

use Zuno\Database\Database;

trait ValidationRules
{
    /**
     * Validate a field based on the given rule.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name to validate.
     * @param string $rule The validation rule.
     * @param mixed $ruleValue The value associated with the rule (e.g., min:6 => 6).
     * @return string|null The error message if validation fails, otherwise null.
     */
    protected function sanitizeUserRequest(array $input, string $fieldName, string $rule, mixed $ruleValue = null): ?string
    {
        if ($this->isFileField($fieldName)) {
            return $this->validateFile($fieldName, $rule, $ruleValue);
        }

        // Handle required rule separately, even if other rules are present.
        if ($rule === 'required') {
            if ($this->isEmptyFieldRequired($input, $fieldName)) {
                return $this->_removeUnderscore(ucfirst($fieldName)) . " is required";
            }
        }

        // Check if the field is nullable and the value is null or empty
        if ($rule === 'null' && $this->isNullable($input, $fieldName)) {
            return null;
        }

        // If the value is null or empty and the rule is not 'null', proceed with validation
        if ($this->isNullable($input, $fieldName) && $rule !== 'null') {
            return null;
        } else {
            switch ($rule) {
                case 'required':
                    if ($this->isEmptyFieldRequired($input, $fieldName)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " is required";
                    }
                    break;

                case 'email':
                    if (!$this->isEmailValid($input, $fieldName)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " is invalid";
                    }
                    break;

                case 'min':
                    if ($this->isLessThanMin($input, $fieldName, $ruleValue)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must be at least " . $ruleValue . " characters";
                    }
                    break;

                case 'max':
                    if ($this->isMoreThanMax($input, $fieldName, $ruleValue)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must not exceed " . $ruleValue . " characters";
                    }
                    break;

                case 'unique':
                    if ($this->isRecordUnique($input, $fieldName, $ruleValue)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " already exists";
                    }
                    break;
                case 'date':
                    if (!$this->isDateValid($input, $fieldName)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must be a valid date";
                    }
                    break;

                case 'gte':
                    if (!$this->isDateGreaterThanOrEqual($input, $fieldName, $ruleValue)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must be greater than or equal to " . $ruleValue;
                    }
                    break;

                case 'lte':
                    if (!$this->isDateLessThanOrEqual($input, $fieldName, $ruleValue)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must be less than or equal to " . $ruleValue;
                    }
                    break;

                case 'gt':
                    if (!$this->isDateGreaterThan($input, $fieldName, $ruleValue)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must be greater than " . $ruleValue;
                    }
                    break;

                case 'lt':
                    if (!$this->isDateLessThan($input, $fieldName, $ruleValue)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must be less than " . $ruleValue;
                    }
                    break;
                case 'int':
                    if (!$this->isInteger($input, $fieldName)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must be an integer";
                    }
                    break;

                case 'float':
                    if (!$this->isFloat($input, $fieldName, $ruleValue)) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must be a float with " . $ruleValue . " decimal places";
                    }
                    break;

                case 'between':
                    if (!$this->isBetween($input, $fieldName, $ruleValue)) {
                        $range = explode(',', $ruleValue);
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must be between " . $range[0] . " and " . $range[1];
                    }
                    break;
            }
        }

        return null;
    }

    /**
     * Check if the field value is an integer.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @return bool True if the field value is an integer, false otherwise.
     */
    protected function isInteger(array $input, string $fieldName): bool
    {
        $value = $input[$fieldName] ?? '';
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Check if the field value is a float with the specified decimal places.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @param int $decimalPlaces The number of decimal places.
     * @return bool True if the field value is a valid float with the specified decimal places, false otherwise.
     */
    protected function isFloat(array $input, string $fieldName, int $decimalPlaces): bool
    {
        $value = $input[$fieldName] ?? '';
        if (!is_numeric($value)) {
            return false;
        }

        // Check if the number of decimal places matches the rule
        $decimalPart = explode('.', $value)[1] ?? '';
        return strlen($decimalPart) <= $decimalPlaces;
    }

    /**
     * Check if the field value is between the given range.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @param string $ruleValue The range (e.g., "2,5").
     * @return bool True if the field value is within the range, false otherwise.
     */
    protected function isBetween(array $input, string $fieldName, string $ruleValue): bool
    {
        $value = $input[$fieldName] ?? '';
        if (!is_numeric($value)) {
            return false;
        }

        $range = explode(',', $ruleValue);
        $min = (float)$range[0];
        $max = (float)$range[1];

        return $value >= $min && $value <= $max;
    }

    /**
     * Check if the field value is nullable (null or empty).
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @return bool True if the field value is null or empty, false otherwise.
     */
    protected function isNullable(array $input, string $fieldName): bool
    {
        $value = $input[$fieldName] ?? '';
        return $value === null || $value === '';
    }

    /**
     * Check if the field is a valid date.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @return bool True if the field is a valid date, false otherwise.
     */
    protected function isDateValid(array $input, string $fieldName): bool
    {
        $date = $input[$fieldName] ?? '';
        return strtotime($date) !== false;
    }

    /**
     * Check if the field value is greater than or equal to the given date.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @param string $ruleValue The date to compare against.
     * @return bool True if the field value is greater than or equal to the given date, false otherwise.
     */
    protected function isDateGreaterThanOrEqual(array $input, string $fieldName, string $ruleValue): bool
    {
        $date = $input[$fieldName] ?? '';
        $compareDate = $ruleValue === 'today' ? date('Y-m-d') : $ruleValue;
        return strtotime($date) >= strtotime($compareDate);
    }

    /**
     * Check if the field value is less than or equal to the given date.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @param string $ruleValue The date to compare against.
     * @return bool True if the field value is less than or equal to the given date, false otherwise.
     */
    protected function isDateLessThanOrEqual(array $input, string $fieldName, string $ruleValue): bool
    {
        $date = $input[$fieldName] ?? '';
        $compareDate = $ruleValue === 'today' ? date('Y-m-d') : $ruleValue;
        return strtotime($date) <= strtotime($compareDate);
    }

    /**
     * Check if the field value is greater than the given date.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @param string $ruleValue The date to compare against.
     * @return bool True if the field value is greater than the given date, false otherwise.
     */
    protected function isDateGreaterThan(array $input, string $fieldName, string $ruleValue): bool
    {
        $date = $input[$fieldName] ?? '';
        $compareDate = $ruleValue === 'today' ? date('Y-m-d') : $ruleValue;
        return strtotime($date) > strtotime($compareDate);
    }

    /**
     * Check if the field value is less than the given date.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @param string $ruleValue The date to compare against.
     * @return bool True if the field value is less than the given date, false otherwise.
     */
    protected function isDateLessThan(array $input, string $fieldName, string $ruleValue): bool
    {
        $date = $input[$fieldName] ?? '';
        $compareDate = $ruleValue === 'today' ? date('Y-m-d') : $ruleValue;
        return strtotime($date) < strtotime($compareDate);
    }

    /**
     * Check if the field is a file field.
     *
     * @param string $fieldName The field name.
     * @return bool True if the field is a file field, false otherwise.
     */
    protected function isFileField(string $fieldName): bool
    {
        return isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Validate a file field based on the given rule.
     *
     * @param string $fieldName The file field name.
     * @param string $rule The validation rule.
     * @param mixed $ruleValue The value associated with the rule.
     * @return string|null The error message if validation fails, otherwise null.
     */
    protected function validateFile(string $fieldName, string $rule, mixed $ruleValue = null): ?string
    {
        $file = $_FILES[$fieldName];

        switch ($rule) {
            case 'required':
                if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                    return $this->_removeUnderscore(ucfirst($fieldName)) . " is required";
                }
                break;

            case 'image':
                if (!@getimagesize($file['tmp_name'])) {
                    return $this->_removeUnderscore(ucfirst($fieldName)) . " must be an image";
                }
                break;

            case 'mimes':
                $allowedTypes = explode(',', $ruleValue);
                $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($fileExtension, $allowedTypes)) {
                    return $this->_removeUnderscore(ucfirst($fieldName)) . " must be of type: " . implode(', ', $allowedTypes) . ".";
                }
                break;

            case 'dimensions':
                $dimensions = $this->parseDimensionsRule($ruleValue);
                if ($dimensions) {
                    list($width, $height) = getimagesize($file['tmp_name']);

                    if (isset($dimensions['min_width']) && $width < $dimensions['min_width']) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must have a minimum width of " . $dimensions['min_width'] . " pixels.";
                    }

                    if (isset($dimensions['min_height']) && $height < $dimensions['min_height']) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must have a minimum height of " . $dimensions['min_height'] . " pixels.";
                    }

                    if (isset($dimensions['max_width']) && $width > $dimensions['max_width']) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must have a maximum width of " . $dimensions['max_width'] . " pixels.";
                    }

                    if (isset($dimensions['max_height']) && $height > $dimensions['max_height']) {
                        return $this->_removeUnderscore(ucfirst($fieldName)) . " must have a maximum height of " . $dimensions['max_height'] . " pixels.";
                    }
                }
                break;

            case 'max':
                $maxSize = $this->parseSizeRule($ruleValue);
                if ($file['size'] > $maxSize) {
                    return $this->_removeUnderscore(ucfirst($fieldName)) . " must not exceed " . $this->formatBytes($maxSize) . ".";
                }
                break;
        }

        return null;
    }

    /**
     * Parse the dimensions rule value.
     *
     * @param string $ruleValue The dimensions rule value.
     * @return array<string, int>|null The parsed dimensions or null if invalid.
     */
    protected function parseDimensionsRule(string $ruleValue): ?array
    {
        $dimensions = [];
        $parts = explode(',', $ruleValue);

        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                list($key, $value) = explode('=', $part);
                $dimensions[trim($key)] = (int)trim($value);
            }
        }

        return !empty($dimensions) ? $dimensions : null;
    }

    /**
     * Parse the size rule value.
     *
     * @param string $ruleValue The size rule value.
     * @return int The size in bytes.
     */
    protected function parseSizeRule(string $ruleValue): int
    {
        $unit = strtoupper(substr($ruleValue, -1));
        $size = (int)substr($ruleValue, 0, -1);

        switch ($unit) {
            case 'K': // Kilobytes
                return $size * 1024;
            case 'M': // Megabytes
                return $size * 1024 * 1024;
            case 'G': // Gigabytes
                return $size * 1024 * 1024 * 1024;
            default: // Bytes
                return (int)$ruleValue;
        }
    }

    /**
     * Format bytes into a human-readable format.
     *
     * @param int $bytes The size in bytes.
     * @return string The formatted size.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    /**
     * Check if a required field is empty.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @return bool
     */
    protected function isEmptyFieldRequired(array $input, string $fieldName): bool
    {
        return !isset($input[$fieldName]) || $input[$fieldName] === '';
    }

    /**
     * Check if a field value is less than the minimum length.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @param int $value The minimum length.
     * @return bool
     */
    protected function isLessThanMin(array $input, string $fieldName, int $value): bool
    {
        return strlen($input[$fieldName]) < $value;
    }

    /**
     * Check if a field value exceeds the maximum length.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @param int $value The maximum length.
     * @return bool
     */
    protected function isMoreThanMax(array $input, string $fieldName, int $value): bool
    {
        return strlen($input[$fieldName]) > $value;
    }

    /**
     * Check duplicate records exists or not
     * @param mixed $pdo
     * @param mixed $tableName
     * @param mixed $fieldName
     * @param mixed $fieldValue
     * @return bool
     */
    public function checkRecordExists($pdo, $tableName, $fieldName, $fieldValue): bool
    {
        try {
            $sql = "SELECT 1 FROM `$tableName` WHERE `$fieldName` = :fieldValue LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':fieldValue', $fieldValue);
            $stmt->execute();

            return $stmt->fetchColumn() !== false;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Check if a record is unique.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @param string $value The table name.
     * @return bool
     */
    protected function isRecordUnique(array $input, string $fieldName, string $value): bool
    {
        $pdo = Database::getPdoInstance();

        return $this->checkRecordExists($pdo, $value, $fieldName, $input[$fieldName]);
    }

    /**
     * Validate if the email is valid.
     *
     * @param array $input The input data.
     * @param string $fieldName The field name.
     * @return bool
     */
    protected function isEmailValid(array $input, string $fieldName): bool
    {
        $email = $input[$fieldName] ?? '';
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Remove underscores from a string and capitalize words.
     *
     * @param string $string The input string.
     * @return string
     */
    protected function _removeUnderscore(string $string): string
    {
        return str_replace("_", " ", $string);
    }

    /**
     * Remove the suffix from a rule string.
     *
     * @param string $string The rule string.
     * @return string
     */
    protected function _removeRuleSuffix(string $string): string
    {
        return explode(":", $string)[0];
    }

    /**
     * Get the suffix from a rule string.
     *
     * @param string $string The rule string.
     * @return string|null
     */
    protected function _getRuleSuffix(string $string): ?string
    {
        $arr = explode(":", $string);

        return $arr[1] ?? null;
    }
}
