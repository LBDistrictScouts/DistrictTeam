# District Team Kubernetes deployment

This directory deploys the CakePHP application and PostgreSQL with Kustomize.
The shared resources live in `base/`; the two isolated environments are
`overlays/test/` and `overlays/prod/`. One-off database migrations live under
`operations/`.

## Prerequisites

- Kustomize (or `kubectl` with Kustomize support)
- the 1Password Kubernetes Operator
- cert-manager with `ClusterIssuer/letsencrypt-prod`
- the `local-db-ssd-rwo` storage class and a node labelled
  `storage.homelab/db-ssd=true`
- DNS records for the test and production hostnames

## Container image

Build the included `Dockerfile` and publish it as:

```text
ghcr.io/lbdistrictscouts/districtteam:<tag>
```

The manifests default to `latest`. Change `images[].newTag` in
`base/kustomization.yaml`, or set an overlay-specific image tag, for immutable
deployments.

## Secrets

The 1Password Operator items are:

- test: `vaults/Infrastructure/items/DistrictTeam Test`
- production: `vaults/Infrastructure/items/DistrictTeam Prod`
- GHCR: `vaults/Infrastructure/items/ArgoCD - LBD Repo Creds`

Each DistrictTeam item must expose these exact keys:

- `SECURITY_SALT`
- `DATABASE_URL`
- `POSTGRES_DB`
- `POSTGRES_USER`
- `POSTGRES_PASSWORD`
- `EMAIL_TRANSPORT_DEFAULT_URL` (optional, but required to send email)

Use these database hosts in `DATABASE_URL`:

```text
test-district-team-postgres
prod-district-team-postgres
```

For example:

```text
postgres://USER:PASSWORD@test-district-team-postgres:5432/DATABASE?encoding=utf8&timezone=UTC&cacheMetadata=true
```

## Deploy

```bash
kubectl apply -k K8S/overlays/test
kubectl apply -k K8S/overlays/prod
```

Run migrations intentionally after the corresponding application deployment:

```bash
kubectl apply -k K8S/overlays/test/operations
kubectl apply -k K8S/overlays/prod/operations
```

The default hostnames are:

- test: `district-team-test.lbdscouts.org.uk`
- production: `district-team.lbdscouts.org.uk`

Update the ConfigMap, nginx, and Certificate patches together if either name
changes. nginx terminates TLS directly behind a `LoadBalancer` Service. On a
new cluster, wait for cert-manager to create the environment's TLS Secret
before expecting the application pods to become ready.
