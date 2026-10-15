# Manufacturing workflow foundations

## Product structure and order snapshots

- BOM and routing changes are made in draft versions. Activating a draft archives the previous active version; active and archived versions cannot be edited through the CRUD workflow.
- BOM component rows can optionally reference an inventory item. The inventory relationship is intentionally not inferred from similar-looking SKUs.
- A production order captures the selected active BOM and routing, including JSON copies of their items and operations. Existing orders receive a backfill snapshot when the additive migration runs.
- Existing order actual/good quantities are preserved as a baseline. New batch output is added to that baseline rather than replacing historical or simulated order totals.
- Each batch copies the order snapshots at creation, so later product-structure changes do not rewrite the batch's definition.

## Batch and material control

- Batch planned quantities cannot exceed the unallocated planned quantity on their production order.
- A batch must have an order snapshot of an active routing with operations, mapped inventory components, and sufficient reservations before it can start.
- Reserving material records an inventory transaction but does not change on-hand stock. Recording consumption reduces on-hand stock and writes an append-only transaction with the cost snapshot.
- Unused reservations must be explicitly released before a batch can be completed or cancelled. Actual and good output are validated and rolled up from completed batches to the production order.
- Inventory creation records an opening-balance transaction. Manual on-hand changes create signed adjustment transactions; a change cannot reduce stock below active reservations. Items with transaction history cannot be deleted, and their unit cannot be changed.

## Energy, OEE, and costing

- Existing IoT telemetry payloads and endpoints remain unchanged. Energy records can be associated with a batch and routing operation through the existing authenticated CRUD interface. Existing order-level telemetry is not automatically distributed across batches.
- OEE records can optionally reference a batch and operation. Existing OEE calculations and historical records remain unchanged; historical OEE is not automatically attributed to a batch.
- Batch material actual cost uses consumed quantities multiplied by the unit-cost snapshot. Energy actual cost includes only energy records explicitly linked to the batch. Labor and overhead actuals are manual append-only cost entries.
- Routing energy values are estimates, not sensor readings. No energy tariff or labor rate is invented for forecasts.

## Database deployment

The added migrations are additive and support SQLite and MySQL. On an existing database, back up the database file or take the normal database backup before running:

```sh
php artisan migrate --seed
```

Use `php artisan migrate:fresh --seed` only against a disposable development database. The feature tests use an in-memory SQLite database and do not reset the local development database.
