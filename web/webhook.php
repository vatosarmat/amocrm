
<?php

use AmoCRM\EntitiesServices\EntityNotes;
use AmoCRM\EntitiesServices\Users;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\NoteType\CommonNote;

require_once dirname(__DIR__).'/src/init.php';

log_all();

function makeApiClient()
{
    $apiClient = new \AmoCRM\Client\AmoCRMApiClient($_SERVER['AMOCRM_CLIENT_ID'], $_SERVER['AMOCRM_CLIENT_SECRET'],
        $_SERVER['AMOCRM_REDIRECT_URL']);
    $apiClient->setAccountBaseDomain($_SERVER['AMOCRM_BASE_DOMAIN']);

    $accessToken = (new \App\AccessTokenManager($apiClient))->get();

    if (! $accessToken) {
        exit('Failed to get access token');
    }

    $apiClient->setAccessToken($accessToken);

    return $apiClient;
}

function makeAddNote(array $entityData, Users $usersService, EntityNotes $notesService)
{
    $createdAt = isset($entityData['created_at']) ? date('H:i d.m.Y', $entityData['created_at']) : 'пусто';

    $responsible = isset($entityData['responsible_user_id']) ?
        ($usersService->getOne($entityData['responsible_user_id'])->getName() ?? 'пусто')."({$entityData['responsible_user_id']})" : 'пусто';

    $name = $entityData['name'] ?? 'пусто';

    $text = <<<TEXT
    Название: $name
    Ответственный: $responsible
    Время добавления: $createdAt
    TEXT;

    $note = new CommonNote();
    $note->setText($text)->setEntityId($entityData['id']);
    $note = $notesService->addOne($note);
}

function makeUpdateNote(array $entityData, EntityNotes $notesService)
{
    $updatedAt = isset($entityData['updated_at']) ? date('H:i d.m.Y', $entityData['updated_at']) : 'пусто';

    $fields = [];
    foreach ($entityData['custom_fields'] as $field) {
        $newValue = implode(', ', array_map(fn ($item) => $item['value'], $field['values']));
        $fields[] = "{$field['name']}({$field['code']}): $newValue";
    }
    $fieldsText = count($fields) > 0 ? implode("\n", $fields) : 'нет';

    $name = $entityData['name'] ?? 'пусто';

    $text = <<<TEXT
    Название: $name
    Время изменения: $updatedAt
    Измененные поля:
    $fieldsText
    TEXT;

    $note = new CommonNote();
    $note->setText($text)->setEntityId($entityData['id']);
    $note = $notesService->addOne($note);
}

function runEntityHooks(string $entityName, $apiClient, Users $usersService)
{
    if (isset($_POST[$entityName])) {
        $notesService = $apiClient->notes($entityName);
        if (isset($_POST[$entityName]['add'])) {
            foreach ($_POST[$entityName]['add'] as $contactData) {
                makeAddNote($contactData, $usersService, $notesService);
            }
        }

        if (isset($_POST[$entityName]['update'])) {
            foreach ($_POST[$entityName]['update'] as $contactData) {
                makeUpdateNote($contactData, $notesService);
            }
        }
    }

}

$apiClient = makeApiClient();
$usersService = $apiClient->users();

runEntityHooks(EntityTypesInterface::CONTACTS, $apiClient, $usersService);
runEntityHooks(EntityTypesInterface::LEADS, $apiClient, $usersService);
