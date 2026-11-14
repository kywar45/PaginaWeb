import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { DatabaseSync } from 'node:sqlite'

const source = readFileSync(new URL('../src/Repositories/UserRepository.php', import.meta.url), 'utf8')
const countSql = source.match(/function countOrders\(\$userId\)[\s\S]*?prepare\('([\s\S]*?)'\)/)?.[1]
const pageSql = source.match(/function ordersPage\(\$userId, \$limit, \$offset\)[\s\S]*?prepare\('([\s\S]*?)'\)/)?.[1]
assert.ok(countSql && pageSql)
const db = new DatabaseSync(':memory:')
db.exec('CREATE TABLE folios (id INTEGER PRIMARY KEY, id_usuario INTEGER, orden TEXT, fecha TEXT, fecha_pago TEXT, total TEXT, estatus TEXT)')
const insert = db.prepare('INSERT INTO folios VALUES (?, ?, ?, ?, ?, ?, ?)')
for (let id = 1; id <= 21; id++) insert.run(id, 23, `SN-${id}`, '2026-11-13 12:00:00', id % 3 === 0 ? null : id % 3 === 1 ? '0000-00-00 00:00:00' : '2026-11-13 12:00:00', '100', id === 21 ? 'Cancelado' : 'Pedido')
insert.run(99, 99, 'PRIVATE', '2026-11-13 12:00:00', null, '500', 'Pedido')
assert.equal(Object.values(db.prepare(countSql).get({ user_id: 23 }))[0], 21)
const rows = [0, 10, 20].flatMap(offset => db.prepare(pageSql).all({ user_id: 23, limit: 10, offset }))
assert.deepEqual(rows.map(row => row.id), Array.from({ length: 21 }, (_, i) => 21 - i), 'Stable pages, latest IDs first on tied dates, no other account')
assert.ok(rows.some(row => row.fecha_pago === null) && rows.some(row => row.fecha_pago === '0000-00-00 00:00:00'), 'Both unpaid representations included')
assert.ok(rows.some(row => row.estatus === 'Cancelado'), 'No implicit status filter')
assert.deepEqual(db.prepare(pageSql).all({ user_id: 24, limit: 10, offset: 0 }), [])
db.close()
console.log('Account order SQL tests passed: all statuses, unpaid dates, ownership and stable pagination.')
