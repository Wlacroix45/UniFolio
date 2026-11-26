<?php

namespace App\DataFixtures;

use App\Entity\ApcNiveau;
use App\Repository\CompetenceRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ApcNiveauFixture extends Fixture implements OrderedFixtureInterface
{
    private $competence;

    public function __construct(CompetenceRepository $competence)
    {
        $this->competence = $competence;
    }


    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return 4;
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager): void
    {
        $competence1 = $this->competence->findOneBy(['nom_court' => 'Entreprendre']);
        $competence2 = $this->competence->findOneBy(['nom_court' => 'Développer']);
        $competence3 = $this->competence->findOneBy(['nom_court' => 'Comprendre']);
        $competence4 = $this->competence->findOneBy(['nom_court' => 'Concevoir']);
        $competence5 = $this->competence->findOneBy(['nom_court' => 'Exprimer']);
        $competence6 = $this->competence->findOneBy(['nom_court' => 'Piloter']);
        $competence7 = $this->competence->findOneBy(['nom_court' => 'Rédiger']);
        $competence8 = $this->competence->findOneBy(['nom_court' => 'Sécuriser']);
        $competence9 = $this->competence->findOneBy(['nom_court' => 'Conseiller']);

        $niveau1 = new ApcNiveau();
        $niveau1->setIntranetId(1)
            ->setLibelle('Comprendre les éléments de communication et les attentes utilisateurs')
            ->setOrdre(1)
            ->setCompetences($competence3);
        $manager->persist($niveau1);

        $niveau2 = new ApcNiveau();
        $niveau2->setIntranetId(2)
            ->setLibelle('Comprendre la stratégie de communication et l’expérience utilisateur')
            ->setOrdre(2)
            ->setCompetences($competence3);
        $manager->persist($niveau2);

        $niveau3 = new ApcNiveau();
        $niveau3->setIntranetId(3)
            ->setLibelle('Comprendre les écosystèmes, les besoins des utilisateurs et les dispositifs de communication numérique')
            ->setOrdre(3)
            ->setCompetences($competence3);
        $manager->persist($niveau3);

        $niveau4 = new ApcNiveau();
        $niveau4->setIntranetId(4)
            ->setLibelle('Concevoir une réponse stratégique')
            ->setOrdre(1)
            ->setCompetences($competence4);
        $manager->persist($niveau4);

        $niveau5 = new ApcNiveau();
        $niveau5->setIntranetId(5)
            ->setLibelle('Co-concevoir une réponse stratégique')
            ->setOrdre(2)
            ->setCompetences($competence4);
        $manager->persist($niveau5);

        $niveau6 = new ApcNiveau();
        $niveau6->setIntranetId(6)
            ->setLibelle('Co-concevoir une réponse stratégique complexe et prospective')
            ->setOrdre(3)
            ->setCompetences($competence4);
        $manager->persist($niveau6);

        $niveau7 = new ApcNiveau();
        $niveau7->setIntranetId(7)
            ->setLibelle('Exprimer un message par des productions simples')
            ->setOrdre(1)
            ->setCompetences($competence5);
        $manager->persist($niveau7);

        $niveau8 = new ApcNiveau();
        $niveau8->setIntranetId(8)
            ->setLibelle('Exprimer une identité visuelle et éditoriale trans-média')
            ->setOrdre(2)
            ->setCompetences($competence5);
        $manager->persist($niveau8);

        $niveau9 = new ApcNiveau();
        $niveau9->setIntranetId(9)
            ->setLibelle('Exprimer un récit interactif et une direction artistique')
            ->setOrdre(3)
            ->setCompetences($competence5);
        $manager->persist($niveau9);

        $niveau10 = new ApcNiveau();
        $niveau10->setIntranetId(10)
            ->setLibelle('Développer un site web simple et le met en ligne')
            ->setOrdre(1)
            ->setCompetences($competence2);
        $manager->persist($niveau10);

        $niveau11 = new ApcNiveau();
        $niveau11->setIntranetId(11)
            ->setLibelle('Développer une application Web interactive')
            ->setOrdre(2)
            ->setCompetences($competence2);
        $manager->persist($niveau11);

        $niveau12 = new ApcNiveau();
        $niveau12->setIntranetId(12)
            ->setLibelle('Développer un écosystème numérique complexe')
            ->setOrdre(3)
            ->setCompetences($competence2);
        $manager->persist($niveau12);

        $niveau13 = new ApcNiveau();
        $niveau13->setIntranetId(13)
            ->setLibelle('Entreprendre un projet numérique')
            ->setOrdre(1)
            ->setCompetences($competence1);
        $manager->persist($niveau13);

        $niveau14 = new ApcNiveau();
        $niveau14->setIntranetId(14)
            ->setLibelle('Entreprendre un projet au sein d’un écosystème numérique')
            ->setOrdre(2)
            ->setCompetences($competence1);
        $manager->persist($niveau14);

        $niveau15 = new ApcNiveau();
        $niveau15->setIntranetId(15)
            ->setLibelle('Entreprendre dans le numérique')
            ->setOrdre(3)
            ->setCompetences($competence1);
        $manager->persist($niveau15);

        $niveau16 = new ApcNiveau();
        $niveau16->setIntranetId(16)
            ->setLibelle('Piloter les activités élémentaires sous contrôle')
            ->setOrdre(1)
            ->setCompetences($competence6);
        $manager->persist($niveau16);

        $niveau17 = new ApcNiveau();
        $niveau17->setIntranetId(17)
            ->setLibelle('Piloter les activités essentielles en soutien')
            ->setOrdre(2)
            ->setCompetences($competence6);
        $manager->persist($niveau17);

        $niveau18 = new ApcNiveau();
        $niveau18->setIntranetId(18)
            ->setLibelle('Piloter les activités complexes en autonomie')
            ->setOrdre(3)
            ->setCompetences($competence6);
        $manager->persist($niveau18);

        $niveau19 = new ApcNiveau();
        $niveau19->setIntranetId(19)
            ->setLibelle('Conseiller en tant que contributeur à la mission de conseil')
            ->setOrdre(1)
            ->setCompetences($competence9);
        $manager->persist($niveau19);

        $niveau20 = new ApcNiveau();
        $niveau20->setIntranetId(20)
            ->setLibelle('Conseiller en tant qu’acteur de la mission de conseil')
            ->setOrdre(2)
            ->setCompetences($competence9);
        $manager->persist($niveau20);

        $niveau21 = new ApcNiveau();
        $niveau21->setIntranetId(21)
            ->setLibelle('Conseiller en tant que responsable de la mission de conseil')
            ->setOrdre(3)
            ->setCompetences($competence9);
        $manager->persist($niveau21);

        $niveau22 = new ApcNiveau();
        $niveau22->setIntranetId(22)
            ->setLibelle('Exercer une sécurisation ponctuelle ')
            ->setOrdre(1)
            ->setCompetences($competence8);
        $manager->persist($niveau22);

        $niveau23 = new ApcNiveau();
        $niveau23->setIntranetId(23)
            ->setLibelle('Réaliser une sécurisation sectorielle')
            ->setOrdre(2)
            ->setCompetences($competence8);
        $manager->persist($niveau23);

        $niveau24 = new ApcNiveau();
        $niveau24->setIntranetId(24)
            ->setLibelle('Assurer une sécurisation systémique')
            ->setOrdre(3)
            ->setCompetences($competence8);
        $manager->persist($niveau24);

        $niveau25 = new ApcNiveau();
        $niveau25->setIntranetId(25)
            ->setLibelle('Exprimer un raisonnement simple à l\'écrit')
            ->setOrdre(1)
            ->setCompetences($competence7);
        $manager->persist($niveau25);

        $niveau26 = new ApcNiveau();
        $niveau26->setIntranetId(26)
            ->setLibelle('Formuler un raisonnement composite à l\'écrit')
            ->setOrdre(2)
            ->setCompetences($competence7);
        $manager->persist($niveau26);

        $niveau27 = new ApcNiveau();
        $niveau27->setIntranetId(27)
            ->setLibelle('Élaborer un raisonnement complexe à l\'écrit')
            ->setOrdre(3)
            ->setCompetences($competence7);
        $manager->persist($niveau27);

        $manager->flush();
    }
}
