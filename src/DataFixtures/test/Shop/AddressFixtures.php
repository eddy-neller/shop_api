<?php

namespace App\DataFixtures\test\Shop;

use App\Entity\Shop\Address;
use App\Infrastructure\DataFixtures\DataFixturesTrait;
use App\Infrastructure\DataFixtures\test\User\UserFixtures;
use App\Infrastructure\Entity\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AddressFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    use DataFixturesTrait;

    public function load(ObjectManager $manager): void
    {
        /** @var User $userMember */
        $userMember = $this->getReference('user_member', User::class);

        $addressesData = [
            [
                'name' => 'Address name 1',
                'firstname' => 'Jean',
                'lastname' => 'Dupont',
                'company' => 'ACME Corp',
                'address' => '123 rue de la Paix',
                'zip' => '75001',
                'city' => 'Paris',
                'country' => 'France',
                'phone' => '+33 1 23 45 67 89',
            ],
            [
                'name' => 'Address name 2',
                'firstname' => 'Marie',
                'lastname' => 'Martin',
                'company' => null,
                'address' => '45 avenue des Champs',
                'zip' => '69001',
                'city' => 'Lyon',
                'country' => 'France',
                'phone' => '+33 4 12 34 56 78',
            ],
        ];

        foreach ($addressesData as $addressData) {
            $address = new Address();
            $address->setName($addressData['name']);
            $address->setFirstname($addressData['firstname']);
            $address->setLastname($addressData['lastname']);
            $address->setCompany($addressData['company']);
            $address->setAddress($addressData['address']);
            $address->setZip($addressData['zip']);
            $address->setCity($addressData['city']);
            $address->setCountry($addressData['country']);
            $address->setPhone($addressData['phone']);
            $address->setUser($userMember);

            $timestamps = $this->generateTimestamps();
            $address->setCreatedAt($timestamps['createdAt']);
            $address->setUpdatedAt($timestamps['updatedAt']);

            $manager->persist($address);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
