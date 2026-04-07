import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/login",
      name: "login",
      component: () => import("../views/auth/LoginView.vue"),
      meta: { libre: true },
    },
    {
      path: "/registro",
      name: "registro",
      component: () => import("../views/auth/RegistroView.vue"),
      meta: { requiereAuth: true },
    },
    {
      path: "/",
      name: "dashboard",
      component: () => import("../views/DashboardView.vue"),
      meta: { title: "Bienvenido al panel", requiereAuth: true },
    },
    {
      path: "/clientes",
      name: "clientes",
      component: () => import("../views/ClientesView.vue"),
      meta: { requiereAuth: true },
    },
    {
      path: "/facturas",
      name: "facturas",
      component: () => import("../views/FacturasView.vue"),
      meta: { requiereAuth: true },
    },
  ],
});

// Guardia de navegación para proteger rutas
router.beforeEach((to, from) => {
  const authStore = useAuthStore();

  if (to.meta.requiereAuth && !authStore.isLoggedIn) {
    return { name: "login" };
  } else if (to.meta.libre && authStore.isLoggedIn) {
    return { name: "dashboard" };
  }
});

export default router;
