# District Team authentication

* **Author:** Jacob Tyler
* **Date:** 2026-11-18

## Overview

District Team needs to restrict access to its directory and editing screens while preserving the district-site build on internal Kubernetes GitHub runners. Use Cognito managed sign-in for people. A signed-in user must belong to the exact Cognito group `district-team-{env}` for the deployment. Start with HTTP Basic Auth over HTTPS for the build, using a dedicated credential supplied as a CI secret.

## Analysis

**Browser sign-in.** Use the DistrictBadges CakePHP flow as the reference for authorization code with PKCE, state and nonce checks, token validation, session refresh and logout. Follow DistrictWorkflow's pattern of an explicit environment setting and an exact group match. DistrictBadges also matches people to local accounts. District Team's first release can use group membership alone. Verify memberships before cutover. For routine removal, recheck the required group when tokens are refreshed and end the app session if membership is missing or refresh is denied. Allow existing sessions to last until the next scheduled check or expiry. For urgent removal, invalidate the user's app sessions across replicas and revoke their Cognito refresh tokens so they cannot obtain new tokens from those sessions. Operators can also withdraw WireGuard or firewall access where those network controls apply. Give users who lack the required group a way to request access.

**Multiple replicas.** District Team defaults to PHP sessions and the base Kubernetes deployment has two replicas. Sign-in and authenticated sessions need to work when the callback or any later request reaches a different pod, including during a rollout. Choose a session store accessible from both replicas or another design that meets the same requirement. Verify sign-in, group denial, expiry, and logout across pods. Cache Cognito signing keys and keep token refresh bounded so ordinary requests can continue between refreshes.

**Two existing API consumers.** The appointment add and edit forms use `/api/member-search` and `/api/appointment-contact-methods`. Preserve those calls with the signed-in browser session. Restrict session access to the routes needed by the UI unless broader access is approved. The district-site build uses a server-side fetch helper and receives the API URL from GitHub Actions. Add the credential there, inventory any other callers and determine which routes the build needs before granting access. The API exposes directory data including member profiles and contact methods. Check that the published static output contains only the data intended for each audience and that credentials stay out of artifacts, caches and logs.

**Cutover and recovery.** Test the real district-site build against an environment that already enforces auth. An open endpoint can return success even if the runner or ingress drops the Authorization header. Verify HTTPS and header forwarding on the internal runner path, check the response schema with valid credentials, and confirm missing or invalid credentials receive a JSON `401` without a browser redirect. Repeat the build after production enforcement and after each credential rotation. Identify the rollback owner and test a recovery path that preserves API protection. Carry valid credentials across rotation and rollback. Record sanitized failure categories and request IDs so operators can distinguish CI secrets and ingress problems from Cognito configuration or provider failures.

## Goals vs. Non-Goals

### In-scope goals

* Require Cognito sign-in and exact environment group membership for access to the browser app.
* Preserve the appointment forms' authenticated lookups and the district-site build's read-only API access.
* Keep the existing API payloads and verify allowed and denied requests during the rollout.

### Out-of-scope non-goals

* API writes, local user provisioning, and new browser roles.
* Cognito machine-to-machine JWTs or support for multiple machine credential schemes in the first release.
* Changes to the rules district-site uses to decide which directory data it publishes.

## Alternatives Weighed

* **Cognito machine-to-machine access tokens.** Defer until a client needs short-lived access tokens or separate scopes. It adds an app client, scoped token validation, a token exchange in the build and token-request costs. [AWS Cognito machine-to-machine authorization](https://docs.aws.amazon.com/cognito/latest/developerguide/cognito-user-pools-define-resource-servers.html)
* **Require Basic Auth for every API request.** The appointment forms already call the API. Giving their browser code a build credential would expose it, and moving the lookups would require a separate migration.
* **Require a local user record as DistrictBadges does.** Group membership covers the stated browser access rule. Revisit local records if product requirements call for local roles or attribution.

* **Move the browser lookups to their own routes.** This would make the authorization boundary easier to see, with the cost of updating the appointment form URLs. Keep it open for design review alongside explicit per-route rules on the existing URLs.

## Key Decisions & Staging for Next Steps

* Assign ownership of group provisioning, membership, and revocation. Set the routine refresh and session expiry intervals during design. Verify that routine group removal ends access at the next revalidation and that operators can invalidate a user's active sessions across replicas and revoke their refresh tokens when needed.
* Confirm the complete caller inventory and the routes required by the build and browser. Specify the API authorization policy at the CakePHP route boundary or an established platform boundary that supports both approved callers.
* Identify the staging environment, GitHub Actions secret names, ingress path, credential owner and rotation procedure, rollout owner, and rollback trigger. Treat a full build against enforced auth as the production gate.
