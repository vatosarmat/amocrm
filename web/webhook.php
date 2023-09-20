
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

function makeCreationNote(array $entityData, Users $usersService, EntityNotes $notesService)
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

$apiClient = makeApiClient();
$usersService = $apiClient->users();

if (isset($_POST['contacts'])) {
    $notesService = $apiClient->notes(EntityTypesInterface::CONTACTS);
    if (isset($_POST['contacts']['add'])) {
        foreach ($_POST['contacts']['add'] as $contactData) {
            makeCreationNote($contactData, $usersService, $notesService);
        }
    }
}

if (isset($_POST['leads'])) {
    $notesService = $apiClient->notes(EntityTypesInterface::LEADS);
    if (isset($_POST['leads']['add'])) {
        foreach ($_POST['leads']['add'] as $leadData) {
            makeCreationNote($leadData, $usersService, $notesService);
        }
    }
}
