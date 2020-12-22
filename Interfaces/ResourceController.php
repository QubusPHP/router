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

interface ResourceController
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
     * Show the form for creating a new resource.
     */
    public function create();

    /**
     * Show the form/view for editing the specified resource.
     *
     * @param  int $id
     */
    public function edit($id);

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
