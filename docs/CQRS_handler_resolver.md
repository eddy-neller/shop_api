# Handler Resolver - Fonctionnement et Configuration

## Vue d'ensemble

Le **Handler Resolver** est un composant central du système CQRS qui permet de découvrir automatiquement et d'instancier les handlers appropriés pour chaque commande ou requête, en se basant sur une **convention de nommage**.

## Principe de fonctionnement

### 1. Convention de nommage

Le resolver utilise une convention simple pour associer automatiquement une commande/query à son handler :

-   **Commandes** : `{Action}Command` → `{Action}CommandHandler`
    -   Exemple : `RegisterUserCommand` → `RegisterUserCommandHandler`
-   **Queries** : `{Action}Query` → `{Action}QueryHandler`
    -   Exemple : `DisplayUserQuery` → `DisplayUserQueryHandler`

### 2. Flux d'exécution

```
┌─────────────────┐
│   Command/Query │
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│   CommandBus/QueryBus    │
└────────┬─────────────────┘
         │
         ▼
┌─────────────────────────┐
│  HandlerResolver         │
│  - Découvre le handler   │
│  - Récupère depuis      │
│    ServiceLocator       │
│  - Cache le callable    │
└────────┬─────────────────┘
         │
         ▼
┌─────────────────────────┐
│   Handler.handle()      │
└─────────────────────────┘
```

## Architecture

### Structure des classes

```
App\Application\Shared\CQRS\
├── Command\
│   ├── CommandHandlerResolverInterface.php    # Interface
│   └── CommandHandlerResolver.php             # Implémentation
└── Query\
    ├── QueryHandlerResolverInterface.php       # Interface
    └── QueryHandlerResolver.php                # Implémentation
```

## Configuration des services

### 1. Tagging automatique des handlers

Dans `config/services.yaml`, les handlers sont automatiquement tagués :

```yaml
# Tagger les command handlers
App\Application\User\UseCase\Command\:
    resource: "../application/src/User/UseCase/Command/**/*CommandHandler.php"
    tags: ["app.cqrs.command_handler"]

# Tagger les query handlers
App\Application\User\UseCase\Query\:
    resource: "../application/src/User/UseCase/Query/**/*QueryHandler.php"
    tags: ["app.cqrs.query_handler"]
```

**Résultat** : Tous les fichiers `*CommandHandler.php` et `*QueryHandler.php` dans ces dossiers sont automatiquement :

-   Enregistrés comme services
-   Tagués avec `app.cqrs.command_handler` ou `app.cqrs.query_handler`

### 2. Configuration des resolvers

Les resolvers sont configurés manuellement dans `services.yaml` pour éviter les dépendances au framework dans la couche Application :

```yaml
# CQRS - Configuration des resolvers avec injection manuelle des ServiceLocators
App\Application\Shared\CQRS\Command\CommandHandlerResolver:
    arguments:
        $handlerLocator: !tagged_locator { tag: "app.cqrs.command_handler" }

App\Application\Shared\CQRS\Query\QueryHandlerResolver:
    arguments:
        $handlerLocator: !tagged_locator { tag: "app.cqrs.query_handler" }
```

Le tag `!tagged_locator` crée automatiquement un `ServiceLocator` contenant tous les services tagués avec le tag spécifié.

### 3. Configuration des middlewares et Bus

Les middlewares sont également tagués et injectés dans les Bus :

```yaml
# CQRS - Configuration des middlewares
App\Application\Shared\CQRS\Middleware\CommandLoggingMiddleware:
    tags: ["app.cqrs.command_middleware"]

App\Application\Shared\CQRS\Middleware\QueryLoggingMiddleware:
    tags: ["app.cqrs.query_middleware"]

# CQRS - Configuration des Bus avec injection manuelle des middlewares
App\Application\Shared\CQRS\Command\CommandBus:
    arguments:
        $middlewares: !tagged_iterator { tag: "app.cqrs.command_middleware" }

App\Application\Shared\CQRS\Query\QueryBus:
    arguments:
        $middlewares: !tagged_iterator { tag: "app.cqrs.query_middleware" }
```

Le tag `!tagged_iterator` injecte automatiquement un itérable contenant tous les services tagués.

### 2. Injection via ServiceLocator

Le resolver reçoit un **ServiceLocator** contenant uniquement les handlers tagués, configuré manuellement dans `services.yaml` :

```php
public function __construct(
    private readonly ContainerInterface $handlerLocator,
) {}
```

La configuration dans `services.yaml` utilise `!tagged_locator` pour créer automatiquement un ServiceLocator :

```yaml
App\Application\Shared\CQRS\Command\CommandHandlerResolver:
    arguments:
        $handlerLocator: !tagged_locator { tag: "app.cqrs.command_handler" }
```

**Avantages du ServiceLocator** :

-   ✅ Contient **uniquement** les handlers (pas tout le container)
-   ✅ Peut accéder aux services **privés** (les handlers restent privés)
-   ✅ Implémente `Psr\Container\ContainerInterface` (standard PSR-11)
-   ✅ Pas de dépendance à Symfony dans l'Application layer (configuration dans services.yaml uniquement)

## Découverte automatique

### Algorithme de découverte

**Pour les Commands :**

```php
private function discoverHandlerClass(string $commandClass): string
{
    // 1. Vérifier que la classe se termine par "Command"
    if (!str_ends_with($commandClass, 'Command')) {
        throw new RuntimeException('Command must end with "Command"');
    }

    // 2. Remplacer "Command" par "CommandHandler"
    $handlerClass = preg_replace('/Command$/', 'CommandHandler', $commandClass);
    // RegisterUserCommand → RegisterUserCommandHandler

    // 3. Vérifier que la classe existe
    if (!class_exists($handlerClass)) {
        throw new RuntimeException("Handler class not found: {$handlerClass}");
    }

    return $handlerClass;
}
```

**Pour les Queries :**

```php
private function discoverHandlerClass(string $queryClass): string
{
    // 1. Vérifier que la classe se termine par "Query"
    if (!str_ends_with($queryClass, 'Query')) {
        throw new RuntimeException('Query must end with "Query"');
    }

    // 2. Remplacer "Query" par "QueryHandler"
    $handlerClass = preg_replace('/Query$/', 'QueryHandler', $queryClass);
    // DisplayUserQuery → DisplayUserQueryHandler

    // 3. Vérifier que la classe existe
    if (!class_exists($handlerClass)) {
        throw new RuntimeException("Handler class not found: {$handlerClass}");
    }

    return $handlerClass;
}
```

### Exemples de mapping

| Commande/Query                 | Handler découvert                     |
| ------------------------------ | ------------------------------------- |
| `RegisterUserCommand`          | `RegisterUserCommandHandler`          |
| `UpdatePasswordCommand`        | `UpdatePasswordCommandHandler`        |
| `DisplayUserQuery`             | `DisplayUserQueryHandler`             |
| `CheckPasswordResetTokenQuery` | `CheckPasswordResetTokenQueryHandler` |

## Cache des handlers

Le resolver met en cache les callables pour éviter de :

-   Redécouvrir le handler à chaque appel
-   Réinterroger le ServiceLocator
-   Recréer le callable

```php
private array $handlerCache = [];

// Premier appel : découverte + cache
$callable = $resolver->resolve($command);

// Appels suivants : retour direct depuis le cache
$callable = $resolver->resolve($command); // ⚡ Plus rapide
```

## Séparation des responsabilités

### Application Layer (indépendant de Symfony)

```php
// ✅ Utilise uniquement PSR-11 (standard)
use Psr\Container\ContainerInterface;

// ✅ Pas de dépendance à Symfony
// ✅ Pas d'attributs Symfony dans le code
// ✅ Peut être testé indépendamment
```

Le resolver reçoit simplement un `ContainerInterface` (PSR-11) sans connaître son implémentation. Aucun attribut Symfony n'est utilisé dans la couche Application.

### Infrastructure Layer (configuration Symfony)

```yaml
# Configuration dans services.yaml
# - Tagging des handlers
# - Création du ServiceLocator via !tagged_locator
# - Injection des middlewares via !tagged_iterator
```

Toute la configuration Symfony est centralisée dans `services.yaml`, ce qui maintient la couche Application complètement indépendante du framework.

## Avantages de cette approche

### 1. Auto-discovery

-   ✅ Pas besoin de mapper manuellement chaque commande → handler
-   ✅ Ajouter une commande = créer le handler, c'est tout
-   ✅ Convention claire et prévisible

### 2. Découplage

-   ✅ Application layer indépendant de Symfony
-   ✅ Utilise uniquement PSR-11 (standard)
-   ✅ Facilement testable

### 3. Performance

-   ✅ Cache des callables
-   ✅ ServiceLocator léger (contient uniquement les handlers)
-   ✅ Services privés (pas de pollution du container public)

### 4. Maintenabilité

-   ✅ Configuration centralisée dans `services.yaml`
-   ✅ Pas de dépendances au framework dans la couche Application
-   ✅ Code auto-documenté via la convention de nommage

## Exemple complet

### 1. Création d'une commande

```php
// RegisterUserCommand.php
final class RegisterUserCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email,
        public readonly string $username,
        public readonly string $plainPassword,
    ) {}
}
```

### 2. Création du handler

```php
// RegisterUserHandler.php
final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        // ... autres dépendances
    ) {}

    public function handle(RegisterUserCommand $command): RegisterUserOutput
    {
        // Logique métier
        $user = new User(...);
        $this->userRepository->save($user);
        return new RegisterUserOutput($user);
    }
}
```

### 3. Utilisation

```php
// Dans un Processor API Platform
final class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $command = new RegisterUserCommand(
            email: $data->email,
            username: $data->username,
            plainPassword: $data->plainPassword,
        );

        // Le resolver découvre automatiquement RegisterUserCommandHandler
        return $this->commandBus->dispatch($command);
    }
}
```

### 4. Flux d'exécution détaillé

```
1. RegisterUserProcessor crée RegisterUserCommand
   ↓
2. CommandBus.dispatch(RegisterUserCommand)
   ↓
3. CommandHandlerResolver.resolve(RegisterUserCommand)
   ↓
4. Découverte : RegisterUserCommand → RegisterUserCommandHandler
   ↓
5. ServiceLocator.get('RegisterUserCommandHandler')
   ↓
6. Création du callable et mise en cache
   ↓
7. Exécution : RegisterUserCommandHandler.handle(RegisterUserCommand)
   ↓
8. Retour du résultat
```

## Configuration requise

### services.yaml

```yaml
services:
    # Tagging automatique des handlers
    App\Application\User\UseCase\Command\:
        resource: "../application/src/User/UseCase/Command/**/*CommandHandler.php"
        tags: ["app.cqrs.command_handler"]

    App\Application\User\UseCase\Query\:
        resource: "../application/src/User/UseCase/Query/**/*QueryHandler.php"
        tags: ["app.cqrs.query_handler"]

    # Configuration des resolvers avec injection manuelle des ServiceLocators
    App\Application\Shared\CQRS\Command\CommandHandlerResolver:
        arguments:
            $handlerLocator: !tagged_locator { tag: "app.cqrs.command_handler" }

    App\Application\Shared\CQRS\Query\QueryHandlerResolver:
        arguments:
            $handlerLocator: !tagged_locator { tag: "app.cqrs.query_handler" }

    # Configuration des middlewares
    App\Application\Shared\CQRS\Middleware\CommandLoggingMiddleware:
        tags: ["app.cqrs.command_middleware"]

    App\Application\Shared\CQRS\Middleware\QueryLoggingMiddleware:
        tags: ["app.cqrs.query_middleware"]

    # Configuration des Bus avec injection manuelle des middlewares
    App\Application\Shared\CQRS\Command\CommandBus:
        arguments:
            $middlewares:
                !tagged_iterator { tag: "app.cqrs.command_middleware" }

    App\Application\Shared\CQRS\Query\QueryBus:
        arguments:
            $middlewares: !tagged_iterator { tag: "app.cqrs.query_middleware" }
```

### Classe du resolver

```php
use Psr\Container\ContainerInterface;

public function __construct(
    private readonly ContainerInterface $handlerLocator,
) {}
```

**Note importante** : La configuration utilise `!tagged_locator` et `!tagged_iterator` directement dans `services.yaml` pour éviter toute dépendance au framework dans la couche Application. Le resolver utilise uniquement l'interface standard PSR-11 `ContainerInterface`.

## Troubleshooting

### Erreur : "Handler not found"

**Cause** : Le handler n'est pas tagué ou n'existe pas.

**Solution** :

1. Vérifier que le handler est dans le bon dossier (`Command/` ou `Query/`)
2. Vérifier que le handler se termine par `CommandHandler` ou `QueryHandler`
3. Vérifier le tag dans `services.yaml`
4. Vider le cache : `php bin/console cache:clear`

### Erreur : "Handler class not found"

**Cause** : La convention de nommage n'est pas respectée.

**Solution** :

-   `RegisterUserCommand` doit avoir un handler `RegisterUserCommandHandler`
-   `DisplayUserQuery` doit avoir un handler `DisplayUserQueryHandler`
-   Vérifier que les deux classes existent
-   Vérifier les namespaces

### Handler non découvert

**Cause** : Le handler n'est pas dans le ServiceLocator.

**Solution** :

1. Vérifier les tags : `php bin/console debug:container --tag=app.cqrs.command_handler`
2. Vérifier que le handler est bien enregistré : `php bin/console debug:container RegisterUserCommandHandler`

## Conclusion

Le Handler Resolver offre une solution élégante pour :

-   ✅ Découvrir automatiquement les handlers via convention de nommage
-   ✅ Maintenir l'indépendance complète de l'Application layer (aucune dépendance à Symfony)
-   ✅ Utiliser des services privés via ServiceLocator
-   ✅ Configuration centralisée dans `services.yaml` (séparation claire des responsabilités)
-   ✅ Utilisation de standards PSR-11 uniquement dans la couche Application

Cette approche respecte les principes de **Clean Architecture** et de **CQRS** tout en restant simple et maintenable. La configuration manuelle dans `services.yaml` garantit qu'aucune dépendance au framework ne s'infiltre dans la couche Application, préservant ainsi l'indépendance et la testabilité du code métier.
