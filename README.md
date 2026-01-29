# Debug Snapshot Bundle

Symfony bundle for exporting and importing Doctrine ORM aggregates as a debug snapshot ZIP.

## Requirements

- PHP 8.2+
- Symfony 7.1+
- Doctrine ORM 2.17+ or 3.x

## Installation

```bash
composer require evgenijfaustov/debug-snapshot-bundle
```
If the bundle was not registered automatically, add it to config/bundles.php:

```
Evgenijfaustov\DebugSnapshotBundle\DebugSnapshotBundle::class => ['all' => true],
```
## Configuration

```yaml
debug_snapshot:
  enabled: true
  profiles:
    order:
      root_class: App\Entity\Order
      max_depth: 3
      max_nodes: 5000
      include:
        App\Entity\Order: [customer, items]
        App\Entity\Customer: []
        App\Entity\OrderItem: []
      pii_fields:
        App\Entity\Customer: [email, phone]
```
## include rules

include is an allowlist of Doctrine associations to traverse per entity class.

If a class is present in include with [], no relations are traversed for that class.

Relations not listed in include are not exported.
## Export

```bash
php bin/console debug:snapshot:export order <ORDER_ID> --out=var/snapshots --anonymize=1
```

## Import

```bash
php bin/console debug:snapshot:import var/snapshots/<FILE>.zip
```

## Snapshot format (v1)

- meta.json contains format, createdAt, profile, root, checksum
- snapshot.json contains format, root, entities with fields and relations

## Notes

- Composite identifiers are not supported.
- Only scalar fields are exported; relations are stored as references.
- PII masking is applied when `--anonymize=1` is passed.
