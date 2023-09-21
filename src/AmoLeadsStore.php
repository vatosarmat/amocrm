<?php

namespace App;

class AmoLeadsStore implements AmoEntityStore
{
    const FIELDS = ['name', 'price'];

    public static \mysqli $mysqli;

    private function normalizeData(array $data): array
    {
        $data['name'] = $data['name'] ?? '';
        $data['price'] = isset($data['price']) ? intval($data['price']) : 0;

        return $data;
    }

    public function insert(array $data)
    {
        $data = $this->normalizeData($data);

        self::$mysqli->execute_query('INSERT INTO leads (id,'.implode(',', self::FIELDS).') VALUES (?,?,?)',
            [$data['id'], $data['name'], $data['price']]);
    }

    public function update(array $data): array
    {
        $data = $this->normalizeData($data);

        $result = self::$mysqli->execute_query('SELECT id,'.implode(',', self::FIELDS).' FROM leads WHERE id=?',
            [$data['id']]);

        if ($result && ($lead = $result->fetch_assoc())) {
            $changed = [];
            if ($data['name'] !== $lead['name']) {
                $changed['name'] = ['name' => 'Название', 'values' => [$data['name']]];
            }
            if ($data['price'] !== $lead['price']) {
                $changed['price'] = ['name' => 'Бюджет', 'values' => [$data['price']]];
            }

            self::$mysqli->execute_query(
                'UPDATE leads SET '.implode(',', array_map(fn ($f) => $f.'=?', self::FIELDS)).' WHERE id=?',
                [$data['name'], $data['price'], $data['id']]);

            return $changed;
        } else {
            $this->insert($data);
            unset($data['id']);

            return $data;
        }
    }
}
