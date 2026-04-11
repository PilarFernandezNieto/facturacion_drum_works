import { ref, computed } from "vue";
import { defineStore } from "pinia";
import api from "@/api/axios";

export const useFacturaStore = defineStore("factura", () => {
  const facturas = ref([]);
  const isLoading = ref(false);
  const generando = ref(false);

  // Filtros
  const filtroMes = ref("");
  const filtroEstado = ref("");

  const mesesDisponibles = computed(() => {
    const meses = new Set();
    facturas.value.forEach((f) => {
      const d = new Date(f.fecha_emision);
      meses.add(
        `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`,
      );
    });
    return Array.from(meses).sort().reverse();
  });

  const facturasFiltradas = computed(() => {
    return facturas.value.filter((f) => {
      const matchEstado =
        !filtroEstado.value || f.estado === filtroEstado.value;
      const d = new Date(f.fecha_emision);
      const mesAnio = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(
        2,
        "0",
      )}`;
      const matchMes = !filtroMes.value || mesAnio === filtroMes.value;
      return matchEstado && matchMes;
    });
  });

  const cargarFacturas = async () => {
    isLoading.value = true;
    try {
      const respuesta = await api.get("facturas");
      facturas.value = respuesta.data;
    } catch (error) {
      console.error("Error al cargar facturas:", error);
      throw error;
    } finally {
      isLoading.value = false;
    }
  };

  const generarMasiva = async () => {
    generando.value = true;
    try {
      const respuesta = await api.post("facturas/generar-masiva");
      await cargarFacturas();
      return respuesta.data;
    } catch (error) {
      console.error("Error en generación masiva:", error);
      throw error;
    } finally {
      generando.value = false;
    }
  };

  const guardarBolo = async (datos) => {
    try {
      const respuesta = await api.post("facturas", {
        ...datos,
        serie: "B",
      });
      // Ya no recarga aquí
      return respuesta.data;
    } catch (error) {
      console.error("Error al guardar bolo:", error);
      throw error;
    }
  };

  const cambiarEstado = async (id, actualEstado) => {
    const nuevoEstado = actualEstado === "pendiente" ? "pagada" : "pendiente";
    try {
      const respuesta = await api.put(`facturas/${id}/estado`, {
        estado: nuevoEstado,
      });
      // Actualizar localmente para evitar recarga completa
      const index = facturas.value.findIndex((f) => f.id === id);
      if (index !== -1) {
        facturas.value[index].estado = nuevoEstado;
      }
      return { nuevoEstado, respuesta: respuesta.data };
    } catch (error) {
      console.error("Error al cambiar estado:", error);
      throw error;
    }
  };

  const eliminarFactura = async (id) => {
    try {
      await api.delete(`facturas/${id}`);
      await cargarFacturas();
    } catch (error) {
      console.error("Error al eliminar factura:", error);
      throw error;
    }
  };
  const descargarPDF = async (id, codigo) => {
    try {
      const respuesta = await api.get(`/facturas/${id}/pdf`, {
        responseType: "blob",
      });
      const url = window.URL.createObjectURL(respuesta.data);
      const link = document.createElement("a");
      link.href = url;
      link.download = `factura-${codigo}.pdf`;
      link.click();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error("Error al descargar el PDF:", error);
      throw error;
    }
  };

  function nombreMes(mesAnio) {
    if (!mesAnio) return "";
    const [anio, mes] = mesAnio.split("-");
    return new Date(anio, mes - 1).toLocaleString("es-ES", {
      month: "long",
      year: "numeric",
    });
  }

  return {
    facturas,
    isLoading,
    generando,
    filtroMes,
    filtroEstado,
    mesesDisponibles,
    facturasFiltradas,
    cargarFacturas,
    generarMasiva,
    guardarBolo,
    cambiarEstado,
    eliminarFactura,
    descargarPDF,
    nombreMes,
  };
});
