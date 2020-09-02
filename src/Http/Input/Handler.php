<?php

declare(strict_types=1);

namespace Qubus\Router\Http\Input;

use Qubus\Exception\Data\TypeException;
use Qubus\Router\Http\Request;
use Qubus\Router\Interfaces\ItemInterface;

use function array_flip;
use function array_intersect_key;
use function array_shift;
use function count;
use function file_get_contents;
use function in_array;
use function is_array;
use function json_decode;
use function parse_str;
use function strpos;
use function trim;

class Handler
{
    /** @var array */
    protected $get = [];

    /** @var array */
    protected $post = [];

    /** @var array */
    protected $file = [];

    /** @var Request */
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->parseInputs();
    }

    /**
     * Parse input values
     */
    public function parseInputs(): void
    {
        /* Parse get requests */
        if (count($_GET) !== 0) {
            $this->get = $this->parseInputItem($_GET);
        }

        /* Parse post requests */
        $postVars = $_POST;

        if (in_array($this->request->getMethod(), ['put', 'patch', 'delete'], false) === true) {
            parse_str(file_get_contents('php://input'), $postVars);
        }

        if (count($postVars) !== 0) {
            $this->post = $this->parseInputItem($postVars);
        }

        /* Parse get requests */
        if (count($_FILES) !== 0) {
            $this->file = $this->parseFiles();
        }
    }

    public function parseFiles(): array
    {
        $list = [];
        foreach ((array) $_FILES as $key => $value) {
            // Handle array input
            if (is_array($value['name']) === false) {
                $values['index'] = $key;
                try {
                    $list[$key] = File::createFromArray($values + $value);
                } catch (TypeException $e) {
                }
                continue;
            }

            $keys  = [$key];
            $files = $this->rearrangeFile($value['name'], $keys, $value);

            if (isset($list[$key]) === true) {
                $list[$key][] = $files;
            } else {
                $list[$key] = $files;
            }
        }
        return $list;
    }

    /**
     * Rearrange multi-dimensional file object created by PHP.
     *
     * @param array      $index
     * @param array|null $original
     */
    protected function rearrangeFile(array $values, &$index, $original): array
    {
        $originalIndex = $index[0];
        array_shift($index);

        $output = [];
        foreach ($values as $key => $value) {
            if (is_array($original['name'][$key]) === false) {
                try {
                    $file = File::createFromArray([
                        'index'    => empty($key) === true && empty($originalIndex) === false ? $originalIndex : $key,
                        'name'     => $original['name'][$key],
                        'error'    => $original['error'][$key],
                        'tmp_name' => $original['tmp_name'][$key],
                        'type'     => $original['type'][$key],
                        'size'     => $original['size'][$key],
                    ]);

                    if (isset($output[$key]) === true) {
                        $output[$key][] = $file;
                        continue;
                    }

                    $output[$key] = $file;
                    continue;
                } catch (TypeException $e) {
                }
            }

            $index[] = $key;

            $files = $this->rearrangeFile($value, $index, $original);

            if (isset($output[$key]) === true) {
                $output[$key][] = $files;
            } else {
                $output[$key] = $files;
            }
        }
        return $output;
    }

    /**
     * Parse input item from array
     */
    protected function parseInputItem(array $array): array
    {
        $list = [];
        foreach ($array as $key => $value) {
            // Handle array input
            if (is_array($value) === false) {
                $list[$key] = new Item($key, $value);
                continue;
            }

            $output     = $this->parseInputItem($value);
            $list[$key] = $output;
        }
        return $list;
    }

    /**
     * Find input object
     *
     * @param array ...$methods
     * @return ItemInterface|array|null
     */
    public function find(string $index, ...$methods)
    {
        $element = null;

        if (count($methods) === 0 || in_array('get', $methods, true) === true) {
            $element = $this->get($index);
        }

        if (($element === null && count($methods) === 0) || (count($methods) !== 0 && in_array('post', $methods, true) === true)) {
            $element = $this->post($index);
        }

        if (($element === null && count($methods) === 0) || (count($methods) !== 0 && in_array('file', $methods, true) === true)) {
            $element = $this->file($index);
        }

        return $element;
    }

    /**
     * Get input element value matching index
     *
     * @param array ...$methods
     * @return string|array
     */
    public function value(string $index, ?string $defaultValue = null, ...$methods)
    {
        $input  = $this->find($index, ...$methods);
        $output = [];
        /* Handle collection */
        if (is_array($input) === true) {
            /** @var Item $item */
            foreach ($input as $item) {
                $output[] = $item->getValue();
            }
            return count($output) === 0 ? $defaultValue : $output;
        }
        return $input === null || ($input !== null && trim($input->getValue()) === '') ? $defaultValue : $input->getValue();
    }

    /**
     * Check if a input-item exist
     *
     * @param array ...$methods
     */
    public function exists(string $index, ...$methods): bool
    {
        return $this->value($index, null, ...$methods) !== null;
    }

    /**
     * Find post-value by index or return default value.
     *
     * @return Item|array|string|null
     */
    public function post(string $index, ?string $defaultValue = null)
    {
        return $this->post[$index] ?? $defaultValue;
    }

    /**
     * Find file by index or return default value.
     *
     * @return File|array|string|null
     */
    public function file(string $index, ?string $defaultValue = null)
    {
        return $this->file[$index] ?? $defaultValue;
    }

    /**
     * Find parameter/query-string by index or return default value.
     *
     * @return Item|array|string|null
     */
    public function get(string $index, ?string $defaultValue = null)
    {
        return $this->get[$index] ?? $defaultValue;
    }

    /**
     * Get all get/post items
     *
     * @param array $filter Only take items in filter
     */
    public function all(array $filter = []): array
    {
        $output = $_GET;
        if ($this->request->getMethod() === 'post') {
            // Append POST data
            $output  += $_POST;
            $contents = file_get_contents('php://input');
            // Append any PHP-input json
            if (strpos(trim($contents), '{') === 0) {
                $post = json_decode($contents, true);
                if ($post !== false) {
                    $output += $post;
                }
            }
        }
        return count($filter) > 0 ? array_intersect_key($output, array_flip($filter)) : $output;
    }

    /**
     * Add GET parameter
     */
    public function addGet(string $key, Item $item): void
    {
        $this->get[$key] = $item;
    }

    /**
     * Add POST parameter
     */
    public function addPost(string $key, Item $item): void
    {
        $this->post[$key] = $item;
    }

    /**
     * Add FILE parameter
     */
    public function addFile(string $key, File $item): void
    {
        $this->file[$key] = $item;
    }
}
