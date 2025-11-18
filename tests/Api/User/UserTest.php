<?php

declare(strict_types=1);

namespace Api\User;

use App\DataFixtures\test\User\UserFixtures;
use App\Service\User\TokenManager;
use App\Tests\Api\BaseTest;
use Faker\Factory;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

final class UserTest extends BaseTest
{
    protected const string URL_API_OPE = self::URL_API . 'users';

    protected const string USER_DATA = 'user_member_6';

    protected const array CRITERIA_IRI = ['username' => self::USER_DATA];

    protected ?string $iri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->iri = $this->findIriByHttp(self::URL_API_OPE, self::CRITERIA_IRI, asAdmin: true);
    }

    public static function provideColUser(): Generator
    {
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];

        $assertions = [
            BaseTest::ASSERTION_TYPE['SERIALIZATION'] => [
                'hasKey' => [
                    'id',
                    'firstname',
                    'lastname',
                    'username',
                    'email',
                    'roles',
                    'status',
                    'avatarUrl',
                    'lastVisit',
                    'createdAt',
                ],
                'hasNotKey' => [
                    'nbLogin',
                    'updatedAt',
                    'password',
                    'avatarFile',
                ],
            ],
        ];

        yield 'Normal' => [
            [
                'auth_bearer' => $adminToken,
            ],
            $assertions,
        ];
        yield 'Pagin' => [
            [
                'auth_bearer' => $adminToken,
                'query' => self::generateQuery(
                    [
                        'page' => self::PAGIN_PAGE,
                        'ipp' => self::PAGIN_IPP,
                    ]
                ),
            ],
            $assertions,
        ];
        // TODO: Add filter exclude_id test
        yield 'Filter' => [
            [
                'auth_bearer' => $adminToken,
                'query' => self::generateQuery(
                    [
                        'filters' => [],
                    ]
                ),
            ],
            $assertions,
        ];
    }

    #[DataProvider('provideColUser')]
    public function testColUser(
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

    public static function provideColUserException(): Generator
    {
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
                'auth_bearer' => self::PLACEHOLDERS['TOKENS']['MEMBER'],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Access Denied',
            ],
        ];
    }

    #[DataProvider('provideColUserException')]
    public function testColUserException(
        array $options,
        array $exception,
    ): void {
        $this->testException(
            Request::METHOD_GET,
            self::URL_API_OPE,
            $options,
            $exception
        );
    }

    public static function provideRegisterSuccess(): Generator
    {
        $fakeData = self::getFakeDataUser();

        $assertSerialization = [
            'hasKey' => [
                'id',
                'username',
                'email',
                'roles',
                'status',
                'lastVisit',
                'createdAt',
                'nbLogin',
                'updatedAt',
            ],
            'hasNotKey' => [
                'password',
                'plainPassword',
                'avatarFile',
            ],
        ];

        yield 'Full' => [
            [
                'json' => [
                    'email' => $fakeData['email'],
                    'username' => $fakeData['username'],
                    'password' => $fakeData['password'],
                    'confirmPassword' => $fakeData['password'],
                    'preferences' => [
                        'lang' => 'EN',
                    ],
                ],
            ],
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
                BaseTest::ASSERTION_TYPE['EQUAL'] => [
                    'email' => $fakeData['email'],
                    'username' => $fakeData['username'],
                ],
            ],
        ];
    }

    #[DataProvider('provideRegisterSuccess')]
    public function testRegisterSuccess(
        array $options,
        array $asserts,
    ): void {
        $this->testSuccess(
            Request::METHOD_POST,
            self::URL_API_OPE . '/register',
            $options,
            Response::HTTP_CREATED,
            $asserts,
        );
    }

    public static function provideRegisterException(): Generator
    {
        $faker = Factory::create();

        yield 'Empty' => [
            [
                'json' => [],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'email: This value should not be blank.
username: This value should not be blank.
password: This value should not be blank.
confirmPassword: This value should not be blank.
preferences: This value should not be blank.',
            ],
        ];
        yield 'Email invalid' => [
            [
                'json' => [
                    'email' => $faker->sentence(),
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'email: This value is not a valid email address.',
            ],
        ];
        yield 'Preference invalid' => [
            [
                'json' => [
                    'preferences' => [],
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'preferences.lang: This value should not be blank.',
            ],
        ];
        yield 'Preference.lang invalid' => [
            [
                'json' => [
                    'preferences' => [
                        'lang' => 'F',
                    ],
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'preferences.lang: The language must be exactly 2 characters long.',
            ],
        ];
        yield 'Password confirmation mismatch' => [
            [
                'json' => [
                    'email' => $faker->email(),
                    'username' => $faker->userName(),
                    'password' => 'ValidPassword123!',
                    'confirmPassword' => 'DifferentPassword123!',
                    'preferences' => [
                        'lang' => 'EN',
                    ],
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'confirmPassword: The password confirmation does not match.',
            ],
        ];
        yield 'Missing confirm password' => [
            [
                'json' => [
                    'email' => $faker->email(),
                    'username' => $faker->userName(),
                    'password' => 'ValidPassword123!',
                    'preferences' => [
                        'lang' => 'EN',
                    ],
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'confirmPassword: This value should not be blank.',
            ],
        ];
    }

    #[DataProvider('provideRegisterException')]
    public function testRegisterException(
        array $options,
        array $exception,
    ): void {
        $this->testException(Request::METHOD_POST, self::URL_API_OPE . '/register', $options, $exception);
    }

    public static function provideEmailActivationRequestSuccess(): Generator
    {
        $faker = Factory::create();

        yield 'Valid email' => [
            [
                'json' => [
                    'email' => $faker->email(),
                ],
            ],
        ];
    }

    #[DataProvider('provideEmailActivationRequestSuccess')]
    public function testEmailActivationRequestSuccess(
        array $options,
    ): void {
        $this->testSuccess(
            Request::METHOD_POST,
            self::URL_API_OPE . '/register/email-activation-request',
            $options,
            Response::HTTP_NO_CONTENT,
        );
    }

    public static function provideEmailActivationRequestException(): Generator
    {
        $faker = Factory::create();

        yield 'Empty request' => [
            [
                'json' => [],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'email: This value should not be blank.',
            ],
        ];
        yield 'Email invalid' => [
            [
                'json' => [
                    'email' => $faker->sentence(),
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'email: This value is not a valid email address.',
            ],
        ];
    }

    #[DataProvider('provideEmailActivationRequestException')]
    public function testEmailActivationRequestException(
        array $options,
        array $exception,
    ): void {
        $this->testException(
            Request::METHOD_POST,
            self::URL_API_OPE . '/register/email-activation-request',
            $options,
            $exception
        );
    }

    public static function provideEmailActivationValidationSuccess(): Generator
    {
        $encoded = base64_encode(UserFixtures::ACTIVATION_EMAIL . TokenManager::TOKEN_SEPARATOR . UserFixtures::ACTIVATION_RAW_TOKEN);

        yield 'Valid token' => [
            [
                'json' => [
                    'token' => $encoded,
                ],
            ],
        ];
    }

    #[DataProvider('provideEmailActivationValidationSuccess')]
    public function testEmailActivationValidationSuccess(
        array $options,
    ): void {
        $this->testSuccess(
            Request::METHOD_POST,
            self::URL_API_OPE . '/register/validation',
            $options,
            Response::HTTP_NO_CONTENT,
        );
    }

    public static function provideEmailActivationValidationException(): Generator
    {
        yield 'Empty request' => [
            [
                'json' => [],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'token: This value should not be blank.',
            ],
        ];
        yield 'Missing token' => [
            [
                'json' => [
                    'username' => 'test',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'token: This value should not be blank.',
            ],
        ];
        yield 'Empty string token' => [
            [
                'json' => [
                    'token' => '',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'token: This value should not be blank.',
            ],
        ];
    }

    #[DataProvider('provideEmailActivationValidationException')]
    public function testEmailActivationValidationException(
        array $options,
        array $exception,
    ): void {
        $this->testException(
            Request::METHOD_POST,
            self::URL_API_OPE . '/register/validation',
            $options,
            $exception
        );
    }

    public static function providePasswordResetRequestSuccess(): Generator
    {
        $faker = Factory::create();

        yield 'Valid email' => [
            [
                'json' => [
                    'email' => $faker->email(),
                ],
            ],
        ];
    }

    #[DataProvider('providePasswordResetRequestSuccess')]
    public function testPasswordResetRequestSuccess(
        array $options,
    ): void {
        $this->testSuccess(
            Request::METHOD_POST,
            self::URL_API_OPE . '/reset-password/request',
            $options,
            Response::HTTP_NO_CONTENT,
        );
    }

    public static function providePasswordResetRequestException(): Generator
    {
        yield 'Empty request' => [
            [
                'json' => [],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'email: This value should not be blank.',
            ],
        ];
        yield 'Missing email' => [
            [
                'json' => [
                    'username' => 'test',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'email: This value should not be blank.',
            ],
        ];
        yield 'Invalid email format' => [
            [
                'json' => [
                    'email' => 'invalid-email',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'email: This value is not a valid email address.',
            ],
        ];
        yield 'Invalid email format with spaces' => [
            [
                'json' => [
                    'email' => ' invalid-email ',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'email: This value is not a valid email address.',
            ],
        ];
        yield 'Empty string email' => [
            [
                'json' => [
                    'email' => '',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'email: This value should not be blank.',
            ],
        ];
    }

    #[DataProvider('providePasswordResetRequestException')]
    public function testPasswordResetRequestException(
        array $options,
        array $exception,
    ): void {
        $this->testException(
            Request::METHOD_POST,
            self::URL_API_OPE . '/reset-password/request',
            $options,
            $exception
        );
    }

    public static function providePasswordResetCheckSuccess(): Generator
    {
        $encoded = base64_encode(UserFixtures::ACTIVATION_EMAIL . TokenManager::TOKEN_SEPARATOR . UserFixtures::ACTIVATION_RAW_TOKEN);

        yield 'Valid token' => [
            [
                'json' => [
                    'token' => $encoded,
                ],
            ],
        ];
    }

    #[DataProvider('providePasswordResetCheckSuccess')]
    public function testPasswordResetCheckSuccess(
        array $options,
    ): void {
        $this->testSuccess(
            Request::METHOD_POST,
            self::URL_API_OPE . '/reset-password/check',
            $options,
            Response::HTTP_NO_CONTENT,
        );
    }

    public static function providePasswordResetCheckException(): Generator
    {
        yield 'Empty request' => [
            [
                'json' => [],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'token: This value should not be blank.',
            ],
        ];
        yield 'Missing token' => [
            [
                'json' => [
                    'email' => 'test@example.com',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'token: This value should not be blank.',
            ],
        ];
        yield 'Empty string token' => [
            [
                'json' => [
                    'token' => '',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'token: This value should not be blank.',
            ],
        ];
    }

    #[DataProvider('providePasswordResetCheckException')]
    public function testPasswordResetCheckException(
        array $options,
        array $exception,
    ): void {
        $this->testException(
            Request::METHOD_POST,
            self::URL_API_OPE . '/reset-password/check',
            $options,
            $exception
        );
    }

    public static function providePasswordResetConfirmSuccess(): Generator
    {
        $encoded = base64_encode(UserFixtures::ACTIVATION_EMAIL . TokenManager::TOKEN_SEPARATOR . UserFixtures::ACTIVATION_RAW_TOKEN);

        yield 'Valid token and password' => [
            [
                'json' => [
                    'token' => $encoded,
                    'newPassword' => 'NewPassword123!',
                    'confirmNewPassword' => 'NewPassword123!',
                ],
            ],
        ];
    }

    #[DataProvider('providePasswordResetConfirmSuccess')]
    public function testPasswordResetConfirmSuccess(
        array $options,
    ): void {
        $this->testSuccess(
            Request::METHOD_POST,
            self::URL_API_OPE . '/reset-password/confirm',
            $options,
            Response::HTTP_NO_CONTENT,
        );
    }

    public static function providePasswordResetConfirmException(): Generator
    {
        yield 'Empty request' => [
            [
                'json' => [],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'token: This value should not be blank.
newPassword: This value should not be blank.
confirmNewPassword: This value should not be blank.',
            ],
        ];
        yield 'Missing token' => [
            [
                'json' => [
                    'password' => 'NewPassword123!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'token: This value should not be blank.',
            ],
        ];
        yield 'Missing new password' => [
            [
                'json' => [
                    'token' => 'valid-reset-token-123',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'newPassword: This value should not be blank.',
            ],
        ];
        yield 'Empty string token' => [
            [
                'json' => [
                    'token' => '',
                    'password' => 'NewPassword123!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'token: This value should not be blank.',
            ],
        ];
        yield 'Empty string new password' => [
            [
                'json' => [
                    'token' => 'valid-reset-token-123',
                    'newPassword' => '',
                    'confirmNewPassword' => '',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'newPassword: This value should not be blank.
confirmNewPassword: This value should not be blank.',
            ],
        ];
        yield 'Password too short' => [
            [
                'json' => [
                    'token' => 'valid-reset-token-123',
                    'newPassword' => 'Short1!',
                    'confirmPassword' => 'Short1!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'newPassword: Invalid password.',
            ],
        ];
        yield 'Password too long' => [
            [
                'json' => [
                    'token' => 'valid-reset-token-123',
                    'newPassword' => 'VeryLongPasswordThatExceedsTheMaximumLengthAllowed123!',
                    'confirmPassword' => 'VeryLongPasswordThatExceedsTheMaximumLengthAllowed123!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'newPassword: Invalid password.',
            ],
        ];
        yield 'Password without special character' => [
            [
                'json' => [
                    'token' => 'valid-reset-token-123',
                    'newPassword' => 'Password123',
                    'confirmPassword' => 'Password123',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'newPassword: Invalid password.',
            ],
        ];
        yield 'Password without digit' => [
            [
                'json' => [
                    'token' => 'valid-reset-token-123',
                    'newPassword' => 'Password!@#',
                    'confirmNewPassword' => 'Password!@#',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'newPassword: Invalid password.',
            ],
        ];
        yield 'Password without uppercase' => [
            [
                'json' => [
                    'token' => 'valid-reset-token-123',
                    'newPassword' => 'password123!',
                    'confirmNewPassword' => 'password123!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'newPassword: Invalid password.',
            ],
        ];
        yield 'Password confirmation mismatch' => [
            [
                'json' => [
                    'token' => 'valid-reset-token-123',
                    'newPassword' => 'ValidPassword123!',
                    'confirmNewPassword' => 'DifferentPassword123!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'confirmNewPassword: The password confirmation does not match.',
            ],
        ];
        yield 'Missing confirm password' => [
            [
                'json' => [
                    'token' => 'valid-reset-token-123',
                    'newPassword' => 'ValidPassword123!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'confirmNewPassword: This value should not be blank.',
            ],
        ];
    }

    #[DataProvider('providePasswordResetConfirmException')]
    public function testPasswordResetConfirmException(
        array $options,
        array $exception,
    ): void {
        $this->testException(
            Request::METHOD_POST,
            self::URL_API_OPE . '/reset-password/confirm',
            $options,
            $exception
        );
    }

    public function testGetUser(): void
    {
        $assertSerialization = [
            'hasKey' => [
                'id',
                'firstname',
                'lastname',
                'username',
                'email',
                'roles',
                'status',
                'avatarUrl',
                'lastVisit',
                'createdAt',
                'nbLogin',
                'updatedAt',
            ],
            'hasNotKey' => [
                'password',
                'plainPassword',
                'avatarFile',
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

    public function testGetMe(): void
    {
        $assertSerialization = [
            'hasKey' => [
                'id',
                'firstname',
                'lastname',
                'username',
                'email',
                'roles',
                'status',
                'avatarUrl',
                'lastVisit',
                'createdAt',
                'nbLogin',
                'updatedAt',
            ],
            'hasNotKey' => [
                'password',
                'plainPassword',
                'avatarFile',
            ],
        ];

        $this->testSuccess(
            Request::METHOD_GET,
            self::URL_API_OPE . '/me',
            [
                'auth_bearer' => $this->getToken(self::USER_DATA),
            ],
            Response::HTTP_OK,
            [
                BaseTest::ASSERTION_TYPE['SERIALIZATION'] => $assertSerialization,
            ],
        );
    }

    public static function provideGetMeException(): Generator
    {
        yield 'No role' => [
            [],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];
        yield 'Empty token' => [
            [
                'auth_bearer' => '',
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];
        yield 'Invalid token' => [
            [
                'auth_bearer' => 'invalid-token',
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];
        yield 'Expired token' => [
            [
                'auth_bearer' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2NzM5MjQwMDAsImV4cCI6MTY3MzkyNDAwMSwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiZXhwaXJlZCJ9.expired-signature',
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];
        yield 'Malformed token' => [
            [
                'auth_bearer' => 'not-a-valid-jwt-token',
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];
        yield 'Token without Bearer prefix' => [
            [
                'auth_bearer' => 'valid-jwt-token-without-bearer-prefix',
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP 401 returned',
            ],
        ];
    }

    #[DataProvider('provideGetMeException')]
    public function testGetMeException(
        array $options,
        array $exception,
    ): void {
        $this->testException(
            Request::METHOD_GET,
            self::URL_API_OPE . '/me',
            $options,
            $exception
        );
    }

    public static function provideUpdatePasswordSuccess(): Generator
    {
        $ownerToken = self::PLACEHOLDERS['USER']['MEMBER'];

        yield 'Update Password' => [
            [
                'auth_bearer' => $ownerToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'currentPassword' => self::USER_DATA,
                    'newPassword' => 'NewPassword123!',
                    'confirmNewPassword' => 'NewPassword123!',
                ],
            ],
        ];
    }

    #[DataProvider('provideUpdatePasswordSuccess')]
    public function testUpdatePasswordSuccess(
        array $options,
    ): void {
        $this->testSuccess(
            Request::METHOD_PATCH,
            self::URL_API_OPE . '/me/update-password',
            $options,
            Response::HTTP_NO_CONTENT,
        );
    }

    public static function provideUpdatePasswordException(): Generator
    {
        $faker = Factory::create();
        $ownerToken = self::PLACEHOLDERS['USER']['MEMBER'];

        yield 'Bad Current Password' => [
            [
                'auth_bearer' => $ownerToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'currentPassword' => $faker->password(),
                    'newPassword' => 'NewPassword123!',
                    'confirmNewPassword' => 'NewPassword123!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'currentPassword: Invalid password.',
            ],
        ];
        yield 'Missing current password' => [
            [
                'auth_bearer' => $ownerToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'newPassword' => 'NewPassword123!',
                    'confirmNewPassword' => 'NewPassword123!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'currentPassword: This value should not be blank.',
            ],
        ];
        yield 'Missing new password' => [
            [
                'auth_bearer' => $ownerToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'currentPassword' => self::USER_DATA,
                    'confirmNewPassword' => 'NewPassword123!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'newPassword: This value should not be blank.',
            ],
        ];
        yield 'Missing confirm password' => [
            [
                'auth_bearer' => $ownerToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'currentPassword' => self::USER_DATA,
                    'newPassword' => 'NewPassword123!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'confirmNewPassword: This value should not be blank.',
            ],
        ];
        yield 'Password confirmation mismatch' => [
            [
                'auth_bearer' => $ownerToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'currentPassword' => self::USER_DATA,
                    'newPassword' => 'NewPassword123!',
                    'confirmNewPassword' => 'DifferentPassword123!',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'confirmNewPassword: The password confirmation does not match.',
            ],
        ];
        yield 'Same password as current' => [
            [
                'auth_bearer' => $ownerToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'currentPassword' => self::USER_DATA,
                    'newPassword' => self::USER_DATA,
                    'confirmNewPassword' => self::USER_DATA,
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'newPassword: The new password must be different from the current password.',
            ],
        ];
        yield 'Weak password' => [
            [
                'auth_bearer' => $ownerToken,
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'currentPassword' => self::USER_DATA,
                    'newPassword' => 'weak',
                    'confirmNewPassword' => 'weak',
                ],
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'newPassword: Invalid new password.',
            ],
        ];
    }

    #[DataProvider('provideUpdatePasswordException')]
    public function testUpdatePasswordException(
        array $options,
        array $exception,
    ): void {
        $this->testException(
            Request::METHOD_PATCH,
            self::URL_API_OPE . '/me/update-password',
            $options,
            $exception
        );
    }

    public static function provideEditAvatarSuccess(): Generator
    {
        yield 'Normal' => [
            [
                'extra' => [
                    'files' => [
                        'avatarFile' => self::PLACEHOLDERS['IMAGES']['VENOM'],
                    ],
                ],
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'auth_bearer' => self::PLACEHOLDERS['TOKENS']['MEMBER'],
            ],
            [
                BaseTest::ASSERTION_TYPE['NOT_NULL'] => ['avatarUrl'],
            ],
        ];
    }

    #[DataProvider('provideEditAvatarSuccess')]
    public function testEditAvatarSuccess(
        array $options,
        array $asserts,
    ): void {
        $this->testSuccess(
            Request::METHOD_POST,
            self::URL_API_OPE . '/me/avatar',
            $options,
            Response::HTTP_CREATED,
            $asserts,
        );
    }

    public static function provideEditAvatarException(): Generator
    {
        $ownerToken = self::PLACEHOLDERS['TOKENS']['MEMBER'];

        yield 'No role' => [
            [
                'extra' => [
                    'files' => [
                        'avatarFile' => self::PLACEHOLDERS['IMAGES']['VENOM'],
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
        yield 'Missing file' => [
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'auth_bearer' => $ownerToken,
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'avatarFile: Please upload an avatar.',
            ],
        ];
        /* yield 'File too large' => [
            [
                'extra' => [
                    'files' => [
                        'avatarFile' => $this->getImage('large_image.jpg', __METHOD__, 300000), // 300k > 200k
                    ],
                ],
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'auth_bearer' => $ownerToken,
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'avatarFile: The file is too large (300 kB). Allowed maximum size is 200 kB.',
            ],
        ];
        yield 'Invalid mime type' => [
            [
                'extra' => [
                    'files' => [
                        'avatarFile' => $this->getImage('document.pdf', __METHOD__, 50000, 'application/pdf'),
                    ],
                ],
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'auth_bearer' => $ownerToken,
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'avatarFile: The mime type of the file is invalid (application/pdf). Allowed mime types are image/png, image/gif, image/jpeg, image/pjpeg.',
            ],
        ];
        yield 'Image too small' => [
            [
                'extra' => [
                    'files' => [
                        'avatarFile' => $this->getImage('small_image.jpg', __METHOD__, 50000, 'image/jpeg', 50, 50), // 50x50 < 96x96
                    ],
                ],
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'auth_bearer' => $ownerToken,
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'avatarFile: The image width is too small (50 px). Minimum width is 96 px.',
            ],
        ];
        yield 'Image too large' => [
            [
                'extra' => [
                    'files' => [
                        'avatarFile' => $this->getImage('large_image.jpg', __METHOD__, 50000, 'image/jpeg', 200, 200), // 200x200 > 96x96
                    ],
                ],
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'auth_bearer' => $ownerToken,
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'avatarFile: The image width is too large (200 px). Maximum width is 96 px.',
            ],
        ]; */
        yield 'Wrong content type header' => [
            [
                'extra' => [
                    'files' => [
                        'avatarFile' => self::PLACEHOLDERS['IMAGES']['VENOM'],
                    ],
                ],
                'headers' => ['Content-Type' => 'application/json'],
                'auth_bearer' => $ownerToken,
            ],
            [
                'class' => ClientExceptionInterface::class,
                'code' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                'message' => 'The content-type "application/json" is not supported.',
            ],
        ];
    }

    #[DataProvider('provideEditAvatarException')]
    public function testEditAvatarException(
        array $options,
        array $exception,
    ): void {
        $this->testException(
            Request::METHOD_POST,
            self::URL_API_OPE . '/me/avatar',
            $options,
            $exception
        );
    }

    public static function provideDeleteUserSuccess(): Generator
    {
        $adminToken = self::PLACEHOLDERS['TOKENS']['ADMIN'];

        yield 'Full: Admin' => [
            [
                'auth_bearer' => $adminToken,
            ],
        ];
    }

    #[DataProvider('provideDeleteUserSuccess')]
    public function testDeleteUserSuccess(
        array $options,
    ): void {
        $this->testSuccess(
            Request::METHOD_DELETE,
            $this->iri,
            $options,
            Response::HTTP_NO_CONTENT,
        );
    }

    public static function provideDeleteUserException(): Generator
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

    #[DataProvider('provideDeleteUserException')]
    public function testDeleteUserException(
        array $options,
        array $exception,
    ): void {
        $this->testException(Request::METHOD_DELETE, $this->iri, $options, $exception);
    }

    private static function getFakeDataUser(): array
    {
        $faker = Factory::create();

        return [
            'email' => $faker->email(),
            'username' => $faker->userName(),
            'password' => 'User_max88',
        ];
    }
}
