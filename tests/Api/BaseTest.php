<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\HasOwnerInterface;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

/**
 * @SuppressWarnings("PMD")
 */
abstract class BaseTest extends ApiTestCase
{
    protected const string URL_API = '/api/';

    protected const string URL_LOGIN = self::URL_API . 'login';

    public const array ASSERTION_TYPE = [
        'SERIALIZATION' => 'serialization',
        'EQUAL' => 'equals',
        'NULL' => 'null',
        'NOT_NULL' => 'notNull',
        'DATE' => 'date',
        'TRANSLATION' => 'translation',
        'IRI' => 'iri',
        'EMPTY' => 'empty',
        'PAGINATION' => 'pagin',
        'FILTER' => 'filter',
    ];

    protected const int PAGIN_IPP = 2;

    protected const int PAGIN_PAGE_ONE = 1;

    protected const int PAGIN_PAGE = 3;

    protected const array MEDIA_TYPE = [
        'IMAGE' => 'IMAGE',
    ];

    protected const array PLACEHOLDERS = [
        'TOKENS' => [
            'ADMIN' => 'ADMIN_TOKEN_PLACEHOLDER',
            'MODER' => 'MODER_TOKEN_PLACEHOLDER',
            'MEMBER' => 'MEMBER_TOKEN_PLACEHOLDER',
            'MEMBER_4' => 'MEMBER_4_TOKEN_PLACEHOLDER',
            'MEMBER_8' => 'MEMBER_8_TOKEN_PLACEHOLDER',
            'MODER_1' => 'MODER_1_TOKEN_PLACEHOLDER',
            'MODER_2' => 'MODER_2_TOKEN_PLACEHOLDER',
            'MODER_3' => 'MODER_3_TOKEN_PLACEHOLDER',
            'ADMIN_4' => 'ADMIN_4_TOKEN_PLACEHOLDER',
        ],
        'IMAGES' => [
            'PAYSAGE' => 'PAYSAGE_IMAGE_PLACEHOLDER',
            'LARGE' => 'LARGE_IMAGE_PLACEHOLDER',
            'PDF' => 'PDF_IMAGE_PLACEHOLDER',
            'WIDE' => 'WIDE_IMAGE_PLACEHOLDER',
            'TALL' => 'TALL_IMAGE_PLACEHOLDER',
            'VENOM' => 'VENOM_IMAGE_PLACEHOLDER',
        ],
        'USER' => [
            'MEMBER' => 'USER_MEMBER_PLACEHOLDER',
        ],
    ];

    protected Client $client;

    protected Generator $faker;

    protected EntityManagerInterface $em;

    protected string $userAdmin = 'user_admin';

    protected string $userModer = 'user_moder';

    protected string $userMember = 'user_member';

    protected array $listOtherAdmin = [
        'user_admin_1',
        'user_admin_2',
        'user_admin_3',
    ];

    protected array $listOtherModer = [
        'user_moder_4',
        'user_moder_5',
        'user_moder_6',
    ];

    protected array $listOtherMember = [
        'user_member_7',
        'user_member_8',
        'user_member_9',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
        $this->client = self::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    protected function getApiClient(): HttpClientInterface
    {
        return $this->client ?? self::createClient();
    }

    protected function getFaker(): Generator
    {
        return $this->faker ?? Factory::create();
    }

    protected function getManager(): EntityManagerInterface
    {
        return $this->em ?? static::getContainer()->get('doctrine')->getManager();
    }

    protected function request(string $method, string $url, array $options = []): ?ResponseInterface
    {
        try {
            if (!isset($options['headers'])) {
                $options['headers'] = [];
            }

            if (!isset($options['headers']['Accept'])) {
                $options['headers']['Accept'] = 'application/json';
            }

            return $this->getApiClient()->request($method, $url, $options);
        } catch (Throwable $e) {
            dump($e->getMessage());

            return null;
        }
    }

    protected function getToken(string $username): string
    {
        try {
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'email' => $username . '@en-develop.fr',
                    'password' => $username,
                ],
            ];

            $response = $this->getApiClient()->request(Request::METHOD_POST, self::URL_LOGIN, $options);

            $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data) || !isset($data['token'])) {
                throw new RuntimeException('Token not found');
            }

            return $data['token'];
        } catch (Throwable $e) {
            throw new RuntimeException('getToken: ' . $e->getMessage());
        }
    }

    protected function testSuccess(
        string $method,
        string $uri,
        array $options,
        int $code,
        array $asserts = [],
        bool $noTreatment = false,
    ): ?array {
        $options = $this->replacePlaceholders($options);

        $res = $this->request(
            $method,
            $uri,
            $options
        );

        try {
            $statusCode = $res->getStatusCode();
            $result = [];

            if (!in_array($statusCode, [204, 205], true)) {
                $content = $res->getContent();
                $result = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (Throwable $e) {
            throw new RuntimeException('testSuccess: invalid response: ' . $e->getMessage());
        }

        self::assertResponseIsSuccessful();
        self::assertResponseStatusCodeSame($code);

        if (!$noTreatment) {
            if (Request::METHOD_DELETE !== $method) {
                $isCollection = $this->isCollectionResponse($result);
                $res = $this->getTestResult($result, $isCollection);
                $this->makeAssertion($res, $asserts);

                return $res;
            }

            return null;
        }

        return $this->getTestResult($result);
    }

    protected function testException(
        string $method,
        string $uri,
        array $options,
        array $exception,
    ): void {
        $this->expectException($exception['class']);
        $this->expectExceptionCode($exception['code']);
        $this->expectExceptionMessage($exception['message']);

        $options = $this->replacePlaceholders($options);

        $response = $this->request(
            $method,
            $uri,
            $options
        );

        json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Find an IRI by HTTP request.
     *
     * A utiliser pour les entités qui ont la traduction activée.
     */
    protected function findIriByHttp(string $uri, array $criteria, bool $asAdmin = false): string
    {
        $page = 1;
        $headers = [];

        if ($asAdmin) {
            $headers['Authorization'] = 'Bearer ' . $this->getToken($this->userAdmin);
        }

        while (true) {
            $res = $this->request('GET', $uri . '?page=' . $page, [
                'headers' => $headers,
            ]);

            try {
                $items = $res->toArray();
            } catch (Throwable $e) {
                throw new RuntimeException('findIriByHttp: invalid response');
            }

            if ([] === $items) {
                break;
            }

            foreach ($items as $item) {
                foreach ($criteria as $key => $value) {
                    if (!isset($item[$key]) || $item[$key] !== $value) {
                        continue 2;
                    }
                }

                return $uri . '/' . $item['id'];
            }

            ++$page;
        }

        throw new RuntimeException('No IRI found');
    }

    protected function makeAssertion(
        array $res,
        array $asserts,
    ): void {
        foreach ($asserts as $type => $assert) {
            switch ($type) {
                case self::ASSERTION_TYPE['SERIALIZATION']:
                    foreach ($assert as $typeSerialization => $value) {
                        foreach ($value as $val) {
                            $this->analyseSerializeAssertion($val, $res, $typeSerialization);
                        }
                    }

                    break;
                case self::ASSERTION_TYPE['EQUAL']:
                    foreach ($assert as $key => $value) {
                        $this->assertEquals($res[$key], $value);
                    }

                    break;
                case self::ASSERTION_TYPE['NULL']:
                    foreach ($assert as $value) {
                        $this->assertNull($res[$value]);
                    }

                    break;
                case self::ASSERTION_TYPE['NOT_NULL']:
                    foreach ($assert as $value) {
                        $this->assertNotNull($res[$value]);
                    }

                    break;
                case self::ASSERTION_TYPE['DATE']:
                case self::ASSERTION_TYPE['TRANSLATION']:
                    foreach ($assert as $key => $value) {
                        $this->assertStringContainsString($value, (string) $res[$key]);
                    }

                    break;
                case self::ASSERTION_TYPE['EMPTY']:
                    $this->assertEmpty($res);
                    break;
                case self::ASSERTION_TYPE['PAGINATION']:
                case self::ASSERTION_TYPE['FILTER']:
                    break;
            }
        }
    }

    protected function switchKeySerialization(array $array, array $unwantedKeys): array
    {
        $hasKey = $array['hasKey'] ?? [];
        $hasNotKey = $array['hasNotKey'] ?? [];

        foreach ($unwantedKeys as $unwantedKey) {
            $keyIndex = array_search($unwantedKey, $hasKey, true);
            if (false !== $keyIndex) {
                unset($hasKey[$keyIndex]);
                $hasNotKey[] = $unwantedKey;
            }
        }

        $array['hasKey'] = array_values($hasKey);
        $array['hasNotKey'] = array_values($hasNotKey);

        return $array;
    }

    protected function calculateExpectedOrders(array $itemsBefore, object $itemToDelete): array
    {
        $expectedOrders = [];

        foreach ($itemsBefore as $item) {
            $itemId = $item->getId()->toString();
            $itemToDeleteId = $itemToDelete->getId()->toString();

            if ($itemId !== $itemToDeleteId) {
                $expectedOrders[$itemId] = $item->getOrdre() > $itemToDelete->getOrdre()
                    ? $item->getOrdre() - 1
                    : $item->getOrdre();
            }
        }

        return $expectedOrders;
    }

    protected function getCollection(mixed $class, array $criteria, ?int $limit = null): array
    {
        $qb = $this->getManager()->getRepository($class)->createQueryBuilder('o'); // @phpstan-ignore-line

        foreach ($criteria as $field => $value) {
            if (str_contains($field, '.')) {
                [$relation, $column] = explode('.', $field);
                $qb->innerJoin('o.' . $relation, 'r');
                $qb->andWhere(sprintf('r.%s = :criteria', $column));
            } else {
                $qb->andWhere(sprintf('o.%s = :criteria', $field));
            }

            $qb->setParameter('criteria', $value);
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    protected function getInstance(mixed $class, array $criteria): mixed
    {
        $this->getManager()->clear();

        return $this->getManager()->getRepository($class)->findOneBy($criteria); // @phpstan-ignore-line
    }

    protected function getOwner(mixed $class, array $criteria): string
    {
        /** @var HasOwnerInterface $instance */
        $instance = $this->getInstance($class, $criteria);

        return $instance->getUser()->getUsername();
    }

    protected function getNotOwner(mixed $class, array $criteria, string $role): string
    {
        $owner = $this->getOwner($class, $criteria);

        return match ($role) {
            User::ROLES['admin'] => $this->filterUsers($owner, $this->listOtherAdmin),
            User::ROLES['moder'] => $this->filterUsers($owner, $this->listOtherModer),
            User::ROLES['user'] => $this->filterUsers($owner, $this->listOtherMember),
            default => null,
        };
    }

    protected function getImage(string $filename, string $suffix): UploadedFile
    {
        return $this->getPhysicalTempFile($filename, $suffix);
    }

    protected function getIdFromIri(string $iri): string
    {
        return array_reverse(explode('/', $iri))[0];
    }

    protected static function generateQuery(
        array $options = [],
    ): array {
        $query = [];

        // page
        if (array_key_exists('page', $options) && $options['page']) {
            $query['page'] = $options['page'];
        }

        // itemsPerPage
        if (array_key_exists('ipp', $options) && $options['ipp']) {
            $query['itemsPerPage'] = $options['ipp'];
        }

        // filters
        if (array_key_exists('filters', $options) && !empty($options['filters'])) {
            foreach ($options['filters'] as $value) {
                switch ($value['filter']) {
                    case 'exists':
                        $query['exists[' . $value['field'] . ']'] = $value['value'];
                        break;
                    case 'order':
                        $query['order[' . $value['field'] . ']'] = $value['sort'];
                        break;
                    case 'date':
                    case 'search':
                    case 'boolean':
                        $query[$value['field']] = $value['value'];
                        break;
                    case 'property':
                        foreach ($value['value'] as $v) {
                            $query['properties'][] = $v;
                        }

                        break;
                }
            }
        }

        return $query;
    }

    private function isCollectionResponse(array $res): bool
    {
        // Nouveau format paginé
        if (isset($res['items']) && is_array($res['items'])) {
            return array_is_list($res['items']) && isset($res['items'][0]['id']);
        }

        // Ancien format (liste brute)
        return array_is_list($res) && isset($res[0]['id']);
    }

    private function analyseSerializeAssertion(mixed $data, mixed $res, string $type): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    /* Assertion sur un subressource */
                    if ('hasKey' === $type) {
                        $this->serializeAssertion($key, $res, $type);
                    }

                    foreach ($value as $y) {
                        $this->analyseSerializeAssertion($y, $res[$key], $type);
                    }
                }
            }

            return;
        }

        $this->serializeAssertion($data, $res, $type);
    }

    private function serializeAssertion(mixed $data, mixed $res, string $type): void
    {
        switch ($type) {
            case 'hasKey':
                $this->assertArrayHasKey($data, $res);
                break;
            case 'hasNotKey':
                $this->assertArrayNotHasKey($data, $res);
                break;
        }
    }

    private function getTestResult(array $res, bool $onlyFirst = false): ?array
    {
        // Détection d'une collection paginée (nouveau format avec 'items')
        if (isset($res['items']) && is_array($res['items'])) {
            $items = $res['items'];

            if ($onlyFirst) {
                return $items[0] ?? null;
            }

            return $items;
        }

        // Détection d'une collection simple (ancien format)
        if ($onlyFirst && [] !== $res && array_is_list($res)) {
            return $res[0];
        }

        // Sinon, renvoie tel quel (item unique ou vide)
        return $res;
    }

    private function filterUsers(string $owner, array $otherUsers): string
    {
        $user = $otherUsers[0];
        foreach ($otherUsers as $user) {
            if ($user !== $owner) {
                break;
            }
        }

        return $user;
    }

    /**
     * Code à remplacer dès que possible.
     */
    private function replacePlaceholders(array $options): array
    {
        // Replace token placeholders
        if (isset($options['auth_bearer'])) {
            $tokenPlaceholder = $options['auth_bearer'];

            if ($tokenPlaceholder === self::PLACEHOLDERS['TOKENS']['ADMIN']) {
                $options['auth_bearer'] = $this->getToken($this->userAdmin);
            } elseif ($tokenPlaceholder === self::PLACEHOLDERS['TOKENS']['MODER']) {
                $options['auth_bearer'] = $this->getToken($this->userModer);
            } elseif ($tokenPlaceholder === self::PLACEHOLDERS['TOKENS']['MEMBER']) {
                $options['auth_bearer'] = $this->getToken($this->userMember);
            } elseif ($tokenPlaceholder === self::PLACEHOLDERS['TOKENS']['MEMBER_4']) {
                $options['auth_bearer'] = $this->getToken('user_member_4');
            } elseif ($tokenPlaceholder === self::PLACEHOLDERS['TOKENS']['MEMBER_8']) {
                $options['auth_bearer'] = $this->getToken('user_member_8');
            } elseif ($tokenPlaceholder === self::PLACEHOLDERS['TOKENS']['MODER_1']) {
                $options['auth_bearer'] = $this->getToken('user_moder_1');
            } elseif ($tokenPlaceholder === self::PLACEHOLDERS['TOKENS']['MODER_2']) {
                $options['auth_bearer'] = $this->getToken('user_moder_2');
            } elseif ($tokenPlaceholder === self::PLACEHOLDERS['TOKENS']['MODER_3']) {
                $options['auth_bearer'] = $this->getToken('user_moder_3');
            } elseif ($tokenPlaceholder === self::PLACEHOLDERS['TOKENS']['ADMIN_4']) {
                $options['auth_bearer'] = $this->getToken('user_admin_4');
            } elseif ($tokenPlaceholder === self::PLACEHOLDERS['USER']['MEMBER']) {
                $options['auth_bearer'] = $this->getToken('user_member_6');
            }
        }

        // Replace image placeholders in files
        if (isset($options['files']['imageFile'])) {
            $options['files']['imageFile'] = $this->replaceImagePlaceholder($options['files']['imageFile']);
        }

        // Replace image placeholders in extra/files
        if (isset($options['extra']['files']['imageFile'])) {
            $options['extra']['files']['imageFile'] = $this->replaceImagePlaceholder($options['extra']['files']['imageFile']);
        }

        // Replace avatar file placeholders in extra/files
        if (isset($options['extra']['files']['avatarFile'])) {
            $options['extra']['files']['avatarFile'] = $this->replaceImagePlaceholder($options['extra']['files']['avatarFile']);
        }

        return $options;
    }

    private function replaceImagePlaceholder(string $placeholder): UploadedFile|string
    {
        return match ($placeholder) {
            self::PLACEHOLDERS['IMAGES']['PAYSAGE'] => $this->getImage('paysage.jpg', __METHOD__),
            self::PLACEHOLDERS['IMAGES']['LARGE'] => $this->getImage('large_image.jpg', __METHOD__),
            self::PLACEHOLDERS['IMAGES']['PDF'] => $this->getImage('document.pdf', __METHOD__),
            self::PLACEHOLDERS['IMAGES']['WIDE'] => $this->getImage('wide_image.jpg', __METHOD__),
            self::PLACEHOLDERS['IMAGES']['TALL'] => $this->getImage('tall_image.jpg', __METHOD__),
            self::PLACEHOLDERS['IMAGES']['VENOM'] => $this->getImage('venom.jpg', __METHOD__),
            default => $placeholder,
        };
    }

    private function getPhysicalTempFile(string $filename, string $suffix): UploadedFile
    {
        $dir = 'images';

        $cleanSuffix = explode('::', $suffix)[1];

        $tmpFilePath = sys_get_temp_dir() . '/' . $cleanSuffix . '.jpg';

        copy(
            static::getContainer()->getParameter('kernel.project_dir') . '/assets/tests/' . $dir . '/' . $filename,
            $tmpFilePath
        );

        return new UploadedFile($tmpFilePath, $filename);
    }
}
