<?php

namespace App\DataFixtures\dev\Shop;

use App\Entity\Shop\Carrier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CarrierFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $carriers = [
            0 => [
                'name' => 'Collisimo',
                'description' => 'Profitez d\'une livraison premium avec un colis chez
                vous dans les 72 prochaines heures.',
                'price' => 990,
            ],
            1 => [
                'name' => 'Chronopost',
                'description' => 'Profitez d\'une livraison express avec un colis chez
                vous dans les 24 prochaines heures.',
                'price' => 1490,
            ],
        ];

        foreach ($carriers as $value) {
            $carrier = new Carrier();
            $carrier->setName($value['name']);
            $carrier->setDescription($value['description']);
            $carrier->setPrice($value['price']);
            $manager->persist($carrier);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['dev'];
    }
}
