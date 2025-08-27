<?php
namespace App\EventListener;

use PDO;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class DailyCreditListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'security.interactive_login' => 'onLogin',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        // Vérifier que c'est bien un objet User
        if (!$user instanceof User) {
            return;
        }

        $userId = $user->getId();
        $today = (new \DateTime())->format('Y-m-d');

        $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Vérifier la dernière date de crédit
        $stmt = $pdo->prepare("SELECT dernier_credit_jour, credits FROM user WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData && (!$userData['dernier_credit_jour'] || $userData['dernier_credit_jour'] < $today)) {
            // Ajouter 1 crédit et mettre à jour la date
            $stmt = $pdo->prepare("UPDATE user SET credits = credits + 1, dernier_credit_jour = :today WHERE id = :id");
            $stmt->execute([
                'id' => $userId,
                'today' => $today
            ]);
        }
    }
}
