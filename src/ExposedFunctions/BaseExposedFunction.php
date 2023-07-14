<?php

namespace Larry\Larry\ExposedFunctions;

use Exception;
use ReflectionMethod;

abstract class BaseExposedFunction
{
    public static string $name;
    public static string $description;
    public static bool $closesConversation = false;
    public static bool $speaksResultToUser = false;
    protected static array $params = [];

    /**
     * Validate, then describe to GPT.
     *  Running validation here so that, if function is poorly constructed, we error before charging a prompt.
     *
     * @return void
     * @throws Exception
     */
    public static function validate(): void
    {
        $reflection = new \ReflectionMethod(static::class, 'execute');

        $parameters = $reflection->getParameters();

        // Verify that the number of parameters match.
        if (count(static::$params) !== count($parameters)) {
            throw new \Exception("Parameter count mismatch in " . static::class);
        }

        // Verify the parameter names match.
        foreach ($parameters as $index => $parameter) {
            $paramName = array_keys(static::$params)[$index];

            if ($paramName !== $parameter->getName()) {
                throw new \Exception("Parameter name mismatch in " . static::class);
            }
        }
    }

    public static function describe(): array
    {
        self::validate();

        $parameters = [];
        foreach (static::$params as $paramName => $paramProperties) {
            $parameters[$paramName] = [
                'type' => $paramProperties['type'],
            ];

            if (isset($paramProperties['description'])) {
                $parameters[$paramName]['description'] = $paramProperties['description'];
            }

            if (isset($paramProperties['enum'])) {
                $parameters[$paramName]['enum'] = $paramProperties['enum'];
            }
        }

        $required = array_keys(array_filter(static::$params, function ($param) {
            return $param['required'] ?? false;
        }));

        return [
            'name' => static::$name,
            'description' => static::$description,
            'parameters' => [
                'type' => 'object',
                'properties' => $parameters,
                'required' => $required,
            ],
        ];
    }

    final public static function parseArgs(string $jsonArgs): array
    {
        // Decoding JSON to an associative array
        $args = json_decode($jsonArgs, true);

        // Rearrange $args to match the order in the static $params
        $executionArguments = [];
        foreach (static::$params as $paramName => $paramDetails) {
            if (array_key_exists($paramName, $args)) {
                $executionArguments[$paramName] = $args[$paramName];
            } else if (array_key_exists('required', $paramDetails) && $paramDetails['required']) {
                throw new Exception("Required parameter $paramName is missing");
            }
        }

        return $executionArguments;

        // Call execute function with argument array
        // $result = call_user_func_array([$this, 'execute'], $this->executionArguments);
    }
}
