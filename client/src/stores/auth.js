import { ref, computed } from "vue";
import { defineStore } from "pinia";
import { useRouter } from "vue-router";
import axios, { getToken, setToken, clearToken } from "@/api/axios";

export const useAuthStore = defineStore("auth", () => {
  const user = ref(null);
  const isLoggedIn = computed(() => !!user.value || !!getToken());
  const fetchingUser = ref(false);

  const router = useRouter();

  const fetchUser = async () => {
    fetchingUser.value = true;
    try {
      const { data } = await axios.get("usuario");

      user.value = data;
    } catch (error) {
      if (error.response?.status === 409) {
        router.push({ name: "verify-email" });
      }
    } finally {
      fetchingUser.value = false;
    }
  };

  const login = async (processing, errors, { ...data }) => {
    processing.value = true;
    errors.value = {};

    try {
      const response = await axios.post("login", data);
      console.log(response);

      // Guardar token
      setToken(response.data.access_token);

      // Obtener datos del usuario
      user.value = response.data.user;

      router.push({ name: "dashboard" });
    } catch (error) {
      if (error.response.status === 422) {
        errors.value = error.response.data.errors;
      }
    } finally {
      processing.value = false;
    }
  };

  const register = async (processing, errors, { ...data }) => {
    processing.value = true;
    errors.value = {};

    try {
      const response = await axios.post("registro", data);

      // Guardar token
      setToken(response.data.access_token);

      // Obtener datos del usuario
      user.value = response.data.user;

      router.push({ name: "dashboard" });
    } catch (error) {
      if (error.response.status === 422) {
        errors.value = error.response.data.errors;
      }
    } finally {
      processing.value = false;
    }
  };

  const logout = async () => {
    await axios.post("logout");

    user.value = null;
    clearToken();

    router.push({ name: "login" });
  };

  const initAuth = async () => {
    const token = getToken();
    if (token) {
      await fetchUser();
    }
  };

  return {
    user,
    isLoggedIn,
    fetchUser,
    initAuth,
    login,
    register,

    logout,
    token: getToken,
  };
});
