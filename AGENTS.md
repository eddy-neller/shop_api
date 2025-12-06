# 🧭 Architecture & Repository Guidelines

---

## 1. Repository Guidelines

### 1.1. Project Structure & Module Organization

**Domain-driven layout :**

-   `domain/` – cœur métier :
    -   entités / agrégats (`Model/`),
    -   value objects (`ValueObject/`),
    -   domain events (`Event/`),
    -   exceptions métier (`Exception/`).
-   `application/` – cas d’usage & orchestration :
    -   CQRS (Commands/Queries + Handlers),
    -   Ports (interfaces vers l’extérieur),
    -   services applicatifs partagés.
-   `infrastructure/` – implémentations techniques :
    -   Doctrine (repositories, mappers),
    -   adapters Symfony / HTTP / FS / queue,
    -   implémentations des Ports Application.
-   `presentation/` – interface HTTP/API :
    -   ressources API Platform,
    -   DTOs HTTP,
    -   Processors / Providers,
    -   Presenters, validators, sécurité.
-   `src/` – bootstrap Symfony partagé (Kernel, config Symfony, bundles, etc.).

**HTTP/UI :**

-   `public/`, `templates/`, `translations/`, `resources/` pour :
    -   assets,
    -   templates éventuels,
    -   fichiers de traduction.

**Tests & tooling :**

-   `tests/` :
    -   reflète les bounded contexts / features (User, Shop, etc.).
-   `migrations/` :
    -   migrations Doctrine.
-   Docker & Make :
    -   `docker*/`, `docker-compose*.yml`,
    -   `Makefile`, `makefile.conf(.dist)`.

---

### 1.2. Build, Test, and Development Commands

Utiliser **`make`** pour éviter les lignes de commande trop longues (Docker = runtime par défaut) :

```bash
make install        # build images, containers, vendors, init DB dev+test
make up / down      # docker-compose up/down; down-hard pour prune images/volumes
make serve-start    # Symfony local server si non Docker
make serve-stop

make unit                       # full PHPUnit suite
make unit-filter f=ClassNameTest   # test ciblé
make unit-suite s=api.catalog      # suite ciblée
make unit-coverage             # HTML coverage dans coverage/

make stan           # PHPStan
make phpcs          # PHPCS
make phpcsfixer_dry # PHP-CS-Fixer en dry-run
```

---

### 1.3. Coding Style & Naming

-   PSR-12 via PHPCS / PHP-CS-Fixer :

    -   indentation 4 espaces,
    -   1 classe par fichier,
    -   types de retour explicites.

-   Naming :

    -   Classes / interfaces : `PascalCase`

        -   ex. `RegisterUserCommandHandler`, `DisplayUserQueryHandler`, `UserRepositoryInterface`.

    -   Propriétés / paramètres : `camelCase`.
    -   Clés d’env / config : `SNAKE_CASE`.

-   Avant commit :

    -   lancer `make phpcsfixer_dry`,
    -   ne pas committer `var/`, `coverage/`, cache, etc.

---

### 1.4. Testing Guidelines

-   Config PHPUnit : `phpunit.dist.xml`.
-   Tests dans `tests/.../*Test.php`, en miroir des bounded contexts / features.
-   Utiliser :

    -   `make unit-filter f=SomethingTest`,
    -   `make unit-suite s=...`,
    -   `make unit-coverage` pour les changements métier sensibles.

-   Base de données :

    -   DB de test dédiée, initialisée par `make install`,
    -   ne **jamais** réutiliser la DB de dev pour les tests.

### 1.7. Rappels pour les imports PHP

-   Quand un fichier change de namespace ou de dossier, **ajoute/ajuste les imports `use`** plutôt que d’utiliser des classes pleinement qualifiées dans le code (évite les `new \App\...` en plein corps).
-   Vérifie le haut de fichier après un move/rename pour conserver la lisibilité (`use App\Domain\User\Identity\ValueObject\Username;` plutôt que `\App\Domain\User\Identity\ValueObject\Username` inline).
-   **Ne pas ajouter de tests dans les dossiers exclus de `phpunit.dist.xml`** (`<exclude>`). Place les nouveaux tests dans les suites existantes (cf. sections `<testsuite>`).

---

### 1.5. Commits & Pull Requests

**Commits :**

-   Sujet court, impératif (≤ 70 chars) :

    -   ex. `Add CQRS handler for user registration`.

-   Body pour :

    -   contexte,
    -   breaking changes,
    -   décisions d’architecture.

**Pull Requests :**

-   Décrire clairement :

    -   **scope** (ce qui est inclus),
    -   **risque / impact** (tech + métier),
    -   **tests réalisés**.

-   Lier les issues/tickets.
-   Ajouter des screenshots / extraits d’API si :

    -   la Presentation change,
    -   les contrats publics (DTO/API) changent.

Avant d’ouvrir une PR, exécuter au minimum :

-   `make stan`
-   `make phpcs`
-   `make unit` (ou suite ciblée)
-   Documenter tout check volontairement ignoré.

---

### 1.6. Security & Configuration

-   Ne jamais committer de secrets :

    -   utiliser `.env.local*`, `makefile.conf`, secrets CI.
    -   `.env.test` = valeurs par défaut spécifiques aux tests.

-   Quand les ports / services Docker changent :

    -   mettre à jour **à la fois** :

        -   `makefile.conf`
        -   `docker-compose*.yml`

    -   pour garder les environnements alignés (local, CI, prod).

---

## 2. Clean Architecture – Vue d’ensemble

### 2.1. Dépendances autorisées

```text
Presentation  →  Application  →  Domain
                    ↓
                 Ports (interfaces)
                    ↑
             Infrastructure (adapters)
```

**Règles d’or :**

-   `domain/` :

    -   ✅ logique métier pure (entities, VOs, events, exceptions),
    -   ❌ aucune dépendance vers Application / Infra / Presentation,
    -   ❌ aucun framework (Symfony, Doctrine, API Platform, Ramsey, …).

-   `application/` :

    -   ✅ dépend de Domain + Ports,
    -   ❌ ne dépend pas de Presentation / Infrastructure,
    -   ❌ n’utilise pas directement Symfony/Doctrine/API Platform.

-   `infrastructure/` :

    -   ✅ implémente les Ports,
    -   ✅ dépend de Domain + frameworks,
    -   ❌ ne dépend pas de Presentation.

-   `presentation/` :

    -   ✅ expose l’API (API Platform, contrôleurs, DTOs HTTP),
    -   ✅ parle à Application **via les Buses CQRS** + DTOs (Commands/Queries/Outputs),
    -   ❌ ne parle jamais directement aux handlers ou repos Infra.

---

## 3. Domain Layer – DDD & Règles Métier

> **But** : cœur métier pur, sans aucun détail technique.

### 3.1. Périmètre

La couche Domain contient :

-   Entités / Agrégats (`Model/`),
-   Value Objects (`ValueObject/`),
-   Domain Events (`Event/`),
-   Exceptions métier (`Exception/`).

Organisation par bounded context :

```text
domain/
├── User/
│   ├── src/Model/
│   ├── src/ValueObject/
│   ├── src/Event/
│   └── src/Exception/
├── Shop/
└── SharedKernel/
    └── src/Event/   # DomainEventInterface, DomainEventTrait, …
```

### 3.2. Règles clés

-   Domain utilise uniquement :

    -   PHP natif, SPL (`DateTimeImmutable`, exceptions standard, etc.),
    -   éventuellement `SharedKernel` (events).

-   Domain **ne dépend jamais** de :

    -   `App\Application\*`,
    -   `App\Infrastructure\*`,
    -   `App\Presentation\*`,
    -   Symfony, Doctrine, API Platform, Ramsey, HTTP.

### 3.3. Entités & Agrégats

-   Aggregate Root :

    -   encapsule l’état métier,
    -   expose des **méthodes métier** (pas de `setXxx()` publics),
    -   ne contient pas de code technique.

-   Constructeur :

    -   privé ou protégé,
    -   création via factory methods : `create()`, `register()`, `place()`, `reconstitute()`.

-   Modifs d’état :

    -   toujours via méthodes métier (`activate`, `cancel`, `changeEmail`, `addItem`, …),
    -   qui gèrent :

        -   invariants,
        -   `updatedAt`,
        -   Domain Events.

### 3.4. Value Objects

-   `final`, propriétés `private` (souvent `readonly`).
-   Aucune mutation → immuables.
-   Validation métier dans le constructeur / factory (`fromString`, `fromInt`, …).
-   Comparaison par valeur avec `equals(self $other): bool`.
-   Utilisation :

    -   emails, montants, quantités, statuts, préférences, langues, tokens, limites, etc.
    -   ne pas laisser passer des `string`/`int` bruts pour ces concepts.

### 3.5. Domain Events

-   Représentent des faits métier :

    -   `OrderPlaced`, `OrderCancelled`, `UserRegistered`, …

-   Règles :

    -   vivent dans `domain/<Context>/src/Event/`,
    -   implémentent `DomainEventInterface` du SharedKernel,
    -   peuvent utiliser `DomainEventTrait` pour `occurredOn`.

-   L’Aggregate Root :

    -   enregistre les events (`recordEvent()`),
    -   les expose (`releaseEvents()`).

### 3.6. Exceptions métier

-   Base par bounded context :

    -   `UserDomainException`, `OrderDomainException`, …

-   Exceptions ciblées :

    -   `ActivationLimitReachedException`, `InsufficientStockException`, etc.

-   Messages métier, pas techniques.

### 3.7. Temps & timestamps

-   Domain ne fait **jamais** `new \DateTimeImmutable()` en dur.
-   Les méthodes métier reçoivent toujours `DateTimeImmutable $now`.
-   `createdAt` :

    -   défini dans les factory methods,
    -   **immuable** (pas de `setCreatedAt()`).

-   `updatedAt` :

    -   mis à jour dans chaque méthode métier qui modifie l’état,
    -   via un setter privé (`setUpdatedAt()`).

### 3.8. Testabilité Domain

-   Tests unitaires purs :

    -   pas de kernel,
    -   pas de DB,
    -   pas de services Symfony.

-   On :

    -   crée des VOs/Aggregates,
    -   appelle les méthodes métier,
    -   vérifie l’état, les events, les exceptions.

### 3.9. Checklist Domain

Avant de valider du code Domain :

-   [ ] Aucun `use App\Application\*`, `App\Infrastructure\*`, `App\Presentation\*`.
-   [ ] Aucun import Symfony/Doctrine/API Platform/HTTP/Ramsey.
-   [ ] Les agrégats sont créés via des factory methods (`create`, `register`, `place`, `reconstitute`).
-   [ ] Les Value Objects sont immuables et valident leurs invariants.
-   [ ] Toute méthode métier sensible reçoit un `DateTimeImmutable $now`.
-   [ ] `createdAt` immuable, `updatedAt` mis à jour explicitement.
-   [ ] Aucun `setXxx()` public sur les agrégats.
-   [ ] Les Domain Events existent pour les changements importants.
-   [ ] Les tests Domain tournent sans framework.

---

## 4. Application Layer – Use Cases & Ports

> **But** : orchestrer les cas d’usage, sans détails techniques.

### 4.1. Rôle & dépendances

-   Contient :

    -   Commands / Queries,
    -   Handlers,
    -   Ports (interfaces),
    -   services applicatifs partagés (Clock, Transaction, etc.).

-   Peut dépendre de :

    -   Domain,
    -   Ports (`application/.../Port`).

-   Ne doit pas dépendre de :

    -   Presentation,
    -   Infrastructure,
    -   Symfony / Doctrine / API Platform.

### 4.2. Ports (interfaces)

**Shared Ports (`Application/Shared/Port/`) :**

-   `ClockInterface` – abstraction du temps (`now()`).
-   `ConfigInterface` – lecture de configuration.
-   `TransactionalInterface` – exécution atomique de blocs.
-   `FileInterface` – abstraction de fichier (pas d’UploadedFile Symfony).
-   `EventDispatcherInterface` – publication d’événements.
-   `UuidGeneratorInterface` – génération d’UUID.

**Ports métiers (ex. User) (`Application/User/Port/`) :**

-   `UserRepositoryInterface`
-   `PasswordHasherInterface`
-   `TokenProviderInterface`
-   `AvatarUploaderInterface`
-   etc.

**Règle :**

> Toute dépendance externe (DB, HTTP client, FS, queue…)
> → un Port dans `application/.../Port`, implémenté dans `infrastructure/...`.

### 4.3. CQRS en Application

Organisation :

-   `UseCase/Command/...` :

    -   `*Command`,
    -   `*CommandHandler`.

-   `UseCase/Query/...` :

    -   `*Query`,
    -   `*QueryHandler`.

Conventions :

-   `SomethingCommand` → `SomethingCommandHandler`.
-   `SomethingQuery` → `SomethingQueryHandler`.
-   Handler :

    -   une seule méthode publique `handle(SomethingCommand|SomethingQuery $message)`.

Les buses & resolvers sont dans `Application/Shared/CQRS/` :

-   indépendants des frameworks (PSR-11, PSR-3).
-   `CommandHandlerResolver` :

    -   déduit le handler par convention (`FooCommand` → `FooCommandHandler`),
    -   utilise un container PSR-11,
    -   met en cache les callables.

-   `QueryHandlerResolver` :

    -   déduit le handler par convention (`FooQuery` → `FooQueryHandler`),
    -   utilise un container PSR-11,
    -   met en cache les callables.

### 4.4. Handlers – Règles

**Command Handlers :**

-   Orchestration d’écriture :

    -   charger des agrégats via les repositories,
    -   appeler les méthodes métier Domain,
    -   persister / publier les events via les Ports.

-   Utilisent uniquement :

    -   Domain,
    -   Ports (`UserRepositoryInterface`, `ClockInterface`, etc.),
    -   `TransactionalInterface` pour les transactions.

-   Ne renvoient que :

    -   DTOs d’output / read models,
    -   ou `void`.

-   **Jamais** :

    -   d’entités Doctrine,
    -   d’objets framework.

**Query Handlers :**

-   Lecture seule (pas d’effets de bord),
-   Utilisent :

    -   read models,
    -   repositories de lecture,
    -   ports dédiés.

-   Renvoient :

    -   DTOs de lecture,
    -   collections typées.

### 4.5. Gestion du temps (ClockInterface)

-   Ne jamais faire `new \DateTimeImmutable()` dans Application.
-   Toujours :

    -   injecter `ClockInterface`,
    -   utiliser `$this->clock->now()`,
    -   passer `$now` au Domain.

### 4.6. Testabilité Application

-   Chaque handler :

    -   dépend d’interfaces (Ports),
    -   est testable avec des mocks `UserRepositoryInterface`, `ClockInterface`, etc.

-   Aucun attribute/annotation framework dans Application :

    -   pas de `#[AsMessageHandler]`, `#[AutowireIterator]`, etc.

-   Wiring → uniquement dans Infrastructure.

### 4.7. Checklist Application

Avant d’ajouter/modifier un use case :

-   [ ] Le code est dans `application/.../UseCase/Command|Query`.
-   [ ] Le DTO s’appelle `...Command` ou `...Query`.
-   [ ] Le handler s’appelle `...CommandHandler` ou `...QueryHandler` et expose `handle()`.
-   [ ] Le handler dépend uniquement de Ports + Domain.
-   [ ] Le temps est géré via `ClockInterface`.
-   [ ] Les tests mockent les Ports et tournent sans kernel.

---

## 5. CQRS – Règles Globales (Application + Presentation)

### 5.1. Buses

-   Tout cas d’usage passe par :

    -   `CommandBusInterface` pour les écritures,
    -   `QueryBusInterface` pour les lectures.

-   Interdit :

    -   Presentation ne doit jamais injecter directement un handler,
    -   aucun code hors Application ne doit appeler `handle()`.

> Mantra : **“toujours via le Bus, jamais via le Handler”**.

### 5.2. Découverte automatique

-   Conventions :

    -   `FooCommand` → `FooCommandHandler`,
    -   `BarQuery` → `BarQueryHandler`.

-   Les resolvers (`CommandHandlerResolver` et `QueryHandlerResolver`) :

    -   appliquent ces conventions,
    -   résolvent via PSR-11,
    -   mettent en cache.

Aucun mapping manuel Command → Handler ailleurs.

### 5.3. Middlewares CQRS

-   Middlewares dans `Application/Shared/CQRS/Middleware/`.
-   Rôles :

    -   logging (PSR-3),
    -   metrics,
    -   validation croisée, etc.

-   Pas de logique métier, uniquement cross-cutting.
-   Ordre / activation câblés dans `services.yaml` (Infrastructure) via `!tagged_iterator`.

### 5.4. Checklist CQRS

-   [ ] Nouveau use case → Command/Query + Handler, pas de contrôleur “gros”.
-   [ ] Presentation utilise **uniquement** les Buses.
-   [ ] Aucun handler accessible directement depuis Presentation/Infra.
-   [ ] Eventuels tests pour vérifier que chaque Command/Query a un Handler associé (recommandé).

---

## 6. Infrastructure Layer – Adapters & Frameworks

> **But** : implémenter les Ports, encapsuler les frameworks.

### 6.1. Rôle

-   Implémenter **tous les Ports** Application :

    -   repos, hashers, file storage, email, queues, etc.

-   Encapsuler :

    -   Doctrine (ORM, migrations),
    -   Symfony (services, events, console),
    -   Vich (upload),
    -   Ramsey (UUID),
    -   HTTP clients, queues, FS, etc.

-   Exposer au reste :

    -   `SystemClock` (ClockInterface),
    -   `DoctrineTransactional` (TransactionalInterface),
    -   `SymfonyEventDispatcherAdapter` (EventDispatcherInterface),
    -   etc.

### 6.2. Dépendances

Infrastructure peut dépendre de :

-   `App\Application\...Port\...Interface` (Ports seulement),
-   `App\Domain\...` (agrégats, VOs, events),
-   frameworks & libs externes.

Infrastructure ne doit pas dépendre de :

-   `App\Presentation\*`.

### 6.3. Ports → Implémentations

Exemples :

-   `ClockInterface` → `SystemClock`
-   `ConfigInterface` → `ParameterBagConfig`
-   `TransactionalInterface` → `DoctrineTransactional`
-   `FileInterface` → `SymfonyFileAdapter`
-   `EventDispatcherInterface` → `SymfonyEventDispatcherAdapter`
-   `UuidGeneratorInterface` → `RamseyUuidGenerator`

Ports métier :

-   `UserRepositoryInterface` → `DoctrineUserRepository`
-   `PasswordHasherInterface` → `SymfonyPasswordHasherAdapter`
-   `TokenProviderInterface` → `RandomTokenProvider`
-   `AvatarUploaderInterface` → `VichAvatarUploader`

**Règle :**

> Interface dans `application/…/Port`
> Implémentation + dépendances framework dans `infrastructure/...`
> Binding dans `config/services.yaml`.

### 6.4. Mapping Domain ↔ Persistence

-   Entités Doctrine ≠ entités Domain.
-   Utiliser des mappers dédiés :

    -   `UserMapper::toDomain(DoctrineUser $entity): DomainUser`,
    -   `UserMapper::toDoctrine(DomainUser $user, ?DoctrineUser $entity): DoctrineUser`.

-   Le mapper :

    -   consomme des VOs Domain,
    -   appelle `DomainUser::reconstitute()` pour reconstruire l’agrégat sans events,
    -   préserve les timestamps Domain.

### 6.5. Gestion du temps

-   `SystemClock` implémente `ClockInterface` :

```yaml
# config/services.yaml
services:
    App\Application\Shared\Port\ClockInterface:
        alias: App\Infrastructure\Service\SystemClock
```

### 6.6. Checklist Infrastructure

-   [ ] Chaque Port Application a une implémentation claire.
-   [ ] Les implémentations vivent dans `infrastructure/...`, pas ailleurs.
-   [ ] Le mapping Domain ↔ Doctrine est géré par des mappers dédiés.
-   [ ] Aucun code Infra ne dépend de `presentation/`.
-   [ ] Tous les bindings Ports → Implémentations sont dans `services.yaml`.

---

## 7. Presentation Layer – API / HTTP

> **But** : exposer l’API, valider, sécuriser et transformer les données.

### 7.1. Rôle & dépendances

-   Gère :

    -   ressources API Platform,
    -   DTOs d’entrée (Input),
    -   Processors / Providers,
    -   Presenters, validators, sécurité.

-   Peut dépendre de :

    -   `CommandBusInterface`, `QueryBusInterface`,
    -   DTOs Application (Commands/Queries/Outputs),
    -   Domain pour quelques VOs (ex. `UserId`) ou modèles Domain dans les Presenters,
    -   Symfony (validation, sécurité, sérialisation),
    -   API Platform.

Ne doit pas dépendre de :

-   Repositories Doctrine,
-   Services `infrastructure/*` (hashers, FS, etc.),
-   Implémentations concrètes des Ports.

### 7.2. Flux typique

**Écriture :**

```text
HTTP Request
   ↓
Input DTO (Presentation)
   ↓
Processor
   ↓
Command (Application)
   ↓
CommandBusInterface
   ↓
Handler (Application)
   ↓
Domain / Ports
   ↓
Output/void
   ↓
(éventuelle transformation → Resource/API)
```

**Lecture :**

```text
HTTP Request
   ↓
Provider
   ↓
Query (Application)
   ↓
QueryBusInterface
   ↓
Handler (Application)
   ↓
Read model / Domain
   ↓
Presenter
   ↓
Resource/API
```

### 7.3. Structure recommandée

```text
Presentation/
├── User/
│   ├── ApiResource/   # Endpoints API Platform
│   ├── Dto/           # DTOs d'entrée (Input + validation)
│   ├── State/         # Processors / Providers (CQRS côté API)
│   ├── Presenter/     # Domain/Output → Resource
│   ├── Security/      # Traits & helpers de sécurité
│   └── Validator/     # Validateurs personnalisés Symfony
└── Shared/
    ├── Adapter/       # SymfonyFileAdapter → FileInterface
    └── State/         # Providers/Processors génériques
```

### 7.4. CQRS côté Presentation

-   **Processors** (POST/PUT/PATCH/DELETE) :

    -   Input DTO → Command,
    -   `CommandBusInterface` → Output/void.

-   **Providers** (GET/collection) :

    -   Query → `QueryBusInterface` → Output/read model,
    -   Presenter → Resource exposée.

Presentation ne crée ni n’injecte de Handlers.

### 7.5. Validation & Sécurité

-   Validation :

    -   dans les DTOs Presentation (`Assert\*`, validators custom),
    -   côté HTTP uniquement (pas de logique métier).

-   Sécurité :

    -   conditions `security` / `security_post_denormalize` API Platform,
    -   `Security` Symfony dans les Processors/Providers si besoin,
    -   traits réutilisables (`UserMeSecurityTrait`, etc.).

### 7.6. Adapters

-   Objets framework (ex. `UploadedFile`) sont adaptés à la frontière :

    -   `SymfonyFileAdapter` → `FileInterface`.

-   Application ne voit que l’interface `FileInterface`.

### 7.7. Checklist Presentation

-   [ ] Aucune dépendance vers les repos / services d’`infrastructure/`.
-   [ ] Communication avec Application uniquement via `CommandBusInterface` / `QueryBusInterface`.
-   [ ] Input HTTP → Input DTO → Command/Query – pas de Domain direct dans les endpoints.
-   [ ] Output Application/Domain → Presenter → Resource API.
-   [ ] Validation & sécurité gérées ici, pas dans Application/Infra.

---

## 8. Quick Global Checklist – Nouvelle Feature

Avant de merger une nouvelle feature :

1. **Structure & couches**

    - [ ] Le code est au bon endroit (Domain vs Application vs Infrastructure vs Presentation).
    - [ ] Les dépendances respectent le diagramme de couches.

2. **Domain**

    - [ ] Logique métier dans Domain (pas dans Application/Infra/Presentation).
    - [ ] VOs immuables, agrégats encapsulés, timestamps gérés proprement.

3. **Application**

    - [ ] Use cases modélisés via Command/Query + Handler.
    - [ ] Handlers n’utilisent que Domain + Ports.
    - [ ] Temps via `ClockInterface`.

4. **Infrastructure**

    - [ ] Tous les Ports utilisés ont une implémentation Infrastructure.
    - [ ] Mapping Domain ↔ Persistence géré par des mappers dédiés.

5. **Presentation**

    - [ ] Utilisation exclusive des Buses CQRS.
    - [ ] Validation & sécurité cohérentes.
    - [ ] Aucun accès direct aux repos / services Infra.

6. **Qualité**

    - [ ] `make stan` OK.
    - [ ] `make phpcs` OK.
    - [ ] `make unit` (ou suites ciblées) OK.
    - [ ] Doc / commentaires à jour pour les cas d’usage et endpoints modifiés.
