<script setup>
import { computed } from "vue";
import { useClientes } from "@/composables/useClientes";
import { useFacturas } from "@/composables/useFacturas";
import TarjetaDashboard from "@/components/ui/TarjetaDashboard.vue";

const { alumnos } = useClientes();
const { facturas } = useFacturas();

const alumnosActivos = computed(
  () => (alumnos.value ?? []).filter((a) => a.activo).length,
);

const ahora = new Date();
const mesActual = ahora.getMonth();
const anioActual = ahora.getFullYear();

const facturasMesActual = computed(() => {
  return (facturas.value ?? []).filter((f) => {
    if (f.situacion === "borrador") return false;
    if (!f.fecha_emision) return false;
    const [y, m] = f.fecha_emision.split("-").map(Number);
    return y === anioActual && m - 1 === mesActual;
  });
});

const recaudadoMes = computed(() => {
  return facturasMesActual.value
    .filter((f) => f.estado === "pagada")
    .reduce((sum, f) => sum + parseFloat(f.monto || 0), 0);
});

const facturasPendientes = computed(() => {
  return (facturas.value ?? []).filter((f) => {
    if (f.situacion === "borrador") return false;
    return f.estado === "pendiente";
  }).length;
});
const borradores = computed(() => {
  return (facturas.value ?? []).filter((f) => f.situacion === "borrador")
    .length;
});
</script>

<template>
  <div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
      <!-- Tarjeta Alumnos -->
      <TarjetaDashboard
        label="Alumnos activos"
        :value="alumnosActivos"
        :to="{ name: 'alumnos' }"
      >
        <template #icon>
          <svg
            width="30px"
            height="30px"
            viewBox="-2.4 -2.4 28.80 28.80"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <g
              id="SVGRepo_bgCarrier"
              stroke-width="0"
              transform="translate(0,0), scale(1)"
            >
              <rect
                x="-2.4"
                y="-2.4"
                width="28.80"
                height="28.80"
                rx="14.4"
                fill="#e7f0f3"
                strokewidth="0"
              ></rect>
            </g>
            <g
              id="SVGRepo_tracerCarrier"
              stroke-linecap="round"
              stroke-linejoin="round"
            ></g>
            <g id="SVGRepo_iconCarrier">
              <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M3 18C3 15.3945 4.66081 13.1768 6.98156 12.348C7.61232 12.1227 8.29183 12 9 12C9.70817 12 10.3877 12.1227 11.0184 12.348C11.3611 12.4703 11.6893 12.623 12 12.8027C12.3107 12.623 12.6389 12.4703 12.9816 12.348C13.6123 12.1227 14.2918 12 15 12C15.7082 12 16.3877 12.1227 17.0184 12.348C19.3392 13.1768 21 15.3945 21 18V21H15.75V19.5H19.5V18C19.5 15.5147 17.4853 13.5 15 13.5C14.4029 13.5 13.833 13.6163 13.3116 13.8275C14.3568 14.9073 15 16.3785 15 18V21H3V18ZM9 11.25C8.31104 11.25 7.66548 11.0642 7.11068 10.74C5.9977 10.0896 5.25 8.88211 5.25 7.5C5.25 5.42893 6.92893 3.75 9 3.75C10.2267 3.75 11.3158 4.33901 12 5.24963C12.6842 4.33901 13.7733 3.75 15 3.75C17.0711 3.75 18.75 5.42893 18.75 7.5C18.75 8.88211 18.0023 10.0896 16.8893 10.74C16.3345 11.0642 15.689 11.25 15 11.25C14.311 11.25 13.6655 11.0642 13.1107 10.74C12.6776 10.4869 12.2999 10.1495 12 9.75036C11.7001 10.1496 11.3224 10.4869 10.8893 10.74C10.3345 11.0642 9.68896 11.25 9 11.25ZM13.5 18V19.5H4.5V18C4.5 15.5147 6.51472 13.5 9 13.5C11.4853 13.5 13.5 15.5147 13.5 18ZM11.25 7.5C11.25 8.74264 10.2426 9.75 9 9.75C7.75736 9.75 6.75 8.74264 6.75 7.5C6.75 6.25736 7.75736 5.25 9 5.25C10.2426 5.25 11.25 6.25736 11.25 7.5ZM15 5.25C13.7574 5.25 12.75 6.25736 12.75 7.5C12.75 8.74264 13.7574 9.75 15 9.75C16.2426 9.75 17.25 8.74264 17.25 7.5C17.25 6.25736 16.2426 5.25 15 5.25Z"
                fill="#482d73"
              ></path>
            </g>
          </svg>
        </template>
      </TarjetaDashboard>

      <!-- Tarjeta Recaudado -->
      <TarjetaDashboard
        label="Recadudado Mes"
        :value="recaudadoMes.toFixed(2)"
        value-class="text-emerald-600"
      >
        <template #icon>
          <svg
            width="30px"
            height="30px"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g
              id="SVGRepo_tracerCarrier"
              stroke-linecap="round"
              stroke-linejoin="round"
            ></g>
            <g id="SVGRepo_iconCarrier">
              <path
                d="M16 8.94444C15.1834 7.76165 13.9037 7 12.4653 7C9.99917 7 8 9.23858 8 12C8 14.7614 9.99917 17 12.4653 17C13.9037 17 15.1834 16.2384 16 15.0556M7 10.5H11M7 13.5H11M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z"
                stroke="#ed9f41"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              ></path>
            </g>
          </svg>
        </template>
      </TarjetaDashboard>

      <!-- Tarjeta Pendientes -->
      <TarjetaDashboard
        label="Facturas Pendientes"
        :value="facturasPendientes"
        :to="{ name: 'facturas-bolos' }"
      >
        <template #icon>
          <svg
            fill="#ccc4d5"
            width="30px"
            height="30px"
            viewBox="0 0 16 16"
            xmlns="http://www.w3.org/2000/svg"
          >
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g
              id="SVGRepo_tracerCarrier"
              stroke-linecap="round"
              stroke-linejoin="round"
            ></g>
            <g id="SVGRepo_iconCarrier">
              <path
                d="M5.44 7.47h5.26v1.25H5.44zm0 2.36h5.26v1.25H5.44zm0-4.76h5.26v1.25H5.44z"
              ></path>
              <path
                d="M11.34 1 9.64.28 8.08 1 6.41.28 4.84 1 2.46 0v16l2.38-1 1.57.69L8.08 15l1.56.69 1.7-.69 2.2 1V0zm.94 13.11-.92-.41-1.69.69-1.57-.72-1.68.69-1.55-.69-1.15.47V1.86l1.15.47 1.55-.69 1.68.69 1.57-.69 1.69.69.92-.41z"
              ></path>
            </g>
          </svg>
        </template>
      </TarjetaDashboard>

      <!-- Tarjeta Borradores -->
      <TarjetaDashboard
        label="Borradores"
        :value="borradores"
        :to="{ name: 'facturas-bolos' }"
      >
        <template #icon>
          <svg
            fill="#ccc4d5"
            height="30px"
            width="30px"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 512 512"
          >
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g
              id="SVGRepo_tracerCarrier"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke="#000000"
              stroke-width="20.48"
            >
              <g>
                <g>
                  <path
                    d="M345.6,256c-70.579,0-128,57.421-128,128s57.421,128,128,128s128-57.421,128-128S416.179,256,345.6,256z M345.6,494.933 c-61.167,0-110.933-49.766-110.933-110.933S284.433,273.067,345.6,273.067S456.533,322.833,456.533,384 S406.767,494.933,345.6,494.933z"
                  ></path>
                </g>
              </g>
              <g>
                <g>
                  <path
                    d="M388.267,375.467h-34.133v-59.733c0-4.71-3.814-8.533-8.533-8.533c-4.719,0-8.533,3.823-8.533,8.533V384 c0,4.71,3.814,8.533,8.533,8.533h42.667c4.719,0,8.533-3.823,8.533-8.533S392.986,375.467,388.267,375.467z"
                  ></path>
                </g>
              </g>
              <g>
                <g>
                  <path
                    d="M226.133,469.333H55.467V409.6c0-4.71-3.823-8.533-8.533-8.533c-4.71,0-8.533,3.823-8.533,8.533v68.267 c0,4.71,3.823,8.533,8.533,8.533h179.2c4.71,0,8.533-3.823,8.533-8.533S230.844,469.333,226.133,469.333z"
                  ></path>
                </g>
              </g>
              <g>
                <g>
                  <path
                    d="M46.933,366.933c-4.71,0-8.533,3.823-8.533,8.533S42.223,384,46.933,384h0.085c4.71,0,8.491-3.823,8.491-8.533 S51.644,366.933,46.933,366.933z"
                  ></path>
                </g>
              </g>
              <g>
                <g>
                  <path
                    d="M394.3,139.034L257.766,2.5c-1.596-1.604-3.772-2.5-6.033-2.5h-204.8C42.223,0,38.4,3.823,38.4,8.533v332.8 c0,4.71,3.823,8.533,8.533,8.533c4.71,0,8.533-3.823,8.533-8.533V17.067H243.2v128c0,4.71,3.823,8.533,8.533,8.533h128v76.8 c0,4.71,3.823,8.533,8.533,8.533s8.533-3.823,8.533-8.533v-85.333C396.8,142.805,395.904,140.629,394.3,139.034z M260.267,136.533 V29.133l107.401,107.401H260.267z"
                  ></path>
                </g>
              </g>
            </g>
            <g id="SVGRepo_iconCarrier">
              <g>
                <g>
                  <path
                    d="M345.6,256c-70.579,0-128,57.421-128,128s57.421,128,128,128s128-57.421,128-128S416.179,256,345.6,256z M345.6,494.933 c-61.167,0-110.933-49.766-110.933-110.933S284.433,273.067,345.6,273.067S456.533,322.833,456.533,384 S406.767,494.933,345.6,494.933z"
                  ></path>
                </g>
              </g>
              <g>
                <g>
                  <path
                    d="M388.267,375.467h-34.133v-59.733c0-4.71-3.814-8.533-8.533-8.533c-4.719,0-8.533,3.823-8.533,8.533V384 c0,4.71,3.814,8.533,8.533,8.533h42.667c4.719,0,8.533-3.823,8.533-8.533S392.986,375.467,388.267,375.467z"
                  ></path>
                </g>
              </g>
              <g>
                <g>
                  <path
                    d="M226.133,469.333H55.467V409.6c0-4.71-3.823-8.533-8.533-8.533c-4.71,0-8.533,3.823-8.533,8.533v68.267 c0,4.71,3.823,8.533,8.533,8.533h179.2c4.71,0,8.533-3.823,8.533-8.533S230.844,469.333,226.133,469.333z"
                  ></path>
                </g>
              </g>
              <g>
                <g>
                  <path
                    d="M46.933,366.933c-4.71,0-8.533,3.823-8.533,8.533S42.223,384,46.933,384h0.085c4.71,0,8.491-3.823,8.491-8.533 S51.644,366.933,46.933,366.933z"
                  ></path>
                </g>
              </g>
              <g>
                <g>
                  <path
                    d="M394.3,139.034L257.766,2.5c-1.596-1.604-3.772-2.5-6.033-2.5h-204.8C42.223,0,38.4,3.823,38.4,8.533v332.8 c0,4.71,3.823,8.533,8.533,8.533c4.71,0,8.533-3.823,8.533-8.533V17.067H243.2v128c0,4.71,3.823,8.533,8.533,8.533h128v76.8 c0,4.71,3.823,8.533,8.533,8.533s8.533-3.823,8.533-8.533v-85.333C396.8,142.805,395.904,140.629,394.3,139.034z M260.267,136.533 V29.133l107.401,107.401H260.267z"
                  ></path>
                </g>
              </g>
            </g>
          </svg>
        </template>
      </TarjetaDashboard>
    </div>

    <div
      class="mt-8 sm:mt-12 rounded-3xl p-6 sm:p-8 border border-principal relative overflow-hidden shadow-lg shadow-principal-200"
    >
      <div class="relative z-10 max-w-lg">
        <h2>Empieza a trabajar</h2>
        <p class="mb-6">
          Añade clientes a tu panel y genera todas las facturas del mes con un
          solo clic en la sección de facturas.
        </p>
        <div class="flex flex-col lg:flex-row gap-3">
          <router-link
            :to="{ name: 'alumnos' }"
            class="bg-white text-principal text-center px-6 py-2 rounded-xl font-bold hover:bg-blue-50 transition"
            >Alumnos</router-link
          >
          <router-link
            :to="{ name: 'bolos' }"
            class="bg-white text-principal text-center px-6 py-2 rounded-xl font-bold hover:bg-blue-50 transition"
            >Bolos</router-link
          >
          <router-link
            :to="{ name: 'facturas-clases' }"
            class="bg-principal text-white text-center px-6 py-2 rounded-xl font-bold hover:bg-principal-hover transition"
            >Ver Facturación</router-link
          >
        </div>
      </div>
      <!-- Decoración de fondo -->
      <div
        class="absolute -right-20 -bottom-20 w-64 sm:w-80 h-64 sm:h-80 bg-principal-100 rounded-full opacity-50 blur-3xl pointer-events-none"
      />
    </div>
  </div>
</template>
