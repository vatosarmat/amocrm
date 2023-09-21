
<?php

use AmoCRM\EntitiesServices\EntityNotes;
use AmoCRM\EntitiesServices\Users;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\NoteType\CommonNote;
use App\AmoContactsStore;
use App\AmoLeadsStore;

require_once dirname(__DIR__).'/src/init.php';

log_all();

function makeAddNote(array $entityData, Users $usersService, EntityNotes $notesService, App\AmoEntityStore $entityStore)
{
    try {
        $entityStore->insert($entityData);
    } catch (\mysqli_sql_exception $e) {
        log_error($e);

        return;
    }

    $name = $entityData['name'] ?? 'пусто';

    $createdAt = isset($entityData['created_at']) ? date('H:i d.m.Y', $entityData['created_at']) : 'пусто';

    $responsible = isset($entityData['responsible_user_id']) ?
        ($usersService->getOne($entityData['responsible_user_id'])->getName() ?? 'пусто')."({$entityData['responsible_user_id']})" : 'пусто';

    $text = <<<TEXT
    Название: $name
    Ответственный: $responsible
    Время добавления: $createdAt
    TEXT;

    $note = new CommonNote();
    $note->setText($text)->setEntityId($entityData['id']);
    $note = $notesService->addOne($note);

}

function makeUpdateNote(array $entityData, EntityNotes $notesService, App\AmoEntityStore $entityStore)
{
    try {
        $changedFields = $entityStore->update($entityData);
    } catch (\mysqli_sql_exception $e) {
        log_error($e);

        return;
    }

    $name = $entityData['name'] ?? 'пусто';

    $updatedAt = isset($entityData['updated_at']) ? date('H:i d.m.Y', $entityData['updated_at']) : 'пусто';

    //process changed fields
    $lines = [];
    foreach ($changedFields as $key => $item) {
        $newValue = implode(', ', $item['values']);
        $lines[] = "$item[name]($key): $newValue";
    }
    $fieldsText = count($lines) > 0 ? "Измененные поля:\n".implode("\n", $lines) : '';

    $text = <<<TEXT
    Название: $name
    Время изменения: $updatedAt
    $fieldsText
    TEXT;

    $note = new CommonNote();
    $note->setText($text)->setEntityId($entityData['id']);
    $note = $notesService->addOne($note);
}

function runEntityHooks(string $entityName, App\AmoEntityStore $entityStore, $apiClient, Users $usersService)
{
    if (isset($_POST[$entityName])) {
        $notesService = $apiClient->notes($entityName);
        if (isset($_POST[$entityName]['add'])) {
            foreach ($_POST[$entityName]['add'] as $contactData) {
                makeAddNote($contactData, $usersService, $notesService, $entityStore);
            }
        }

        if (isset($_POST[$entityName]['update'])) {
            foreach ($_POST[$entityName]['update'] as $contactData) {
                makeUpdateNote($contactData, $notesService, $entityStore);
            }
        }
    }

}

$apiClient = new \AmoCRM\Client\AmoCRMApiClient($_SERVER['AMOCRM_CLIENT_ID'], $_SERVER['AMOCRM_CLIENT_SECRET'],
    $_SERVER['AMOCRM_REDIRECT_URL']);
$apiClient->setAccountBaseDomain($_SERVER['AMOCRM_BASE_DOMAIN']);
$accessToken = \App\AccessTokenStore::get();
if (! $accessToken) {
    exit('Failed to get access token');
}
$apiClient->setAccessToken($accessToken);

$usersService = $apiClient->users();

runEntityHooks(EntityTypesInterface::CONTACTS, new AmoContactsStore, $apiClient, $usersService);
runEntityHooks(EntityTypesInterface::LEADS, new AmoLeadsStore, $apiClient, $usersService);

http_response_code(200);
