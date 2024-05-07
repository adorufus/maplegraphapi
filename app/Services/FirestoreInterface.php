<?php

namespace App\Services;
use Illuminate\Support\Collection;

interface Firestore
{
    /**
     * Create New Collection Item
     *
     * @var string $collection
     * @var array $data
     *
     * @return array
     */
    public function create(string $collection, array $data): array;

    /**
     * Fetch Existing Collection Items
     *
     * @var string $collection
     * @return Illuminate\Support\Collection
     */
    public function fetch(string $collection): Collection;

    /**
     * Edit Existing Item in a collection
     *
     * @var string $collection
     * @var string $document
     * @var array $data
     *
     * @return array
     */
    public function edit(string $collection, string $document, array $data): array;

    /**
     * Delete Existing Collection
     *
     * @var string $collection
     * @var string $document
     *
     * @return void
     */
    public function destory(string $collection, string $document): void;
}