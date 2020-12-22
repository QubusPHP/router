<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Interfaces;

interface ApiResourceController
{
    /**
     * Display a listing of the resource.
     */
    public function index();

    /**
     * Display the specified resource.
     *
     * @param  int $id
     */
    public function show($id);

    /**
     * Store a newly created resource in storage.
     */
    public function store();

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     */
    public function update($id);

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     */
    public function destroy($id);
}
