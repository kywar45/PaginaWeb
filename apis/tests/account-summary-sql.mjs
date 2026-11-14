import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { DatabaseSync } from 'node:sqlite'

// Execute the repository's actual portable SELECT against representative rows.
const source = readFileSync(new URL('../src/Repositories/UserRepository.php', import.meta.url), 'utf8')
const sql = source.match(/function countPaidOrders\(\$userId\)[\s\S]*?prepare\("([\s\S]*?)"\)/)?.[1]
assert.ok(sql, 'Repository query exists')
const db = new DatabaseSync(':memory:')
db.exec('CREATE TABLE folios (id INTEGER PRIMARY KEY, id_usuario INTEGER, fecha_pago TEXT, cantidad TEXT, nota TEXT)')
const insert = db.prepare('INSERT INTO folios VALUES (?, ?, ?, ?, ?)')
insert.run(1, 23, '2026-11-01 10:00:00', '100', 'Old field')
insert.run(2, 23, '2026-11-02 10:00:00', '250', 'Old field')
insert.run(3, 23, null, '300', 'Not paid')
insert.run(4, 23, '0000-00-00 00:00:00', '400', 'Legacy unpaid date')
insert.run(5, 99, '2026-11-03 10:00:00', '500', 'Another account')
const count = (id) => Object.values(db.prepare(sql).get({ user_id: id }))[0]
assert.equal(count(23), 2, 'Count orders, not the quantity of pieces')
assert.equal(count(99), 1, 'Separate accounts')
assert.equal(count(24), 0, 'No orders')
db.exec('UPDATE folios SET fecha_pago = NULL WHERE fecha_pago = \'0000-00-00 00:00:00\'')
db.exec('ALTER TABLE folios DROP COLUMN cantidad; ALTER TABLE folios DROP COLUMN nota')
assert.equal(count(23), 2, 'Future null dates and removal of unrelated columns preserve the count')
db.close()
console.log('Account summary SQL tests passed: paid dates, ownership, count and future schema cleanup.')
