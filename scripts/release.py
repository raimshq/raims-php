#!/usr/bin/env python3
"""Bind approved PHP source exports to a release and refresh the existing Packagist package."""

import argparse
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path
import re
import io
import time
import subprocess
import sys
import urllib.error
import urllib.parse
import urllib.request
import zipfile


REPOSITORY = "raimshq/raims-php"
PACKAGE_ID = "raims/raims-php"
PACKAGIST_UPDATE = "https://packagist.org/api/update-package"
PACKAGE_METADATA = "https://repo.packagist.org/p2/raims/raims-php.json"
REPOSITORY_URL = "https://github.com/" + REPOSITORY
VERSION = re.compile(r"(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)(?:-[0-9A-Za-z]+(?:[.-][0-9A-Za-z]+)*)?")
SHA = re.compile(r"[0-9a-f]{40}")
VALIDATION_WORKFLOW = ".github/workflows/validate.yml"
VALIDATION_JOBS = {
    "validatePhp (8.2)": {"php-sdk-check-8.2"},
    "validatePhp (8.3)": {"php-sdk-check-8.3"},
    "validatePhp (8.4)": {"php-sdk-check-8.4"},
    "validatePhp (8.5)": {"php-sdk-check-8.5"},
    "securityLinux": {"php-sdk-security"},
}

sys.dont_write_bytecode = True


def require(condition, message):
    if not condition:
        raise RuntimeError(message)


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def git(source, *args):
    process = subprocess.run(["git", "-C", str(source), *args], capture_output=True, text=True)
    require(process.returncode == 0, "The release source could not be verified with Git.")
    return process.stdout.strip()


def timestamp(value):
    require(isinstance(value, str) and value.endswith("Z"), "A release or validation timestamp is missing.")
    try:
        parsed = datetime.fromisoformat(value[:-1] + "+00:00")
    except ValueError:
        raise RuntimeError("A release or validation timestamp is invalid.") from None
    require(parsed.tzinfo is not None, "Validation timestamps must identify UTC.")
    return parsed.astimezone(timezone.utc)


def positive_id(value):
    require(type(value) in (int, str) and re.fullmatch(r"[1-9][0-9]*", str(value)), "The validation run identity is invalid.")
    return str(value)


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, message, headers, new_url):
        return None


def github_api(path):
    require(path.startswith("/") and not path.startswith("//"), "Invalid GitHub API request.")
    token = os.environ.get("GITHUB_TOKEN")
    require(token, "The read-only GitHub Actions token is unavailable.")
    request = urllib.request.Request("https://api.github.com/repos/" + REPOSITORY + path, headers={
        "Authorization": "Bearer " + token, "Accept": "application/vnd.github+json",
        "X-GitHub-Api-Version": "2026-03-10", "User-Agent": "RaimsSDKRelease",
    })
    try:
        with urllib.request.build_opener(NoRedirect()).open(request, timeout=20) as response:
            raw = response.read(2_000_001)
        require(len(raw) <= 2_000_000, "The GitHub validation response exceeds its limit.")
        result = json.loads(raw.decode("utf-8"))
    except (urllib.error.URLError, TimeoutError, UnicodeError, ValueError):
        raise RuntimeError("The prior validation could not be read from GitHub. Publication remains blocked.") from None
    require(isinstance(result, dict), "The GitHub validation response is invalid.")
    return result


def validation_workflow():
    workflow = github_api("/actions/workflows/validate.yml")
    require(type(workflow.get("id")) is int and workflow["id"] > 0, "The validation workflow identity is missing.")
    require(workflow.get("path") == VALIDATION_WORKFLOW and workflow.get("state") == "active", "The required validation workflow is not active.")
    return workflow["id"]


def attempt_jobs(run_id, attempt):
    result = []
    total = None
    for page in range(1, 21):
        response = github_api("/actions/runs/" + run_id + "/attempts/" + attempt + "/jobs?" + urllib.parse.urlencode({"per_page": 100, "page": page}))
        rows = response.get("jobs")
        require(isinstance(rows, list) and type(response.get("total_count")) is int, "The validation jobs response is incomplete.")
        if total is None:
            total = response["total_count"]
        require(total == response["total_count"] and total >= 0, "The validation job count changed during verification.")
        result.extend(rows)
        if len(result) >= total:
            require(len(result) == total, "The validation jobs response has an unexpected count.")
            return result
        require(len(rows) == 100, "The validation jobs response was truncated.")
    raise RuntimeError("The validation job history exceeds its verification limit.")


def verify_validation_run(run_id, attempt, workflow_id, revision, published_at):
    run_id, attempt = positive_id(run_id), positive_id(attempt)
    run = github_api("/actions/runs/" + run_id + "/attempts/" + attempt)
    require(type(run.get("id")) is int and str(run["id"]) == run_id and str(run.get("run_attempt")) == attempt, "The prior validation run or attempt differs from the requested proof.")
    require(run.get("workflow_id") == workflow_id and run.get("path") == VALIDATION_WORKFLOW, "The prior run belongs to another workflow.")
    require(isinstance(run.get("repository"), dict) and isinstance(run.get("head_repository"), dict) and
            run["repository"].get("full_name") == REPOSITORY and run["head_repository"].get("full_name") == REPOSITORY, "The prior validation did not run against this repository.")
    require(run.get("event") in ("push", "workflow_dispatch"), "Only trusted push or manual validation runs can authorize a release.")
    require(run.get("head_sha") == revision, "The prior validation checked another commit.")
    require(run.get("status") == "completed" and run.get("conclusion") == "success", "The prior validation did not complete successfully.")
    created, started, completed = (timestamp(run.get(key)) for key in ("created_at", "run_started_at", "updated_at"))
    require(created <= started <= completed < published_at, "The validation must finish before the GitHub release is published.")
    jobs = attempt_jobs(run_id, attempt)
    names, identifiers = set(), set()
    route = "codebuild-raims-github-actions-" + run_id + "-" + attempt
    for job in jobs:
        require(isinstance(job, dict) and type(job.get("id")) is int and job["id"] > 0 and job["id"] not in identifiers, "The validation jobs contain a missing or duplicated identity.")
        identifiers.add(job["id"])
        name = job.get("name")
        require(isinstance(name, str) and name and name not in names, "The validation job names are missing or duplicated.")
        names.add(name)
        require(job.get("run_id") == int(run_id) and job.get("head_sha") == revision, "A validation job belongs to another run or commit.")
        require(job.get("status") == "completed" and job.get("conclusion") == "success", "Every validation job must complete with success; skipped jobs cannot authorize publication.")
        job_started, job_completed = timestamp(job.get("started_at")), timestamp(job.get("completed_at"))
        require(started <= job_started <= job_completed <= completed and job_completed < published_at, "A validation job did not finish before the release.")
        labels = job.get("labels")
        require(isinstance(labels, list) and all(isinstance(value, str) for value in labels) and route in labels, "A validation job did not use the required CodeBuild route.")
        require(type(job.get("runner_id")) is int and job["runner_id"] > 0 and isinstance(job.get("runner_name"), str) and job["runner_name"], "The validation runner identity is missing.")
        if name in VALIDATION_JOBS:
            require(VALIDATION_JOBS[name].issubset(labels), "A required validation job used an unexpected runner profile.")
    require(set(VALIDATION_JOBS).issubset(names), "A required PHP runtime or security validation job is missing.")
    return {"validationRunId": run_id, "validationRunAttempt": attempt, "validationWorkflowId": workflow_id,
            "validationCompletedAt": completed.isoformat().replace("+00:00", "Z")}


def prior_validation(revision, published_at, run_id=None, attempt=None):
    workflow_id = validation_workflow()
    require((run_id is None) == (attempt is None), "Provide both the validation run ID and attempt.")
    if run_id is not None:
        return verify_validation_run(run_id, attempt, workflow_id, revision, published_at)
    for page in range(1, 11):
        response = github_api("/actions/workflows/" + str(workflow_id) + "/runs?" +
                              urllib.parse.urlencode({"head_sha": revision, "status": "completed", "per_page": 100, "page": page}))
        rows = response.get("workflow_runs")
        require(isinstance(rows, list), "The prior validation history is unavailable.")
        for run in rows:
            if not isinstance(run, dict) or run.get("head_sha") != revision or run.get("conclusion") != "success":
                continue
            try:
                return verify_validation_run(run.get("id"), run.get("run_attempt"), workflow_id, revision, published_at)
            except RuntimeError as error:
                if "could not be read from GitHub" in str(error):
                    raise
        if len(rows) < 100:
            break
    raise RuntimeError("No successful PHP matrix and security validation for this commit finished before the release.")


def identity(source, validation_run_id=None, validation_run_attempt=None):
    require(os.environ.get("GITHUB_REPOSITORY") == REPOSITORY, "Unexpected release repository.")
    require(os.environ.get("GITHUB_EVENT_NAME") == "release", "Only published releases can publish a package.")
    event = json.loads(Path(os.environ["GITHUB_EVENT_PATH"]).read_text(encoding="utf-8"))
    release = event.get("release", {})
    require(event.get("action") == "published" and release.get("draft") is False, "The release has not been published.")
    require(event.get("repository", {}).get("full_name") == REPOSITORY, "The event repository differs from the workflow.")
    tag = release.get("tag_name", "")
    require(tag.startswith("v") and VERSION.fullmatch(tag[1:]), "Use a versioned release tag such as v0.1.0.")
    revision = os.environ.get("GITHUB_SHA", "")
    require(SHA.fullmatch(revision), "The release revision must be an immutable commit SHA.")
    require(git(source, "rev-parse", "HEAD") == revision, "The checkout differs from the release commit.")
    require(git(source, "rev-parse", "refs/tags/" + tag + "^{commit}") == revision, "The release tag differs from the event commit.")
    require(git(source, "status", "--porcelain") == "", "The release checkout contains local source changes.")
    ancestry = subprocess.run(["git", "-C", str(source), "merge-base", "--is-ancestor", revision, "origin/main"], capture_output=True)
    require(ancestry.returncode == 0, "The release commit must belong to main.")
    metadata = json.loads((source / "composer.json").read_text(encoding="utf-8"))
    require(metadata.get("name") == PACKAGE_ID and "version" not in metadata, "The Composer package identity or version policy is invalid.")
    client = (source / "src/RaimsClient.php").read_text(encoding="utf-8-sig")
    match = re.search(r"public const VERSION = '([^']+)';", client)
    version = tag[1:]
    require(match is not None and match.group(1) == version, "The public client version differs from the release tag.")
    toolkit = os.environ.get("RAIMS_PHP_TOOLKIT_REF", "")
    require(SHA.fullmatch(toolkit), "Pin the reviewed Developer Toolkit commit before publishing this workflow.")
    result = {"repository": REPOSITORY, "packageId": PACKAGE_ID, "version": version,
              "tag": tag, "revision": revision, "toolkitRevision": toolkit}
    for field, variable in (("runId", "GITHUB_RUN_ID"), ("runAttempt", "GITHUB_RUN_ATTEMPT")):
        value = os.environ.get(variable, "")
        require(re.fullmatch(r"[1-9][0-9]*", value), "The workflow run identity is missing.")
        result[field] = value
    require(type(release.get("id")) is int and release["id"] > 0, "The GitHub release identity is missing.")
    result["releaseId"] = release["id"]
    published_at = timestamp(release.get("published_at"))
    published = github_api("/releases/" + str(release["id"]))
    require(published.get("id") == release["id"] and published.get("tag_name") == tag and published.get("draft") is False and
            published.get("published_at") == release.get("published_at"), "The release event differs from its published GitHub identity.")
    result["releasePublishedAt"] = published_at.isoformat().replace("+00:00", "Z")
    result.update(prior_validation(revision, published_at, validation_run_id, validation_run_attempt))
    return result


def archive_files(content, root_prefix=False):
    with zipfile.ZipFile(io.BytesIO(content)) as archive:
        entries = [item for item in archive.infolist() if not item.is_dir()]
        names = [item.filename for item in entries]
        require(len(names) == len(set(names)), "The public source archive contains duplicate members.")
        prefixes = {name.split("/", 1)[0] for name in names}
        if root_prefix:
            require(len(prefixes) == 1 and all("/" in name for name in names), "The GitHub source archive has an invalid root.")
        result = {}
        for entry in entries:
            name = entry.filename.split("/", 1)[1] if root_prefix else entry.filename
            parts = name.replace("\\", "/").split("/")
            forbidden = {".git", ".github", ".tmp", "vendor", "contracts", "scripts", "security", "tests", "test", "simulators", "simulator", "fixtures"}
            require(name and not name.startswith(("/", "\\")) and ".." not in parts and not forbidden.intersection(parts),
                    "Development or test content entered the public source archive.")
            require(parts[-1] not in {"AGENTS.md", "TODO.md", "composer.lock"} and not parts[-1].startswith(".env") and not name.endswith(".log"),
                    "Private or development content entered the public source archive.")
            require((entry.external_attr >> 16) & 0o170000 != 0o120000, "The public source archive must not contain symbolic links.")
            result[name] = hashlib.sha256(archive.read(entry)).hexdigest()
        require({"composer.json", "LICENSE", "src/RaimsClient.php", "docs/reference.md"}.issubset(result), "The public archive is missing required SDK files.")
        return result


def git_archive_files(source, revision):
    process = subprocess.run(["git", "-C", str(source), "archive", "--format=zip", revision], capture_output=True, timeout=60)
    require(process.returncode == 0, "The immutable public source export could not be verified.")
    return archive_files(process.stdout)


def bundle(source, report_path, destination, run_id, attempt):
    expected = identity(source, run_id, attempt)
    toolkit = source.parent / "Developer-Toolkit"
    require(git(toolkit, "rev-parse", "HEAD") == expected["toolkitRevision"] and git(toolkit, "status", "--porcelain") == "",
            "The validation Toolkit differs from its reviewed immutable revision.")
    report = json.loads(report_path.read_text(encoding="utf-8"))
    require(report.get("status") == "passed" and report.get("liveApiCalled") is False, "The canonical PHP SDK check did not pass.")
    require(Path(report.get("source", "")).resolve() == source, "The canonical report belongs to another source checkout.")
    require(report.get("toolingSha256") == digest(toolkit / "scripts/release-preflight/php_sdk.py"), "The canonical validator changed after the check.")
    export = report.get("githubArchive", {})
    require(export.get("sameFilesAsComposer") is True and export.get("sameBytesAsComposer") is True and export.get("commitCreated") is False,
            "The canonical Composer and Git source archive checks did not pass.")
    composer_archive = report_path.parent / "dist/raims-php-development.zip"
    github_archive = report_path.parent / "dist/raims-php-github.zip"
    require(composer_archive.is_file() and github_archive.is_file(), "The canonical public source archives are missing.")
    files = archive_files(composer_archive.read_bytes())
    require(files == archive_files(github_archive.read_bytes()) == git_archive_files(source, expected["revision"]),
            "The approved public source archives differ from the immutable release source.")
    require(export.get("files") == len(files), "The canonical public file count differs from the release export.")
    require(not destination.exists() or not any(destination.iterdir()), "Use an empty release artifact directory.")
    destination.mkdir(parents=True, exist_ok=True)
    manifest = {"schemaVersion": 1, **expected, "publicFiles": files, "validationReportSha256": digest(report_path),
                "composerArchiveSha256": digest(composer_archive), "gitArchiveSha256": digest(github_archive)}
    (destination / "release-manifest.json").write_text(json.dumps(manifest, sort_keys=True, indent=2) + "\n", encoding="utf-8")
    print("The public PHP source manifest is bound to the release and prior validation.")


def verify(source, directory):
    manifest_path = directory / "release-manifest.json"
    require(manifest_path.is_file() and {path.name for path in directory.iterdir()} == {"release-manifest.json"},
            "The release artifact must contain only the validated source manifest.")
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    require(manifest.get("schemaVersion") == 1, "Unsupported PHP release manifest.")
    expected = identity(source, positive_id(manifest.get("validationRunId")), positive_id(manifest.get("validationRunAttempt")))
    require(all(manifest.get(key) == value for key, value in expected.items()), "The source manifest belongs to another release or workflow attempt.")
    files = git_archive_files(source, expected["revision"])
    require(manifest.get("publicFiles") == files, "The immutable public files differ from the approved release manifest.")
    for name in ("validationReportSha256", "composerArchiveSha256", "gitArchiveSha256"):
        require(isinstance(manifest.get(name), str) and re.fullmatch(r"[0-9a-f]{64}", manifest[name]), "A canonical release evidence checksum is missing.")
    return expected, files


def request_bytes(url, data=None, bearer=None, limit=2_000_000):
    headers = {"Accept": "application/json", "User-Agent": "RaimsPHPRelease/1 (mailto=operacoes@raims.com)"}
    if data is not None:
        headers["Content-Type"] = "application/json"
    if bearer is not None:
        require(url == PACKAGIST_UPDATE, "The SAFE credential cannot be sent to another destination.")
        headers["Authorization"] = "Bearer " + bearer
    request = urllib.request.Request(url, data=data, headers=headers, method="POST" if data is not None else "GET")
    try:
        with urllib.request.build_opener(NoRedirect()).open(request, timeout=30) as response:
            raw = response.read(limit + 1)
        require(len(raw) <= limit, "The registry response exceeds its size limit.")
        return raw
    except urllib.error.HTTPError as error:
        raise RuntimeError("Registry request failed with HTTP " + str(error.code) + "; reconcile its state before retrying.") from None
    except (urllib.error.URLError, TimeoutError, UnicodeError, ValueError):
        raise RuntimeError("The registry response was not received; reconcile its state before retrying.") from None


def publish(source, directory):
    expected, _ = verify(source, directory)
    username, token = os.environ.get("PACKAGIST_USERNAME", ""), os.environ.get("PACKAGIST_SAFE_TOKEN", "")
    require(username == "raims" and re.fullmatch(r"[\x21-\x7e]+", token), "The approved Packagist account or SAFE token is unavailable.")
    # The SAFE token authenticates only this refresh request. Package creation
    # and repository editing are deliberately outside the publisher.
    content = request_bytes(PACKAGIST_UPDATE, json.dumps({"repository": REPOSITORY_URL}).encode("utf-8"), username + ":" + token)
    try:
        response = json.loads(content)
    except (ValueError, UnicodeError):
        raise RuntimeError("The Packagist refresh response is invalid.") from None
    require(isinstance(response, dict) and response.get("status") == "success" and isinstance(response.get("jobs"), list),
            "Packagist did not accept the package refresh.")
    print("Packagist accepted the refresh for " + PACKAGE_ID + " " + expected["version"] + "; public indexing still needs confirmation.")


def public_version(metadata, version):
    require(isinstance(metadata, dict) and metadata.get("minified") in (None, "composer/2.0"), "The public Composer metadata format is unsupported.")
    rows = metadata.get("packages", {}).get(PACKAGE_ID)
    require(isinstance(rows, list), "The public package metadata is incomplete.")
    previous = {}
    for row in rows:
        require(isinstance(row, dict), "The public version metadata is invalid.")
        if metadata.get("minified") == "composer/2.0":
            for key, value in row.items():
                if value == "__unset":
                    previous.pop(key, None)
                else:
                    previous[key] = value
            candidate = dict(previous)
        else:
            candidate = row
        if candidate.get("version") in (version, "v" + version):
            return candidate
    return None


def confirm(source, directory):
    expected, files = verify(source, directory)
    for attempt in range(25):
        try:
            metadata = json.loads(request_bytes(PACKAGE_METADATA))
            candidate = public_version(metadata, expected["version"])
        except RuntimeError as error:
            if "HTTP 404;" not in str(error):
                raise
            candidate = None
        if candidate is not None:
            origin, distribution = candidate.get("source", {}), candidate.get("dist", {})
            public_zip = "https://api.github.com/repos/" + REPOSITORY + "/zipball/" + expected["revision"]
            codeload_zip = "https://codeload.github.com/" + REPOSITORY + "/legacy.zip/" + expected["revision"]
            require(candidate.get("name") == PACKAGE_ID and origin.get("type") == "git" and origin.get("reference") == expected["revision"]
                    and origin.get("url") in {REPOSITORY_URL, REPOSITORY_URL + ".git"} and distribution.get("reference") == expected["revision"]
                    and distribution.get("type") == "zip" and distribution.get("url") in {public_zip, codeload_zip},
                    "The indexed package does not refer to the approved release commit.")
            content = request_bytes("https://codeload.github.com/" + REPOSITORY + "/legacy.zip/" + expected["revision"], limit=20_000_000)
            require(archive_files(content, root_prefix=True) == files, "The public distribution files differ from the approved source export.")
            print("Packagist " + PACKAGE_ID + " " + expected["version"] + " is indexed at the approved commit; public archive members match.")
            return
        if attempt < 24:
            time.sleep(5)
    raise RuntimeError("The package refresh was accepted but the approved version is not indexed yet. Verify public metadata before retrying an update.")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("command", choices=("context", "bundle", "verify", "publish", "confirm"))
    parser.add_argument("--source", type=Path, required=True)
    parser.add_argument("--report", type=Path)
    parser.add_argument("--artifact", type=Path)
    parser.add_argument("--validation-run-id")
    parser.add_argument("--validation-run-attempt")
    args = parser.parse_args()
    source = args.source.resolve()
    if args.command == "context":
        context = identity(source)
        require(os.environ.get("GITHUB_OUTPUT"), "GitHub outputs are unavailable.")
        with Path(os.environ["GITHUB_OUTPUT"]).open("a", encoding="utf-8") as stream:
            for field in ("version", "revision", "toolkitRevision", "validationRunId", "validationRunAttempt"):
                stream.write(field + "=" + str(context[field]) + "\n")
        print("Published PHP release and prior validation identities verified.")
    elif args.command == "bundle":
        require(args.report is not None and args.artifact is not None and args.validation_run_id is not None
                and args.validation_run_attempt is not None, "The canonical report, artifact and prior validation identity are required.")
        bundle(source, args.report.resolve(), args.artifact.resolve(), args.validation_run_id, args.validation_run_attempt)
    else:
        require(args.artifact is not None, "The validated release artifact is required.")
        if args.command == "verify":
            verify(source, args.artifact.resolve())
            print("The immutable public PHP source manifest is verified.")
        elif args.command == "publish":
            publish(source, args.artifact.resolve())
        else:
            confirm(source, args.artifact.resolve())


if __name__ == "__main__":
    try:
        main()
    except (OSError, ValueError, RuntimeError, zipfile.BadZipFile) as error:
        print("PHP release check failed: " + str(error), file=sys.stderr)
        sys.exit(1)
