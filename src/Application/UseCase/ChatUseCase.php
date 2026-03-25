<?php
namespace Application\UseCase;

use Domain\Repository\ChatRepositoryInterface;
use Domain\Repository\OnlineUsersRepositoryInterface;

class ChatUseCase {
    private ChatRepositoryInterface $chatRepo;
    private OnlineUsersRepositoryInterface $onlineRepo;

    public function __construct(ChatRepositoryInterface $chatRepo, OnlineUsersRepositoryInterface $onlineRepo) {
        $this->chatRepo = $chatRepo;
        $this->onlineRepo = $onlineRepo;
    }

    public function sendMessage(string $from, string $to, string $text): array {
        $message = [
            'id'        => uniqid('msg_'),
            'de'        => $from,
            'para'      => $to,
            'texto'     => $text,
            'timestamp' => time(),
            'leido'     => false,
        ];

        if ($this->chatRepo->addMessage($message)) {
            return ['ok' => true, 'mensaje' => $message];
        }
        return ['ok' => false, 'error' => 'No se pudo guardar el mensaje en el servidor'];
    }

    public function getHistory(string $currentUser, string $otherUser): array {
        $allMessages = $this->chatRepo->getAllMessages();
        $conversation = [];
        $changed = false;

        foreach ($allMessages as &$msg) {
            $isFromConversation =
                ($msg['de'] === $currentUser && $msg['para'] === $otherUser) ||
                ($msg['de'] === $otherUser  && $msg['para'] === $currentUser);

            if ($isFromConversation) {
                $conversation[] = $msg;
                if ($msg['para'] === $currentUser && $msg['leido'] === false) {
                    $msg['leido'] = true;
                    $changed = true;
                }
            }
        }
        unset($msg);

        if ($changed) {
            $this->chatRepo->saveMessages($allMessages);
        }

        usort($conversation, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        return ['ok' => true, 'mensajes' => $conversation];
    }

    public function getOnlineUsersWithUnread(string $currentUser): array {
        $activeUsers = $this->onlineRepo->getActiveUsers();
        $allMessages = $this->chatRepo->getAllMessages();
        $unreadByUser = [];

        foreach ($allMessages as $msg) {
            if ($msg['para'] === $currentUser && $msg['leido'] === false) {
                $sender = $msg['de'];
                $unreadByUser[$sender] = ($unreadByUser[$sender] ?? 0) + 1;
            }
        }

        foreach ($activeUsers as $username => &$info) {
            $usernameLower = strtolower($username);
            $info['username']  = $usernameLower;
            $info['no_leidos'] = $unreadByUser[$usernameLower] ?? 0;
        }
        unset($info);

        return ['ok' => true, 'usuarios' => $activeUsers];
    }

    public function getNewMessages(string $currentUser): array {
        $allMessages = $this->chatRepo->getAllMessages();
        $newOnes = [];

        foreach ($allMessages as &$msg) {
            if ($msg['para'] === $currentUser && $msg['leido'] === false) {
                $newOnes[] = [
                    'de'        => $msg['de'],
                    'texto'     => $msg['texto'],
                    'timestamp' => $msg['timestamp'],
                ];
                $msg['leido'] = true;
            }
        }
        unset($msg);

        $this->chatRepo->saveMessages($allMessages);

        return ['ok' => true, 'nuevos' => array_slice($newOnes, -3)];
    }

    public function requestCall(string $from, string $to): array {
        $message = [
            'id'        => uniqid('call_'),
            'de'        => $from,
            'para'      => $to,
            'texto'     => 'videollamada',
            'tipo'      => 'llamada',
            'leido'     => false,
            'timestamp' => time(),
        ];

        if ($this->chatRepo->addMessage($message)) {
            return ['ok' => true];
        }
        return ['ok' => false, 'error' => 'No se pudo registrar la llamada'];
    }
}
