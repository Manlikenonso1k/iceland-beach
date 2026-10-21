Production errors: "Unknown column services.updated_at" when saving a space, and "Unknown column sales.deleted_at" on the Sales page. Nothing is pending in migrate:status and neither column exists on production.

Add ONE additive migration:
- services: add nullable created_at and updated_at if missing
- sales: add softDeletes (deleted_at) if missing
Guard each column with Schema::hasColumn so it's safe on any database. Don't drop or modify anything else.

Also check the other models for the same mismatch (models using timestamps or SoftDeletes whose tables lack those columns) and include any you find in the same migration.

Add a test that saves a space with an image, and one that loads the Sales list. Commit and push, and tell me which tables you changed.