<?php

namespace Redot\Support;

class SettingDefinition
{
    /**
     * The setting's default value.
     */
    protected mixed $default = null;

    /**
     * Whether the setting has a declared default value.
     */
    protected bool $defined = false;

    /**
     * The setting's validation rules.
     *
     * @var array<int|string, mixed>|string|null
     */
    protected array|string|null $rules = null;

    /**
     * The setting's input type.
     */
    protected ?string $type = null;

    /**
     * Set the setting's validation rules.
     */
    public function rules(array|string $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Set the setting's default value.
     */
    public function default(mixed $value): static
    {
        $this->default = $value;
        $this->defined = true;

        return $this;
    }

    /**
     * Set the setting's input type.
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function file(): static
    {
        return $this->type('file');
    }

    public function boolean(): static
    {
        return $this->type('boolean');
    }

    public function string(): static
    {
        return $this->type('string');
    }

    public function integer(): static
    {
        return $this->type('integer');
    }

    public function float(): static
    {
        return $this->type('float');
    }

    public function array(): static
    {
        return $this->type('array');
    }

    /**
     * Determine whether a default value was declared.
     */
    public function hasDefault(): bool
    {
        return $this->defined;
    }

    /**
     * Get the declared default value.
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Get the declared validation rules.
     */
    public function getRules(): array|string|null
    {
        return $this->rules;
    }

    /**
     * Get the declared input type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Convert the definition to its schema representation.
     */
    public function toArray(): array
    {
        $definition = [];

        if ($this->type !== null) {
            $definition['type'] = $this->type;
        }

        if ($this->rules !== null) {
            $definition['rules'] = $this->rules;
        }

        if ($this->defined) {
            $definition['default'] = $this->default;
        }

        return $definition;
    }
}
