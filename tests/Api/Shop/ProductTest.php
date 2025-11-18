<?php

declare(strict_types=1);

namespace App\Tests\Api\Shop;

use App\Entity\Shop\Category;
use App\Entity\Shop\Product;
use App\Tests\Api\BaseTest;
use Faker\Factory;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

final class ProductTest extends BaseTest
{
    protected const string URL_API_OPE = self::URL_API . 'shop/products';

    public const array CRITERIA_IRI = ['title' => 'Product title 1'];

    protected const array CATEGORY_CRITERIA_IRI = ['title' => 'Shop category level 1 title 1'];

    protected ?string $iri;

    protected ?string $categoryIri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->iri = $this->findIriBy(Product::class, self::CRITERIA_IRI);
        $this->categoryIri = $this->findIriBy(Category::class, self::CATEGORY_CRITERIA_IRI);
    }

    public static function provideColShopProduct(): Generator
    {
        $assertions = [
            BaseTest::ASSERTION_TYPE['SERIALIZATION'] => [
                'hasKey' => [
                    'id',
                    'title',
                    'subtitle',
                    'description',
                    'price',
                    'slug',
                    'imageUrl',
                    ['category' => ['id', 'title']],
                    'createdAt',
                ],
                'hasNotKey' => [
                    'imageFile',
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
                                'field' => 'title',
                                'value' => 'Product',
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

    #[DataProvider('provideColShopProduct')]
    public function testColShopProduct(
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

    public static function provideCreateShopProductSuccess(): Generator
    {
        $fakeData = self::getFakeDataShopProduct();
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];
        $categoryIri = 'CATEGORY_IRI_PLACEHOLDER';

        $assertSerialization = [
            'hasKey' => [
                'id',
                'title',
                'subtitle',
                'description',
                'price',
                'slug',
                ['category' => ['id', 'title']],
                'createdAt',
                'updatedAt',
            ],
            'hasNotKey' => [
                'imageFile',
            ],
        ];

        yield 'Full' => [
            [
                'auth_bearer' => $adminToken,
                'json' => [
                    'title' => $fakeData['title'],
                    'subtitle' => $fakeData['subtitle'],
                    'description' => $fakeData['description'],
                    'price' => $fakeData['price'],
                    'slug' => $fakeData['slug'],
                    'category' => $categoryIri,
                ],
            ],
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
                BaseTest::ASSERTION_TYPE['EQUAL'] => [
                    'title' => $fakeData['title'],
                    'subtitle' => $fakeData['subtitle'],
                    'description' => $fakeData['description'],
                    'price' => $fakeData['price'],
                    'slug' => $fakeData['slug'],
                ],
            ],
        ];
    }

    #[DataProvider('provideCreateShopProductSuccess')]
    public function testCreateShopProductSuccess(
        array $options,
        array $asserts,
    ): void {
        // Remplacer le placeholder de catégorie par l'IRI réel
        if (isset($options['json']['category']) && 'CATEGORY_IRI_PLACEHOLDER' === $options['json']['category']) {
            $options['json']['category'] = $this->categoryIri;
        }

        $this->testSuccess(
            Request::METHOD_POST,
            self::URL_API_OPE,
            $options,
            Response::HTTP_CREATED,
            $asserts,
        );
    }

    public static function provideCreateProductException(): Generator
    {
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];
        $memberToken = self::PLACEHOLDERS['TOKENS']['MEMBER'];
        $categoryIri = 'CATEGORY_IRI_PLACEHOLDER';

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

        yield 'Negative price' => [
            [
                'auth_bearer' => $adminToken,
                'json' => [
                    'title' => 'Test Product',
                    'subtitle' => 'Test subtitle',
                    'description' => 'Test description',
                    'price' => -10.50,
                    'slug' => 'test-product',
                    'category' => $categoryIri,
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'price: This value should be either positive or zero.',
            ],
        ];

        yield 'Same title' => [
            [
                'auth_bearer' => $adminToken,
                'json' => [
                    'title' => 'Product title 1',
                    'subtitle' => 'Test subtitle',
                    'description' => 'Test description',
                    'price' => 29.99,
                    'slug' => 'test-product-unique',
                    'category' => $categoryIri,
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'title: A product already has this title.',
            ],
        ];

        yield 'No role' => [
            [
                'auth_bearer' => $memberToken,
                'json' => [
                    'title' => 'Test Product',
                    'subtitle' => 'Test subtitle',
                    'description' => 'Test description',
                    'price' => 29.99,
                    'slug' => 'test-product',
                    'category' => $categoryIri,
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access Denied',
            ],
        ];
    }

    #[DataProvider('provideCreateProductException')]
    public function testCreateProductException(
        array $options,
        array $exception,
    ): void {
        // Remplacer le placeholder de catégorie par l'IRI réel
        if (isset($options['json']['category']) && 'CATEGORY_IRI_PLACEHOLDER' === $options['json']['category']) {
            $options['json']['category'] = $this->categoryIri;
        }

        $this->testException(Request::METHOD_POST, self::URL_API_OPE, $options, $exception);
    }

    public function testGetShopProduct(): void
    {
        $assertSerialization = [
            'hasKey' => [
                'id',
                'title',
                'subtitle',
                'description',
                'price',
                'slug',
                'imageUrl',
                ['category' => ['id', 'title']],
                'createdAt',
                'updatedAt',
            ],
            'hasNotKey' => [
                'imageFile',
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

    public static function provideUpdateShopProductSuccess(): Generator
    {
        $fakeData = self::getFakeDataShopProduct();
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];

        $assertSerialization = [
            'hasKey' => [
                'id',
                'title',
                'subtitle',
                'description',
                'price',
                'slug',
                'imageUrl',
                ['category' => ['id', 'title']],
                'createdAt',
                'updatedAt',
            ],
            'hasNotKey' => [
                'imageFile',
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
                    'subtitle' => $fakeData['subtitle'],
                    'description' => $fakeData['description'],
                    'price' => $fakeData['price'],
                ],
            ],
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
                BaseTest::ASSERTION_TYPE['EQUAL'] => [
                    'title' => $fakeData['title'],
                    'subtitle' => $fakeData['subtitle'],
                    'description' => $fakeData['description'],
                    'price' => $fakeData['price'],
                ],
            ],
        ];

        yield 'Partial: title only' => [
            [
                'auth_bearer' => $adminToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'title' => $fakeData['title'],
                ],
            ],
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
                BaseTest::ASSERTION_TYPE['EQUAL'] => [
                    'title' => $fakeData['title'],
                ],
            ],
        ];
    }

    #[DataProvider('provideUpdateShopProductSuccess')]
    public function testUpdateShopProductSuccess(
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

    public static function provideUpdateProductException(): Generator
    {
        $memberToken = self::PLACEHOLDERS['TOKENS']['MEMBER'];

        yield 'No role' => [
            [
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'title' => 'Updated title',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];

        yield 'Not admin' => [
            [
                'auth_bearer' => $memberToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'title' => 'Updated title',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access Denied',
            ],
        ];
    }

    #[DataProvider('provideUpdateProductException')]
    public function testUpdateProductException(
        array $options,
        array $exception,
    ): void {
        $this->testException(Request::METHOD_PATCH, $this->iri, $options, $exception);
    }

    public static function provideDeleteShopProductSuccess(): Generator
    {
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];

        yield 'Full: Admin' => [
            [
                'auth_bearer' => $adminToken,
            ],
        ];
    }

    #[DataProvider('provideDeleteShopProductSuccess')]
    public function testDeleteShopProductSuccess(
        array $options,
    ): void {
        $this->testSuccess(
            Request::METHOD_DELETE,
            $this->iri,
            $options,
            Response::HTTP_NO_CONTENT,
        );
    }

    public static function provideDeleteShopProductException(): Generator
    {
        $memberToken = self::PLACEHOLDERS['TOKENS']['MEMBER'];

        yield 'No role' => [
            [],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];

        yield 'Not admin' => [
            [
                'auth_bearer' => $memberToken,
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access Denied',
            ],
        ];
    }

    #[DataProvider('provideDeleteShopProductException')]
    public function testDeleteShopProductException(
        array $options,
        array $exception,
    ): void {
        $this->testException(Request::METHOD_DELETE, $this->iri, $options, $exception);
    }

    public static function provideUploadImageProductSuccess(): Generator
    {
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];
        $image = self::PLACEHOLDERS['IMAGES']['PAYSAGE'];

        yield 'Upload Image' => [
            [
                'auth_bearer' => $adminToken,
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'files' => [
                        'imageFile' => $image,
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('provideUploadImageProductSuccess')]
    public function testUploadImageProductSuccess(
        array $options,
    ): void {
        $this->testSuccess(
            Request::METHOD_POST,
            $this->iri . '/image',
            $options,
            Response::HTTP_CREATED,
        );
    }

    public static function provideUploadImageProductException(): Generator
    {
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];
        $memberToken = self::PLACEHOLDERS['TOKENS']['MEMBER'];

        yield 'No role' => [
            [
                'extra' => [
                    'files' => [
                        'imageFile' => self::PLACEHOLDERS['IMAGES']['PAYSAGE'],
                    ],
                ],
                'headers' => ['Content-Type' => 'multipart/form-data'],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];

        yield 'Not admin' => [
            [
                'extra' => [
                    'files' => [
                        'imageFile' => self::PLACEHOLDERS['IMAGES']['PAYSAGE'],
                    ],
                ],
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'auth_bearer' => $memberToken,
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access Denied',
            ],
        ];

        yield 'Missing file' => [
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'auth_bearer' => $adminToken,
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'imageFile: This value should not be blank.',
            ],
        ];

        yield 'Wrong content type header' => [
            [
                'extra' => [
                    'files' => [
                        'imageFile' => self::PLACEHOLDERS['IMAGES']['PAYSAGE'],
                    ],
                ],
                'headers' => ['Content-Type' => 'application/json'],
                'auth_bearer' => $adminToken,
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                'message' => 'The content-type "application/json" is not supported.',
            ],
        ];
    }

    #[DataProvider('provideUploadImageProductException')]
    public function testUploadImageProductException(
        array $options,
        array $exception,
    ): void {
        $this->testException(
            Request::METHOD_POST,
            $this->iri . '/image',
            $options,
            $exception
        );
    }

    private static function getFakeDataShopProduct(): array
    {
        $faker = Factory::create();

        return [
            'title' => $faker->sentence(3, true),
            'subtitle' => $faker->sentence(5, true),
            'description' => $faker->text(200),
            'price' => $faker->randomFloat(2, 10, 500),
            'slug' => $faker->slug(),
        ];
    }
}
