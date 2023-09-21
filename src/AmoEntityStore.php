<?php

namespace App;

interface AmoEntityStore
{
    public function insert(array $data);

    public function update(array $data): array;
}
