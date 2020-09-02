<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

interface ResourceControllerInterface
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
