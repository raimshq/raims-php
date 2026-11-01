# Contract and maintenance

## Library source

The SDK consumes the public API's OpenAPI contract, distributed through
[raimshq/openapi](https://github.com/raimshq/openapi). The snapshot in
`contracts/openapi.json` contains only the published surface.
`contracts/source.json` records the immutable revision, source URL, SHA-256,
API version, and endpoint count.

The initial revision pins 67 endpoints and generates 77 response models. The
generator does not download files, call the API, or access credentials.

## Organization

| Directory or file | Responsibility |
| --- | --- |
| `src/RaimsClient.php` | Service composition and isolated configuration |
| `src/Service` | Named endpoint methods |
| `src/Generated/Contract.php` | Method, path, parameters, and responses for each endpoint |
| `src/EnumValues.php` | Constants generated from public enums |
| `src/Model` | Response classes and nested object types |
| `src/ApiRequestor.php` | Serialization, execution, read retries, and errors |
| `src/HttpClient` | Transport interface, cURL, and HTTP responses |
| `src/ApiResource.php` | Read-only resources and serialization |
| `src/Collection.php` | Cursor navigation |
| `src/ResponseValidator.php` | Structural response validation using generated metadata |
| `src/JsonNumberNormalizer.php` | Exact parsing of JSON integer representations before decoding |
| `src/RequestDiagnostics.php` | Metadata for an HTTP attempt, without parameters or credentials |
| `src/Exception` | Failure categories and metadata |
| `src/Webhook.php` | Delivery body authentication |
| `scripts/generate.php` | Deterministic offline generation |
| `scripts/reference.en.json` | English editorial text for the generated reference |
| `docs/reference.md` | Generated reference for the PHP surface |

`AbstractService.php` and `SimulationsService.php` are maintained manually.
Other services, models, constants, the endpoint catalog, and reference are
generated. Edit the method map in the generator when adding a resource;
generation rejects endpoints that are missing from or duplicated in this map.

Inputs are arrays documented with PHPDoc shapes; responses are named classes
with read-only PHPDoc properties. This provides editor suggestions without
duplicating all server validation. Collections propagate item types through
`foreach` and the iterator for all pages. The generator normalizes nullable
objects and rejects compositions it cannot represent.

The SDK validates configuration, transport limits, and the published response
structure. It requires mandatory fields and checks object, list, and scalar
types while preserving future fields and enums. The API continues to validate
formats, limits, permissions, and business rules.

## Update the contract

1. Choose an approved public revision and download the JSON from its immutable
   URL. Check that revision's manifest and checksum before replacing the snapshot.
2. Review structural and semantic changes: fields, statuses, idempotency,
   pagination, permissions, environment, and pending results. Update
   `contracts/source.json` with the verified source and hash.
3. Adjust the service map when needed and run `composer generate`. Review the
   generated classes, parameters, and reference. Update exact-source English
   translations in `scripts/reference.en.json` when reference text changes.
   Missing translations block generation; they never change the pinned contract.
4. Run `composer check` in the engineering workspace. It combines generated
   output checks, lint, static analysis, contract and transport scenarios, and
   packaging checks. Use the same preflight profile when preparing a delivery.
   Review every changed and new file.
5. Update usage guides and record compatibility changes before publishing a
   version. Incompatible changes require a major version.

Do not edit the snapshot to invent an endpoint or accept a different rule.
When the contract is wrong, fix the canonical API source and wait for its
publication. Generation checks the hash, but compatibility review requires
examining response semantics, including `202` and errors.

## PHP library compatibility

`contracts/php-api.json` records the library's symbol contract: classes,
interfaces, methods, parameters, returns, properties, constants, and documented
types. It is independent of OpenAPI and is not overwritten by HTTP generation.

Run `composer check-compatibility` to compare the current implementation with
this baseline. The check allows compatible additions and rejects removals or
changes that invalidate recorded usage. Documentation descriptions are not
compared; documented model and collection types are. Classes marked
`@internal` and the internal `Generated\Contract` catalog are excluded. The
value of `RaimsClient::VERSION` may change between releases; its presence and
type remain protected.

Complex type changes require explicit review; the check uses conservative
comparison for these cases. Additional optional parameters also require care
in methods that other classes may implement or override.

A deliberate baseline update uses `composer snapshot-api -- --write`. Review
the manifest diff and integration impact before accepting a change. Regenerating
OpenAPI or updating the baseline does not make an incompatible change safe for
consumers.

## Integration diagnostics

The consuming application defines its own availability, invalid-response, and
recovery alerts. Group failures and record only necessary metadata, such as
HTTP status, error code, and `Request-Id`. Do not send per-request alerts or
include keys, bodies, personal documents, or temporary URLs.

The SDK exposes connection, HTTP, and protocol exceptions for this purpose.
It does not send telemetry or external notifications. Recovery must be based
on new valid responses without automatically retrying a previous write.

## Publish a version

The `raims/raims-php` package uses repository release versions.
`packagist-release.yml` reacts to a published GitHub release and updates the
Packagist package after checking commit identity and approval. Pushes and tags
without a release do not start distribution.

1. Update the client version and review public changes and guides. The library
   version is independent of HTTP version `/v1`.
2. Validate the candidate commit in the five expected `Validate PHP SDK` jobs:
   PHP 8.2, 8.3, 8.4, 8.5, and security analysis, all on authorized AWS CodeBuild
   runners. Results for another SHA or an incomplete run do not approve
   distribution.
3. Promote the same commit to `main` through the preflight process and publish
   the authorized release with a `v<version>` tag, such as `v0.1.0`. The tag,
   code, and metadata must identify the same version.
4. Check the `packagist-release.yml` run and the version on
   [Packagist](https://packagist.org/packages/raims/raims-php). Acceptance of the
   update request does not prove that the version is available.
5. Perform a clean Composer installation, check the resolved version and
   revision, and validate the consumer using the canonical checks. Do not
   call the real API to verify installation. Keep validation files and
   dependencies in `.tmp`.

The distribution workflow does not create a release or replace earlier checks.
Keep publication credentials in the workflow's protected configuration;
never include them in recorded commands, files, or examples. A distribution
failure must remain visible until the destination is verified.

## Guides and support knowledge

The BR/EN `integracoes/sdk-php` guide in `raims-developer-docs` is the public
source for installation, requirements, backend usage, keys, permissions,
environment, pagination, idempotency, pending responses, and webhooks. First
distribution also requires a changelog entry with the actual availability date.

After confirming the package, publish the guides through the authorized Docs
process, regenerate catalogs, and validate retrieval of Portuguese and English
procedures through the canonical support checks. Check the catalog version in
the deployed consumer. The SDK uses the existing allowed
`raims-developer-docs` source; it requires neither another financial query tool
nor a context contract change. `Raims-Chat/docs/knowledge-sources.md` records
this responsibility.

Track this work privately, outside GitHub, until the package, published sources,
regenerated catalog, and validated guidance are confirmed. CI, releases,
Packagist, and guide publication each require their own authorization.
