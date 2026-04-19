import Axios from "axios";
import router from "@/router";

// Helpers para gestionar el token
export const getToken = () => sessionStorage.getItem("access_token");
export const setToken = (token) => sessionStorage.setItem("access_token", token);
export const clearToken = () => sessionStorage.removeItem("access_token");

const getBaseURL = () => {
  const envUrl = import.meta.env.VITE_APP_BACKEND_URL;
  return envUrl.endsWith("/api") ? envUrl : `${envUrl}/api`;
};

const axios = Axios.create({
  baseURL: getBaseURL(),
  headers: {
    "X-Requested-With": "XMLHttpRequest",
  },
});

// Interceptor para añadir el token Bearer en cada petición
axios.interceptors.request.use((config) => {
  const token = getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor para manejar errores de autenticación y servidor
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;
    if (status === 401) {
      clearToken();
      router.replace({ name: "login" });
    } else if (status === 429) {
      console.warn("Demasiadas peticiones. Intenta más tarde.");
    } else if (status >= 500) {
      console.error("Error del servidor:", status);
    }
    return Promise.reject(error);
  },
);

export default axios;
