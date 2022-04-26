<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler;

final class CompileResult implements \Stringable
{
    private string $mimeType;

    public function __construct(
        private readonly CompileType $type,
        private readonly string $result,
        ?string $mimeType = null,
    ) {
        $this->mimeType = $mimeType ?? $this->inferMimeType($this->type);
    }

    public function getType(): CompileType
    {
        return $this->type;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function __toString(): string
    {
        return $this->getResult();
    }

    private function inferMimeType(CompileType $type): string
    {
        return match ($type) {
            CompileType::Plain => 'text/plain',
            CompileType::Css => 'text/css',
            CompileType::Json => 'application/json',
            CompileType::Twig => 'text/html',
        };
    }
}
