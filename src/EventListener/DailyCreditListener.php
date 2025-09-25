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

        // Récupérer l'URL de la BDD depuis les variables d'environnement
        $databaseUrl = $_ENV['DATABASE_URL'] ?? null;
        if (!$databaseUrl) {
            return;
        }

        $parsed = parse_url($databaseUrl);

        $host = $parsed['host'] ?? '127.0.0.1';
        $port = $parsed['port'] ?? 5432;
        $dbname = ltrim($parsed['path'] ?? '', '/');
        $dbUser = $parsed['user'] ?? '';
        $dbPass = $parsed['pass'] ?? '';
        $query = $parsed['query'] ?? '';

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        if (strpos($query, 'sslmode=') !== false) {
            parse_str(str_replace('&', ';', $query), $params);
            if (isset($params['sslmode'])) {
                $dsn .= ";sslmode={$params['sslmode']}";
            }
        }

        // Connexion PDO
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Vérifier la dernière date de crédit
        $stmt = $pdo->prepare("SELECT dernier_credit_jour, credits FROM \"utilisateurs\" WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData && (!$userData['dernier_credit_jour'] || $userData['dernier_credit_jour'] < $today)) {
            // Ajouter 1 crédit et mettre à jour la date
            $stmt = $pdo->prepare(
                "UPDATE \"utilisateurs\" SET credits = credits + 1, dernier_credit_jour = :today WHERE id = :id"
            );
            $stmt->execute([
                'id' => $userId,
                'today' => $today
            ]);
        }
    }
}
