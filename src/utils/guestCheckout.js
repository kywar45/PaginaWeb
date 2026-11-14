import { mexicanStates } from './mexicanStates.js'

export function emptyGuestDetails() {
  return {
    nombre: '',
    apellido: '',
    correo: '',
    estado: '',
    ciudad: '',
    colonia: '',
    direccion: '',
    cp: '',
    telefono: '',
  }
}

export const requiredGuestField = (value) => !!String(value ?? '').trim() || 'Completa este campo'
export const validGuestEmail = (value) =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value ?? '').trim()) ||
  'Ingresa un correo electrónico válido'
export const validGuestState = (value) =>
  mexicanStates.includes(value) || 'Selecciona un estado de la lista'
export const validGuestPostalCode = (value) =>
  /^[0-9]{5}$/.test(String(value ?? '')) || 'El código postal debe tener 5 dígitos'
export function validGuestPhone(value) {
  const phone = String(value ?? '').trim()
  const digits = phone.replace(/\D/g, '')
  return (
    (/^\+?[0-9() .-]{7,20}$/.test(phone) && digits.length >= 7 && digits.length <= 15) ||
    'Ingresa un teléfono válido con lada'
  )
}

export function normalizeGuestDetails(details) {
  return Object.fromEntries(
    Object.keys(emptyGuestDetails()).map((key) => [key, String(details[key] ?? '').trim()]),
  )
}
