<?php

namespace App\Command;

use App\Entity\Guichet;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed',
    description: 'Popule la base de données avec les services, guichets et comptes utilisateurs initiaux.',
)]
class SeedDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Services
        $servicesData = [
            ['nom' => 'Carte pétrolière', 'desc' => 'Service dédié à la souscription et gestion des cartes pétrolières AGIL.', 'duree' => 10],
            ['nom' => 'Bon de valeur', 'desc' => 'Service de délivrance et d\'échange des bons de valeur.', 'duree' => 5],
            ['nom' => 'Carte Bons', 'desc' => 'Service spécialisé pour les cartes bons d\'alimentation et de carburant.', 'duree' => 8],
        ];

        $serviceEntities = [];
        foreach ($servicesData as $sData) {
            $existing = $this->entityManager->getRepository(Service::class)->findOneBy(['nomService' => $sData['nom']]);
            if (!$existing) {
                $service = new Service();
                $service->setNomService($sData['nom']);
                $service->setDescription($sData['desc']);
                $service->setDureeMoyenne($sData['duree']);
                $this->entityManager->persist($service);
                $serviceEntities[$sData['nom']] = $service;
            } else {
                $serviceEntities[$sData['nom']] = $existing;
            }
        }

        // 2. Users
        $usersData = [
            ['email' => 'admin@agil.tn', 'pass' => 'admin123', 'roles' => ['ROLE_ADMIN'], 'nom' => 'Ben Ali', 'prenom' => 'Sami', 'phone' => '71000001'],
            ['email' => 'gerant@agil.tn', 'pass' => 'gerant123', 'roles' => ['ROLE_GERANT'], 'nom' => 'Trabelsi', 'prenom' => 'Karim', 'phone' => '71000002'],
            ['email' => 'agiliste1@agil.tn', 'pass' => 'agiliste123', 'roles' => ['ROLE_AGILISTE'], 'nom' => 'Gharbi', 'prenom' => 'Amine', 'phone' => '71000003'],
            ['email' => 'agiliste2@agil.tn', 'pass' => 'agiliste123', 'roles' => ['ROLE_AGILISTE'], 'nom' => 'Jlassi', 'prenom' => 'Sarah', 'phone' => '71000004'],
            ['email' => 'client@agil.tn', 'pass' => 'client123', 'roles' => ['ROLE_CLIENT'], 'nom' => 'Mansouri', 'prenom' => 'Mohamed', 'phone' => '98000005'],
        ];

        $userEntities = [];
        foreach ($usersData as $uData) {
            $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $uData['email']]);
            if (!$existing) {
                $user = new User();
                $user->setEmail($uData['email']);
                $user->setNom($uData['nom']);
                $user->setPrenom($uData['prenom']);
                $user->setTelephone($uData['phone']);
                $user->setRoles($uData['roles']);
                $hashed = $this->passwordHasher->hashPassword($user, $uData['pass']);
                $user->setPassword($hashed);
                $this->entityManager->persist($user);
                $userEntities[$uData['email']] = $user;
            } else {
                $userEntities[$uData['email']] = $existing;
            }
        }

        $this->entityManager->flush();

        // 3. Guichets
        $guichetsData = [
            ['numero' => 'G01', 'service' => 'Carte pétrolière', 'agiliste' => 'agiliste1@agil.tn', 'statut' => 'Ouvert'],
            ['numero' => 'G02', 'service' => 'Bon de valeur', 'agiliste' => 'agiliste2@agil.tn', 'statut' => 'Ouvert'],
            ['numero' => 'G03', 'service' => 'Carte Bons', 'agiliste' => null, 'statut' => 'Fermé'],
        ];

        foreach ($guichetsData as $gData) {
            $existing = $this->entityManager->getRepository(Guichet::class)->findOneBy(['numero' => $gData['numero']]);
            if (!$existing) {
                $guichet = new Guichet();
                $guichet->setNumero($gData['numero']);
                if (isset($serviceEntities[$gData['service']])) {
                    $guichet->setTypeService($serviceEntities[$gData['service']]);
                }
                if ($gData['agiliste'] && isset($userEntities[$gData['agiliste']])) {
                    $guichet->setAgiliste($userEntities[$gData['agiliste']]);
                }
                $guichet->setStatut($gData['statut']);
                $this->entityManager->persist($guichet);
            }
        }

        $this->entityManager->flush();

        $io->success('La base de données AGIL a été populée avec succès !');

        return Command::SUCCESS;
    }
}
