import Swal from 'sweetalert2';

const CustomSwal = Swal.mixin({
  customClass: {
    confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded-lg font-bold mx-2 hover:bg-blue-700 transition outline-none border-none',
    cancelButton: 'bg-slate-200 text-slate-600 px-6 py-2 rounded-lg font-bold mx-2 hover:bg-slate-300 transition outline-none border-none'
  },
  buttonsStyling: false
});

export const confirmDialog = (title, text, icon = 'warning') => {
  return CustomSwal.fire({
    title,
    text,
    icon,
    showCancelButton: true,
    confirmButtonText: 'Sí, continuar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  });
};

export const toast = (title, icon = 'success') => {
  return CustomSwal.fire({
    title,
    icon,
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
  });
};

export const notifyError = (title, text) => {
  return CustomSwal.fire({
    title,
    text,
    icon: 'error',
    confirmButtonText: 'Entendido'
  });
};

export default CustomSwal;
