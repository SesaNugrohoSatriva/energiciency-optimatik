# Manufacturing workflow foundations

## Product routing and order snapshots

- Routing changes are made in draft versions. Activating a draft archives the previous active version; active and archived versions cannot be edited through the CRUD workflow.
- A production order captures the selected active routing, including a JSON copy of its operations. Existing routing snapshots are retained when BOM structures are removed.
- Existing order actual/good quantities are preserved as a baseline. New batch output is added to that baseline rather than replacing historical or simulated order totals.
- Each batch copies the order routing snapshot at creation, so later routing changes do not rewrite the batch's definition.

## Batch and inventory

- Batch planned quantities cannot exceed the unallocated planned quantity on their production order.
- A batch requires an order snapshot of an active routing with operations before it can start.
- Inventory is managed as a master stock list. Opening balances and manual on-hand adjustments are recorded in its ledger; production batches do not reserve or consume inventory.
- Actual and good output are validated and rolled up from completed batches to the production order.

## Energy, OEE, and costing

- Energy records can be associated with a batch and routing operation through the existing authenticated CRUD interface. Existing order-level telemetry is not automatically distributed across batches.
- OEE records can optionally reference a batch and operation. Existing OEE calculations and historical records remain unchanged; historical OEE is not automatically attributed to a batch.
- Product material, labor, and overhead costs are manual master estimates. Batch actual cost includes only energy records explicitly linked to the batch and manual labor/overhead cost entries.
- Routing energy values are estimates, not sensor readings. No energy tariff or labor rate is invented for forecasts.

## Database deployment

Back up the database before deploying the BOM-removal migration. It drops BOM tables and snapshots, batch material requirements, and inventory transactions linked to production batches. Inventory opening-balance and manual-adjustment transactions are retained.

On an existing database, run:

```sh
php artisan migrate --seed
```

Use `php artisan migrate:fresh --seed` only against a disposable development database. The feature tests use an in-memory SQLite database and do not reset the local development database.
