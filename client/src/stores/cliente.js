import { ref } from "vue";
import { defineStore } from "pinia";
import api from "@/api/axios";

export const useClienteStore = defineStore("cliente", () => {
  const clientes = ref([]);
  const isLoading = ref(false);

  const cargarClientes = async () => {
    isLoading.value = true;
    try {
      const respuesta = await api.get("clientes");
      clientes.value = respuesta.data;
    } catch (error) {
      console.error("Error al cargar clientes:", error);
      throw error;
    } finally {
      isLoading.value = false;
    }
  };

  const agregarCliente = async (datos) => {
    try {
      const respuesta = await api.post("clientes", datos);
      await cargarClientes();
      return respuesta.data;
    } catch (error) {
      console.error("Error al agregar cliente:", error);
      throw error;
    }
  };

  const actualizarCliente = async (id, datos) => {
    try {
      const respuesta = await api.put(`clientes/${id}`, datos);
      await cargarClientes();
      return respuesta.data;
    } catch (error) {
      console.error("Error al actualizar cliente:", error);
      throw error;
    }
  };

  const eliminarCliente = async (id) => {
    try {
      await api.delete(`clientes/${id}`);
      await cargarClientes();
    } catch (error) {
      console.error("Error al eliminar cliente:", error);
      throw error;
    }
  };

  return {
    clientes,
    isLoading,
    cargarClientes,
    agregarCliente,
    actualizarCliente,
    eliminarCliente,
  };
});
