import { ref } from "vue";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import api from "@/api/axios";
import { confirmDialog } from "@/utils/swal";

const QUERY_KEY = ["facturas"];

function nombreDesdeCabecera(respuesta, porDefecto) {
  const cabecera = respuesta.headers["content-disposition"] ?? "";
  const coincidencia = cabecera.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
  return coincidencia ? decodeURIComponent(coincidencia[1]) : porDefecto;
}

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

export function useActualizarBorrador() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, datos }) =>
      api.put(`facturas/${id}`, datos).then((r) => r.data),
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
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: QUERY_KEY });
      queryClient.invalidateQueries({ queryKey: ["historial"] });
    },
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
  const queryClient = useQueryClient();
  const descargando = ref(false);

  async function descargarPDF(factura) {
    if (factura.situacion === "borrador") {
      const resultado = await confirmDialog(
        "Emitir factura",
        "Al descargar la factura esta factura se emitirá. Esta acción no se puede deshacer. ¿Continuar?",
        "warning",
      );
      if (!resultado.isConfirmed) return;
    }
    const id = factura.id;
    descargando.value = true;

    try {
      const respuesta = await api.get(`facturas/${id}/pdf`, {
        responseType: "blob",
      });
      const url = window.URL.createObjectURL(respuesta.data);
      const link = document.createElement("a");
      link.href = url;
      link.download = nombreDesdeCabecera(respuesta, `factura-${id}.pdf`);
      link.click();
      window.URL.revokeObjectURL(url);
    } finally {
      descargando.value = false;

      queryClient.invalidateQueries({ queryKey: QUERY_KEY });
    }
  }

  return { descargarPDF, descargando };
}

export function useDescargarPDFMasivo() {
  const queryClient = useQueryClient();
  const descargando = ref(false);

  async function descargarMasivo(facturas, nombreZip = "facturas.zip") {
    if (!facturas.length) return;
    const borradores = facturas.filter((f) => f.situacion === "borrador");
    if (borradores.length > 0) {
      const resultado = await confirmDialog(
        "Emitir facturas",
        `Al descargar este archivo se van a emitir ${borradores.length} facturas. Esta acción no se puede deshacer. ¿Continuas?`,
        "warning",
      );
      if (!resultado.isConfirmed) return;
    }

    descargando.value = true;
    try {
      const { default: JSZip } = await import("jszip");
      const zip = new JSZip();

      // Se emiten por orden de creación para que la numeración siga ese orden

      const ordenDeCreacion = [...facturas].sort((a, b) => a.id - b.id);
      for (const f of ordenDeCreacion) {
        const resp = await api.get(`facturas/${f.id}/pdf`, {
          responseType: "blob",
        });
        zip.file(nombreDesdeCabecera(resp, `factura-${f.id}.pdf`), resp.data);
      }

      const blob = await zip.generateAsync({ type: "blob" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = nombreZip;
      link.click();
      window.URL.revokeObjectURL(url);
    } finally {
      descargando.value = false;
      queryClient.invalidateQueries({ queryKey: QUERY_KEY });
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
