<?php

namespace SdFramework\Validation;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $messages = [];
    private static array $defaultMessages = [
        'required' => 'The :field field is required.',
        'email' => 'The :field must be a valid email address.',
        'min' => 'The :field must be at least :param characters.',
        'max' => 'The :field may not be greater than :param characters.',
        'numeric' => 'The :field must be a number.',
        'alpha' => 'The :field may only contain letters.',
        'alpha_num' => 'The :field may only contain letters and numbers.',
        'url' => 'The :field must be a valid URL.',
        'date' => 'The :field must be a valid date.',
        'array' => 'The :field must be an array.',
        'in' => 'The selected :field is invalid.',
        'unique' => 'The :field has already been taken.',
        'confirmed' => 'The :field confirmation does not match.',
        'regex' => 'The :field format is invalid.',
    ];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $rules = is_string($rules) ? explode('|', $rules) : $rules;

            foreach ($rules as $rule) {
                $params = [];
                
                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                    $params = explode(',', $param);
                }

                $method = 'validate' . str_replace('_', '', ucwords($rule, '_'));
                
                if (!method_exists($this, $method)) {
                    throw new \InvalidArgumentException("Validation rule '{$rule}' does not exist.");
                }

                $value = $this->getValue($field);
                
                if (!$this->$method($field, $value, $params)) {
                    $this->addError($field, $rule, $params);
                }
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function getValue(string $field)
    {
        return $this->data[$field] ?? null;
    }

    private function addError(string $field, string $rule, array $params = []): void
    {
        $message = $this->messages["{$field}.{$rule}"] 
            ?? $this->messages[$rule] 
            ?? self::$defaultMessages[$rule] 
            ?? "The {$field} field is invalid.";

        $message = str_replace(':field', $field, $message);
        
        if (!empty($params)) {
            $message = str_replace(':param', $params[0], $message);
        }

        $this->errors[$field][] = $message;
    }

    private function validateRequired(string $field, $value): bool
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif (is_array($value) && count($value) < 1) {
            return false;
        }
        return true;
    }

    private function validateEmail(string $field, $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateMin(string $field, $value, array $params): bool
    {
        $length = is_string($value) ? mb_strlen($value) : $value;
        return $length >= (int) $params[0];
    }

    private function validateMax(string $field, $value, array $params): bool
    {
        $length = is_string($value) ? mb_strlen($value) : $value;
        return $length <= (int) $params[0];
    }

    private function validateNumeric(string $field, $value): bool
    {
        return is_numeric($value);
    }

    private function validateAlpha(string $field, $value): bool
    {
        return preg_match('/^[\pL\pM]+$/u', $value);
    }

    private function validateAlphaNum(string $field, $value): bool
    {
        return preg_match('/^[\pL\pM\pN]+$/u', $value);
    }

    private function validateUrl(string $field, $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function validateDate(string $field, $value): bool
    {
        return strtotime($value) !== false;
    }

    private function validateArray(string $field, $value): bool
    {
        return is_array($value);
    }

    private function validateIn(string $field, $value, array $params): bool
    {
        return in_array($value, $params);
    }

    private function validateConfirmed(string $field, $value): bool
    {
        return $value === ($this->data["{$field}_confirmation"] ?? null);
    }

    private function validateRegex(string $field, $value, array $params): bool
    {
        return preg_match($params[0], $value);
    }

    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new static($data, $rules, $messages);
    }
}
