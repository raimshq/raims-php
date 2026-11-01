# Contributing

Read the [maintenance guide](docs/maintenance.md) and the instructions in
`AGENTS.md`. Report reproducible issues without credentials, customer data,
or URLs that provide access to files or checkouts.

## Text and examples

Describe the SDK's features, contracts, and usage conditions directly. Do not
present other companies as design, architecture, or implementation references.
This also applies to comments, examples, component names, commits, pull requests,
and releases. Review changed and new files and their links before submitting
a contribution.

Preserve license notices, incorporated-code attribution, and names required
by actual dependencies and integrations. Third-party code must retain its
authorship and provenance.

## Validate changes

```text
composer validate --strict
composer dump-autoload --no-dev
composer check-generated
composer check-compatibility
composer check
```

`composer check` uses the canonical Developer Toolkit validator in the adjacent
checkout. It checks the contract, generation, syntax, types exposed to
integrators, regressions, and package contents. Physical test files are
materialized only in `.tmp`; the command does not call the real API.

On a workstation's first run, use `composer check -- --prepare-tools` to obtain
the pinned PHPStan version with checksum verification. Tools and results stay
in `.tmp`. Without explicit preparation, a missing tool or mismatched hash
blocks validation. `RAIMS_PYTHON` selects a Python compatible with the
engineering environment.

A standalone public clone does not contain the engineering Toolkit. In that
case, the command reports the missing dependency and does not claim a partial
check passed. Generation and lint commands remain available in the SDK source.

Run `php -l` on every PHP file, including the generator. Also check types and
examples with the PHP version used by the application. Dependencies and
validation files stay outside the package. In the Raims workspace, use only
`C:\server\raims\.tmp\raims-php-sdk` for suites, simulators, tools, and reports.
Do not include them in commits or distribution archives.

For transport or generator changes, validation must cover:

- Every pinned endpoint, including method, path, query, JSON, and success status.
- JSON in DELETE, POST without a body, `204`, `202`, and errors without an envelope.
- Null, omission, false, zero, empty maps, and future fields.
- Equivalent integer representations, large values, and fractions that must
  not be accepted through rounding.
- Per-request options, isolation between clients, and sensitive arguments.
- Read-only retries, stable idempotency, and `Retry-After`.
- Pagination in both directions, filters, empty pages, and cycles.
- Verified TLS, blocked redirects, and timeouts using local transport.
- Webhook signatures, rotation, clocks, Base62, and original body bytes.

Use fake transports and local receivers. Sandbox can send external
communications; do not test against the real API as part of library
validation. Stop temporary processes and check their ports before finishing.

## Review distribution

The check inspects the `composer archive` output and a Git archive equivalent
to the GitHub distribution ZIP. `archive.exclude` and `export-ignore` must
produce the same file set without changing the SDK checkout's Git index or
objects. All preparation happens in `.tmp`, without creating a commit.

The package must contain PHP source, Composer metadata, the license, and usage
guides. Contracts, the generator, tools, development dependencies, and evidence
do not need to be distributed to consumers.

`Validate PHP SDK` runs the canonical checks on pinned PHP 8.2, 8.3, 8.4, and 8.5
versions, with security analysis in a separate job. It runs for pushes from
this repository and manual executions on authorized CodeBuild runners. The
private Toolkit is pinned to a commit.

Before promoting a change to `main`, validate the commit on a candidate branch.
The preflight `Deploy` stage requires a successful run for the same SHA, with
all expected jobs completed. Promote that commit without changing its contents.
A canceled or incomplete run, or a run for a different commit, does not approve
publication.

Validation does not publish a release. After all five jobs pass for the same
commit and distribution is authorized, publishing a GitHub release triggers
`packagist-release.yml`, which updates the package on Packagist. Pushes and tags
without a release do not replace this step. Verify the version on Packagist
and a clean Composer installation before declaring distribution complete.
See the [maintenance guide](docs/maintenance.md#publish-a-version).

Do not add personal or GitHub-hosted runners to bypass checks. Publication
credentials do not belong in source, examples, or logs.

## Compatibility

Preserve the namespace, public signatures, and HTTP contract names. The
`contracts/php-api.json` manifest is checked by `composer check-compatibility`
and the full check. To update this baseline deliberately, use
`composer snapshot-api -- --write` and review the diff as described in the
[maintenance guide](docs/maintenance.md#php-library-compatibility).
Do not add write retries, silent idempotency key generation, or incompatible
domain validation. Document behavior and compatibility changes in release
notes. Internal refactors do not need to become integration guidance.
