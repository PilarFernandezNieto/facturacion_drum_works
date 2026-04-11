import { ref } from "vue";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import api from "@/api/axios";

const QUERY_KEY = ["facturas"];

export function useFacturas() {
  const { data, isLoading } = useQuery({
    queryKey: QUERY_KEY,
    queryFn: () => api.get("facturas").then((r) => r.data),
    staleTime: 1000 * 60, // 1 minuto
  });

  return { facturas: data, isLoading };
}

export function useGenerarMasiva() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => api.post("facturas/generar-masiva").then((r) => r.data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
  });
}

export function useGuardarBolo() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (datos) =>
      api.post("facturas", { ...datos, serie: "B" }).then((r) => r.data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
  });
}

export function useCambiarEstado() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, nuevoEstado }) =>
      api
        .put(`facturas/${id}/estado`, { estado: nuevoEstado })
        .then((r) => r.data),
    // Actualización optimista: el cambio de estado es instantáneo en UI
    onMutate: async ({ id, nuevoEstado }) => {
      await queryClient.cancelQueries({ queryKey: QUERY_KEY });
      const prev = queryClient.getQueryData(QUERY_KEY);
      queryClient.setQueryData(QUERY_KEY, (old) =>
        old?.map((f) => (f.id === id ? { ...f, estado: nuevoEstado } : f)),
      );
      return { prev };
    },
    onError: (_err, _vars, ctx) => {
      if (ctx?.prev) queryClient.setQueryData(QUERY_KEY, ctx.prev);
    },
    onSettled: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
  });
}

export function useEliminarFactura() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id) => api.delete(`facturas/${id}`).then((r) => r.data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
  });
}

export function useDescargarPDF() {
  const descargando = ref(false);

  async function descargarPDF(id, codigo) {
    descargando.value = true;
    try {
      const respuesta = await api.get(`facturas/${id}/pdf`, {
        responseType: "blob",
      });
      const url = window.URL.createObjectURL(respuesta.data);
      const link = document.createElement("a");
      link.href = url;
      link.download = `FRA ${codigo.replace("/", "-")}.pdf`;
      link.click();
      window.URL.revokeObjectURL(url);
    } finally {
      descargando.value = false;
    }
  }

  return { descargarPDF, descargando };
}

export function useDescargarPDFMasivo() {
  const descargando = ref(false);

  async function descargarMasivo(facturas, nombreZip = "facturas.zip") {
    if (!facturas.length) return;
    descargando.value = true;
    try {
      const { default: JSZip } = await import("jszip");
      const zip = new JSZip();

      await Promise.all(
        facturas.map(async (f) => {
          const resp = await api.get(`facturas/${f.id}/pdf`, {
            responseType: "blob",
          });
          zip.file(`FRA ${f.codigo.replace("/", "-")}.pdf`, resp.data);
        }),
      );

      const blob = await zip.generateAsync({ type: "blob" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = nombreZip;
      link.click();
      window.URL.revokeObjectURL(url);
    } finally {
      descargando.value = false;
    }
  }

  return { descargarMasivo, descargando };
}

export function nombreMes(mesAnio) {
  if (!mesAnio) return "";
  const [anio, mes] = mesAnio.split("-");
  return new Date(anio, mes - 1).toLocaleString("es-ES", {
    month: "long",
    year: "numeric",
  });
}
