import { readonly, ref } from 'vue'
import authApi from 'src/services/authApi'

const currentUser = ref(null)
const sessionChecked = ref(false)
const sessionLoading = ref(false)
let pendingLoad = null

export function useAuthSession() {
  async function loadCurrentUser({ force = false } = {}) {
    if (sessionChecked.value && !force) {
      return currentUser.value
    }

    if (pendingLoad && !force) {
      return pendingLoad
    }

    sessionLoading.value = true

    pendingLoad = authApi
      .me()
      .then((data) => {
        currentUser.value = data?.usuario || null
        return currentUser.value
      })
      .catch((error) => {
        if (error?.status !== 401) {
          console.warn('No pudimos cargar la sesion.', error)
        }

        currentUser.value = null
        return null
      })
      .finally(() => {
        sessionChecked.value = true
        sessionLoading.value = false
        pendingLoad = null
      })

    return pendingLoad
  }

  function setCurrentUser(user) {
    currentUser.value = user || null
    sessionChecked.value = true
  }

  async function logout() {
    await authApi.logout()

    currentUser.value = null
    sessionChecked.value = true
  }

  return {
    currentUser: readonly(currentUser),
    sessionChecked: readonly(sessionChecked),
    sessionLoading: readonly(sessionLoading),
    loadCurrentUser,
    setCurrentUser,
    logout,
  }
}
