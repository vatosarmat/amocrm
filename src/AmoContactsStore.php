<?php

namespace App;

class AmoContactsStore implements AmoEntityStore
{
    const FIELDS = ['name', 'custom_fields'];

    public static \mysqli $mysqli;

    private function normalizeData(array $data): array
    {
        $data['name'] = $data['name'] ?? '';
        $data['custom_fields'] = array_reduce($data['custom_fields'] ?? [],
            function ($acc, $item) {
                $acc[$item['code']] = ['name' => $item['name'], 'values' => array_map(fn ($el) => $el['value'], $item['values'])];

                return $acc;
            },
            []);

        return $data;
    }

    public function insert(array $data)
    {
        $data = $this->normalizeData($data);

        self::$mysqli->execute_query('INSERT INTO contacts (id,'.implode(',', self::FIELDS).') VALUES (?,?,?)',
            [$data['id'], $data['name'], json_encode($data['custom_fields'])]);
    }

    public function update(array $data): array
    {
        $data = $this->normalizeData($data);

        $result = self::$mysqli->execute_query('SELECT id,'.implode(',', self::FIELDS).' FROM contacts WHERE id=?',
            [$data['id']]);

        if ($result && ($contact = $result->fetch_assoc())) {
            $changed = [];
            if ($data['name'] !== $contact['name']) {
                $changed['name'] = ['name' => 'Название', 'values' => [$data['name']]];
            }
            if ($changedCustomFields = $this->getChangedCustomFields(
                json_decode($contact['custom_fields'], true), $data['custom_fields'])) {
                //merge, don't assign, but merge
                $changed = array_merge($changed, $changedCustomFields);
            }

            self::$mysqli->execute_query(
                'UPDATE contacts SET '.implode(',', array_map(fn ($f) => $f.'=?', self::FIELDS)).' WHERE id=?',
                [$data['name'], json_encode($data['custom_fields']), $data['id']]);

            return $changed;
        } else {
            $this->insert($data);
            unset($data['id']);

            return $data;
        }
    }

    private function getChangedCustomFields(array $old, array $new)
    {
        $result = $new;
        foreach ($new as $newKey => $newValues) {
            if (isset($old[$newKey])) {
                if ($old[$newKey]['values'] === $newValues['values']) {
                    unset($result[$newKey]);
                }
                unset($old[$newKey]);
            }
        }

        foreach ($old as $removedKey => $removedValues) {
            $result[$removedKey] = ['name' => $removedValues['name'], 'values' => []];
        }

        return $result;
    }
}
