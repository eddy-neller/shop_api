<?php

namespace App\DataFixtures\test\Shop;

use App\Entity\Shop\Carrier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CarrierFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $carriersData = [
            [
                'name' => 'Carrier name 1',
                'description' => 'Standard shipping carrier with reliable delivery times.',
                'price' => 5.99,
            ],
            [
                'name' => 'Carrier name 2',
                'description' => 'Express shipping for urgent deliveries.',
                'price' => 12.99,
            ],
            [
                'name' => 'Carrier name 3',
                'description' => 'Economy shipping option with longer delivery times.',
                'price' => 3.50,
            ],
            [
                'name' => 'Carrier name 4',
                'description' => 'International shipping carrier for worldwide delivery.',
                'price' => 25.00,
            ],
            [
                'name' => 'Carrier name 5',
                'description' => 'Premium overnight delivery service.',
                'price' => 19.99,
            ],
        ];

        foreach ($carriersData as $carrierData) {
            $carrier = new Carrier();
            $carrier->setName($carrierData['name']);
            $carrier->setDescription($carrierData['description']);
            $carrier->setPrice($carrierData['price']);

            $manager->persist($carrier);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
