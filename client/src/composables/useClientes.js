import { computed } from "vue";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import api from "@/api/axios";

const QUERY_KEY = ["clientes"];

export function useClientes() {
  const { data, isLoading } = useQuery({
    queryKey: QUERY_KEY,
    queryFn: () => api.get("clientes").then((r) => r.data),
    staleTime: 1000 * 60 * 5, // 5 minutos
  });

  const clientes = computed(() => data.value ?? []);
  const alumnos = computed(() => clientes.value.filter((c) => c.tipo === "alumno"));
  const bolos = computed(() => clientes.value.filter((c) => c.tipo === "bolo"));

  return { clientes, alumnos, bolos, isLoading };
}

export function useAgregarCliente() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (datos) => api.post("clientes", datos).then((r) => r.data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
  });
}

export function useActualizarCliente() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, datos }) =>
      api.put(`clientes/${id}`, datos).then((r) => r.data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
  });
}

export function useEliminarCliente() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id) => api.delete(`clientes/${id}`).then((r) => r.data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
  });
}
