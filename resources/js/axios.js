import axios from "axios";
import { toast } from "react-toastify";

const api = axios.create({
  baseURL: "http://localhost:8000",
  withCredentials: true,
  headers: { Accept: "application/json" },
});

api.interceptors.response.use(
  (res) => res,
  (error) => {
    if (!error.response) {
      toast.error("Erreur réseau");
    } else {
      const status = error.response.status;

      if (status === 401) toast.error("Non authentifié");
      if (status === 403) toast.error("Permission refusée");
      if (status === 423) window.location.href = "/change-password";
      if (status >= 500) toast.error("Erreur serveur");
    }

    return Promise.reject(error);
  }
);

export default api;
