<?php

namespace Larry\Larry\ExposedFunctions;

abstract class AbstractExposedFunction
{
    public static string $name;
    public static string $description;
    public static bool $reprompt;
    public static bool $runOnBackend;

    // @var array[AbstractExposedFunctionParam]
    public static array $params = [];
    // @var array[$name] from new AbstractExposedFunctionParam($name))s;
    public static array $requiredParams = [];

    // @var json_encoded results from running the function
    protected string $results;

    // Run function, return JSON encoded results
    abstract protected function execute(): string;

    /**
     * Describe the function, as per OpenAI specs
     */
    public static function describeToGpt(): array
    {
        $paramsDescription = [];
        $required = [];

        foreach (static::$params as $paramName => $param) {
            // Create description for each parameter
            $paramsDescription[$paramName] = [
                'type' => $param->argumentType,
                'description' => $param->argumentDescription,
            ];

            // Include 'enum' only if argumentEnum is not empty
            if (!empty($param->argumentEnum)) {
                $paramsDescription[$paramName]['enum'] = $param->argumentEnum;
            }

            // Include in 'required' array if the param is required
            if ($param->required) {
                $required[] = $paramName;
            }
        }

        return [
            'name' => static::$name,
            'description' => static::$description,
            'parameters' => [
                'type' => 'object',
                'properties' => $paramsDescription,
                'required' => $required,
            ],
        ];
    }
}
