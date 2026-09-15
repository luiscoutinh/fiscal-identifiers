# Security policy

## Supported versions

There are no stable releases yet. The `main` branch is under active development
and is not ready for production use. A release support policy will be documented
with the first stable release.

## Reporting a vulnerability

Do not disclose vulnerabilities or personal tax identifiers in public issues.
Use [GitHub's private reporting form](https://github.com/luiscoutinh/fiscal-identifiers/security/advisories/new),
when available.

If private reporting is unavailable, open a public issue containing only a request
for a private security contact, with no vulnerability details or sensitive data.
Wait for the maintainer to provide a private channel before sharing the report.

Include affected versions or commits, impact, reproduction steps and a minimal
synthetic example. No response-time commitment is made at this early stage.

## Validation boundaries

A locally plausible identifier does not establish identity, ownership, assignment,
registration or tax status. External verification is contextual and time-dependent;
timeouts and service errors must not be treated as a definitive invalid result.
There are currently no validators or external verification providers in this package.
