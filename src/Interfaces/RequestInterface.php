<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

interface ApiResourceControllerInterface
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
