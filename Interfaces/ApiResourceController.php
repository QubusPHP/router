<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @author     Joshua Parker <josh@joshuaparker.blog>
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Interfaces;

use Psr\Http\Message\RequestInterface;

interface ApiResourceController
{
    /**
     * Display a listing of the resource.
     */
    public function index();

    /**
     * Display the specified resource.
     */
    public function show(int|string $id);

    /**
     * Store a newly created resource in storage.
     */
    public function store(RequestInterface $request);

    /**
     * Update the specified resource in storage.
     */
    public function update(RequestInterface $request);

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int|string $id);
}
