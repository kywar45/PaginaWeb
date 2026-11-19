export const registrationStates = [
  'Aguascalientes',
  'Baja California',
  'Baja California Sur',
  'Campeche',
  'Chiapas',
  'Chihuahua',
  'Ciudad de México',
  'Coahuila',
  'Colima',
  'Durango',
  'Estado de México',
  'Guanajuato',
  'Guerrero',
  'Hidalgo',
  'Jalisco',
  'Michoacán',
  'Morelos',
  'Nayarit',
  'Nuevo León',
  'Oaxaca',
  'Puebla',
  'Querétaro',
  'Quintana Roo',
  'San Luis Potosí',
  'Sinaloa',
  'Sonora',
  'Tabasco',
  'Tamaulipas',
  'Tlaxcala',
  'Veracruz',
  'Yucatán',
  'Zacatecas',
]

export function emptyRegistration() {
  return {
    nombre: '',
    apellido: '',
    correo: '',
    telefono: '',
    direccion: '',
    colonia: '',
    ciudad: '',
    estado: '',
    codigop: '',
    rfc: '',
    password: '',
    password_confirmation: '',
  }
}

export function passwordRule(value) {
  return (
    (typeof value === 'string' &&
      [...value].length >= 8 &&
      new TextEncoder().encode(value).length <= 72 &&
      !value.includes('\0')) ||
    'Usa al menos 8 caracteres y evita una contraseña demasiado larga'
  )
}

export function registrationPayload(form) {
  const fields = [
    'nombre',
    'apellido',
    'correo',
    'telefono',
    'direccion',
    'colonia',
    'ciudad',
    'estado',
    'codigop',
    'rfc',
  ]
  const result = Object.fromEntries(
    fields.map((field) => [field, String(form[field] || '').trim()]),
  )
  result.correo = result.correo.toLowerCase()
  result.rfc = result.rfc.toUpperCase()
  result.password = form.password
  result.password_confirmation = form.password_confirmation
  return result
}
