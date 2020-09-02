<?php

declare(strict_types=1);

namespace Qubus\Router\Http\Input;

use Qubus\Exception\Data\TypeException;
use Qubus\Router\Interfaces\ItemInterface;

use function file_get_contents;
use function move_uploaded_file;
use function pathinfo;
use function str_replace;
use function strtolower;
use function ucfirst;

use const PATHINFO_EXTENSION;

class File implements ItemInterface
{
    public $index;
    public $name;
    public $filename;
    public $size;
    public $type;
    public $errors;
    public $tmpName;

    public function __construct(string $index)
    {
        $this->index  = $index;
        $this->errors = 0;
        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', strtolower($this->index)));
    }

    /**
     * Create from array
     *
     * @throws TypeException
     * @return static
     */
    public static function createFromArray(array $values): self
    {
        if (isset($values['index']) === false) {
            throw new TypeException('Index key is required');
        }

        /* Easy way of ensuring that all indexes-are set and not filling the screen with isset() */

        $values += [
            'tmp_name' => null,
            'type'     => null,
            'size'     => null,
            'name'     => null,
            'error'    => null,
        ];

        return (new static($values['index']))
            ->setSize((int) $values['size'])
            ->setError((int) $values['error'])
            ->setType($values['type'])
            ->setTmpName($values['tmp_name'])
            ->setFilename($values['name']);
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * Set input index
     *
     * @return static
     */
    public function setIndex(string $index): ItemInterface
    {
        $this->index = $index;
        return $this;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * Set file size
     *
     * @return static
     */
    public function setSize(int $size): ItemInterface
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Get mime-type of file
     */
    public function getMime(): string
    {
        return $this->getType();
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @return static
     */
    public function setType(string $type): ItemInterface
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Returns extension without "."
     */
    public function getExtension(): string
    {
        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     * Get human friendly name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set human friendly name.
     * Useful for adding validation etc.
     *
     * @return static
     */
    public function setName(string $name): ItemInterface
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set filename
     *
     * @param string $name
     * @return static
     */
    public function setFilename($name): ItemInterface
    {
        $this->filename = $name;
        return $this;
    }

    /**
     * Get filename
     *
     * @return null|string mixed
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * Move the uploaded temporary file to it's new home
     *
     * @param string $destination
     */
    public function move($destination): bool
    {
        return move_uploaded_file($this->tmpName, $destination);
    }

    /**
     * Get file contents
     */
    public function getContents(): string
    {
        return file_get_contents($this->tmpName);
    }

    /**
     * Return true if an upload error occurred.
     */
    public function hasError(): bool
    {
        return $this->getError() !== 0;
    }

    /**
     * Get upload-error code.
     */
    public function getError(): int
    {
        return (int) $this->errors;
    }

    /**
     * Set error
     *
     * @param int $error
     * @return static
     */
    public function setError($error): ItemInterface
    {
        $this->errors = (int) $error;
        return $this;
    }

    public function getTmpName(): string
    {
        return $this->tmpName;
    }

    /**
     * Set file temp. name
     *
     * @param string $name
     * @return static
     */
    public function setTmpName($name): ItemInterface
    {
        $this->tmpName = $name;
        return $this;
    }

    public function __toString(): string
    {
        return $this->getTmpName();
    }

    public function getValue(): ?string
    {
        return $this->getFilename();
    }

    /**
     * @return static
     */
    public function setValue(string $value): ItemInterface
    {
        $this->filename = $value;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'tmp_name' => $this->tmpName,
            'type'     => $this->type,
            'size'     => $this->size,
            'name'     => $this->name,
            'error'    => $this->errors,
            'filename' => $this->filename,
        ];
    }
}
