import { useQuery } from "@tanstack/vue-query";
import api from "@/api/axios";

export function useHistorial() {
  const { data, isLoading } = useQuery({
    queryKey: ["historial"],
    queryFn: () => api.get("facturas/historial").then((r) => r.data),
    staleTime: 1000 * 60 * 10, // 10 minutos — datos históricos cambian poco
  });

  return { historial: data, isLoading };
}

export function formatearMes(mesAnio) {
  if (!mesAnio) return "";
  const [anio, mes] = mesAnio.split("-");
  const nombre = new Date(anio, mes - 1).toLocaleString("es-ES", { month: "long" });
  return nombre.charAt(0).toUpperCase() + nombre.slice(1) + " " + anio;
}

export function formatearEuros(valor) {
  return Number(valor ?? 0).toLocaleString("es-ES", {
    style: "currency",
    currency: "EUR",
  });
}
