import { ref, computed, watch } from "vue";

export function usePaginacion(lista, porPagina = 15) {
  const pagina = ref(1);

  const total = computed(() => lista.value.length);
  const totalPaginas = computed(() =>
    Math.max(1, Math.ceil(total.value / porPagina)),
  );

  const desde = computed(() =>
    total.value === 0 ? 0 : (pagina.value - 1) * porPagina + 1,
  );
  const hasta = computed(() =>
    Math.min(pagina.value * porPagina, total.value),
  );

  const paginado = computed(() => {
    const inicio = (pagina.value - 1) * porPagina;
    return lista.value.slice(inicio, inicio + porPagina);
  });

  // Números de página con ellipsis: [1] ··· [4][5][6] ··· [10]
  const paginas = computed(() => {
    const n = totalPaginas.value;
    const curr = pagina.value;

    if (n <= 7) return Array.from({ length: n }, (_, i) => i + 1);

    const conjunto = new Set([1, n]);
    for (let i = Math.max(2, curr - 1); i <= Math.min(n - 1, curr + 1); i++) {
      conjunto.add(i);
    }

    const ordenados = Array.from(conjunto).sort((a, b) => a - b);
    const resultado = [];
    for (let i = 0; i < ordenados.length; i++) {
      if (i > 0 && ordenados[i] - ordenados[i - 1] > 1) resultado.push("...");
      resultado.push(ordenados[i]);
    }
    return resultado;
  });

  function irA(n) {
    pagina.value = Math.min(Math.max(1, n), totalPaginas.value);
  }

  // Volver a página 1 cuando cambia la lista (filtros, navegación entre rutas)
  watch(lista, () => {
    pagina.value = 1;
  });

  return { pagina, totalPaginas, paginado, desde, hasta, total, paginas, irA };
}
