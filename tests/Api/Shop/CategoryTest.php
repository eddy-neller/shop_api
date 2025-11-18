<?php

declare(strict_types=1);

namespace App\Tests\Api\Shop;

use App\Entity\Shop\Category;
use App\Tests\Api\BaseTest;
use Faker\Factory;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

final class CategoryTest extends BaseTest
{
    protected const string URL_API_OPE = self::URL_API . 'shop/categories';

    public const array CRITERIA_IRI = ['title' => 'Shop category level 1 title 1'];

    protected ?string $iri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->iri = $this->findIriBy(Category::class, self::CRITERIA_IRI);
    }

    public static function provideColShopCategory(): Generator
    {
        $assertions = [
            BaseTest::ASSERTION_TYPE['SERIALIZATION'] => [
                'hasKey' => [
                    'id',
                    'title',
                    'nbProduct',
                    'slug',
                    'children',
                    'createdAt',
                ],
                'hasNotKey' => [
                    'description',
                    'updatedAt',
                ],
            ],
        ];

        yield 'Normal' => [
            [],
            $assertions,
        ];

        $assertionsWithParent = $assertions;
        $assertionsWithParent[BaseTest::ASSERTION_TYPE['SERIALIZATION']]['hasKey'][] = 'parent';

        yield 'Filter' => [
            [
                'query' => self::generateQuery(
                    [
                        'filters' => [
                            [
                                'filter' => 'search',
                                'field' => 'level',
                                'value' => 1,
                            ],
                        ],
                    ]
                ),
            ],
            $assertionsWithParent,
        ];
    }

    #[DataProvider('provideColShopCategory')]
    public function testColShopCategory(
        array $options,
        array $asserts,
    ): void {
        $this->testSuccess(
            Request::METHOD_GET,
            self::URL_API_OPE,
            $options,
            Response::HTTP_OK,
            $asserts,
        );
    }

    public static function provideCreateShopCategorySuccess(): Generator
    {
        $fakeData = self::getFakeDataShopCategory();
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];

        $assertSerialization = [
            'hasKey' => [
                'id',
                'title',
                'description',
                'nbProduct',
                'slug',
                'children',
                'createdAt',
                'updatedAt',
            ],
        ];

        yield 'Full' => [
            [
                'auth_bearer' => $adminToken,
                'json' => [
                    'title' => $fakeData['title'],
                    'description' => $fakeData['description'],
                ],
            ],
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
                BaseTest::ASSERTION_TYPE['EQUAL'] => [
                    'title' => $fakeData['title'],
                    'description' => $fakeData['description'],
                ],
            ],
        ];
    }

    #[DataProvider('provideCreateShopCategorySuccess')]
    public function testCreateShopCategorySuccess(
        array $options,
        array $asserts,
    ): void {
        $this->testSuccess(
            Request::METHOD_POST,
            self::URL_API_OPE,
            $options,
            Response::HTTP_CREATED,
            $asserts,
        );
    }

    public static function provideCreateCategoryException(): Generator
    {
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];

        yield 'Empty' => [
            [
                'auth_bearer' => $adminToken,
                'json' => [],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'title: This value should not be blank.',
            ],
        ];
        yield 'Same title' => [
            [
                'auth_bearer' => $adminToken,
                'json' => [
                    'title' => 'Shop category level 1 title 1',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'title: A shop category already has this title.',
            ],
        ];
    }

    #[DataProvider('provideCreateCategoryException')]
    public function testCreateCategoryException(
        array $options,
        array $exception,
    ): void {
        $this->testException(Request::METHOD_POST, self::URL_API_OPE, $options, $exception);
    }

    public function testGetShopCategory(): void
    {
        $assertSerialization = [
            'hasKey' => [
                'id',
                'title',
                'description',
                'nbProduct',
                'slug',
                'parent',
                'children',
                'createdAt',
                'updatedAt',
            ],
        ];

        $this->testSuccess(
            Request::METHOD_GET,
            $this->iri,
            [
                'auth_bearer' => $this->getToken($this->userAdmin),
            ],
            Response::HTTP_OK,
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
            ],
        );
    }

    public static function provideUpdateShopCategorySuccess(): Generator
    {
        $fakeData = self::getFakeDataShopCategory();
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];

        $assertSerialization = [
            'hasKey' => [
                'id',
                'title',
                'description',
                'nbProduct',
                'slug',
                'parent',
                'children',
                'createdAt',
                'updatedAt',
            ],
        ];

        yield 'Full' => [
            [
                'auth_bearer' => $adminToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'title' => $fakeData['title'],
                    'description' => $fakeData['description'],
                ],
            ],
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
                BaseTest::ASSERTION_TYPE['EQUAL'] => [
                    'title' => $fakeData['title'],
                    'description' => $fakeData['description'],
                ],
            ],
        ];
    }

    #[DataProvider('provideUpdateShopCategorySuccess')]
    public function testUpdateShopCategorySuccess(
        array $options,
        array $asserts,
    ): void {
        $this->testSuccess(
            Request::METHOD_PATCH,
            $this->iri,
            $options,
            Response::HTTP_OK,
            $asserts,
        );
    }

    public static function provideUpdateCategoryException(): Generator
    {
        yield 'No role' => [
            [
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];
    }

    #[DataProvider('provideUpdateCategoryException')]
    public function testUpdateCategoryException(
        array $options,
        array $exception,
    ): void {
        $this->testException(Request::METHOD_PATCH, $this->iri, $options, $exception);
    }

    public static function provideDeleteShopCategorySuccess(): Generator
    {
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];

        yield 'Full: Admin' => [
            [
                'auth_bearer' => $adminToken,
            ],
        ];
    }

    #[DataProvider('provideDeleteShopCategorySuccess')]
    public function testDeleteShopCategorySuccess(
        array $options,
    ): void {
        $this->testSuccess(
            Request::METHOD_DELETE,
            $this->iri,
            $options,
            Response::HTTP_NO_CONTENT,
        );
    }

    public static function provideDeleteShopCategoryException(): Generator
    {
        yield 'No role' => [
            [],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];
    }

    #[DataProvider('provideDeleteShopCategoryException')]
    public function testDeleteShopCategoryException(
        array $options,
        array $exception,
    ): void {
        $this->testException(Request::METHOD_DELETE, $this->iri, $options, $exception);
    }

    private static function getFakeDataShopCategory(): array
    {
        $faker = Factory::create();

        return [
            'title' => $faker->sentence(5),
            'description' => $faker->text(),
        ];
    }
}
