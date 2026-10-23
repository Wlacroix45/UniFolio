<?php

namespace App\Service;

use App\Entity\Enseignant;
use App\Entity\Etudiant;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ImportUser
{
    private const STUDENT_MAIL_DOMAIN = '@etu.univ-orleans.fr';

    private array $created = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function importerCsv(string $path): int
    {
        $this->created = [];

        try {
            $rows = $this->lireCsv($path);

            $this->entityManager->beginTransaction();
            $usernames = [];
            foreach ($rows as $row) {
                $this->creerUtilisateur($row, $usernames);
            }
            $this->entityManager->flush();
            $this->entityManager->commit();

            return 1;
        } catch (\Throwable) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            $this->created = [];

            return 0;
        }
    }

    public function getComptesCrees(): array
    {
        return $this->created;
    }

    public function getLignesComptesCrees(): array
    {
        return array_map(
            static fn (array $c): string => sprintf('%s : %s', $c['username'], $c['password']),
            $this->created
        );
    }

    private function lireCsv(string $path): array
    {
        if (!is_readable($path)) {
            throw new \RuntimeException(sprintf('Fichier illisible : %s', $path));
        }

        $handle = fopen($path, 'r');
        $firstLine = fgets($handle);
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', (string) $firstLine);
        $delimiter = $this->detecterSeparateur($firstLine);

        $header = array_map(fn ($h) => $this->normaliserCle((string) $h), str_getcsv($firstLine, $delimiter));
        foreach (['nom', 'prenom', 'mail'] as $required) {
            if (!in_array($required, $header, true)) {
                fclose($handle);
                throw new \RuntimeException(sprintf('Colonne manquante : %s', $required));
            }
        }

        $rows = [];
        while (($data = fgetcsv($handle, null, $delimiter)) !== false) {
            if ([null] === $data || '' === trim(implode('', $data))) {
                continue;
            }
            $data = array_pad(array_slice($data, 0, count($header)), count($header), '');
            $rows[] = array_map('trim', array_combine($header, $data));
        }
        fclose($handle);

        return $rows;
    }

    private function detecterSeparateur(string $line): string
    {
        $best = "\t";
        $max = -1;
        foreach (["\t", ';', ','] as $candidate) {
            $count = substr_count($line, $candidate);
            if ($count > $max) {
                $max = $count;
                $best = $candidate;
            }
        }

        return $best;
    }

    private function normaliserCle(string $key): string
    {
        return strtolower((new AsciiSlugger())->slug(trim($key))->toString());
    }

    private function creerUtilisateur(array $row, array &$usernames): void
    {
        $nom = $row['nom'];
        $prenom = $row['prenom'];
        $mail = $row['mail'];
        if ('' === $nom || '' === $prenom || '' === $mail) {
            throw new \RuntimeException('Ligne incomplète (nom, prénom ou mail vide).');
        }

        $isStudent = $this->estEtudiant($row);
        $username = $this->nomUtilisateurUnique($nom, $prenom, $usernames);
        $usernames[$username] = true;

        $user = new Users();
        $user->setUsername($username);
        $user->setEmail($mail);
        $user->setIsVerified(true);
        $user->setRoles([$isStudent ? 'ROLE_ETUDIANT' : 'ROLE_ENSEIGNANT']);

        $plain = bin2hex(random_bytes(6));
        $user->setPassword($this->passwordHasher->hashPassword($user, $plain));

        if ($isStudent) {
            $person = (new Etudiant())->setNom($nom)->setPrenom($prenom)->setMailUniv($mail);
            $person->setUsername($username);
            $user->setEtudiant($person);
        } else {
            $person = (new Enseignant())->setNom($nom)->setPrenom($prenom)->setMailUniv($mail);
            $person->setUsername($username);
            $user->setEnseignant($person);
        }

        $this->entityManager->persist($user);
        $this->created[] = ['username' => $username, 'password' => $plain];
    }

    private function estEtudiant(array $row): bool
    {
        $role = strtolower($row['role'] ?? '');
        if ('' !== $role) {
            return in_array($role, ['etudiant', 'eleve', 'student', 'role_etudiant'], true);
        }

        return str_ends_with(strtolower($row['mail']), self::STUDENT_MAIL_DOMAIN);
    }

    private function nomUtilisateurUnique(string $nom, string $prenom, array $taken): string
    {
        $slugger = new AsciiSlugger();
        $base = strtolower($slugger->slug($nom, '')->toString() . '.' . $slugger->slug($prenom, '')->toString());
        $repository = $this->entityManager->getRepository(Users::class);

        $username = $base;
        for ($i = 2; isset($taken[$username]) || null !== $repository->findOneBy(['username' => $username]); ++$i) {
            $username = $base . $i;
        }

        return $username;
    }
}
