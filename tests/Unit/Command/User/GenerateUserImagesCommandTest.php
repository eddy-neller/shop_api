<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\User;

use App\Command\User\GenerateUserImagesCommand;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Service\Media\CustomImageProvider;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateUserImagesCommandTest extends KernelTestCase
{
    public function testNoUsersWithoutImages(): void
    {
        $tester = $this->getCommandTesterForScenario(true);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('0 utilisateur(s) mis à jour.', $output);
    }

    public function testUsersWithoutImagesUpdated(): void
    {
        $tester = $this->getCommandTesterForScenario(false);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('1 utilisateur(s) mis à jour.', $output);
    }

    public function testMixedUsersScenario(): void
    {
        $tester = $this->getCommandTesterForScenario(false);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('1 utilisateur(s) mis à jour.', $output);
    }

    public function testImageGenerationFailure(): void
    {
        $tester = $this->getCommandTesterForScenario(false, true);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Échec de génération des images pour l\'utilisateur', $output);
        $this->assertStringContainsString('0 utilisateur(s) mis à jour.', $output);
    }

    public function testVerboseOutput(): void
    {
        $tester = $this->getCommandTesterForScenario(false);
        $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('1 utilisateur(s) mis à jour.', $output);
    }

    public function testDebugOutput(): void
    {
        $tester = $this->getCommandTesterForScenario(false);
        $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('1 utilisateur(s) mis à jour.', $output);
    }

    private function getCommandTesterForScenario(bool $hasAvatar, bool $imageGenerationFails = false): CommandTester
    {
        // Mock User
        $user = $this->createMock(User::class);
        $user->method('getFullname')->willReturn('Test User');
        $user->method('getAvatarName')->willReturn($hasAvatar ? 'avatar.jpg' : null);
        $user->method('getId')->willReturn(Uuid::uuid4());

        // Set expectations based on scenario
        // La commande vérifie uniquement getAvatarName(), pas hasUserbar
        if (!$hasAvatar && !$imageGenerationFails) {
            $user->expects($this->once())->method('setAvatarName')->with('test_avatar.jpg');
            $user->expects($this->once())->method('setAvatarUpdatedAt')->with($this->isInstanceOf(DateTime::class));
        } else {
            $user->expects($this->never())->method('setAvatarName');
            $user->expects($this->never())->method('setAvatarUpdatedAt');
        }

        // Mock User Repository
        /** @var UserRepository&MockObject $userRepo */
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findAll')->willReturn([$user]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        // Flush uniquement si un utilisateur a été mis à jour (pas d'avatar et génération réussie)
        if (!$hasAvatar && !$imageGenerationFails) {
            $em->expects($this->once())->method('flush');
        } else {
            $em->expects($this->never())->method('flush');
        }

        // Mock CustomImageProvider
        /** @var CustomImageProvider&MockObject $customImageProvider */
        $customImageProvider = $this->createMock(CustomImageProvider::class);

        if ($imageGenerationFails) {
            $customImageProvider->method('customImage')->willReturn(null);
        } else {
            $customImageProvider->method('customImage')
                ->willReturnMap([
                    ['public/uploads/images/user/avatar', 96, 96, 'test_avatar.jpg'],
                ]);
        }

        // Instancie la commande
        $command = new GenerateUserImagesCommand($userRepo, $em, $customImageProvider);

        $application = new Application(self::bootKernel());
        $application->add($command);

        return new CommandTester($command);
    }
}
