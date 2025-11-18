<?php

declare(strict_types=1);

namespace App\Tests\Api\Shop;

use App\Entity\Shop\Carrier;
use App\Tests\Api\BaseTest;
use Faker\Factory;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

final class CarrierTest extends BaseTest
{
    protected const string URL_API_OPE = self::URL_API . 'shop/carriers';

    public const array CRITERIA_IRI = ['name' => 'Carrier name 1'];

    protected ?string $iri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->iri = $this->findIriBy(Carrier::class, self::CRITERIA_IRI);
    }

    public static function provideColShopCarrier(): Generator
    {
        $assertions = [
            BaseTest::ASSERTION_TYPE['SERIALIZATION'] => [
                'hasKey' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'createdAt',
                    'updatedAt',
                ],
            ],
        ];

        yield 'Normal' => [
            [],
            $assertions,
        ];

        yield 'Pagin' => [
            [
                'query' => self::generateQuery(
                    [
                        'page' => self::PAGIN_PAGE_ONE,
                        'ipp' => self::PAGIN_IPP,
                    ]
                ),
            ],
            $assertions,
        ];

        yield 'Filter' => [
            [
                'query' => self::generateQuery(
                    [
                        'filters' => [
                            [
                                'filter' => 'search',
                                'field' => 'name',
                                'value' => 'Carrier',
                            ],
                            [
                                'filter' => 'order',
                                'field' => 'price',
                                'sort' => 'ASC',
                            ],
                        ],
                    ]
                ),
            ],
            $assertions,
        ];
    }

    #[DataProvider('provideColShopCarrier')]
    public function testColShopCarrier(
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

    public static function provideCreateShopCarrierSuccess(): Generator
    {
        $fakeData = self::getFakeDataShopCarrier();
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];

        $assertSerialization = [
            'hasKey' => [
                'id',
                'name',
                'description',
                'price',
                'createdAt',
                'updatedAt',
            ],
        ];

        yield 'Full' => [
            [
                'auth_bearer' => $adminToken,
                'json' => [
                    'name' => $fakeData['name'],
                    'description' => $fakeData['description'],
                    'price' => $fakeData['price'],
                ],
            ],
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
                BaseTest::ASSERTION_TYPE['EQUAL'] => [
                    'name' => $fakeData['name'],
                    'description' => $fakeData['description'],
                    'price' => $fakeData['price'],
                ],
            ],
        ];
    }

    #[DataProvider('provideCreateShopCarrierSuccess')]
    public function testCreateShopCarrierSuccess(
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

    public static function provideCreateCarrierException(): Generator
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
                'message' => 'name: This value should not be blank.
description: This value should not be blank.
price: This value should not be blank.',
            ],
        ];

        yield 'Negative price' => [
            [
                'auth_bearer' => $adminToken,
                'json' => [
                    'name' => 'Test Carrier',
                    'description' => 'Test description',
                    'price' => -10.50,
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'price: This value should be either positive or zero.',
            ],
        ];

        yield 'No role' => [
            [
                'json' => [
                    'name' => 'Test Carrier',
                    'description' => 'Test description',
                    'price' => 10.50,
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];
    }

    #[DataProvider('provideCreateCarrierException')]
    public function testCreateCarrierException(
        array $options,
        array $exception,
    ): void {
        $this->testException(Request::METHOD_POST, self::URL_API_OPE, $options, $exception);
    }

    public function testGetShopCarrier(): void
    {
        $assertSerialization = [
            'hasKey' => [
                'id',
                'name',
                'description',
                'price',
                'createdAt',
                'updatedAt',
            ],
        ];

        $this->testSuccess(
            Request::METHOD_GET,
            $this->iri,
            [],
            Response::HTTP_OK,
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
            ],
        );
    }

    public static function provideUpdateShopCarrierSuccess(): Generator
    {
        $fakeData = self::getFakeDataShopCarrier();
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];

        $assertSerialization = [
            'hasKey' => [
                'id',
                'name',
                'description',
                'price',
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
                    'name' => $fakeData['name'],
                    'description' => $fakeData['description'],
                    'price' => $fakeData['price'],
                ],
            ],
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
                BaseTest::ASSERTION_TYPE['EQUAL'] => [
                    'name' => $fakeData['name'],
                    'description' => $fakeData['description'],
                    'price' => $fakeData['price'],
                ],
            ],
        ];

        yield 'Partial: name only' => [
            [
                'auth_bearer' => $adminToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'name' => $fakeData['name'],
                ],
            ],
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
                BaseTest::ASSERTION_TYPE['EQUAL'] => [
                    'name' => $fakeData['name'],
                ],
            ],
        ];
    }

    #[DataProvider('provideUpdateShopCarrierSuccess')]
    public function testUpdateShopCarrierSuccess(
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

    public static function provideUpdateCarrierException(): Generator
    {
        yield 'No role' => [
            [
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'name' => 'Updated name',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];
    }

    #[DataProvider('provideUpdateCarrierException')]
    public function testUpdateCarrierException(
        array $options,
        array $exception,
    ): void {
        $this->testException(Request::METHOD_PATCH, $this->iri, $options, $exception);
    }

    public static function provideDeleteShopCarrierSuccess(): Generator
    {
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];

        yield 'Full: Admin' => [
            [
                'auth_bearer' => $adminToken,
            ],
        ];
    }

    #[DataProvider('provideDeleteShopCarrierSuccess')]
    public function testDeleteShopCarrierSuccess(
        array $options,
    ): void {
        $this->testSuccess(
            Request::METHOD_DELETE,
            $this->iri,
            $options,
            Response::HTTP_NO_CONTENT,
        );
    }

    public static function provideDeleteShopCarrierException(): Generator
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

    #[DataProvider('provideDeleteShopCarrierException')]
    public function testDeleteShopCarrierException(
        array $options,
        array $exception,
    ): void {
        $this->testException(Request::METHOD_DELETE, $this->iri, $options, $exception);
    }

    private static function getFakeDataShopCarrier(): array
    {
        $faker = Factory::create();

        return [
            'name' => $faker->sentence(3, true),
            'description' => $faker->text(100),
            'price' => $faker->randomFloat(2, 5, 50),
        ];
    }
}
