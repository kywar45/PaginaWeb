import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { DatabaseSync } from 'node:sqlite'

const source = readFileSync(new URL('../src/Repositories/OrderPdfRepository.php', import.meta.url), 'utf8')
const query = (method) => source.match(new RegExp(`function ${method}\\([^)]*\\)[\\s\\S]*?prepare\\('([\\s\\S]*?)'\\)`))?.[1]
const db = new DatabaseSync(':memory:')
db.exec(`CREATE TABLE folios (id INTEGER PRIMARY KEY, id_usuario INTEGER, orden TEXT, nombres TEXT, fecha TEXT, fecha_pago TEXT, total TEXT, estatus TEXT, shipping_carrier_id INTEGER);
CREATE TABLE shipping_carriers (id INTEGER PRIMARY KEY, nombre TEXT, envio INTEGER, estatus INTEGER);
CREATE TABLE registro_usuario (id INTEGER PRIMARY KEY, id_usuario INTEGER, id_producto INTEGER, nombre TEXT, codigo TEXT, precio TEXT, cantidad INTEGER, orden TEXT);
INSERT INTO shipping_carriers VALUES (1, 'Histórica inactiva', 1, 0), (2, 'Punto Centro', 0, 1);
INSERT INTO folios VALUES (42,23,'SN-42','Cliente','2026-11-13',NULL,'100','Pedido',1), (43,23,'SN-43','Cliente','2026-11-13',NULL,'100','Pedido',2), (99,99,'SN-99','Otra cuenta','2026-11-13',NULL,'100','Pedido',NULL);
INSERT INTO registro_usuario VALUES (1,23,8,'Gel','GEL-01','100',1,'SN-42'), (2,99,9,'Privado','PRIVATE','200',2,'SN-42');`)
const owned = db.prepare(query('findOwned'))
assert.equal(owned.get({ id: 42, user_id: 23 }).carrier_envio, 1, 'Inactive historical carrier retained')
assert.equal(owned.get({ id: 43, user_id: 23 }).carrier_envio, 0, 'Delivery uses carrier envio=0')
assert.equal(owned.get({ id: 42, user_id: 99 }), undefined, 'No foreign order access')
assert.equal(owned.get({ id: 99, user_id: 99 }).carrier_name, null, 'Missing carrier allowed')
const rows = db.prepare(query('itemsForOrder')).all({ reference: 'SN-42', user_id: 23 })
assert.deepEqual(rows.map(row => row.codigo), ['GEL-01'], 'Only the eight allowed fields, filtered by order and session owner')
db.exec("INSERT INTO folios VALUES (100,99,'SN-42','Otra cuenta','2026-11-13',NULL,'100','Pedido',NULL)")
assert.deepEqual(db.prepare(query('itemsForOrder')).all({ reference: 'SN-42', user_id: 23 }).map(row => row.codigo), ['GEL-01'], 'Shared order references cannot mix accounts')
db.close()
console.log('Order PDF SQL tests passed: owner scope, carrier relation, historical rows and shared references.')
