import { onBeforeUnmount, onMounted, ref } from 'vue'

export function useMobileKeyboard() {
  const keyboardVisible = ref(false)
  let cleanup = () => {}

  onMounted(() => {
    const viewport = window.visualViewport
    let fullHeight = Math.max(window.innerHeight, viewport?.height || 0)
    let width = window.innerWidth
    let openingUntil = 0
    let detected = false
    let focusTimer

    function isEditable(target) {
      return target?.isContentEditable || (target?.matches?.('textarea, input') && !target.readOnly && !target.disabled && !['button', 'submit', 'reset', 'checkbox', 'radio', 'range', 'color', 'file', 'hidden'].includes(target.type))
    }
    function update() {
      if (window.innerWidth >= 1024) {
        keyboardVisible.value = false
        return
      }
      if (width !== window.innerWidth) {
        width = window.innerWidth
        fullHeight = Math.max(window.innerHeight, viewport?.height || 0)
      }
      const height = viewport?.height || window.innerHeight
      // Retain the height measured before browsers shrink their layout viewport.
      fullHeight = Math.max(fullHeight, window.innerHeight, height)
      const reduced = (!viewport || viewport.scale === 1) && fullHeight - height > Math.max(120, fullHeight * .18)
      if (reduced && (isEditable(document.activeElement) || keyboardVisible.value)) {
        detected = true
        keyboardVisible.value = true
      } else if (detected || Date.now() >= openingUntil || !isEditable(document.activeElement)) {
        keyboardVisible.value = false
        detected = false
      }
    }
    function focusIn(event) {
      if (window.innerWidth >= 1024 || !isEditable(event.target)) return
      // Hide before the keyboard animation moves fixed elements above it.
      openingUntil = Date.now() + 500
      detected = false
      keyboardVisible.value = true
      clearTimeout(focusTimer)
      focusTimer = setTimeout(update, 520)
    }

    document.addEventListener('focusin', focusIn)
    document.addEventListener('focusout', update)
    window.addEventListener('resize', update)
    viewport?.addEventListener('resize', update)
    update()
    cleanup = () => {
      clearTimeout(focusTimer)
      document.removeEventListener('focusin', focusIn)
      document.removeEventListener('focusout', update)
      window.removeEventListener('resize', update)
      viewport?.removeEventListener('resize', update)
    }
  })
  onBeforeUnmount(() => cleanup())
  return keyboardVisible
}
