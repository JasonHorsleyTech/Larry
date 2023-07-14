<?php

namespace Larry\Larry\ExposedFunctions;

class ExposedFunctionParam
{
    public string $name;
    public string $description;
    public string $type;
    public bool $required = false;
    public array $allows = [];

    private static ?self $instance = null;

    private function __construct()
    {
    }

    public function describe(): array
    {
        $paramDescription = [
            'type' => $this->type,
            'description' => $this->description,
        ];

        if (!empty($this->allows)) {
            $paramDescription['enum'] = $this->allows;
        }

        return $paramDescription;
    }

    private static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public static function setName(string $name): self
    {
        $instance = self::getInstance();
        $instance->name = $name;
        return $instance;
    }

    public function setDescription(string $description): self
    {
        $instance = self::getInstance();
        $instance->description = $description;
        return $instance;
    }

    public function setType(string $type): self
    {
        $instance = self::getInstance();
        $instance->type = $type;
        return $instance;
    }

    public function setRequired(bool $required = true): self
    {
        $instance = self::getInstance();
        $instance->required = $required;
        return $instance;
    }

    public function setAllows(...$values): self
    {
        $instance = self::getInstance();
        $instance->allows = $values;
        return $instance;
    }
}
