// resources/js/axios.js
import axios from "axios";
import { toast } from "react-toastify";

const api = axios.create({
  baseURL: "http://localhost:8000",
  withCredentials: true,
  headers: {
    Accept: "application/json",
  },
});

// 🔥 Interceptor global pour gérer les erreurs
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (!error.response) {
      toast.error("Erreur réseau");
    } else {
      const status = error.response.status;
      if (status === 401) {
        toast.error("Non authentifié. Veuillez vous reconnecter.");
        // window.location.href = "/login"; // optionnel
      } else if (status === 403) {
        toast.error("Permission refusée pour cette action.");
      } else if (status >= 500) {
        toast.error("Erreur serveur. Réessayez plus tard.");
      }
    }
    return Promise.reject(error);
  }
);

export default api;

